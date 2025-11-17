<?php

namespace Ceygenic\Blog\Tests\Feature;

use Ceygenic\Blog\Facades\Blog;
use Ceygenic\Blog\Models\Category;
use Ceygenic\Blog\Models\Post;
use Ceygenic\Blog\Models\Tag;
use Ceygenic\Blog\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->app['config']->set('blog.cache.enabled', true);
    }

    public function test_posts_are_cached(): void
    {
        Post::create([
            'title' => 'Test Post',
            'content' => 'Content',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        // First call - should hit database
        $posts1 = Blog::posts()->getPublished();
        $this->assertCount(1, $posts1);

        // Delete the post from database
        Post::truncate();

        // Second call - should return cached result
        $posts2 = Blog::posts()->getPublished();
        $this->assertCount(1, $posts2);
    }

    public function test_post_cache_is_cleared_on_create(): void
    {
        // Prime the cache
        Blog::posts()->getPublished();

        // Create a new post through repository (which clears cache)
        Blog::posts()->create([
            'title' => 'New Post',
            'content' => 'Content',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        // Cache should be cleared, so we should get the new post
        $posts = Blog::posts()->getPublished();
        $this->assertCount(1, $posts);
        $this->assertEquals('New Post', $posts->first()->title);
    }

    public function test_post_cache_is_cleared_on_update(): void
    {
        $post = Post::create([
            'title' => 'Original Title',
            'content' => 'Content',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        // Prime the cache
        $cached = Blog::posts()->find($post->id);
        $this->assertEquals('Original Title', $cached->title);

        // Update the post
        Blog::posts()->update($post->id, ['title' => 'Updated Title']);

        // Cache should be cleared, so we should get the updated post
        $updated = Blog::posts()->find($post->id);
        $this->assertEquals('Updated Title', $updated->title);
    }

    public function test_categories_are_cached(): void
    {
        Category::create(['name' => 'Tech']);

        // First call - should hit database
        $categories1 = Blog::categories()->all();
        $this->assertCount(1, $categories1);

        // Delete from database
        Category::truncate();

        // Second call - should return cached result
        $categories2 = Blog::categories()->all();
        $this->assertCount(1, $categories2);
    }

    public function test_category_cache_is_cleared_on_create(): void
    {
        // Prime the cache
        Blog::categories()->all();

        // Create a new category through repository (which clears cache)
        Blog::categories()->create(['name' => 'New Category']);

        // Cache should be cleared, so we should get the new category
        $categories = Blog::categories()->all();
        $this->assertCount(1, $categories);
        $this->assertEquals('New Category', $categories->first()->name);
    }

    public function test_tags_are_cached(): void
    {
        Tag::create(['name' => 'laravel']);

        // First call - should hit database
        $tags1 = Blog::tags()->all();
        $this->assertCount(1, $tags1);

        // Delete from database
        Tag::truncate();

        // Second call - should return cached result
        $tags2 = Blog::tags()->all();
        $this->assertCount(1, $tags2);
    }

    public function test_tag_cache_is_cleared_on_create(): void
    {
        // Prime the cache
        Blog::tags()->all();

        // Create a new tag through repository (which clears cache)
        Blog::tags()->create(['name' => 'php']);

        // Cache should be cleared, so we should get the new tag
        $tags = Blog::tags()->all();
        $this->assertCount(1, $tags);
        $this->assertEquals('php', $tags->first()->name);
    }

    public function test_popular_tags_are_cached(): void
    {
        $tag = Tag::create(['name' => 'popular']);
        $post = Post::create([
            'title' => 'Post',
            'content' => 'Content',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
        $post->tags()->attach($tag->id);

        // First call
        $popular1 = Blog::tags()->getPopular(10);
        $this->assertCount(1, $popular1);

        // Delete the post
        $post->delete();

        // Second call - should return cached result
        $popular2 = Blog::tags()->getPopular(10);
        $this->assertCount(1, $popular2);
    }

    public function test_cache_can_be_disabled(): void
    {
        $this->app['config']->set('blog.cache.enabled', false);

        Post::create([
            'title' => 'Test Post',
            'content' => 'Content',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        // First call
        $posts1 = Blog::posts()->getPublished();
        $this->assertCount(1, $posts1);

        // Delete from database
        Post::truncate();

        // Second call - should return empty (no cache)
        $posts2 = Blog::posts()->getPublished();
        $this->assertCount(0, $posts2);
    }

    public function test_eager_loading_is_used_in_repositories(): void
    {
        $category = Category::create(['name' => 'Tech']);
        $post = Post::create([
            'title' => 'Test Post',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        // Enable query logging
        \DB::enableQueryLog();
        \DB::flushQueryLog();

        // Get published posts - should use eager loading
        $posts = Blog::posts()->getPublished();

        $queries = \DB::getQueryLog();
        
        // Should have minimal queries (one for posts, one for categories, one for tags, one for authors)
        // With eager loading, we should have fewer queries than without
        $this->assertLessThanOrEqual(4, count($queries));
    }
}

