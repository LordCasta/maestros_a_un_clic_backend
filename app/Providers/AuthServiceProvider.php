<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\ProfessionalProfile;
use App\Policies\BookingPolicy;
use App\Policies\ProfessionalPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(ProfessionalProfile::class, ProfessionalPolicy::class);
    }
}
