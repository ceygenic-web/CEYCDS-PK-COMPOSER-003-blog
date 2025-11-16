<?php

namespace Ceygenic\Blog\Tests\Feature;

use Ceygenic\Blog\Facades\Blog;
use Ceygenic\Blog\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TagSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_slug_is_auto_generated_from_name(): void
    {
        $tag = Blog::tags()->create([
            'name' => 'Laravel Framework',
        ]);

        $this->assertEquals('laravel-framework', $tag->slug);
    }

    public function test_tag_slug_is_unique_when_duplicate_exists(): void
    {
        Blog::tags()->create([
            'name' => 'PHP',
        ]);

        $tag2 = Blog::tags()->create([
            'name' => 'PHP',
        ]);

        $this->assertEquals('php-1', $tag2->slug);
    }

    public function test_tag_has_post_count_attribute(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $tag = Blog::tags()->create([
            'name' => 'Laravel',
            'slug' => 'laravel',
        ]);

        // Initially no posts
        $this->assertEquals(0, $tag->post_count);

        // Create posts with tag
        $post1 = Blog::posts()->create([
            'title' => 'Post 1',
            'content' => 'Content',
            'category_id' => $category->id,
        ]);
        $post1->tags()->attach($tag->id);

        $post2 = Blog::posts()->create([
            'title' => 'Post 2',
            'content' => 'Content',
            'category_id' => $category->id,
        ]);
        $post2->tags()->attach($tag->id);

        $tag->refresh();
        $this->assertEquals(2, $tag->post_count);
    }

    public function test_can_search_tags_for_auto_complete(): void
    {
        Blog::tags()->create(['name' => 'Laravel', 'slug' => 'laravel']);
        Blog::tags()->create(['name' => 'PHP', 'slug' => 'php']);
        Blog::tags()->create(['name' => 'JavaScript', 'slug' => 'javascript']);
        Blog::tags()->create(['name' => 'Laravel Nova', 'slug' => 'laravel-nova']);

        $results = Blog::tags()->search('Laravel', 10);

        $this->assertCount(2, $results);
        $this->assertTrue($results->contains('name', 'Laravel'));
        $this->assertTrue($results->contains('name', 'Laravel Nova'));
    }

    public function test_search_tags_respects_limit(): void
    {
        // Create more tags than the limit
        for ($i = 1; $i <= 15; $i++) {
            Blog::tags()->create(['name' => "Tag {$i}", 'slug' => "tag-{$i}"]);
        }

        $results = Blog::tags()->search('Tag', 5);

        $this->assertLessThanOrEqual(5, $results->count());
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

        // Laravel should be most popular (3 posts), then PHP and JavaScript (1 each)
        $popular = Blog::tags()->getPopular(10);

        $this->assertGreaterThanOrEqual(3, $popular->count());
        // Laravel should be first (most posts)
        $this->assertEquals('Laravel', $popular->first()->name);
    }

    public function test_popular_tags_respects_limit(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        // Create multiple tags
        for ($i = 1; $i <= 15; $i++) {
            $tag = Blog::tags()->create(['name' => "Tag {$i}", 'slug' => "tag-{$i}"]);
            $post = Blog::posts()->create([
                'title' => "Post {$i}",
                'content' => 'Content',
                'category_id' => $category->id,
            ]);
            $post->tags()->attach($tag->id);
        }

        $popular = Blog::tags()->getPopular(5);

        $this->assertLessThanOrEqual(5, $popular->count());
    }

    public function test_post_count_updates_when_posts_are_deleted(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $tag = Blog::tags()->create([
            'name' => 'Laravel',
            'slug' => 'laravel',
        ]);

        $post1 = Blog::posts()->create([
            'title' => 'Post 1',
            'content' => 'Content',
            'category_id' => $category->id,
        ]);
        $post1->tags()->attach($tag->id);

        $post2 = Blog::posts()->create([
            'title' => 'Post 2',
            'content' => 'Content',
            'category_id' => $category->id,
        ]);
        $post2->tags()->attach($tag->id);

        $tag->refresh();
        $this->assertEquals(2, $tag->post_count);

        Blog::posts()->delete($post1->id);

        $tag->refresh();
        $this->assertEquals(1, $tag->post_count);
    }

    public function test_search_tags_by_slug(): void
    {
        Blog::tags()->create(['name' => 'Laravel', 'slug' => 'laravel']);
        Blog::tags()->create(['name' => 'PHP', 'slug' => 'php']);

        $results = Blog::tags()->search('laravel', 10);

        $this->assertCount(1, $results);
        $this->assertEquals('Laravel', $results->first()->name);
    }
}

