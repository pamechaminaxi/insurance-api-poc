<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name','Admin')->first();

        User::updateOrCreate([
                'name' => 'System Admin',
                'email' => 'admin@insurance.com',
                'password' => Hash::make('123456'),
                'role_id' => $adminRole->id
        ]);
    }
}
