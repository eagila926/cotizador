<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // ========= Gates por rol =========
        Gate::define('is-admin', function ($user) {
            return strtoupper((string)($user->rol ?? '')) === 'ADMIN';
        });

        // Solo ADMIN ve precio distribuidor
        Gate::define('can-see-distributor-price', function ($user) {
            return Gate::allows('is-admin');
        });

        // Solo ADMIN ve tablas de detalle (precios y pesaje)
        Gate::define('can-see-price-tables', function ($user) {
            return Gate::allows('is-admin');
        });
    }
}
