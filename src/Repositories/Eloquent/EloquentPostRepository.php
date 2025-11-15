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
        return Post::with(['category', 'tags'])->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Post::with(['category', 'tags'])->paginate($perPage);
    }

    public function find(int $id)
    {
        return Post::with(['category', 'tags'])->find($id);
    }

    public function findBySlug(string $slug)
    {
        return Post::with(['category', 'tags'])->where('slug', $slug)->first();
    }

    public function create(array $data)
    {
        $post = Post::create($data);

        if (isset($data['tags']) && is_array($data['tags'])) {
            $post->tags()->sync($data['tags']);
        }

        return $post->load(['category', 'tags']);
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
        return Post::with(['category', 'tags'])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->get();
    }

    public function getPublishedPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return Post::with(['category', 'tags'])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }
}

