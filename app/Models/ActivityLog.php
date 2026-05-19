<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;

class ActivityLog extends Model
{
    // boot logs activity method 
    use HasFactory, LogsActivity;

    // fillable properties
    protected $fillable = [
        'user_id',
        'action',
        'old_value',
        'new_value',
    ];

    // casts for old value and new value
    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
    ];

    // user relationship
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // log activity method
    public static function log($action, $oldValue = null, $newValue = null)
    {
        return self::create([
            'user_id' => auth()->id() ?? 1,
            'action' => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);
    }
}
