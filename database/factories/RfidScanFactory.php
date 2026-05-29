<?php

namespace Database\Factories;

use App\Models\Package;
use App\Models\RfidScan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RfidScan>
 */
class RfidScanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'package_id' => Package::factory(),
            'scanner_id' => strtoupper(fake()->bothify('GATE-##')),
            'location_type' => fake()->randomElement(['warehouse', 'vehicle', 'checkpoint']),
            'scanned_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ];
    }
}
