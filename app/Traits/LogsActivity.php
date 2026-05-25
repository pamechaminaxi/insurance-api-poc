<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    // boot logs activity method 
    public static function bootLogsActivity()
    {
        // log activity when model is created
        static::created(function ($model) {
            $modelClass = get_class($model);
            $action = null;

            // check model class and log activity
            if ($modelClass === \App\Models\Quote::class) {

                // Load customer relationship
                $model->load('customer');

                // Get customer name safely
                $customerName = $model->customer
                    ? $model->customer->name
                    : 'Unknown Customer';

                $action = "Quote {$model->quote_number} was created for customer '{$customerName}'.";

                // Convert model to array
                $newValue = $model->toArray();

                // Remove sensitive/internal fields
                unset(
                    $newValue['id'],
                    $newValue['customer_user_id'],
                    $newValue['created_by'],
                    $newValue['agent_id']
                );

                // Optional: remove nested customer sensitive data
                if (isset($newValue['customer'])) {
                    unset(
                        $newValue['customer']['id'],
                        $newValue['customer']['password'],
                        $newValue['customer']['remember_token'],
                        $newValue['customer']['role_id']
                    );
                }

                self::logActivity($action, null, $newValue);
            } elseif ($modelClass === \App\Models\ClaimDocument::class) {
                $model->load('claim');
                $claimNumber = $model->claim ? $model->claim->claim_number : "ID: {$model->claim_id}";
                $action = "Claim document '{$model->file_name}' was uploaded for Claim {$claimNumber}.";

                // Build new_value with claim relation data (excluding id and foreign keys from claim)
                $newValue = $model->toArray();
                unset($newValue['claim_id']);
                unset($newValue['id']);
                if (isset($newValue['claim'])) {
                    unset($newValue['claim']['id'], $newValue['claim']['quote_id'], $newValue['claim']['user_id']);
                }

                self::logActivity($action, null, $newValue);
            }
        });

        // log activity when model is updated
        static::updated(function ($model) {
            $modelClass = get_class($model);

            // check model class and log activity
            if ($modelClass === \App\Models\Claim::class && $model->wasChanged('status')) {
                $oldStatus = $model->getOriginal('status');
                $newStatus = $model->status;
                $action = "Claim {$model->claim_number} status changed from '{$oldStatus}' to '{$newStatus}'.";
                
                self::logActivity($action, ['status' => $oldStatus], ['status' => $newStatus]);
            }
        });
    }

    // log activity method 
    protected static function logActivity($action, $oldValue, $newValue)
    {
        // fallback to first user if not logged in (e.g. during seeding or test setup)
        ActivityLog::create([
            'user_id' => Auth::id() ?? (\App\Models\User::first()->id ?? 1), 
            'action' => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);

    }
}
