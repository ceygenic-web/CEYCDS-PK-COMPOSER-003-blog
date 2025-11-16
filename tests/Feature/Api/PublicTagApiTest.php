<?php

namespace Ceygenic\Blog\Tests\Feature\Api;

use Ceygenic\Blog\Facades\Blog;
use Ceygenic\Blog\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PublicTagApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_tags(): void
    {
        Blog::tags()->create([
            'name' => 'Laravel',
            'slug' => 'laravel',
        ]);

        $response = $this->getJson('/api/blog/tags');

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

    public function test_can_get_posts_by_tag(): void
    {
        $tag = Blog::tags()->create([
            'name' => 'Laravel',
            'slug' => 'laravel',
        ]);

        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $post = Blog::posts()->create([
            'title' => 'Laravel Post',
            'slug' => 'laravel-post',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
            'tags' => [$tag->id],
        ]);

        $response = $this->getJson("/api/blog/tags/{$tag->slug}/posts");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);
    }
}

