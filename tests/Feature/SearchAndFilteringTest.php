<?php

namespace Ceygenic\Blog\Tests\Feature;

use Ceygenic\Blog\Facades\Blog;
use Ceygenic\Blog\Models\Category;
use Ceygenic\Blog\Models\Post;
use Ceygenic\Blog\Models\Tag;
use Ceygenic\Blog\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SearchAndFilteringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test User model
        if (!class_exists('App\\Models\\User')) {
            eval('
                namespace App\Models;
                use Illuminate\Foundation\Auth\User as Authenticatable;
                
                class User extends Authenticatable {
                    protected $fillable = ["name", "email", "password"];
                }
            ');
        }
    }

    public function test_can_search_posts_by_title(): void
    {
        Post::create([
            'title' => 'Laravel Tutorial',
            'content' => 'This is a Laravel tutorial',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        Post::create([
            'title' => 'PHP Basics',
            'content' => 'Learn PHP basics',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $results = Blog::posts()->search('Laravel', 15);

        $this->assertCount(1, $results);
        $this->assertEquals('Laravel Tutorial', $results->first()->title);
    }

    public function test_can_search_posts_by_content(): void
    {
        Post::create([
            'title' => 'Post 1',
            'content' => 'This post is about JavaScript',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        Post::create([
            'title' => 'Post 2',
            'content' => 'This post is about Python',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $results = Blog::posts()->search('JavaScript', 15);

        $this->assertCount(1, $results);
        $this->assertStringContainsString('JavaScript', $results->first()->content);
    }

    public function test_search_returns_relevance_sorted_results(): void
    {
        // Title match should have higher relevance than content match
        Post::create([
            'title' => 'PHP Tutorial',
            'content' => 'Learn PHP',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        Post::create([
            'title' => 'JavaScript Guide',
            'content' => 'This is about PHP programming',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $results = Blog::posts()->search('PHP', 15);

        $this->assertCount(2, $results);
        // First result should be the one with PHP in title (higher relevance)
        $this->assertEquals('PHP Tutorial', $results->first()->title);
    }

    public function test_can_filter_posts_by_category(): void
    {
        $category1 = Category::create(['name' => 'Technology']);
        $category2 = Category::create(['name' => 'Science']);

        Post::create([
            'title' => 'Tech Post',
            'content' => 'Content',
            'category_id' => $category1->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        Post::create([
            'title' => 'Science Post',
            'content' => 'Content',
            'category_id' => $category2->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $results = Post::byCategory($category1->id)->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Tech Post', $results->first()->title);
    }

    public function test_can_filter_posts_by_tag(): void
    {
        $tag1 = Tag::create(['name' => 'laravel']);
        $tag2 = Tag::create(['name' => 'php']);

        $post1 = Post::create([
            'title' => 'Laravel Post',
            'content' => 'Content',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
        $post1->tags()->attach($tag1->id);

        $post2 = Post::create([
            'title' => 'PHP Post',
            'content' => 'Content',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
        $post2->tags()->attach($tag2->id);

        $results = Post::byTag($tag1->id)->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Laravel Post', $results->first()->title);
    }

    public function test_can_filter_posts_by_multiple_tags(): void
    {
        $tag1 = Tag::create(['name' => 'laravel']);
        $tag2 = Tag::create(['name' => 'php']);
        $tag3 = Tag::create(['name' => 'tutorial']);

        $post1 = Post::create([
            'title' => 'Post 1',
            'content' => 'Content',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
        $post1->tags()->attach([$tag1->id, $tag2->id]);

        $post2 = Post::create([
            'title' => 'Post 2',
            'content' => 'Content',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
        $post2->tags()->attach($tag3->id);

        $results = Post::byTags([$tag1->id, $tag2->id])->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Post 1', $results->first()->title);
    }

    public function test_can_filter_posts_by_author(): void
    {
        /** @phpstan-ignore-next-line */
        $author1 = \App\Models\User::create(['name' => 'Author 1', 'email' => 'author1@test.com']);
        /** @phpstan-ignore-next-line */
        $author2 = \App\Models\User::create(['name' => 'Author 2', 'email' => 'author2@test.com']);

        Post::create([
            'title' => 'Post by Author 1',
            'content' => 'Content',
            'author_id' => $author1->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        Post::create([
            'title' => 'Post by Author 2',
            'content' => 'Content',
            'author_id' => $author2->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $results = Post::byAuthor($author1->id)->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Post by Author 1', $results->first()->title);
    }

    public function test_can_filter_posts_by_date_range(): void
    {
        Post::create([
            'title' => 'Old Post',
            'content' => 'Content',
            'status' => 'published',
            'published_at' => now()->subMonths(2),
        ]);

        Post::create([
            'title' => 'Recent Post',
            'content' => 'Content',
            'status' => 'published',
            'published_at' => now()->subWeek(),
        ]);

        $startDate = now()->subMonth()->toDateString();
        $results = Post::byDateRange($startDate)->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Recent Post', $results->first()->title);
    }

    public function test_can_filter_posts_by_status(): void
    {
        Post::create([
            'title' => 'Draft Post',
            'content' => 'Content',
            'status' => 'draft',
        ]);

        Post::create([
            'title' => 'Published Post',
            'content' => 'Content',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $results = Post::byStatus('draft')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Draft Post', $results->first()->title);
    }

    public function test_published_scope_only_returns_published_posts(): void
    {
        Post::create([
            'title' => 'Draft Post',
            'content' => 'Content',
            'status' => 'draft',
        ]);

        Post::create([
            'title' => 'Published Post',
            'content' => 'Content',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        Post::create([
            'title' => 'Future Post',
            'content' => 'Content',
            'status' => 'published',
            'published_at' => now()->addDay(),
        ]);

        $results = Post::published()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Published Post', $results->first()->title);
    }

    public function test_search_only_returns_published_posts(): void
    {
        Post::create([
            'title' => 'Published Laravel Post',
            'content' => 'Content about Laravel',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        Post::create([
            'title' => 'Draft Laravel Post',
            'content' => 'Content about Laravel',
            'status' => 'draft',
        ]);

        $results = Blog::posts()->search('Laravel', 15);

        $this->assertCount(1, $results);
        $this->assertEquals('Published Laravel Post', $results->first()->title);
    }
}

