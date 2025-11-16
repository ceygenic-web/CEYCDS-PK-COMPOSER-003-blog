<?php

namespace Ceygenic\Blog\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TagRepositoryInterface
{
    // Get all tags.
    public function all(): Collection;

    // Get paginated tags.
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    // Find a tag by ID.
    public function find(int $id);

    // Find a tag by slug.
    public function findBySlug(string $slug);

    // Create a new tag.
    public function create(array $data);

    // Update a tag.
    public function update(int $id, array $data): bool;

    // Delete a tag.
    public function delete(int $id): bool;

    // Search tags by name (for auto-complete).
    public function search(string $query, int $limit = 10): Collection;

    // Get popular tags (tags with most posts).
    public function getPopular(int $limit = 10): Collection;
}

