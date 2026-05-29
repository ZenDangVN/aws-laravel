<?php

use App\Http\Controllers\Api\RfidScanController;
use Illuminate\Support\Facades\Route;

Route::post('/rfid/scan', [RfidScanController::class, 'store'])
    ->middleware('throttle:300,1')
    ->name('api.rfid.scan');
