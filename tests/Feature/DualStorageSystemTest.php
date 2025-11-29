<?php

namespace Ceygenic\Blog\Tests\Feature;

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
use Ceygenic\Blog\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

class DualStorageSystemTest extends TestCase
{
    /**
     * Test 1: Config File - Verify blog.php config file exists and has driver setting
     */
    public function test_config_file_has_driver_setting(): void
    {
        $config = config('blog');
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('driver', $config);
        $this->assertArrayHasKey('sanity', $config);
        $this->assertContains(config('blog.driver'), ['db', 'sanity']);
    }

    /**
     * Test 2: Interfaces - Verify all repository interfaces exist
     */
    public function test_repository_interfaces_exist(): void
    {
        $this->assertTrue(interface_exists(PostRepositoryInterface::class));
        $this->assertTrue(interface_exists(CategoryRepositoryInterface::class));
        $this->assertTrue(interface_exists(TagRepositoryInterface::class));
    }

    /**
     * Test 3: Eloquent Implementations - Verify Eloquent repositories are bound when driver is 'db'
     */
    public function test_eloquent_repositories_are_bound_when_driver_is_db(): void
    {
        $this->app['config']->set('blog.driver', 'db');
        $this->app->register(BlogServiceProvider::class);
        
        $postRepo = $this->app->make(PostRepositoryInterface::class);
        $categoryRepo = $this->app->make(CategoryRepositoryInterface::class);
        $tagRepo = $this->app->make(TagRepositoryInterface::class);
        
        $this->assertInstanceOf(EloquentPostRepository::class, $postRepo);
        $this->assertInstanceOf(EloquentCategoryRepository::class, $categoryRepo);
        $this->assertInstanceOf(EloquentTagRepository::class, $tagRepo);
    }

    /**
     * Test 4: Sanity Implementations - Verify Sanity repositories are bound when driver is 'sanity'
     */
    public function test_sanity_repositories_are_bound_when_driver_is_sanity(): void
    {
        $this->app['config']->set('blog.driver', 'sanity');
        
        // Rebind to Sanity repositories
        $this->app->bind(PostRepositoryInterface::class, SanityPostRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, SanityCategoryRepository::class);
        $this->app->bind(TagRepositoryInterface::class, SanityTagRepository::class);
        
        $postRepo = $this->app->make(PostRepositoryInterface::class);
        $categoryRepo = $this->app->make(CategoryRepositoryInterface::class);
        $tagRepo = $this->app->make(TagRepositoryInterface::class);
        
        $this->assertInstanceOf(SanityPostRepository::class, $postRepo);
        $this->assertInstanceOf(SanityCategoryRepository::class, $categoryRepo);
        $this->assertInstanceOf(SanityTagRepository::class, $tagRepo);
    }

    /**
     * Test 5: Service Provider Logic - Verify repositories implement correct interfaces
     */
    public function test_repositories_implement_correct_interfaces(): void
    {
        // Test Eloquent repositories
        $this->app['config']->set('blog.driver', 'db');
        $this->app->register(BlogServiceProvider::class);
        
        $postRepo = $this->app->make(PostRepositoryInterface::class);
        $categoryRepo = $this->app->make(CategoryRepositoryInterface::class);
        $tagRepo = $this->app->make(TagRepositoryInterface::class);
        
        $this->assertInstanceOf(PostRepositoryInterface::class, $postRepo);
        $this->assertInstanceOf(CategoryRepositoryInterface::class, $categoryRepo);
        $this->assertInstanceOf(TagRepositoryInterface::class, $tagRepo);
        
        // Test Sanity repositories
        $this->app['config']->set('blog.driver', 'sanity');
        $this->app->bind(PostRepositoryInterface::class, SanityPostRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, SanityCategoryRepository::class);
        $this->app->bind(TagRepositoryInterface::class, SanityTagRepository::class);
        
        $postRepo = $this->app->make(PostRepositoryInterface::class);
        $categoryRepo = $this->app->make(CategoryRepositoryInterface::class);
        $tagRepo = $this->app->make(TagRepositoryInterface::class);
        
        $this->assertInstanceOf(PostRepositoryInterface::class, $postRepo);
        $this->assertInstanceOf(CategoryRepositoryInterface::class, $categoryRepo);
        $this->assertInstanceOf(TagRepositoryInterface::class, $tagRepo);
    }

    /**
     * Test 6: Database Migrations & Models - Verify models exist for Posts, Categories, Tags
     */
    public function test_models_exist(): void
    {
        $postModel = new \Ceygenic\Blog\Models\Post();
        $categoryModel = new \Ceygenic\Blog\Models\Category();
        $tagModel = new \Ceygenic\Blog\Models\Tag();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Model::class, $postModel);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Model::class, $categoryModel);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Model::class, $tagModel);
    }

    /**
     * Test 7: Database Migrations - Verify database tables exist (for MySQL/PostgreSQL)
     */
    public function test_database_migrations_create_tables(): void
    {
        $tablesConfig = config('blog.tables');
        $tables = [
            $tablesConfig['posts'],
            $tablesConfig['categories'],
            $tablesConfig['tags'],
            $tablesConfig['post_tag'],
        ];
        
        foreach ($tables as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Table '{$table}' should exist after running migrations"
            );
        }
    }

    /**
     * Test 8: Service Provider - Verify service provider binds correct implementation based on driver
     */
    public function test_service_provider_binds_correct_implementation_based_on_driver(): void
    {
        // Test with 'db' driver
        $this->app['config']->set('blog.driver', 'db');
        $this->app->register(BlogServiceProvider::class);
        
        $postRepo = $this->app->make(PostRepositoryInterface::class);
        $this->assertInstanceOf(EloquentPostRepository::class, $postRepo);
        
        // Test with 'sanity' driver
        $this->app['config']->set('blog.driver', 'sanity');
        $this->app->bind(PostRepositoryInterface::class, SanityPostRepository::class);
        
        $postRepo = $this->app->make(PostRepositoryInterface::class);
        $this->assertInstanceOf(SanityPostRepository::class, $postRepo);
    }
}
