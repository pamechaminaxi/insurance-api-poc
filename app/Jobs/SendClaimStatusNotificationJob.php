<?php

namespace App\Jobs;

use App\Mail\ClaimStatusChangedMail;
use App\Models\Claim;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendClaimStatusNotificationJob implements ShouldQueue
{
    use Queueable;

    public Claim $claim;
    public string $previousStatus;
    public string $newStatus;

    // Number of times the job may be attempted before failing
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(Claim $claim, string $previousStatus, string $newStatus)
    {
        $this->claim          = $claim;
        $this->previousStatus = $previousStatus;
        $this->newStatus      = $newStatus;
    }

    /**
     * Execute the job.
     * This runs in the background via: php artisan queue:work
     */
    public function handle(): void
    {
        Log::info('Start Job for sending claim email to ' . $this->claim->user->email);
        try{
             // Load the claim owner (customer) with their email
            $claim = $this->claim->load('user', 'quote');
            $customerEmail = $claim->user->email;
            $customerName  = $claim->user->name;

            Mail::to($customerEmail, $customerName)
                ->send(new ClaimStatusChangedMail($claim, $this->previousStatus, $this->newStatus));
            Log::info('Claim email sent successfully to ' . $customerEmail);
        }catch(\Exception $e){
            Log::error('Failed to send claim email: ' . $e->getMessage());
            throw $e;
        }
    }
}
