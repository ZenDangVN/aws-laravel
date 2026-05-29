<?php

namespace Database\Factories;

use App\Models\Shipment;
use App\Models\Vehicle;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => strtoupper(fake()->unique()->bothify('SHP-########')),
            'origin_warehouse_id' => Warehouse::factory(),
            'destination_warehouse_id' => Warehouse::factory(),
            'vehicle_id' => fake()->optional(0.7)->passthrough(Vehicle::factory()),
            'status' => fake()->randomElement(['pending', 'loading', 'in_transit', 'arrived', 'completed']),
            'scheduled_at' => fake()->dateTimeBetween('-7 days', '+7 days'),
        ];
    }
}
