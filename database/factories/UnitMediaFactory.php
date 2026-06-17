<?php

namespace Database\Factories;

use App\Models\Unit;
use App\Models\UnitMedia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnitMedia>
 */
class UnitMediaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'unit_id' => Unit::factory(),
            'type' => fake()->randomElement(['image', 'pdf', 'floorplan', 'video']),
            'path' => 'units/'.fake()->uuid().'.jpg',
            'caption' => fake()->optional()->sentence(),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
