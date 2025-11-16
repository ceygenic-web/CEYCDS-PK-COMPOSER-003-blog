<?php

namespace Ceygenic\Blog\Tests\Feature;

use Ceygenic\Blog\Facades\Blog;
use Ceygenic\Blog\Models\AuthorProfile;
use Ceygenic\Blog\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthorManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test User model that uses the BlogAuthor trait
        if (!class_exists('App\\Models\\User')) {
            // Create a simple User class for testing
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
    }

    public function test_can_create_author_profile(): void
    {
        /** @phpstan-ignore-next-line */
        $user = \App\Models\User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $profile = $user->updateAuthorProfile([
            'bio' => 'Software developer and blogger',
            'avatar' => '/avatars/john.jpg',
            'social_links' => [
                'twitter' => 'https://twitter.com/johndoe',
                'github' => 'https://github.com/johndoe',
            ],
        ]);

        $this->assertNotNull($profile);
        $this->assertEquals('Software developer and blogger', $profile->bio);
        $this->assertEquals('/avatars/john.jpg', $profile->avatar);
        $this->assertArrayHasKey('twitter', $profile->social_links);
    }

    public function test_user_has_author_profile_relationship(): void
    {
        /** @phpstan-ignore-next-line */
        $user = \App\Models\User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        AuthorProfile::create([
            'user_id' => $user->id,
            'bio' => 'Content writer',
            'avatar' => '/avatars/jane.jpg',
        ]);

        $this->assertNotNull($user->authorProfile);
        $this->assertEquals('Content writer', $user->authorProfile->bio);
    }

    public function test_user_has_blog_posts_relationship(): void
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

        $post = Blog::posts()->create([
            'title' => 'My Post',
            'content' => 'Content',
            'category_id' => $category->id,
            'author_id' => $user->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->assertCount(1, $user->blogPosts);
        $this->assertEquals('My Post', $user->blogPosts->first()->title);
    }

    public function test_user_can_access_bio_via_attribute(): void
    {
        /** @phpstan-ignore-next-line */
        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        AuthorProfile::create([
            'user_id' => $user->id,
            'bio' => 'Test bio',
        ]);

        $this->assertEquals('Test bio', $user->bio);
    }

    public function test_user_can_access_avatar_via_attribute(): void
    {
        /** @phpstan-ignore-next-line */
        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        AuthorProfile::create([
            'user_id' => $user->id,
            'avatar' => '/avatars/test.jpg',
        ]);

        $this->assertEquals('/avatars/test.jpg', $user->avatar);
    }

    public function test_user_can_access_social_links_via_attribute(): void
    {
        /** @phpstan-ignore-next-line */
        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $socialLinks = [
            'twitter' => 'https://twitter.com/test',
            'linkedin' => 'https://linkedin.com/in/test',
        ];

        AuthorProfile::create([
            'user_id' => $user->id,
            'social_links' => $socialLinks,
        ]);

        $this->assertEquals($socialLinks, $user->social_links);
    }

    public function test_author_profile_returns_null_when_not_set(): void
    {
        /** @phpstan-ignore-next-line */
        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->assertNull($user->bio);
        $this->assertNull($user->avatar);
        $this->assertNull($user->social_links);
    }

    public function test_can_update_existing_author_profile(): void
    {
        /** @phpstan-ignore-next-line */
        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $user->updateAuthorProfile([
            'bio' => 'Initial bio',
        ]);

        $user->updateAuthorProfile([
            'bio' => 'Updated bio',
            'avatar' => '/avatars/new.jpg',
        ]);

        $user->refresh();
        $this->assertEquals('Updated bio', $user->bio);
        $this->assertEquals('/avatars/new.jpg', $user->avatar);
    }

    public function test_author_profile_has_user_relationship(): void
    {
        /** @phpstan-ignore-next-line */
        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $profile = AuthorProfile::create([
            'user_id' => $user->id,
            'bio' => 'Test bio',
        ]);

        $this->assertNotNull($profile->user);
        $this->assertEquals($user->id, $profile->user->id);
    }
}

