<?php

use App\Http\Controllers\Logistics\DashboardController as LogisticsDashboardController;
use App\Http\Controllers\Logistics\PackageController;
use App\Http\Controllers\Logistics\ShipmentController;
use App\Http\Controllers\Logistics\VehicleController;
use App\Http\Controllers\Logistics\WarehouseController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::post('uploads', [UploadController::class, 'store'])->name('uploads.store');
    Route::get('uploads/{upload}', [UploadController::class, 'show'])->name('uploads.show');
    Route::delete('uploads/{upload}', [UploadController::class, 'destroy'])->name('uploads.destroy');

    Route::prefix('logistics')->name('logistics.')->group(function () {
        Route::get('/', [LogisticsDashboardController::class, 'index'])->name('dashboard');
        Route::resource('packages', PackageController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::resource('shipments', ShipmentController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::resource('warehouses', WarehouseController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('vehicles', VehicleController::class)->only(['index', 'store', 'update', 'destroy']);
    });
});

require __DIR__.'/settings.php';
