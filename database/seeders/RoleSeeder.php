<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::truncate(); // Clear existing roles to avoid duplicates
        Role::create(['name'=>'Admin']);
        Role::create(['name'=>'Agent']);
        Role::create(['name'=>'Customer']);
    }
}
