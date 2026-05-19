<?php

namespace App\Services;

use App\Jobs\SendClaimStatusNotificationJob;
use App\Models\Claim;
use App\Models\Quote;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClaimService
{
    // Cache TTL: 10 minutes
    const CACHE_TTL = 600;

    /**
     * Build a unique, deterministic cache key for a given user + filters combo.
     */
    private function buildClaimCacheKey(array $filters): string
    {
        $userId = auth()->id() ?? 'guest';
        // Sort filters so different ordering of the same params hits the same cache entry
        ksort($filters);
        return 'claims_user_' . $userId . '_' . md5(json_encode($filters));
    }

    // get all claims (Redis cached)
    public function getAllClaims($filters = [])
    {
        $cacheKey = $this->buildClaimCacheKey($filters);

        return Cache::tags(['claims'])->remember($cacheKey, self::CACHE_TTL, function () use ($filters) {
            $query = Claim::query();

            //filter by status
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            //filter by user if customer
            if (auth()->user()->role->name === 'Customer') {
                $query->where('user_id', auth()->id());
            }

            return $query->with(['quote', 'user', 'documents'])->paginate($filters['per_page'] ?? 15);
        });
    }

    // create claim (busts claims cache)
    public function createClaim($data)
    {
        // Business Rule: Claim can be created ONLY IF quote.status = APPROVED and is_delete = 0
        $quote = Quote::withTrashed()->findOrFail($data['quote_id']);

        // check if quote is deleted
        if ($quote->is_delete == 1 || $quote->trashed()) {
            throw new \Exception('A claim cannot be created for a deleted quote.', 400);
        }

        //check if quote is approved
        if ($quote->status !== 'approved') {
            throw new \Exception('A claim can only be created for an APPROVED quote.', 400);
        }

        //check if total claim amount exceeds the policy coverage limit
        $totalClaimed = Claim::where('quote_id', $quote->id)
            ->whereIn('status', ['Pending', 'Under Review', 'Approved', 'Settled'])
            ->sum('claim_amount');

        if (($totalClaimed + $data['claim_amount']) > $quote->coverage_amount) {
            throw new \Exception('Total claim amount exceeds the policy coverage limit of ' . $quote->coverage_amount, 422);
        }

        //create claim in transaction
        $claim = DB::transaction(function () use ($data) {
            $data['claim_number'] = 'CL-' . strtoupper(Str::random(8));
            $data['user_id'] = auth()->id();
            return Claim::create($data);
        });

        // Invalidate all cached claim listings so new claim appears immediately
        Cache::tags(['claims'])->flush();

        return $claim;
    }

    // get claim by id
    public function getClaimById($id)
    {
        $claim = Claim::with(['quote', 'user', 'documents'])->findOrFail($id);

        // Security check for customers
        if (auth()->user()->role->name === 'Customer' && $claim->user_id !== auth()->id()) {
            throw new \Exception('Unauthorized access to this claim', 403);
        }

        return $claim;
    }

    // update claim status method (busts claims cache + dispatches email job)
    public function updateClaimStatus($id, $status)
    {
        $claim = Claim::findOrFail($id);

        // Define allowed forward transitions
        $validTransitions = [
            'Pending'      => ['Under Review', 'Rejected'],
            'Under Review' => ['Approved', 'Rejected'],
            'Approved'     => ['Settled'],
            'Rejected'     => [], // Terminal state
            'Settled'      => [], // Terminal state
        ];

        //check if status is valid
        $previousStatus = $claim->status;

        if (!in_array($status, $validTransitions[$previousStatus] ?? [])) {
            throw new \Exception("Invalid status transition from {$previousStatus} to {$status}.", 422);
        }

        $claim->update(['status' => $status]);

        // Invalidate all cached claim listings so updated status appears immediately
        Cache::tags(['claims'])->flush();

        // Dispatch background job to email the customer about the status change
        // API returns 200 OK instantly; email sends asynchronously via queue worker
        SendClaimStatusNotificationJob::dispatch($claim, $previousStatus, $status);

        return $claim;
    }

    // update claim method (busts claims cache + dispatches email job if status changed)
    public function updateClaim($id, $data)
    {
        $claim = $this->getClaimById($id);
        $previousStatus = $claim->status;
        $statusChanged  = false;

        //update status if changed
        if (isset($data['status']) && $data['status'] !== $claim->status) {
            $validTransitions = [
                'Pending'      => ['Under Review', 'Rejected'],
                'Under Review' => ['Approved', 'Rejected'],
                'Approved'     => ['Settled'],
                'Rejected'     => [], // Terminal state
                'Settled'      => [], // Terminal state
            ];

            //check if status is valid
            $newStatus = $data['status'];

            if (!in_array($newStatus, $validTransitions[$previousStatus] ?? [])) {
                throw new \Exception("Invalid status transition from {$previousStatus} to {$newStatus}.", 422);
            }

            $statusChanged = true;
        }

        //update claim amount if changed
        if (isset($data['claim_amount']) && $data['claim_amount'] != $claim->claim_amount) {
            $totalClaimed = Claim::where('quote_id', $claim->quote_id)
                ->where('id', '!=', $id) // Exclude current claim
                ->whereIn('status', ['Pending', 'Under Review', 'Approved', 'Settled'])
                ->sum('claim_amount');

            //check if total claim amount exceeds the policy coverage limit
            if (($totalClaimed + $data['claim_amount']) > $claim->quote->coverage_amount) {
                throw new \Exception('Total claim amount exceeds the policy coverage limit of ' . $claim->quote->coverage_amount, 422);
            }
        }

        //update claim
        $claim->update($data);

        // Invalidate all cached claim listings so updated data appears immediately
        Cache::tags(['claims'])->flush();

        // Dispatch email notification if status was changed
        if ($statusChanged) {
            SendClaimStatusNotificationJob::dispatch($claim, $previousStatus, $data['status']);
        }

        return $claim;
    }
}
