<?php

use App\Enums\Role;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Commune;
use App\Models\User;

it('seeds catalogs and demo data on a fresh database', function () {
    $this->seed();

    expect(Commune::count())->toBe(21)
        ->and(Commune::where('code', '14')->first()->neighbors()->count())->toBeGreaterThan(0)
        ->and(Category::roots()->count())->toBe(9)
        ->and(Category::whereNotNull('parent_id')->count())->toBeGreaterThan(9)
        ->and(User::where('email', 'admin@maestros.test')->first()->role)->toBe(Role::Admin)
        ->and(User::publiclyListed()->count())->toBe(13)
        ->and(Booking::where('client_id', User::where('email', 'cliente@maestros.test')->value('id'))->count())->toBe(3);
});
