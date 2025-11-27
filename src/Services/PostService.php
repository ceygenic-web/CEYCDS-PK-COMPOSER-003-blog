<?php

namespace Ceygenic\Blog\Services;

use Ceygenic\Blog\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Spatie\QueryBuilder\QueryBuilder;

class PostService
{
    // Create a draft post.
    public function createDraft(array $data): Post
    {
        $data['status'] = 'draft';
        $data['published_at'] = null;

        return Post::create($data);
    }

    // Publish a post.

    public function publish(int $postId, ?\DateTime $publishedAt = null): Post
    {
        $post = Post::findOrFail($postId);
        $post->publish($publishedAt);

        return $post->fresh();
    }

    // Unpublish a post.

    public function unpublish(int $postId): Post
    {
        $post = Post::findOrFail($postId);
        $post->unpublish();

        return $post->fresh();
    }

    // Toggle post status.

    public function toggleStatus(int $postId): Post
    {
        $post = Post::findOrFail($postId);
        $post->toggleStatus();

        return $post->fresh();
    }

    // Schedule a post for future publication.

    public function schedule(int $postId, \DateTime|string $date): Post
    {
        $post = Post::findOrFail($postId);

        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        $post->schedule($date);

        return $post->fresh();
    }

    // Duplicate a post.

    public function duplicate(int $postId, ?string $newTitle = null): Post
    {
        $post = Post::findOrFail($postId);

        return $post->duplicate($newTitle);
    }

    // Archive a post.

    public function archive(int $postId): Post
    {
        $post = Post::findOrFail($postId);
        $post->archive();

        return $post->fresh();
    }

    // Restore a post from archive.
   
    public function restore(int $postId): Post
    {
        $post = Post::findOrFail($postId);
        $post->restore();

        return $post->fresh();
    }

    protected function buildPublicPostQuery(array $filters = [])
    {
        $query = Post::query()
            ->with(['category', 'tags', 'author']);

        if (array_key_exists('category_id', $filters) && $filters['category_id'] !== null) {
            $query->byCategory((int) $filters['category_id']);
        }

        if (array_key_exists('tag_id', $filters) && $filters['tag_id'] !== null) {
            $query->byTag((int) $filters['tag_id']);
        }

        if (array_key_exists('tag_ids', $filters) && $filters['tag_ids'] !== null) {
            $tagIds = is_array($filters['tag_ids'])
                ? $filters['tag_ids']
                : explode(',', (string) $filters['tag_ids']);

            $query->byTags(array_map('intval', $tagIds));
        }

        if (array_key_exists('author_id', $filters) && $filters['author_id'] !== null) {
            $query->byAuthor((int) $filters['author_id']);
        }

        if (
            array_key_exists('start_date', $filters) && $filters['start_date'] !== null ||
            array_key_exists('end_date', $filters) && $filters['end_date'] !== null
        ) {
            $query->byDateRange(
                $filters['start_date'] ?? null,
                $filters['end_date'] ?? null
            );
        }

        if (array_key_exists('status', $filters) && $filters['status'] === 'published') {
            $query->published();
        } else {
            $query->published();
        }

        return QueryBuilder::for($query)
            ->allowedFilters(['title', 'status', 'category_id', 'author_id'])
            ->allowedSorts(['title', 'published_at', 'created_at', 'reading_time'])
            ->defaultSort('-published_at');
    }

    public function getPublicPosts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->buildPublicPostQuery($filters)
            ->paginate($perPage);
    }
}
