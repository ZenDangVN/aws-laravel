<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plate_number' => strtoupper(fake()->unique()->bothify('??-####-??')),
            'type' => fake()->randomElement(['truck', 'van', 'motorcycle']),
            'driver_name' => fake()->name(),
            'driver_phone' => fake()->optional()->phoneNumber(),
            'status' => fake()->randomElement(['available', 'on_route', 'maintenance']),
        ];
    }
}
