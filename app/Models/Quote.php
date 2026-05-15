<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;

class Quote extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'quote_number',
        'insurance_type',
        'premium_amount',
        'status',
        'customer_name',
        'customer_user_id',
        'created_by',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function claims()
    {
        return $this->hasMany(Claim::class);
    }
}
