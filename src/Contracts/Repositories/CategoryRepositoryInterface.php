<?php

namespace Ceygenic\Blog\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CategoryRepositoryInterface
{
    // Get all categories.
    public function all(): Collection;

    // Get paginated categories.
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    // Find a category by ID.
    public function find(int $id);

    // Find a category by slug.
    public function findBySlug(string $slug);

    // Create a new category.
    public function create(array $data);

    // Update a category.
    public function update(int $id, array $data): bool;

    // Delete a category.
    public function delete(int $id): bool;

    // Get all categories ordered by order field.
    public function allOrdered(): Collection;

    // Move category up in order.
    public function moveUp(int $id): bool;

    // Move category down in order.
    public function moveDown(int $id): bool;

    // Set category order position.
    public function setOrder(int $id, int $order): bool;
}

