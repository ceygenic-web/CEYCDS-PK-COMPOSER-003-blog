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

    public function test_can_search_tags_for_auto_complete(): void
    {
        Blog::tags()->create(['name' => 'Laravel', 'slug' => 'laravel']);
        Blog::tags()->create(['name' => 'PHP', 'slug' => 'php']);
        Blog::tags()->create(['name' => 'Laravel Nova', 'slug' => 'laravel-nova']);

        $response = $this->getJson('/api/blog/tags?search=Laravel');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'type',
                    'id',
                    'attributes' => [
                        'name',
                        'slug',
                        'post_count',
                    ],
                ],
            ],
        ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);
        $this->assertTrue(collect($data)->contains('attributes.name', 'Laravel'));
        $this->assertTrue(collect($data)->contains('attributes.name', 'Laravel Nova'));
    }

    public function test_can_get_popular_tags(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $tag1 = Blog::tags()->create(['name' => 'Laravel', 'slug' => 'laravel']);
        $tag2 = Blog::tags()->create(['name' => 'PHP', 'slug' => 'php']);
        $tag3 = Blog::tags()->create(['name' => 'JavaScript', 'slug' => 'javascript']);

        // Create posts and attach tags
        $post1 = Blog::posts()->create([
            'title' => 'Post 1',
            'content' => 'Content',
            'category_id' => $category->id,
        ]);
        $post1->tags()->attach([$tag1->id, $tag2->id]);

        $post2 = Blog::posts()->create([
            'title' => 'Post 2',
            'content' => 'Content',
            'category_id' => $category->id,
        ]);
        $post2->tags()->attach([$tag1->id, $tag3->id]);

        $post3 = Blog::posts()->create([
            'title' => 'Post 3',
            'content' => 'Content',
            'category_id' => $category->id,
        ]);
        $post3->tags()->attach([$tag1->id]);

        $response = $this->getJson('/api/blog/tags/popular?limit=10');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'type',
                    'id',
                    'attributes' => [
                        'name',
                        'slug',
                        'post_count',
                    ],
                ],
            ],
        ]);

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(3, count($data));
        // Laravel should be first (most posts)
        $this->assertEquals('Laravel', $data[0]['attributes']['name']);
    }

    public function test_popular_tags_respects_limit(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        // Create multiple tags with posts
        for ($i = 1; $i <= 10; $i++) {
            $tag = Blog::tags()->create(['name' => "Tag {$i}", 'slug' => "tag-{$i}"]);
            $post = Blog::posts()->create([
                'title' => "Post {$i}",
                'content' => 'Content',
                'category_id' => $category->id,
            ]);
            $post->tags()->attach($tag->id);
        }

        $response = $this->getJson('/api/blog/tags/popular?limit=5');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertLessThanOrEqual(5, count($data));
    }
}

