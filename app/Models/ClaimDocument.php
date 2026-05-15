<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Traits\LogsActivity;

class ClaimDocument extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'claim_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
    ];

    public function claim()
    {
        return $this->belongsTo(Claim::class);
    }
}
