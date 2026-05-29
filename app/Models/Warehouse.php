<?php

namespace App\Models;

use Database\Factories\WarehouseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    /** @use HasFactory<WarehouseFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'address',
        'city',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function outboundShipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'origin_warehouse_id');
    }

    public function inboundShipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'destination_warehouse_id');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class, 'current_warehouse_id');
    }

    public function rfidScans(): HasMany
    {
        return $this->hasMany(RfidScan::class);
    }
}
