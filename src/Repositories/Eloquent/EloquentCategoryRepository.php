<?php

namespace Ceygenic\Blog\Repositories\Eloquent;

use Ceygenic\Blog\Contracts\Repositories\CategoryRepositoryInterface;
use Ceygenic\Blog\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    public function all(): Collection
    {
        return Category::ordered()->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Category::ordered()->paginate($perPage);
    }

    public function find(int $id)
    {
        return Category::find($id);
    }

    public function findBySlug(string $slug)
    {
        return Category::where('slug', $slug)->first();
    }

    public function create(array $data)
    {
        return Category::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $category = Category::findOrFail($id);
        return $category->update($data);
    }

    public function delete(int $id): bool
    {
        $category = Category::findOrFail($id);
        return $category->delete();
    }

    public function allOrdered(): Collection
    {
        return Category::ordered()->get();
    }

    public function moveUp(int $id): bool
    {
        $category = Category::findOrFail($id);
        return $category->moveUp();
    }

    public function moveDown(int $id): bool
    {
        $category = Category::findOrFail($id);
        return $category->moveDown();
    }

    public function setOrder(int $id, int $order): bool
    {
        $category = Category::findOrFail($id);
        return $category->setOrder($order);
    }
}

