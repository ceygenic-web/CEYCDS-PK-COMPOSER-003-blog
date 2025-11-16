<?php

namespace Ceygenic\Blog\Tests\Feature\Api;

use Ceygenic\Blog\Facades\Blog;
use Ceygenic\Blog\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminPostApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Bypass authentication middleware for admin tests
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
    }

    public function test_can_create_post(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $response = $this->postJson('/api/blog/admin/posts', [
            'title' => 'New Post',
            'slug' => 'new-post',
            'content' => 'Post content',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->toIso8601String(),
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => [
                        'title',
                        'slug',
                    ],
                ],
            ]);
    }

    public function test_can_update_post(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $post = Blog::posts()->create([
            'title' => 'Original Title',
            'slug' => 'original-slug',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->putJson("/api/blog/admin/posts/{$post->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'title' => 'Updated Title',
                    ],
                ],
            ]);
    }

    public function test_can_delete_post(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $post = Blog::posts()->create([
            'title' => 'To Delete',
            'slug' => 'to-delete',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->deleteJson("/api/blog/admin/posts/{$post->id}");

        $response->assertStatus(204);
    }

    public function test_validation_on_create(): void
    {
        $response = $this->postJson('/api/blog/admin/posts', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'content']);
    }
}

