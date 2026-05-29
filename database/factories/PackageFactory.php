<?php

namespace Database\Factories;

use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rfid_tag' => strtoupper(fake()->unique()->bothify('RFID-########')),
            'tracking_number' => strtoupper(fake()->unique()->bothify('TRK-??########')),
            'description' => fake()->optional()->sentence(3),
            'weight' => fake()->randomFloat(2, 0.1, 50),
            'status' => fake()->randomElement(['pending', 'in_transit', 'at_warehouse', 'out_for_delivery', 'delivered']),
        ];
    }
}
