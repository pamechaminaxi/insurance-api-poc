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

            $query = Quote::query()->with(['customer', 'creator']);

            // Role-based filtering
            $user = auth()->user();

            if ($user->role->name === 'Customer') {
                $query->where('customer_user_id', $user->id);
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

            return $query->paginate($filters['per_page'] ?? 15);
        });
    }

    // create quote (busts quotes cache + dispatches quote-created email job)
    public function createQuote($data)
    {
        return DB::transaction(function () use ($data) {
            Quote::syncExpiredQuotes();

            // Check if there is already an active quote for the same customer and insurance type in any status
            $existingQuote = Quote::where('customer_user_id', $data['customer_user_id'])
                ->where('insurance_type', $data['insurance_type'])
                ->where('is_expired', false)
                ->first();

            if ($existingQuote) {
                if ($existingQuote->status === 'rejected') {
                    throw new \Exception('A quote for this customer with the same insurance type has been previously rejected, and cannot be re-created.', 422);
                }
                throw new \Exception('A quote for this customer with the same insurance type already exists.', 422);
            }

            $data['quote_number'] = 'QT-' . strtoupper(Str::random(8));
            $data['created_by'] = auth()->id();
            $data['status'] = 'draft'; // Explicitly set to draft on creation

            $quote = Quote::forceCreate($data);

            // Invalidate all cached quote listings so new quote appears immediately
            Cache::tags(['quotes'])->flush();

            // Dispatch background job to notify the customer that a quote was created for them
            // Only notify if the quote has a linked customer account
            if ($quote->customer_user_id) {
                SendQuoteNotificationJob::dispatch($quote, 'created');
            }

            return $quote;
        });
    }

    // get quote by id
    public function getQuoteById($id)
    {
        $quote = Quote::with(['customer', 'creator', 'claims'])->findOrFail($id);

        // Security check for customers
        if (auth()->user()->role->name === 'Customer' && $quote->customer_user_id !== auth()->id()) {
            throw new \Exception('Unauthorized access to this quote', 403);
        }

        return $quote;
    }

    // update quote (busts quotes cache + dispatches approved email job)
    public function updateQuote($id, $data)
    {
        return DB::transaction(function () use ($id, $data) {
            Quote::syncExpiredQuotes();

            $quote = Quote::findOrFail($id);
            $previousStatus = $quote->status;

            // Check if quote has expired (valid up to 1 year from created_at)
            if ($quote->is_expired) {
                throw new \Exception('This quote has expired.', 422);
            }

            // Business Rule: Agent can only edit if status is 'draft'
            if ($quote->status !== 'draft' && auth()->user()->role->name === 'Agent') {
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
