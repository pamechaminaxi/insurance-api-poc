<?php

namespace App\Services;

use App\Models\Quote;
use Illuminate\Support\Str;

class QuoteService
{
    // get all quotes 
    public function getAllQuotes($filters = [])
    {
        $query = Quote::query();

        // Role-based filtering
        $user = auth()->user();
        if ($user->role->name === 'Customer') {
            $query->where('customer_user_id', $user->id);
        }

        //filter by status 
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        //search by quote number or customer name 
        if (isset($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('quote_number', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('customer_name', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->with(['customer', 'creator'])->paginate($filters['per_page'] ?? 15);
    }

    // create quote
    public function createQuote($data)
    {
        $data['quote_number'] = 'QT-' . strtoupper(Str::random(8));
        $data['created_by'] = auth()->id();
        $data['status'] = 'draft'; // Explicitly set to draft on creation
        
        return Quote::create($data);
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

    // update quote
    public function updateQuote($id, $data)
    {
        $quote = Quote::findOrFail($id);

        // Business Rule: Agent can only edit if status is 'draft'
        if ($quote->status !== 'draft' && auth()->user()->role->name === 'Agent') {
            throw new \Exception('Quote can only be edited when in draft status.', 403);
        }

        $quote->update($data);
        return $quote;
    }

    // delete quote
    public function deleteQuote($id)
    {
        $quote = Quote::findOrFail($id);
        
        if ($quote->status !== 'draft' && auth()->user()->role->name !== 'Admin') {
            throw new \Exception('Only draft quotes can be deleted by Agents.', 403);
        }

        return $quote->delete();
    }
}
