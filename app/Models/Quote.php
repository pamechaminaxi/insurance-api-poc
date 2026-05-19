<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;

class Quote extends Model
{
    //boot logs activity method 
    use HasFactory, LogsActivity;

    // fillable properties
    protected $fillable = [
        'quote_number',
        'insurance_type',
        'premium_amount',
        'coverage_amount',
        'status',
        'customer_name',
        'customer_user_id',
        'created_by',
    ];

    // customer relationship
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    // creator relationship
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // claims relationship
    public function claims()
    {
        return $this->hasMany(Claim::class);
    }
}
