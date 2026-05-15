<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClaimService
{
    public function getAllClaims($filters = [])
    {
        $query = Claim::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // If user is customer, only show their own claims
        if (auth()->user()->role->name === 'Customer') {
            $query->where('user_id', auth()->id());
        }

        return $query->with(['quote', 'user', 'documents'])->paginate($filters['per_page'] ?? 15);
    }

    public function createClaim($data)
    {
        // Business Rule: Claim can be created ONLY IF quote.status = APPROVED
        $quote = Quote::findOrFail($data['quote_id']);

        if ($quote->status !== 'approved') {
            throw new \Exception('A claim can only be created for an APPROVED quote.', 400);
        }

        return DB::transaction(function () use ($data) {
            $data['claim_number'] = 'CL-' . strtoupper(Str::random(8));
            $data['user_id'] = auth()->id();
            
            $claim = Claim::create($data);

            return $claim;
        });
    }

    public function getClaimById($id)
    {
        $claim = Claim::with(['quote', 'user', 'documents'])->findOrFail($id);

        // Security check for customers
        if (auth()->user()->role->name === 'Customer' && $claim->user_id !== auth()->id()) {
            throw new \Exception('Unauthorized access to this claim', 403);
        }

        return $claim;
    }

    public function updateClaimStatus($id, $status)
    {
        $claim = Claim::findOrFail($id);
        $claim->update(['status' => $status]);
        return $claim;
    }

    public function updateClaim($id, $data)
    {
        $claim = $this->getClaimById($id);
        $claim->update($data);
        return $claim;
    }
}
