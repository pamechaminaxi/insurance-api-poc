<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    public static function bootLogsActivity()
    {
        static::created(function ($model) {
            self::logActivity($model, 'Created', null, $model->toArray());
        });

        static::updated(function ($model) {
            $oldValues = array_intersect_key($model->getOriginal(), $model->getDirty());
            $newValues = $model->getDirty();

            self::logActivity($model, 'Updated', $oldValues, $newValues);
        });
    }

    protected static function logActivity($model, $action, $oldValue, $newValue)
    {
        ActivityLog::create([
            'user_id' => Auth::id() ?? 1, // Fallback to user 1 if not logged in (e.g. during seeding)
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);
    }
}
