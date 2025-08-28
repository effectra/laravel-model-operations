<?php

namespace Effectra\Operations\Provider;

use Illuminate\Support\ServiceProvider;

class LaravelModelOperationsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register bindings or singletons here
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Perform post-registration booting of services
        $this->publishes([
            __DIR__.'/../../config/model-operations.php' => config_path('model-operations.php'),
        ], 'config');

        $this->mergeConfigFrom(
            __DIR__.'/../../config/model-operations.php', 'model-operations'
        );
    }
}