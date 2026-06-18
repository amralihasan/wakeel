<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\WhatsAppChannel;
use Illuminate\Database\Seeder;

class WhatsAppChannelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            // Ensure company has a WhatsApp channel registered
            $channelId = 'ch-'.strtolower(str_replace(' ', '-', $company->name)).'-'.fake()->numberBetween(100, 999);

            // Only update company if it does not have dialog360_channel_id set
            if (empty($company->dialog360_channel_id)) {
                $company->update(['dialog360_channel_id' => $channelId]);
            } else {
                $channelId = $company->dialog360_channel_id;
            }

            // Create WhatsApp Channel if not exists for this company
            if (! WhatsAppChannel::where('assigned_company_id', $company->id)->exists()) {
                WhatsAppChannel::create([
                    'number' => fake()->unique()->e164PhoneNumber(),
                    'channel_id' => $channelId,
                    'status' => 'active',
                    'assigned_company_id' => $company->id,
                ]);
            }
        }
    }
}
