<?php

namespace Ceygenic\__PackageStudly;

use Illuminate\Support\ServiceProvider;

class __PackageStudly__ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/__package_name__.php', '__package_name__');

        $this->app->singleton('__package_name__', function () {
            return new __PackageStudly__();
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/__package_name__.php' => config_path('__package_name__.php'),
        ], '__package_name__-config');
    }
}


