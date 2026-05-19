<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;


class Role extends Model
{
    // boot logs activity method
    use HasFactory;

    // fillable properties
    protected $fillable = ['name'];

    // users relationship
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
