<?php

namespace Ceygenic\Blog\Tests\Feature\Api;

use Ceygenic\Blog\Facades\Blog;
use Ceygenic\Blog\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PublicPostApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_posts(): void
    {
        // Create test data
        $category = Blog::categories()->create([
            'name' => 'Technology',
            'slug' => 'technology',
        ]);

        Blog::posts()->create([
            'title' => 'Test Post 1',
            'slug' => 'test-post-1',
            'content' => 'Content 1',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->getJson('/api/blog/posts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'type',
                        'id',
                        'attributes' => [
                            'title',
                            'slug',
                            'content',
                        ],
                    ],
                ],
            ]);
    }

    public function test_can_get_single_post(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $post = Blog::posts()->create([
            'title' => 'Single Post',
            'slug' => 'single-post',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->getJson('/api/blog/posts/single-post');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'type' => 'posts',
                    'id' => (string) $post->id,
                    'attributes' => [
                        'title' => 'Single Post',
                        'slug' => 'single-post',
                    ],
                ],
            ]);
    }

    public function test_can_search_posts(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        Blog::posts()->create([
            'title' => 'Laravel Tutorial',
            'slug' => 'laravel-tutorial',
            'content' => 'Learn Laravel',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->getJson('/api/blog/posts/search?q=Laravel');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);
    }

    public function test_can_filter_posts_by_status(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        Blog::posts()->create([
            'title' => 'Published Post',
            'slug' => 'published-post',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->getJson('/api/blog/posts?filter[status]=published');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);
    }

    public function test_can_sort_posts(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        Blog::posts()->create([
            'title' => 'Post A',
            'slug' => 'post-a',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->getJson('/api/blog/posts?sort=title');

        $response->assertStatus(200);
    }

    public function test_returns_404_for_nonexistent_post(): void
    {
        $response = $this->getJson('/api/blog/posts/nonexistent');

        $response->assertStatus(404)
            ->assertJsonStructure([
                'errors',
            ]);
    }
}

