<?php

use App\Models\Package;
use App\Models\User;

test('package list requires authentication', function () {
    $this->get('/logistics/packages')->assertRedirect('/login');
});

test('authenticated user can list packages', function () {
    $user = User::factory()->create();
    Package::factory()->count(3)->create();

    $this->actingAs($user)
        ->get('/logistics/packages')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('logistics/packages/Index')
            ->has('packages.data', 3)
        );
});

test('can filter packages by status', function () {
    $user = User::factory()->create();
    Package::factory()->count(2)->create(['status' => 'delivered']);
    Package::factory()->count(1)->create(['status' => 'pending']);

    $this->actingAs($user)
        ->get('/logistics/packages?status=delivered')
        ->assertInertia(fn ($page) => $page->has('packages.data', 2));
});

test('can view package detail', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create();

    $this->actingAs($user)
        ->get("/logistics/packages/{$package->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('logistics/packages/Show')
            ->where('package.id', $package->id)
        );
});
