<?php

namespace Ceygenic\Blog\Tests\Feature\Api;

use Ceygenic\Blog\Facades\Blog;
use Ceygenic\Blog\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PublicCategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_categories(): void
    {
        Blog::categories()->create([
            'name' => 'Technology',
            'slug' => 'technology',
        ]);

        $response = $this->getJson('/api/blog/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'type',
                        'id',
                        'attributes' => [
                            'name',
                            'slug',
                        ],
                    ],
                ],
            ]);
    }

    public function test_can_get_posts_by_category(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        Blog::posts()->create([
            'title' => 'Tech Post',
            'slug' => 'tech-post',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->getJson("/api/blog/categories/{$category->slug}/posts");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);
    }
}

