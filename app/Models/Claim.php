<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;

class Claim extends Model
{
    //boot logs activity method
    use HasFactory, LogsActivity;

    // fillable properties
    protected $fillable = [
        'claim_number',
        'quote_id',
        'user_id',
        'claim_amount',
        'description',
        'status',
    ];

    // quote relationship
    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    // user relationship
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // claim documents relationship
    public function documents()
    {
        return $this->hasMany(ClaimDocument::class);
    }
}
