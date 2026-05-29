<?php

namespace App\Models;

use Database\Factories\PackageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    /** @use HasFactory<PackageFactory> */
    use HasFactory;

    protected $fillable = [
        'rfid_tag',
        'tracking_number',
        'description',
        'weight',
        'shipment_id',
        'current_warehouse_id',
        'current_vehicle_id',
        'status',
    ];

    protected $casts = [
        'weight' => 'float',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function currentWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'current_warehouse_id');
    }

    public function currentVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'current_vehicle_id');
    }

    public function rfidScans(): HasMany
    {
        return $this->hasMany(RfidScan::class);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
