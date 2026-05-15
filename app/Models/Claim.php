<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;

class Claim extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'claim_number',
        'quote_id',
        'user_id',
        'claim_amount',
        'description',
        'status',
    ];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documents()
    {
        return $this->hasMany(ClaimDocument::class);
    }
}
