<?php

namespace Ceygenic\Blog\Repositories\Eloquent;

use Ceygenic\Blog\Contracts\Repositories\PostRepositoryInterface;
use Ceygenic\Blog\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentPostRepository implements PostRepositoryInterface
{
    public function all(): Collection
    {
        return Post::with(['category', 'tags', 'author'])->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Post::with(['category', 'tags', 'author'])->paginate($perPage);
    }

    public function find(int $id)
    {
        return Post::with(['category', 'tags', 'author'])->find($id);
    }

    public function findBySlug(string $slug)
    {
        return Post::with(['category', 'tags', 'author'])->where('slug', $slug)->first();
    }

    public function create(array $data)
    {
        $post = Post::create($data);

        if (isset($data['tags']) && is_array($data['tags'])) {
            $post->tags()->sync($data['tags']);
        }

        return $post->load(['category', 'tags', 'author']);
    }

    public function update(int $id, array $data): bool
    {
        $post = Post::findOrFail($id);

        if (isset($data['tags']) && is_array($data['tags'])) {
            $post->tags()->sync($data['tags']);
            unset($data['tags']);
        }

        return $post->update($data);
    }

    public function delete(int $id): bool
    {
        $post = Post::findOrFail($id);
        return $post->delete();
    }

    public function getPublished(): Collection
    {
        return Post::with(['category', 'tags', 'author'])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->get();
    }

    public function getPublishedPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return Post::with(['category', 'tags', 'author'])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }

    public function createDraft(array $data)
    {
        $data['status'] = 'draft';
        $data['published_at'] = null;

        return $this->create($data);
    }

    public function publish(int $id, ?\DateTime $publishedAt = null)
    {
        $post = Post::findOrFail($id);
        $post->publish($publishedAt);

        return $post->load(['category', 'tags', 'author']);
    }

    public function unpublish(int $id)
    {
        $post = Post::findOrFail($id);
        $post->unpublish();

        return $post->load(['category', 'tags', 'author']);
    }

    public function toggleStatus(int $id)
    {
        $post = Post::findOrFail($id);
        $post->toggleStatus();

        return $post->load(['category', 'tags', 'author']);
    }

    public function schedule(int $id, \DateTime $date)
    {
        $post = Post::findOrFail($id);
        $post->schedule($date);

        return $post->load(['category', 'tags', 'author']);
    }

    public function duplicate(int $id, ?string $newTitle = null)
    {
        $post = Post::findOrFail($id);

        return $post->duplicate($newTitle);
    }

    public function archive(int $id)
    {
        $post = Post::findOrFail($id);
        $post->archive();

        return $post->load(['category', 'tags', 'author']);
    }

    public function restore(int $id)
    {
        $post = Post::findOrFail($id);
        $post->restore();

        return $post->load(['category', 'tags', 'author']);
    }

    public function getDrafts(): Collection
    {
        return Post::with(['category', 'tags', 'author'])
            ->where('status', 'draft')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getScheduled(): Collection
    {
        return Post::with(['category', 'tags', 'author'])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '>', now())
            ->orderBy('published_at', 'asc')
            ->get();
    }

    public function getArchived(): Collection
    {
        return Post::with(['category', 'tags', 'author'])
            ->where('status', 'archived')
            ->orderBy('updated_at', 'desc')
            ->get();
    }
}

