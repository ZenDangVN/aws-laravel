<?php

namespace App\Events;

use App\Models\Package;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PackageStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Package $package) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("packages.{$this->package->id}")];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->package->id,
            'status' => $this->package->status,
            'tracking_number' => $this->package->tracking_number,
            'current_warehouse' => $this->package->currentWarehouse?->only(['id', 'name']),
            'current_vehicle' => $this->package->currentVehicle?->only(['id', 'plate_number']),
        ];
    }
}
