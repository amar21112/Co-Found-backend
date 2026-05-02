<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/home';

    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            // Core API routes
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Auth module
            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/v1/auth.php'));

            // Profile module
            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/v1/profile.php'));

            // Collaboration module
            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/v1/collaboration.php'));

            // Calls module
            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/v1/calls.php'));

            // Admin module
            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/v1/admin.php'));

            // ML module
            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/v1/ml.php'));

            // Web routes
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
