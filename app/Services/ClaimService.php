<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClaimService
{
    // get all claims
    public function getAllClaims($filters = [])
    {
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
    }

    // create claim
    public function createClaim($data)
    {
        // Business Rule: Claim can be created ONLY IF quote.status = APPROVED
        $quote = Quote::findOrFail($data['quote_id']);

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
        return DB::transaction(function () use ($data) {
            $data['claim_number'] = 'CL-' . strtoupper(Str::random(8));
            $data['user_id'] = auth()->id();
            $claim = Claim::create($data);
            return $claim;
        });
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

    // update claim status method       
    public function updateClaimStatus($id, $status)
    {
        $claim = Claim::findOrFail($id);

        // Define allowed forward transitions
        $validTransitions = [
            'Pending' => ['Under Review', 'Rejected'],
            'Under Review' => ['Approved', 'Rejected'],
            'Approved' => ['Settled'],
            'Rejected' => [], // Terminal state
            'Settled' => [], // Terminal state
        ];

        //check if status is valid 
        $currentStatus = $claim->status;

        if (!in_array($status, $validTransitions[$currentStatus] ?? [])) {
            throw new \Exception("Invalid status transition from {$currentStatus} to {$status}.", 422);
        }

        $claim->update(['status' => $status]);
        return $claim;
    }

    // update claim method
    public function updateClaim($id, $data)
    {
        $claim = $this->getClaimById($id);

        //update status if changed 
        if (isset($data['status']) && $data['status'] !== $claim->status) {
            $validTransitions = [
                'Pending' => ['Under Review', 'Rejected'],
                'Under Review' => ['Approved', 'Rejected'],
                'Approved' => ['Settled'],
                'Rejected' => [], // Terminal state
                'Settled' => [], // Terminal state
            ];

            //check if status is valid 
            $currentStatus = $claim->status;
            $newStatus = $data['status'];

            if (!in_array($newStatus, $validTransitions[$currentStatus] ?? [])) {
                throw new \Exception("Invalid status transition from {$currentStatus} to {$newStatus}.", 422);
            }
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
        return $claim;
    }
}
