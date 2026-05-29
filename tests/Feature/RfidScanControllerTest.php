<?php

use App\Events\RfidScanReceived;
use App\Models\Package;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Event;

test('rfid scan endpoint creates scan record', function () {
    Event::fake();
    $package = Package::factory()->create(['rfid_tag' => 'RFID-001']);
    $warehouse = Warehouse::factory()->create();

    $this->postJson('/api/rfid/scan', [
        'rfid_tag' => 'RFID-001',
        'scanner_id' => 'GATE-01',
        'location_type' => 'warehouse',
        'warehouse_id' => $warehouse->id,
    ])->assertCreated()->assertJsonStructure(['scan_id', 'status']);

    $this->assertDatabaseHas('rfid_scans', [
        'package_id' => $package->id,
        'scanner_id' => 'GATE-01',
        'warehouse_id' => $warehouse->id,
        'location_type' => 'warehouse',
    ]);
});

test('rfid scan updates package status to at_warehouse', function () {
    Event::fake();
    $package = Package::factory()->create(['rfid_tag' => 'RFID-002', 'status' => 'in_transit']);
    $warehouse = Warehouse::factory()->create();

    $this->postJson('/api/rfid/scan', [
        'rfid_tag' => 'RFID-002',
        'scanner_id' => 'GATE-02',
        'location_type' => 'warehouse',
        'warehouse_id' => $warehouse->id,
    ]);

    expect($package->fresh()->status)->toBe('at_warehouse');
});

test('rfid scan broadcasts RfidScanReceived event', function () {
    Event::fake();
    $package = Package::factory()->create(['rfid_tag' => 'RFID-003']);

    $this->postJson('/api/rfid/scan', [
        'rfid_tag' => 'RFID-003',
        'scanner_id' => 'GATE-03',
        'location_type' => 'checkpoint',
    ]);

    Event::assertDispatched(RfidScanReceived::class);
});

test('rfid scan returns 404 for unknown rfid tag', function () {
    $this->postJson('/api/rfid/scan', [
        'rfid_tag' => 'UNKNOWN-TAG',
        'scanner_id' => 'GATE-01',
        'location_type' => 'checkpoint',
    ])->assertNotFound();
});
