<?php

namespace Ceygenic\Blog\Services;

use Ceygenic\Blog\Models\Post;
use Illuminate\Support\Carbon;

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
}

