<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\GptConfiguration;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create super admin
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@servicebot.local',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Create demo business
        $business = Business::create([
            'name' => 'Demo Restaurant',
            'slug' => 'demo-restaurant',
            'is_active' => true,
            'settings' => [
                'timezone' => 'UTC',
                'language' => 'en',
            ],
        ]);

        // Create manager for demo business
        $manager = User::create([
            'name' => 'Demo Manager',
            'email' => 'manager@demo.local',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'business_id' => $business->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Link manager to business
        $business->users()->attach($manager->id, ['role' => 'manager']);

        // Create GPT configuration
        GptConfiguration::create([
            'business_id' => $business->id,
            'name' => 'Default Configuration',
            'model' => 'gpt-4-turbo-preview',
            'max_tokens' => 1000,
            'temperature' => 0.7,
            'system_prompt' => "You are a helpful assistant for Demo Restaurant. Help customers with:
- Making table reservations
- Placing food orders
- Answering questions about the menu
- Handling complaints professionally

When a customer wants to take an action, embed it in your response using this format:
[ACTION:action_type]{\"key\": \"value\"}[/ACTION]

Always be polite and professional.",
            'available_actions' => ['reservation', 'order', 'inquiry', 'complaint'],
            'is_active' => true,
        ]);

        $this->command->info('Seeding complete!');
        $this->command->table(
            ['User', 'Email', 'Password', 'Role'],
            [
                ['Super Admin', 'admin@servicebot.local', 'password', 'super_admin'],
                ['Demo Manager', 'manager@demo.local', 'password', 'manager'],
            ]
        );
    }
}
