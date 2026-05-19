<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Traits\LogsActivity;

class ClaimDocument extends Model
{
    // boot logs activity method
    use HasFactory, LogsActivity;

    // fillable properties
    protected $fillable = [
        'claim_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
    ];

    // claim relationship
    public function claim()
    {
        return $this->belongsTo(Claim::class);
    }
}
