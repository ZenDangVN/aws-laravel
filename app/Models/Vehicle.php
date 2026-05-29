<?php

namespace App\Models;

use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    protected $fillable = [
        'plate_number',
        'type',
        'driver_name',
        'driver_phone',
        'status',
    ];

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class, 'current_vehicle_id');
    }

    public function rfidScans(): HasMany
    {
        return $this->hasMany(RfidScan::class);
    }
}
