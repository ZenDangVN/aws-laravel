<?php

use App\Models\User;

test('logistics dashboard requires authentication', function () {
    $this->get('/logistics')->assertRedirect('/login');
});

test('authenticated user can view logistics dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/logistics')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('logistics/Dashboard')
            ->has('stats')
            ->has('recent_scans')
            ->has('scans_per_hour')
        );
})->skip(fn () => config('database.default') === 'sqlite', 'EXTRACT() not supported in SQLite');

test('dashboard stats contain expected keys', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/logistics')
        ->assertInertia(fn ($page) => $page
            ->has('stats.total_packages')
            ->has('stats.in_transit')
            ->has('stats.delivered')
            ->has('stats.active_shipments')
            ->has('stats.scans_today')
        );
})->skip(fn () => config('database.default') === 'sqlite', 'EXTRACT() not supported in SQLite');
