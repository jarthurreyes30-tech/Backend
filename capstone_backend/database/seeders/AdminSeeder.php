<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Seed only the admin account.
     * This seeder can be run independently to ensure admin account exists.
     */
    public function run(): void
    {
        // Admin - password: 'password'
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(), // Admin should be pre-verified
            ]
        );

        $this->command->info('Admin account created/updated successfully.');
        $this->command->info('Email: admin@example.com');
        $this->command->info('Password: password');
        $this->command->info('Admin ID: ' . $admin->id);
    }
}
