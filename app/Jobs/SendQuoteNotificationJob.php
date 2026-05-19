<?php

namespace App\Jobs;

use App\Mail\QuoteNotificationMail;
use App\Models\Quote;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendQuoteNotificationJob implements ShouldQueue
{
    use Queueable;

    public Quote $quote;
    public string $eventType; // 'created' or 'approved'

    // Number of times the job may be attempted before failing
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(Quote $quote, string $eventType)
    {
        $this->quote     = $quote;
        $this->eventType = $eventType;
    }

    /**
     * Execute the job.
     * This runs in the background via: php artisan queue:work
     */
    public function handle(): void
    {
        Log::info('Start Job for sending quote email to ' . $this->quote->customer->email);
        try{
            // Load the customer linked to this quote
            $quote = $this->quote->load('customer', 'creator');
            $customerEmail = $quote->customer->email;
            $customerName  = $quote->customer->name;

            Mail::to($customerEmail, $customerName)
                ->send(new QuoteNotificationMail($quote, $this->eventType));
            Log::info('Quote notification sent successfully to ' . $customerEmail);
        }catch(\Exception $e){
            Log::error('Failed to send quote notification: ' . $e->getMessage());
            throw $e;
        }
    }
}