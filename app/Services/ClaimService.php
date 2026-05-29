<?php

namespace App\Services;

use App\Jobs\SendClaimStatusNotificationJob;
use App\Models\Claim;
use App\Models\Quote;
use App\Models\User;
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
            
            // Get authenticated user
            $authUser = auth()->user();
            
            /*
            |--------------------------------------------------------------------------
            | Role-Based Claim Visibility
            |--------------------------------------------------------------------------
            */
            
            // Customer:
            // Can view only their own claims
            if ($authUser->role->name === 'Customer') {
                $query->where('user_id', $authUser->id);
            }
            
            // Agent:
            // Can view claims only for quotes assigned to them
            if ($authUser->role->name === 'Agent') {
                $query->whereHas('quote', function ($quoteQuery) use ($authUser) {
                        $quoteQuery->where('agent_id', $authUser->id);
                    });
                }
                
                /*
                |--------------------------------------------------------------------------
                | Filters
                |--------------------------------------------------------------------------
                */
                // Filter by status
                if (isset($filters['status'])) {
                    $query->where('status', $filters['status']);
                }
                
                /*
                |--------------------------------------------------------------------------
                | Return Paginated Claims
                |--------------------------------------------------------------------------
                */
                
                $paginator = $query->with(['quote', 'user', 'documents']) ->paginate($filters['per_page'] ?? 15);
                return $paginator->toArray();
            }
        );
    }
    
    // create claim (busts claims cache)
    public function createClaim($data)
    {
        return DB::transaction(function () use ($data) {
            
            Quote::syncExpiredQuotes();
            
            /*
            |--------------------------------------------------------------------------
            | Get Authenticated User
            |--------------------------------------------------------------------------
            */
            
            $authUser = auth()->user();
            
            /*
            |--------------------------------------------------------------------------
            | Prevent Inactive Users
            |--------------------------------------------------------------------------
            */
            
            if (!$authUser->is_active) {
                throw new \Exception('Your account is inactive. You cannot create claims.', 403);
            }
            
            /*
            |--------------------------------------------------------------------------
            | Get Quote
            |--------------------------------------------------------------------------
            */

            // Claim can be created even if quote is soft deleted check is needed
            $quote = Quote::withTrashed()->findOrFail($data['quote_id']);

            /*
            |--------------------------------------------------------------------------
            | Agent Ownership Validation
            |--------------------------------------------------------------------------
            */
            
            // Agent can create claim ONLY for quotes assigned to them
            if ($authUser->role->name === 'Agent' && $quote->agent_id !== $authUser->id) {
                
                throw new \Exception('You are not authorized to create claims for this quote.', 403);
            }
            
            /*
            |--------------------------------------------------------------------------
            | Customer Ownership Validation
            |--------------------------------------------------------------------------
            */
            
            // Customer can create claim ONLY for their own quotes
            if ($authUser->role->name === 'Customer' && $quote->customer_user_id !== $authUser->id) {
                throw new \Exception('You are not authorized to create claims for this quote.', 403);
            }
            
            /*
            |--------------------------------------------------------------------------
            | Check Customer Status
            |--------------------------------------------------------------------------
            */
            
            $customer = User::findOrFail($quote->customer_user_id);
            
            if (!$customer->is_active) {
                throw new \Exception('Cannot create claim for inactive customer.', 422);
            }
            
            /*
            |--------------------------------------------------------------------------
            | Quote Validation
            |--------------------------------------------------------------------------
            */
            
            // Claim allowed only for approved quotes
            if ($quote->status !== 'approved') {
                throw new \Exception('A claim can only be created for an APPROVED quote.', 400);
            }
            
            // Prevent claim creation for expired quotes
            if ($quote->is_expired) {
                throw new \Exception('A claim cannot be created for an expired quote.', 400);
            }

            if($quote->deleted_at !== null){
                throw new \Exception('A claim cannot be created for a deleted quote.', 400);
            }
            
            /*
            |--------------------------------------------------------------------------
            | Coverage Validation
            |--------------------------------------------------------------------------
            */
            
            // Total approved/pending claims must not exceed coverage
            $totalClaimed = Claim::where('quote_id', $quote->id)
                ->whereIn('status', [
                    'Pending',
                    'Under Review',
                    'Approved',
                    'Settled'
                    ])
                    ->sum('claim_amount');
                    
                    if (($totalClaimed + $data['claim_amount']) > $quote->coverage_amount) {
                        throw new \Exception(
                            'Total claim amount exceeds the policy coverage limit of ' . $quote->coverage_amount,
                            422
                        );
                    }
                    
                    /*
                    |--------------------------------------------------------------------------
                    | Create Claim
                    |--------------------------------------------------------------------------
                    */
                    
                    $data['claim_number'] = 'CL-' . strtoupper(Str::random(8));
                    
                    // Claim creator
                    $data['user_id'] = $authUser->id;
                    
                    $claim = Claim::create($data);
                    
                    /*
                    |--------------------------------------------------------------------------
                    | Clear Cache
                    |--------------------------------------------------------------------------
                    */
                    
                    Cache::tags(['claims'])->flush();
                    
                    return $claim;
                });
    }

    // get claim by id
    public function getClaimById($id)
    {
        $claim = Claim::with([
            'quote',
            'user',
            'documents'
        ])->findOrFail($id);
        
        $authUser = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | Customer Access Control
        |--------------------------------------------------------------------------
        */

        // Customer can view only their own claims
        if ($authUser->role->name === 'Customer' && $claim->user_id !== $authUser->id) {
            throw new \Exception('Unauthorized access to this claim', 403);
        }
        
        /*
        |--------------------------------------------------------------------------
        | Agent Access Control
        |--------------------------------------------------------------------------
        */
        
        // Agent can view only claims belonging to quotes assigned to them
        if ($authUser->role->name === 'Agent' && $claim->quote->agent_id !== $authUser->id) {
            throw new \Exception('Unauthorized access to this claim', 403);
        }
        
        /*
        |--------------------------------------------------------------------------
        | Admin Access
        |--------------------------------------------------------------------------
        */

        // Admin has unrestricted access
        
        return $claim;
    }

    // update claim status method (busts claims cache + dispatches email job)
    public function updateClaimStatus($id, $status)
    {
        return DB::transaction(function () use ($id, $status) {
            
            $claim = Claim::with('quote')->findOrFail($id);
            
            $authUser = auth()->user();
            
            /*
            |--------------------------------------------------------------------------
            | Active User Validation
            |--------------------------------------------------------------------------
            */
            
            // Prevent inactive users from updating claim status
            if (!$authUser->is_active) {
                throw new \Exception('Your account is inactive. You cannot update claim status.', 403);
            }
            
            /*
            |--------------------------------------------------------------------------
            | Customer Restriction
            |--------------------------------------------------------------------------
            */
            
            // Customers are not allowed to update claim status
            if ($authUser->role->name === 'Customer') {
                throw new \Exception('Customers are not allowed to update claim status.', 403);
            }
            
            /*
            |--------------------------------------------------------------------------
            | Agent Ownership Validation
            |--------------------------------------------------------------------------
            */
            
            // Agent can update status only for claims assigned to their quotes
            if ($authUser->role->name === 'Agent' && $claim->quote->agent_id !== $authUser->id) {
                throw new \Exception('You are not authorized to update this claim status.', 403);
            }
            
            /*
            |--------------------------------------------------------------------------
            | Valid Status Transitions
            |--------------------------------------------------------------------------
            */
            
            // Define allowed forward transitions
            $validTransitions = [
                'Pending'      => ['Under Review', 'Rejected'],
                'Under Review' => ['Approved', 'Rejected'],
                'Approved'     => ['Settled'],
                'Rejected'     => [], // Terminal state
                'Settled'      => [], // Terminal state
            ];
            
            $previousStatus = $claim->status;
            
            // Validate status transition
            if (!in_array($status, $validTransitions[$previousStatus] ?? [])) {
                throw new \Exception("Invalid status transition from {$previousStatus} to {$status}.", 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Update Claim Status
            |--------------------------------------------------------------------------
            */
            
            $claim->update([
                'status' => $status
            ]);
            
            /*
            |--------------------------------------------------------------------------
            | Clear Cache
            |--------------------------------------------------------------------------
            */
            
            // Invalidate all cached claim listings
            Cache::tags(['claims'])->flush();
            
            /*
            |--------------------------------------------------------------------------
            | Send Notification
            |--------------------------------------------------------------------------
            */
            
            // Dispatch email notification asynchronously
            SendClaimStatusNotificationJob::dispatch($claim, $previousStatus, $status);
            
            return $claim;
        });
    }
    
    // update claim method (busts claims cache + dispatches email job if status changed)
    public function updateClaim($id, $data)
    {
        return DB::transaction(function () use ($id, $data) {
            
            $claim = $this->getClaimById($id);
            
            $authUser = auth()->user();
            
            /*
            |--------------------------------------------------------------------------
            | Active User Validation
            |--------------------------------------------------------------------------
            */
            
            // Prevent inactive users from updating claims
            if (!$authUser->is_active) {
                throw new \Exception('Your account is inactive. You cannot update claims.', 403);
            }
            
            /*
            |--------------------------------------------------------------------------
            | Agent Ownership Validation
            |--------------------------------------------------------------------------
            */
            
            // Agent can update only claims belonging to quotes assigned to them
            if ($authUser->role->name === 'Agent' && $claim->quote->agent_id !== $authUser->id) {
                throw new \Exception('You are not authorized to update this claim.', 403);
            }
            
            /*
            |--------------------------------------------------------------------------
            | Customer Update Restriction
            |--------------------------------------------------------------------------
            */
            
            // Customer cannot update claims
            if ($authUser->role->name === 'Customer') {
                throw new \Exception('Customers are not allowed to update claims.', 403);
            }
            
            /*
            |--------------------------------------------------------------------------
            | Status Transition Validation
            |--------------------------------------------------------------------------
            */
            
            $previousStatus = $claim->status;
            
            $statusChanged = false;
            
            // Update status if changed
            if (isset($data['status']) && $data['status'] !== $claim->status) {

                $validTransitions = [
                    'Pending'      => ['Under Review', 'Rejected'],
                    'Under Review' => ['Approved', 'Rejected'],
                    'Approved'     => ['Settled'],
                    'Rejected'     => [], // Terminal state
                    'Settled'      => [], // Terminal state
                ];
                
                $newStatus = $data['status'];
                
                // Check valid transition
                if (!in_array($newStatus, $validTransitions[$previousStatus] ?? [])) {
                    throw new \Exception("Invalid status transition from {$previousStatus} to {$newStatus}.", 422);
                }
                
                $statusChanged = true;
            }
            
            /*
            |--------------------------------------------------------------------------
            | Claim Amount Validation
            |--------------------------------------------------------------------------
            */
            
            // Validate updated claim amount
            if (isset($data['claim_amount']) && $data['claim_amount'] != $claim->claim_amount) {
                
                $totalClaimed = Claim::where('quote_id', $claim->quote_id)->where('id', '!=', $id)
                ->whereIn('status', ['Pending', 'Under Review', 'Approved', 'Settled'])
                ->sum('claim_amount');
                
                // Prevent exceeding coverage limit
                if (($totalClaimed + $data['claim_amount']) > $claim->quote->coverage_amount) {
                    
                    throw new \Exception('Total claim amount exceeds the policy coverage limit of ' . $claim->quote->coverage_amount, 422);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Update Claim
            |--------------------------------------------------------------------------
            */

            $claim->update($data);
            
            /*
            |--------------------------------------------------------------------------
            | Clear Cache
            |--------------------------------------------------------------------------
            */
            
            Cache::tags(['claims'])->flush();
            
            /*
            |--------------------------------------------------------------------------
            | Send Notification
            |--------------------------------------------------------------------------
            */
            
            // Dispatch email notification if status changed
            if ($statusChanged) {
                SendClaimStatusNotificationJob::dispatch($claim, $previousStatus, $data['status']);
            }

            return $claim;
        });
    }
}
