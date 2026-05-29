<?php

namespace App\Services;

use App\Jobs\SendQuoteNotificationJob;
use App\Models\Quote;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class QuoteService
{
    // Cache TTL: 10 minutes
    const CACHE_TTL = 600;

    /**
     * Build a unique, deterministic cache key for a given user + filters combo.
     */
    private function buildQuoteCacheKey(array $filters): string
    {
        $userId = auth()->id() ?? 'guest';
        // Sort filters so different ordering of the same params hits the same cache entry
        ksort($filters);
        return 'quotes_user_' . $userId . '_' . md5(json_encode($filters));
    }

    // get all quotes (Redis cached)
    public function getAllQuotes($filters = [])
    {
        Quote::syncExpiredQuotes();

        $cacheKey = $this->buildQuoteCacheKey($filters);

        return Cache::tags(['quotes'])->remember($cacheKey, self::CACHE_TTL, function () use ($filters) {

            $query = Quote::query()->with(['customer', 'creator', 'agent']);

            // Role-based filtering
            $user = auth()->user();

            if ($user->role->name === 'Customer') {
                $query->where('customer_user_id', $user->id);
            }

            if ($user->role->name === 'Agent') {
                $query->where('agent_id', $user->id);
            }

            // filter by status
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            // search by quote number or customer name
            if (isset($filters['search'])) {

                $search = $filters['search'];

                $query->where(function ($q) use ($search) {
                    
                    // search by quote number
                    $q->where('quote_number', 'like', '%' . $search . '%')
                    
                    // search by customer name from users table
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', '%' . $search . '%');
                    });
                });
            }

            $paginator = $query->paginate($filters['per_page'] ?? 15);
            return $paginator->toArray();
        });
    }

    // create quote (busts quotes cache + dispatches quote-created email job)
    public function createQuote($data)
    {
        return DB::transaction(function () use ($data) {

            // Check authenticated user
            $authUser = auth()->user();

            // Prevent inactive admin/agent from creating quotes
            if (!$authUser->is_active) {

                throw new \Exception(
                    'Your account is inactive. You cannot create quotes.',
                    403
                );
            }

            // Check customer exists
            $customer = User::findOrFail($data['customer_user_id']);

            // Prevent quote creation for inactive customer
            if (!$customer->is_active) {

                throw new \Exception(
                    'Cannot create quote for inactive customer.',
                    422
                );
            }

            // If logged-in user is Agent
            // automatically assign agent_id
            if ($authUser->role->name === 'Agent') {

                $data['agent_id'] = $authUser->id;
            }

            // Validate assigned agent if admin selected
            if (isset($data['agent_id'])) {

                $agent = User::findOrFail($data['agent_id']);

                // Check selected user role
                if ($agent->role->name !== 'Agent') {

                    throw new \Exception(
                        'Selected user is not an agent.',
                        422
                    );
                }

                // Check selected agent status
                if (!$agent->is_active) {

                    throw new \Exception(
                        'Cannot assign inactive agent.',
                        422
                    );
                }
            }

            // Sync expired quotes
            Quote::syncExpiredQuotes();

            // Check if there is already an active quote
            // for same customer + insurance type
            $existingQuote = Quote::where(
                    'customer_user_id',
                    $data['customer_user_id']
                )
                ->where(
                    'insurance_type',
                    $data['insurance_type']
                )
                ->where('is_expired', false)
                ->first();

            if ($existingQuote) {

                if ($existingQuote->status === 'rejected') {

                    throw new \Exception(
                        'A quote for this customer with the same insurance type has been previously rejected, and cannot be re-created.',
                        422
                    );
                }

                throw new \Exception(
                    'A quote for this customer with the same insurance type already exists.',
                    422
                );
            }

            // Generate quote details
            $data['quote_number'] = 'QT-' . strtoupper(Str::random(8));

            // Store creator user id
            $data['created_by'] = $authUser->id;

            // Default status
            $data['status'] = 'draft';

            // Create quote
            $quote = Quote::forceCreate($data);

            // Clear cache
            Cache::tags(['quotes'])->flush();

            // Dispatch notification job
            if ($quote->customer_user_id) {

                SendQuoteNotificationJob::dispatch(
                    $quote,
                    'created'
                );
            }

            return $quote;
        });
    }

    // get quote by id
    public function getQuoteById($id)
    {
        $quote = Quote::with(['customer', 'creator', 'agent', 'claims'])->findOrFail($id);

        // Security check for customers
        if (auth()->user()->role->name === 'Customer' && $quote->customer_user_id !== auth()->id()) {
            throw new \Exception('Unauthorized access to this quote', 403);
        }

        if (auth()->user()->role->name === 'Agent' && $quote->agent_id !== auth()->id()) {
            throw new \Exception('Unauthorized access to this quote', 403);
        }

        return $quote;
    }

    // update quote (busts quotes cache + dispatches approved email job)
    public function updateQuote($id, $data)
    {
        return DB::transaction(function () use ($id, $data) {
            // Sync expired quotes
            Quote::syncExpiredQuotes();

            // Get authenticated user
            $authUser = auth()->user();

            // Find quote
            $quote = Quote::findOrFail($id);

            $previousStatus = $quote->status;

            // Prevent inactive admin/agent from updating quote
            if (!$authUser->is_active) {
                throw new \Exception('Your account is inactive. You cannot update quotes.', 403);
            }

            // Agent can update only their own assigned quotes
            if ($authUser->role->name === 'Agent' && $quote->agent_id !== $authUser->id) {
                throw new \Exception('You are not authorized to update this quote.', 403);
            }

            // Agents cannot reassign quotes
            if ($authUser->role->name === 'Agent' && isset($data['agent_id'])) {
                throw new \Exception('Agents cannot reassign quotes.', 403);
            }

            // Check if quote has expired
            if ($quote->is_expired) {
                throw new \Exception('This quote has expired.', 422);
            }

            // Agent can only edit draft quotes
            if ($quote->status !== 'draft' && $authUser->role->name === 'Agent') {
                throw new \Exception('Quote can only be edited when in draft status.', 403);
            }

            // Enforce Quote status transition rules
            if (isset($data['status']) && $data['status'] !== $previousStatus) {
                $newStatus = $data['status'];
                if ($previousStatus === 'draft') {
                    if ($newStatus === 'approved' || $newStatus === 'rejected') {
                        throw new \Exception("A draft quote cannot be approved or rejected. It must be submitted first.", 422);
                    }
                } elseif ($previousStatus === 'submitted') {
                    if ($newStatus === 'draft') {
                        throw new \Exception("A submitted quote cannot be changed back to draft status.", 422);
                    }
                } elseif ($previousStatus === 'approved') {
                    throw new \Exception("An approved quote status cannot be changed.", 422);
                } elseif ($previousStatus === 'rejected') {
                    throw new \Exception("A rejected quote status cannot be changed.", 422);
                }
            }

            $quote->update($data);

            // Invalidate all cached quote listings so updated data appears immediately
            Cache::tags(['quotes'])->flush();

            // Dispatch email if the status was just changed to 'approved'
            if (isset($data['status']) && $data['status'] === 'approved' && $previousStatus !== 'approved') {
                if ($quote->customer_user_id) {
                    SendQuoteNotificationJob::dispatch($quote, 'approved');
                }
            }

            return $quote;
        });
    }

    // delete quote (busts quotes cache)
    public function deleteQuote($id)
    {
        return DB::transaction(function () use ($id) {
            $quote = Quote::findOrFail($id);

            // if ($quote->status !== 'draft' && auth()->user()->role->name !== 'Admin') {
            //     throw new \Exception('Only draft quotes can be deleted by Agents.', 403);
            // }

            $result = $quote->delete();

            // Invalidate all cached quote listings so deleted quote disappears immediately
            Cache::tags(['quotes'])->flush();

            return $result;
        });
    }
}
