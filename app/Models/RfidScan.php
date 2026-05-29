<?php

namespace App\Models;

use Database\Factories\RfidScanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfidScan extends Model
{
    /** @use HasFactory<RfidScanFactory> */
    use HasFactory;

    protected $fillable = [
        'package_id',
        'scanner_id',
        'warehouse_id',
        'vehicle_id',
        'location_type',
        'scanned_at',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
