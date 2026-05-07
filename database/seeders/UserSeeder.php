<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create staff account
        User::firstOrCreate(
            ['user_email' => 'staff@swinetrack.com'],
            ['user_password_hash' => Hash::make('password123')]
        );

        // Create admin account
        User::firstOrCreate(
            ['user_email' => 'admin@swinetrack.com'],
            ['user_password_hash' => Hash::make('password123')]
        );

        // Create test account
        User::firstOrCreate(
            ['user_email' => 'test@swinetrack.local'],
            ['user_password_hash' => Hash::make('password123')]
        );
    }
}
