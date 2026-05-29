<?php

namespace App\Models;

use Database\Factories\ShipmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    /** @use HasFactory<ShipmentFactory> */
    use HasFactory;

    protected $fillable = [
        'reference_number',
        'origin_warehouse_id',
        'destination_warehouse_id',
        'vehicle_id',
        'status',
        'scheduled_at',
        'departed_at',
        'arrived_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'departed_at' => 'datetime',
        'arrived_at' => 'datetime',
    ];

    public function originWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'origin_warehouse_id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }
}
