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

    // hide internal storage path from API responses
    protected $hidden = [
        'file_path',
    ];

    // append custom attribute in response
    protected $appends = [
        'file_url',
    ];

    // accessor to get the public URL of the file
    public function getFileUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }

    // claim relationship
    public function claim()
    {
        return $this->belongsTo(Claim::class);
    }
}
