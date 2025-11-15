<?php

namespace Ceygenic\Blog\Repositories\Eloquent;

use Ceygenic\Blog\Contracts\Repositories\TagRepositoryInterface;
use Ceygenic\Blog\Models\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentTagRepository implements TagRepositoryInterface
{
    public function all(): Collection
    {
        return Tag::all();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Tag::paginate($perPage);
    }

    public function find(int $id)
    {
        return Tag::find($id);
    }

    public function findBySlug(string $slug)
    {
        return Tag::where('slug', $slug)->first();
    }

    public function create(array $data)
    {
        return Tag::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $tag = Tag::findOrFail($id);
        return $tag->update($data);
    }

    public function delete(int $id): bool
    {
        $tag = Tag::findOrFail($id);
        return $tag->delete();
    }
}

