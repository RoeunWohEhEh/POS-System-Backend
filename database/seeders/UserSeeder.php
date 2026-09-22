<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('12345678'),
            'role_id' => 1,
        ]);

        User::create([
            'name' => 'Staff',
            'email' => 'roeunstaff@gmail.com',
            'password' => Hash::make('12345678'),
            'role_id' => 2,
        ]);
    }
}
