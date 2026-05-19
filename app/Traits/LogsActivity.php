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
                $action = "Quote {$model->quote_number} was created for customer '{$model->customer_name}'.";
                self::logActivity($action, null, $model->toArray());
            } elseif ($modelClass === \App\Models\ClaimDocument::class) {
                $claimNumber = $model->claim ? $model->claim->claim_number : "ID: {$model->claim_id}";
                $action = "Claim document '{$model->file_name}' was uploaded for Claim {$claimNumber}.";
                self::logActivity($action, null, $model->toArray());
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
        // fallback to user 1 if not logged in (e.g. during seeding)
        ActivityLog::create([
            'user_id' => Auth::id() ?? 1, 
            'action' => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);
    }
}
