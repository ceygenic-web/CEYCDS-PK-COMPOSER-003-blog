<?php

namespace Ceygenic\Blog\Tests\Feature\Api;

use Ceygenic\Blog\Facades\Blog;
use Ceygenic\Blog\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminCategoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Bypass authentication middleware for admin tests
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
    }

    public function test_can_create_category(): void
    {
        $response = $this->postJson('/api/blog/admin/categories', [
            'name' => 'New Category',
            'slug' => 'new-category',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => [
                        'name',
                        'slug',
                    ],
                ],
            ]);
    }

    public function test_can_update_category(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Original',
            'slug' => 'original',
        ]);

        $response = $this->putJson("/api/blog/admin/categories/{$category->id}", [
            'name' => 'Updated',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'name' => 'Updated',
                    ],
                ],
            ]);
    }

    public function test_can_delete_category(): void
    {
        $category = Blog::categories()->create([
            'name' => 'To Delete',
            'slug' => 'to-delete',
        ]);

        $response = $this->deleteJson("/api/blog/admin/categories/{$category->id}");

        $response->assertStatus(204);
    }
}
