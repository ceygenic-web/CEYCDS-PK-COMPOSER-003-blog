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

    public function test_can_filter_posts_by_category(): void
    {
        $category1 = Blog::categories()->create(['name' => 'Tech']);
        $category2 = Blog::categories()->create(['name' => 'Science']);

        Blog::posts()->create([
            'title' => 'Tech Post',
            'content' => 'Content',
            'category_id' => $category1->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        Blog::posts()->create([
            'title' => 'Science Post',
            'content' => 'Content',
            'category_id' => $category2->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->getJson("/api/blog/posts?category_id={$category1->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Tech Post', $data[0]['attributes']['title']);
    }

    public function test_can_filter_posts_by_tag(): void
    {
        $tag = Blog::tags()->create(['name' => 'laravel']);

        $post = Blog::posts()->create([
            'title' => 'Laravel Post',
            'content' => 'Content',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $post->tags()->attach($tag->id);

        Blog::posts()->create([
            'title' => 'Other Post',
            'content' => 'Content',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->getJson("/api/blog/posts?tag_id={$tag->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Laravel Post', $data[0]['attributes']['title']);
    }

    public function test_can_filter_posts_by_author(): void
    {
        // Create a test User model that uses the BlogAuthor trait
        if (!class_exists('App\\Models\\User')) {
            eval('
                namespace App\Models;
                use Illuminate\Foundation\Auth\User as Authenticatable;
                use Ceygenic\Blog\Traits\BlogAuthor;
                
                class User extends Authenticatable {
                    use BlogAuthor;
                    protected $fillable = ["name", "email", "password"];
                }
            ');
        }
        
        // Configure the auth provider to use our test User model
        $this->app['config']->set('auth.providers.users.model', 'App\\Models\\User');
        $this->app['config']->set('blog.author.user_model', 'App\\Models\\User');

        /** @phpstan-ignore-next-line */
        $author = \App\Models\User::create(['name' => 'John Doe', 'email' => 'john@test.com']);

        Blog::posts()->create([
            'title' => 'Author Post',
            'content' => 'Content',
            'author_id' => $author->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        Blog::posts()->create([
            'title' => 'Other Post',
            'content' => 'Content',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->getJson("/api/blog/posts?author_id={$author->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Author Post', $data[0]['attributes']['title']);
    }

    public function test_can_filter_posts_by_date_range(): void
    {
        Blog::posts()->create([
            'title' => 'Old Post',
            'content' => 'Content',
            'status' => 'published',
            'published_at' => now()->subMonths(2),
        ]);

        Blog::posts()->create([
            'title' => 'Recent Post',
            'content' => 'Content',
            'status' => 'published',
            'published_at' => now()->subWeek(),
        ]);

        $startDate = now()->subMonth()->toDateString();
        $response = $this->getJson("/api/blog/posts?start_date={$startDate}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Recent Post', $data[0]['attributes']['title']);
    }

    public function test_search_returns_relevance_sorted_results(): void
    {
        Blog::posts()->create([
            'title' => 'PHP Tutorial',
            'content' => 'Learn PHP',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Blog::posts()->create([
            'title' => 'JavaScript Guide',
            'content' => 'This is about PHP programming',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->getJson('/api/blog/posts/search?q=PHP');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
        // First result should have PHP in title (higher relevance)
        $this->assertEquals('PHP Tutorial', $data[0]['attributes']['title']);
    }
}

