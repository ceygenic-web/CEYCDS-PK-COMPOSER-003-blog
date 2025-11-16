<?php

namespace Ceygenic\Blog\Tests\Feature\Api;

use Ceygenic\Blog\Facades\Blog;
use Ceygenic\Blog\Models\AuthorProfile;
use Ceygenic\Blog\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PublicAuthorApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
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
    }

    public function test_can_get_author_with_profile(): void
    {
        /** @phpstan-ignore-next-line */
        $user = \App\Models\User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        AuthorProfile::create([
            'user_id' => $user->id,
            'bio' => 'Software developer',
            'avatar' => '/avatars/john.jpg',
            'social_links' => [
                'twitter' => 'https://twitter.com/johndoe',
            ],
        ]);

        $response = $this->getJson("/api/blog/authors/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'attributes' => [
                        'name',
                        'email',
                        'bio',
                        'avatar',
                        'social_links',
                    ],
                    'relationships' => [
                        'posts',
                    ],
                    'links',
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals('John Doe', $data['attributes']['name']);
        $this->assertEquals('Software developer', $data['attributes']['bio']);
        $this->assertEquals('/avatars/john.jpg', $data['attributes']['avatar']);
    }

    public function test_can_get_author_with_posts(): void
    {
        /** @phpstan-ignore-next-line */
        $user = \App\Models\User::create([
            'name' => 'Author',
            'email' => 'author@example.com',
        ]);

        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $post1 = Blog::posts()->create([
            'title' => 'Post 1',
            'content' => 'Content',
            'category_id' => $category->id,
            'author_id' => $user->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $post2 = Blog::posts()->create([
            'title' => 'Post 2',
            'content' => 'Content',
            'category_id' => $category->id,
            'author_id' => $user->id,
            'status' => 'published',
            'published_at' => now()->subHours(2),
        ]);

        $response = $this->getJson("/api/blog/authors/{$user->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data['relationships']['posts']['data']);
    }

    public function test_can_get_author_posts_paginated(): void
    {
        /** @phpstan-ignore-next-line */
        $user = \App\Models\User::create([
            'name' => 'Author',
            'email' => 'author@example.com',
        ]);

        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        // Create multiple posts
        for ($i = 1; $i <= 5; $i++) {
            Blog::posts()->create([
                'title' => "Post {$i}",
                'content' => 'Content',
                'category_id' => $category->id,
                'author_id' => $user->id,
                'status' => 'published',
                'published_at' => now()->subDays($i),
            ]);
        }

        $response = $this->getJson("/api/blog/authors/{$user->id}/posts?per_page=3");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);

        $data = $response->json('data');
        $this->assertCount(3, $data);
    }

    public function test_returns_404_for_nonexistent_author(): void
    {
        $response = $this->getJson('/api/blog/authors/999');

        $response->assertStatus(404)
            ->assertJsonStructure([
                'errors' => [
                    '*' => [
                        'status',
                        'title',
                        'detail',
                    ],
                ],
            ]);
    }

    public function test_author_posts_only_returns_published_posts(): void
    {
        /** @phpstan-ignore-next-line */
        $user = \App\Models\User::create([
            'name' => 'Author',
            'email' => 'author@example.com',
        ]);

        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        Blog::posts()->create([
            'title' => 'Published Post',
            'content' => 'Content',
            'category_id' => $category->id,
            'author_id' => $user->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        Blog::posts()->create([
            'title' => 'Draft Post',
            'content' => 'Content',
            'category_id' => $category->id,
            'author_id' => $user->id,
            'status' => 'draft',
        ]);

        Blog::posts()->create([
            'title' => 'Scheduled Post',
            'content' => 'Content',
            'category_id' => $category->id,
            'author_id' => $user->id,
            'status' => 'published',
            'published_at' => now()->addDay(),
        ]);

        $response = $this->getJson("/api/blog/authors/{$user->id}/posts");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Published Post', $data[0]['attributes']['title']);
    }

    public function test_author_resource_includes_all_profile_fields(): void
    {
        /** @phpstan-ignore-next-line */
        $user = \App\Models\User::create([
            'name' => 'Test Author',
            'email' => 'test@example.com',
        ]);

        AuthorProfile::create([
            'user_id' => $user->id,
            'bio' => 'Test bio',
            'avatar' => '/avatars/test.jpg',
            'social_links' => [
                'twitter' => 'https://twitter.com/test',
                'linkedin' => 'https://linkedin.com/in/test',
            ],
        ]);

        $response = $this->getJson("/api/blog/authors/{$user->id}");

        $response->assertStatus(200);
        $data = $response->json('data.attributes');
        
        $this->assertEquals('Test Author', $data['name']);
        $this->assertEquals('test@example.com', $data['email']);
        $this->assertEquals('Test bio', $data['bio']);
        $this->assertEquals('/avatars/test.jpg', $data['avatar']);
        $this->assertArrayHasKey('twitter', $data['social_links']);
        $this->assertArrayHasKey('linkedin', $data['social_links']);
    }
}

