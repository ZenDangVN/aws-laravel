<?php

namespace App\Events;

use App\Models\RfidScan;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RfidScanReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly RfidScan $scan) {}

    public function broadcastOn(): array
    {
        return [new Channel('logistics')];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->scan->id,
            'rfid_tag' => $this->scan->package->rfid_tag,
            'tracking_number' => $this->scan->package->tracking_number,
            'package_status' => $this->scan->package->status,
            'scanner_id' => $this->scan->scanner_id,
            'location_type' => $this->scan->location_type,
            'warehouse' => $this->scan->warehouse?->only(['id', 'name', 'code']),
            'vehicle' => $this->scan->vehicle?->only(['id', 'plate_number']),
            'scanned_at' => $this->scan->scanned_at->toISOString(),
        ];
    }
}
