<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create multiple demo users with different roles
        $users = [
            [
                'user_email' => 'admin@swinetrack.local',
                'user_password_hash' => Hash::make('password123'),
            ],
            [
                'user_email' => 'test@swinetrack.local',
                'user_password_hash' => Hash::make('password123'),
            ],
            [
                'user_email' => 'cashier1@swinetrack.local',
                'user_password_hash' => Hash::make('password123'),
            ],
            [
                'user_email' => 'cashier2@swinetrack.local',
                'user_password_hash' => Hash::make('password123'),
            ],
            [
                'user_email' => 'cashier3@swinetrack.local',
                'user_password_hash' => Hash::make('password123'),
            ],
            [
                'user_email' => 'manager@swinetrack.local',
                'user_password_hash' => Hash::make('password123'),
            ],
            [
                'user_email' => 'supervisor@swinetrack.local',
                'user_password_hash' => Hash::make('password123'),
            ],
            [
                'user_email' => 'staff@swinetrack.com',
                'user_password_hash' => Hash::make('password123'),
            ],
        ];

        foreach ($users as $user) {
            User::firstOrCreate(
                ['user_email' => $user['user_email']],
                ['user_password_hash' => $user['user_password_hash']]
            );
        }

        $this->call([
            ProductSeeder::class,
            SupplierSeeder::class,
            ReportDemoDataSeeder::class,
        ]);
    }
}
