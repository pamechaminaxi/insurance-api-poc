<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PasswordReset extends Model
{
    // boot logs activity method
    use HasFactory;

    // timestamps disabled for this model
    public $timestamps = false;

    // fillable properties
    protected $fillable = [
        'email',
        'token',
        'created_at'
    ];

    // casts for created at
    protected $casts = [
        'created_at' => 'datetime'
    ];
}
