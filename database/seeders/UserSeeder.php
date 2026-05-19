<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'Admin')->first();
        $agentRole = Role::where('name', 'Agent')->first();
        $customerRole = Role::where('name', 'Customer')->first();

        User::create([
            'name' => 'Admin User',
            'email' => 'admin@insurance.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
        ]);

        // User::create([
        //     'name' => 'Agent User',
        //     'email' => 'agent@insurance.com',
        //     'password' => Hash::make('password'),
        //     'role_id' => $agentRole->id,
        // ]);

        // User::create([
        //     'name' => 'Customer User',
        //     'email' => 'customer@insurance.com',
        //     'password' => Hash::make('password'),
        //     'role_id' => $customerRole->id,
        // ]);
    }
}
