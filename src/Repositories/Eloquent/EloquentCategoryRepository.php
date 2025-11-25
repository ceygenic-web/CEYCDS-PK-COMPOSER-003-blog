<?php

namespace Ceygenic\Blog\Repositories\Eloquent;

use Ceygenic\Blog\Contracts\Repositories\CategoryRepositoryInterface;
use Ceygenic\Blog\Models\Category;
use Ceygenic\Blog\Traits\HasCache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    use HasCache;
    public function all(): Collection
    {
        return $this->remember('categories:all', function () {
            return Category::ordered()->get();
        }, 'categories');
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        // Pagination can't be cached easily
        return Category::ordered()->paginate($perPage);
    }

    public function find(int $id)
    {
        return $this->remember("categories:{$id}", function () use ($id) {
            return Category::find($id);
        }, 'categories');
    }

    public function findBySlug(string $slug)
    {
        return $this->remember("categories:slug:{$slug}", function () use ($slug) {
            return Category::where('slug', $slug)->first();
        }, 'categories');
    }

    public function create(array $data)
    {
        $category = Category::create($data);
        $this->clearCategoryCache();
        
        // Dispatch event
        if (config('blog.features.events', true)) {
            event(new \Ceygenic\Blog\Events\CategoryCreated($category));
        }
        
        return $category;
    }

    public function update(int $id, array $data): bool
    {
        $category = Category::findOrFail($id);
        $slug = $category->slug;
        $result = $category->update($data);

        if ($result) {
            $this->clearCategoryCache();
            $this->forgetCache("categories:{$id}");
            $this->forgetCache("categories:slug:{$slug}");
        }

        return $result;
    }

    public function delete(int $id): bool
    {
        $category = Category::findOrFail($id);
        $slug = $category->slug;
        $result = $category->delete();

        if ($result) {
            $this->clearCategoryCache();
            $this->forgetCache("categories:{$id}");
            $this->forgetCache("categories:slug:{$slug}");
        }

        return $result;
    }

    public function allOrdered(): Collection
    {
        return $this->remember('categories:ordered', function () {
            return Category::ordered()->get();
        }, 'categories');
    }

    // Clear all category-related cache.
    protected function clearCategoryCache(): void
    {
        $this->forgetCache('categories:all');
        $this->forgetCache('categories:ordered');
        $this->clearCacheByPattern('categories:*');
    }

    public function moveUp(int $id): bool
    {
        $category = Category::findOrFail($id);
        $result = $category->moveUp();
        if ($result) {
            $this->clearCategoryCache();
        }
        return $result;
    }

    public function moveDown(int $id): bool
    {
        $category = Category::findOrFail($id);
        $result = $category->moveDown();
        if ($result) {
            $this->clearCategoryCache();
        }
        return $result;
    }

    public function setOrder(int $id, int $order): bool
    {
        $category = Category::findOrFail($id);
        $result = $category->setOrder($order);
        if ($result) {
            $this->clearCategoryCache();
        }
        return $result;
    }
}

