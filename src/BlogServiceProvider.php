<?php

namespace Ceygenic\Blog;

use Ceygenic\Blog\Contracts\Repositories\CategoryRepositoryInterface;
use Ceygenic\Blog\Contracts\Repositories\PostRepositoryInterface;
use Ceygenic\Blog\Contracts\Repositories\TagRepositoryInterface;
use Ceygenic\Blog\Repositories\Eloquent\EloquentCategoryRepository;
use Ceygenic\Blog\Repositories\Eloquent\EloquentPostRepository;
use Ceygenic\Blog\Repositories\Eloquent\EloquentTagRepository;
use Ceygenic\Blog\Repositories\Sanity\SanityCategoryRepository;
use Ceygenic\Blog\Repositories\Sanity\SanityPostRepository;
use Ceygenic\Blog\Repositories\Sanity\SanityTagRepository;
use Illuminate\Support\ServiceProvider;

class BlogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/blog.php', 'blog');

        $this->registerRepositories();

        $this->app->singleton('blog', function ($app) {
            return new Blog(
                $app->make(PostRepositoryInterface::class),
                $app->make(CategoryRepositoryInterface::class),
                $app->make(TagRepositoryInterface::class)
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/blog.php' => config_path('blog.php'),
        ], 'blog-config');

        // Publish migrations (optional - for customization)
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'blog-migrations');

        // Load migrations automatically
        // Note: The users table migration checks if table exists before creating
        // This prevents conflicts with host app's existing users table
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load API routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\BlogVerifyDualStorageCommand::class,
            ]);
        }
    }

    /**
     * Register repository bindings based on the configured driver.
     */
    protected function registerRepositories(): void
    {
        $driver = config('blog.driver', 'db');

        if ($driver === 'sanity') {
            $this->app->bind(PostRepositoryInterface::class, SanityPostRepository::class);
            $this->app->bind(CategoryRepositoryInterface::class, SanityCategoryRepository::class);
            $this->app->bind(TagRepositoryInterface::class, SanityTagRepository::class);
        } else {
            $this->app->bind(PostRepositoryInterface::class, EloquentPostRepository::class);
            $this->app->bind(CategoryRepositoryInterface::class, EloquentCategoryRepository::class);
            $this->app->bind(TagRepositoryInterface::class, EloquentTagRepository::class);
        }
    }
}



