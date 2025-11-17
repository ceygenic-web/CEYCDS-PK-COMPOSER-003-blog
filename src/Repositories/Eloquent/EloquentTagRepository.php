<?php

namespace Ceygenic\Blog\Repositories\Eloquent;

use Ceygenic\Blog\Contracts\Repositories\TagRepositoryInterface;
use Ceygenic\Blog\Models\Tag;
use Ceygenic\Blog\Traits\HasCache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentTagRepository implements TagRepositoryInterface
{
    use HasCache;
    public function all(): Collection
    {
        return $this->remember('tags:all', function () {
            return Tag::all();
        }, 'tags');
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        // Pagination can't be cached easily
        return Tag::paginate($perPage);
    }

    public function find(int $id)
    {
        return $this->remember("tags:{$id}", function () use ($id) {
            return Tag::find($id);
        }, 'tags');
    }

    public function findBySlug(string $slug)
    {
        return $this->remember("tags:slug:{$slug}", function () use ($slug) {
            return Tag::where('slug', $slug)->first();
        }, 'tags');
    }

    public function create(array $data)
    {
        $tag = Tag::create($data);
        $this->clearTagCache();
        return $tag;
    }

    public function update(int $id, array $data): bool
    {
        $tag = Tag::findOrFail($id);
        $slug = $tag->slug;
        $result = $tag->update($data);

        if ($result) {
            $this->clearTagCache();
            $this->forgetCache("tags:{$id}");
            $this->forgetCache("tags:slug:{$slug}");
        }

        return $result;
    }

    public function delete(int $id): bool
    {
        $tag = Tag::findOrFail($id);
        $slug = $tag->slug;
        $result = $tag->delete();

        if ($result) {
            $this->clearTagCache();
            $this->forgetCache("tags:{$id}");
            $this->forgetCache("tags:slug:{$slug}");
        }

        return $result;
    }

    public function search(string $query, int $limit = 10): Collection
    {
        // Search results are dynamic, so we use a shorter cache TTL
        $cacheKey = "tags:search:" . md5($query . $limit);
        return $this->remember($cacheKey, function () use ($query, $limit) {
            return Tag::where('name', 'like', "%{$query}%")
                ->orWhere('slug', 'like', "%{$query}%")
                ->limit($limit)
                ->orderBy('name')
                ->get();
        }, 'tags');
    }

    public function getPopular(int $limit = 10): Collection
    {
        return $this->remember("tags:popular:{$limit}", function () use ($limit) {
            return Tag::withCount('posts')
                ->orderBy('posts_count', 'desc')
                ->orderBy('name')
                ->limit($limit)
                ->get();
        }, 'tags');
    }

    /**
     * Clear all tag-related cache.
     */
    protected function clearTagCache(): void
    {
        $this->forgetCache('tags:all');
        $this->clearCacheByPattern('tags:*');
    }
}