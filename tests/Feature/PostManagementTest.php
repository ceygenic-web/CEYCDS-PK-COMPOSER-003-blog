<?php

namespace Ceygenic\Blog\Tests\Feature;

use Ceygenic\Blog\Facades\Blog;
use Ceygenic\Blog\Models\Post;
use Ceygenic\Blog\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PostManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_slug_is_auto_generated_from_title(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $post = Blog::posts()->create([
            'title' => 'My Awesome Post Title',
            'content' => 'Post content here',
            'category_id' => $category->id,
        ]);

        $this->assertEquals('my-awesome-post-title', $post->slug);
    }

    public function test_slug_is_unique_when_duplicate_exists(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        Blog::posts()->create([
            'title' => 'Test Post',
            'content' => 'Content',
            'category_id' => $category->id,
        ]);

        $post2 = Blog::posts()->create([
            'title' => 'Test Post',
            'content' => 'Content',
            'category_id' => $category->id,
        ]);

        $this->assertEquals('test-post-1', $post2->slug);
    }

    public function test_slug_updates_when_title_changes(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $post = Blog::posts()->create([
            'title' => 'Original Title',
            'content' => 'Content',
            'category_id' => $category->id,
        ]);

        $originalSlug = $post->slug;

        Blog::posts()->update($post->id, [
            'title' => 'Updated Title',
        ]);

        $post->refresh();
        $this->assertEquals('updated-title', $post->slug);
        $this->assertNotEquals($originalSlug, $post->slug);
    }

    public function test_reading_time_is_auto_calculated(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        // Create content with approximately 400 words 
        $content = str_repeat('word ', 400);

        $post = Blog::posts()->create([
            'title' => 'Test Post',
            'content' => $content,
            'category_id' => $category->id,
        ]);

        $this->assertNotNull($post->reading_time);
        $this->assertGreaterThanOrEqual(2, $post->reading_time);
    }

    public function test_reading_time_updates_when_content_changes(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $post = Blog::posts()->create([
            'title' => 'Test Post',
            'content' => 'Short content',
            'category_id' => $category->id,
        ]);

        $originalReadingTime = $post->reading_time;

        // Update with much longer content
        $longContent = str_repeat('word ', 1000);
        Blog::posts()->update($post->id, [
            'content' => $longContent,
        ]);

        $post->refresh();
        $this->assertGreaterThan($originalReadingTime, $post->reading_time);
    }

    public function test_can_create_draft_post(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $post = Blog::createDraft([
            'title' => 'Draft Post',
            'content' => 'Draft content',
            'category_id' => $category->id,
        ]);

        $this->assertEquals('draft', $post->status);
        $this->assertNull($post->published_at);
    }

    public function test_can_publish_post(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $post = Blog::posts()->create([
            'title' => 'Draft Post',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'draft',
        ]);

        $publishedPost = Blog::publishPost($post->id);

        $this->assertEquals('published', $publishedPost->status);
        $this->assertNotNull($publishedPost->published_at);
    }

    public function test_can_unpublish_post(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $post = Blog::posts()->create([
            'title' => 'Published Post',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $unpublishedPost = Blog::unpublishPost($post->id);

        $this->assertEquals('draft', $unpublishedPost->status);
        $this->assertNull($unpublishedPost->published_at);
    }

    public function test_can_toggle_post_status(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        // Test toggle from draft to published
        $post = Blog::posts()->create([
            'title' => 'Draft Post',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'draft',
        ]);

        $toggledPost = Blog::togglePostStatus($post->id);
        $this->assertEquals('published', $toggledPost->status);

        // Test toggle from published to draft
        $toggledPost = Blog::togglePostStatus($post->id);
        $this->assertEquals('draft', $toggledPost->status);
    }

    public function test_can_schedule_post(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $post = Blog::posts()->create([
            'title' => 'Scheduled Post',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'draft',
        ]);

        $futureDate = now()->addDays(7);
        $scheduledPost = Blog::schedulePost($post->id, $futureDate);

        $this->assertEquals('published', $scheduledPost->status);
        $this->assertTrue($scheduledPost->isScheduled());
        $this->assertEquals($futureDate->format('Y-m-d H:i:s'), $scheduledPost->published_at->format('Y-m-d H:i:s'));
    }

    public function test_can_duplicate_post(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $tag = Blog::tags()->create([
            'name' => 'Laravel',
            'slug' => 'laravel',
        ]);

        $originalPost = Blog::posts()->create([
            'title' => 'Original Post',
            'content' => 'Original content',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
            'tags' => [$tag->id],
        ]);

        $duplicatedPost = Blog::duplicatePost($originalPost->id);

        $this->assertNotEquals($originalPost->id, $duplicatedPost->id);
        $this->assertEquals('Original Post (Copy)', $duplicatedPost->title);
        $this->assertEquals('draft', $duplicatedPost->status);
        $this->assertNull($duplicatedPost->published_at);
        $this->assertNotEquals($originalPost->slug, $duplicatedPost->slug);
        $this->assertCount(1, $duplicatedPost->tags);
    }

    public function test_can_duplicate_post_with_custom_title(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $originalPost = Blog::posts()->create([
            'title' => 'Original Post',
            'content' => 'Content',
            'category_id' => $category->id,
        ]);

        $duplicatedPost = Blog::duplicatePost($originalPost->id, 'Custom Title');

        $this->assertEquals('Custom Title', $duplicatedPost->title);
    }

    public function test_can_archive_post(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $post = Blog::posts()->create([
            'title' => 'Published Post',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $archivedPost = Blog::archivePost($post->id);

        $this->assertEquals('archived', $archivedPost->status);
        $this->assertTrue($archivedPost->isArchived());
    }

    public function test_can_restore_post_from_archive(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $post = Blog::posts()->create([
            'title' => 'Archived Post',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'archived',
        ]);

        $restoredPost = Blog::restorePost($post->id);

        $this->assertEquals('draft', $restoredPost->status);
        $this->assertFalse($restoredPost->isArchived());
    }

    public function test_can_get_draft_posts(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        Blog::posts()->create([
            'title' => 'Draft 1',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'draft',
        ]);

        Blog::posts()->create([
            'title' => 'Draft 2',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'draft',
        ]);

        Blog::posts()->create([
            'title' => 'Published',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $drafts = Blog::getDrafts();

        $this->assertCount(2, $drafts);
        foreach ($drafts as $draft) {
            $this->assertEquals('draft', $draft->status);
        }
    }

    public function test_can_get_scheduled_posts(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        Blog::posts()->create([
            'title' => 'Scheduled 1',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->addDays(7),
        ]);

        Blog::posts()->create([
            'title' => 'Scheduled 2',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->addDays(14),
        ]);

        Blog::posts()->create([
            'title' => 'Published',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $scheduled = Blog::getScheduled();

        $this->assertCount(2, $scheduled);
        foreach ($scheduled as $post) {
            $this->assertTrue($post->isScheduled());
        }
    }

    public function test_can_get_archived_posts(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        Blog::posts()->create([
            'title' => 'Archived 1',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'archived',
        ]);

        Blog::posts()->create([
            'title' => 'Archived 2',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'archived',
        ]);

        Blog::posts()->create([
            'title' => 'Published',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $archived = Blog::getArchived();

        $this->assertCount(2, $archived);
        foreach ($archived as $post) {
            $this->assertTrue($post->isArchived());
        }
    }

    public function test_post_model_is_published_check(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $publishedPost = Blog::posts()->create([
            'title' => 'Published',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $draftPost = Blog::posts()->create([
            'title' => 'Draft',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'draft',
        ]);

        $this->assertTrue($publishedPost->isPublished());
        $this->assertFalse($draftPost->isPublished());
    }

    public function test_post_model_is_draft_check(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $draftPost = Blog::posts()->create([
            'title' => 'Draft',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'draft',
        ]);

        $this->assertTrue($draftPost->isDraft());
    }

    public function test_post_model_is_archived_check(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $archivedPost = Blog::posts()->create([
            'title' => 'Archived',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'archived',
        ]);

        $this->assertTrue($archivedPost->isArchived());
    }

    public function test_post_model_is_scheduled_check(): void
    {
        $category = Blog::categories()->create([
            'name' => 'Tech',
            'slug' => 'tech',
        ]);

        $scheduledPost = Blog::posts()->create([
            'title' => 'Scheduled',
            'content' => 'Content',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->addDays(7),
        ]);

        $this->assertTrue($scheduledPost->isScheduled());
    }
}

