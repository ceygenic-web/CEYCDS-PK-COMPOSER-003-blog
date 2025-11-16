<?php

namespace Ceygenic\Blog\Console\Commands;

use Ceygenic\Blog\BlogServiceProvider;
use Ceygenic\Blog\Contracts\Repositories\CategoryRepositoryInterface;
use Ceygenic\Blog\Contracts\Repositories\PostRepositoryInterface;
use Ceygenic\Blog\Contracts\Repositories\TagRepositoryInterface;
use Ceygenic\Blog\Repositories\Eloquent\EloquentCategoryRepository;
use Ceygenic\Blog\Repositories\Eloquent\EloquentPostRepository;
use Ceygenic\Blog\Repositories\Eloquent\EloquentTagRepository;
use Ceygenic\Blog\Repositories\Sanity\SanityCategoryRepository;
use Ceygenic\Blog\Repositories\Sanity\SanityPostRepository;
use Ceygenic\Blog\Repositories\Sanity\SanityTagRepository;
use Illuminate\Console\Command;

class BlogVerifyDualStorageCommand extends Command
{
    protected $signature = 'blog:verify-dual-storage';
    protected $description = 'Verify that the dual storage system (DB and Sanity) is working correctly';

    public function handle(): int
    {
        $this->info('Verifying Dual Storage System');
        $this->newLine();

        $allPassed = true;

        // Test 1: Config File
        $this->info('1. Testing Configuration...');
        $config = config('blog');
        if (is_array($config) && isset($config['driver']) && isset($config['sanity'])) {
            $this->line('   <fg=green>✓</> Config file exists and has required keys');
            $this->line('   Driver: <fg=cyan>' . config('blog.driver', 'db') . '</>');
        } else {
            $this->error('   ✗ Config file missing or incomplete');
            $allPassed = false;
        }
        $this->newLine();

        // Test 2: Interfaces Exist
        $this->info('2. Testing Repository Interfaces...');
        $interfaces = [
            PostRepositoryInterface::class,
            CategoryRepositoryInterface::class,
            TagRepositoryInterface::class,
        ];

        foreach ($interfaces as $interface) {
            if (interface_exists($interface)) {
                $this->line('   <fg=green>✓</> ' . class_basename($interface) . ' exists');
            } else {
                $this->error('   ✗ ' . class_basename($interface) . ' does not exist');
                $allPassed = false;
            }
        }
        $this->newLine();

        // Test 3: Eloquent Repositories
        $this->info('3. Testing Eloquent Repository Implementations...');
        $app = $this->getLaravel();
        $app['config']->set('blog.driver', 'db');
        $app->register(BlogServiceProvider::class);

        $eloquentRepos = [
            'Post' => [PostRepositoryInterface::class, EloquentPostRepository::class],
            'Category' => [CategoryRepositoryInterface::class, EloquentCategoryRepository::class],
            'Tag' => [TagRepositoryInterface::class, EloquentTagRepository::class],
        ];

        foreach ($eloquentRepos as $name => [$interface, $implementation]) {
            try {
                $repo = $app->make($interface);
                if ($repo instanceof $implementation) {
                    $this->line("   <fg=green>✓</> {$name}Repository bound correctly (Eloquent)");
                } else {
                    $this->error("   ✗ {$name}Repository not bound correctly");
                    $allPassed = false;
                }
            } catch (\Exception $e) {
                $this->error("   ✗ {$name}Repository binding failed: " . $e->getMessage());
                $allPassed = false;
            }
        }
        $this->newLine();

        // Test 4: Sanity Repositories
        $this->info('4. Testing Sanity Repository Implementations...');
        $app['config']->set('blog.driver', 'sanity');
        $app->register(BlogServiceProvider::class);

        $sanityRepos = [
            'Post' => [PostRepositoryInterface::class, SanityPostRepository::class],
            'Category' => [CategoryRepositoryInterface::class, SanityCategoryRepository::class],
            'Tag' => [TagRepositoryInterface::class, SanityTagRepository::class],
        ];

        foreach ($sanityRepos as $name => [$interface, $implementation]) {
            try {
                $repo = $app->make($interface);
                if ($repo instanceof $implementation) {
                    $this->line("   <fg=green>✓</> {$name}Repository bound correctly (Sanity)");
                } else {
                    $this->error("   ✗ {$name}Repository not bound correctly");
                    $allPassed = false;
                }
            } catch (\Exception $e) {
                $this->error("   ✗ {$name}Repository binding failed: " . $e->getMessage());
                $allPassed = false;
            }
        }
        $this->newLine();

        // Test 5: Models Exist
        $this->info('5. Testing Models...');
        $models = [
            'Post' => \Ceygenic\Blog\Models\Post::class,
            'Category' => \Ceygenic\Blog\Models\Category::class,
            'Tag' => \Ceygenic\Blog\Models\Tag::class,
        ];

        foreach ($models as $name => $modelClass) {
            if (class_exists($modelClass)) {
                $this->line("   <fg=green>✓</> {$name} model exists");
            } else {
                $this->error("   ✗ {$name} model does not exist");
                $allPassed = false;
            }
        }
        $this->newLine();

        // Test 6: Migrations
        $this->info('6. Testing Database Migrations...');
        $tables = ['posts', 'categories', 'tags', 'post_tag'];
        $allTablesExist = true;

        foreach ($tables as $table) {
            if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                $this->line("   <fg=green>✓</> Table '{$table}' exists");
            } else {
                $this->warn("   ⚠ Table '{$table}' does not exist (run migrations)");
                $allTablesExist = false;
            }
        }
        $this->newLine();

        // Summary
        if ($allPassed && $allTablesExist) {
            $this->info('✅ All dual storage system components verified successfully!');
            $this->newLine();
            $this->line('Summary:');
            $this->line('  - Configuration: Working');
            $this->line('  - Interfaces: All exist');
            $this->line('  - Eloquent Repositories: Bound correctly');
            $this->line('  - Sanity Repositories: Bound correctly');
            $this->line('  - Models: All exist');
            $this->line('  - Database Tables: All exist');
            return self::SUCCESS;
        } else {
            $this->warn(' Some components need attention.');
            if (!$allTablesExist) {
                $this->line('   Run: php artisan migrate');
            }
            return self::FAILURE;
        }
    }
}

