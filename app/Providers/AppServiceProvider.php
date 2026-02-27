<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Prevent lazy loading in non-production to catch N+1 queries early
        Model::preventLazyLoading(!app()->isProduction());

        // Log slow queries (>500ms) in production for monitoring
        if (app()->isProduction()) {
            DB::listen(function ($query) {
                if ($query->time > 500) {
                    Log::warning('Slow query detected', [
                        'sql' => $query->sql,
                        'time_ms' => $query->time,
                        'bindings' => $query->bindings,
                    ]);
                }
            });
        }
    }
}
