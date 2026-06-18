<?php

namespace Database\Seeders;

use App\Models\Unit;
use App\Models\UnitMedia;
use Illuminate\Database\Seeder;

class UnitMediaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = Unit::all();

        foreach ($units as $unit) {
            // Seed 1-3 media files for each unit if it doesn't have media
            if ($unit->media()->exists()) {
                continue;
            }

            $types = ['image', 'floorplan', 'video'];
            $count = fake()->numberBetween(1, 3);

            for ($i = 0; $i < $count; $i++) {
                UnitMedia::create([
                    'unit_id' => $unit->id,
                    'type' => fake()->randomElement($types),
                    'path' => 'units/'.fake()->uuid().'.jpg',
                    'caption' => fake()->optional()->sentence(),
                    'sort_order' => $i,
                ]);
            }
        }
    }
}
