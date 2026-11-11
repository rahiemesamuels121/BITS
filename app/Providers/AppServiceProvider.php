<?php

namespace App\Providers;

use App\Models\Form;
use App\Models\User;
use App\Policies\FormPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Form::class, FormPolicy::class);
        Gate::define('manage-clients', fn (User $user) => $user->isAdmin());
        Gate::define('manage-contracts', fn (User $user) => $user->isAdmin());
        RateLimiter::for('form-submissions', fn (Request $request) => Limit::perMinute(20)->by($request->ip().'|'.$request->route('slug')));
    }
}
