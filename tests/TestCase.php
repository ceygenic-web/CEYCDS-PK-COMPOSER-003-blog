<?php

namespace Ceygenic\Blog\Tests;

use Ceygenic\Blog\BlogServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            BlogServiceProvider::class,
        ];
    }

    /**
     * Define database migrations.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Use in-memory SQLite for testing by default
        // You can override this by setting DB_CONNECTION in your .env file
        if (!config('database.default')) {
            $this->app['config']->set('database.default', 'testing');
            $this->app['config']->set('database.connections.testing', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
        }

        // Use array cache driver for tests to avoid needing cache table
        $this->app['config']->set('cache.default', 'array');

        // Configure filesystem for testing - ensure public disk is available
        if (!config('filesystems.disks.public')) {
            $this->app['config']->set('filesystems.disks.public', [
                'driver' => 'local',
                'root' => storage_path('app/public'),
                'url' => env('APP_URL') . '/storage',
                'visibility' => 'public',
            ]);
        }

        // Ensure blog media disk defaults to 'public' for tests
        // This prevents tests from trying to use S3 when the package isn't installed
        $this->app['config']->set('blog.media.disk', 'public');

        // Configure auth guards for testing
        // Set up a basic guard configuration that works without Sanctum
        $this->app['config']->set('auth.defaults.guard', 'web');
        
        $this->app['config']->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);

        // Configure Sanctum guard if not already set
        if (!config('auth.guards.sanctum')) {
            $this->app['config']->set('auth.guards.sanctum', [
                'driver' => 'token',
                'provider' => 'users',
                'hash' => false,
            ]);
        }

        $this->app['config']->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => \Illuminate\Foundation\Auth\User::class,
        ]);

        // Set up API routes for testing
        $this->app['router']->getRoutes()->refreshNameLookups();
    }
}


