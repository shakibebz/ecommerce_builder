<?php
// database/seeders/UserSeeder.php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // Create crawler user
        $crawler = User::create([
            'name' => 'Python Crawler',
            'email' => 'crawler@system.local',
            'password' => Hash::make('crawler-password-123'),
            'email_verified_at' => now(),
        ]);

        // Create tokens
        $adminToken = $admin->createToken('admin-token', ['*'])->plainTextToken;
        $crawlerToken = $crawler->createToken('crawler-token', ['product:create'])->plainTextToken;

        // Output tokens for reference
        $this->command->info('Admin Token: ' . $adminToken);
        $this->command->info('Crawler Token: ' . $crawlerToken);

        // You might want to save these to a file for testing
        file_put_contents(storage_path('tokens.txt'),
            "Admin Token: {$adminToken}\n" .
            "Crawler Token: {$crawlerToken}\n" .
            "Admin Email: admin@example.com\n" .
            "Crawler Email: crawler@system.local\n"
        );
    }
}
