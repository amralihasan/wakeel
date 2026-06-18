<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CompanySeeder::class,
            UserSeeder::class,
            WhatsAppChannelSeeder::class,
            UnitSeeder::class,
            UnitMediaSeeder::class,
            LeadSeeder::class,
            ConversationSeeder::class,
            MessageSeeder::class,
            HandoffSeeder::class,
            VisitSeeder::class,
            FollowUpLogSeeder::class,
            ContactMessageSeeder::class,
        ]);
    }
}
