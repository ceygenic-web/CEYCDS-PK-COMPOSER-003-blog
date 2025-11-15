<?php

namespace Ceygenic\Blog\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PostRepositoryInterface
{
    /**
     * Get all posts.
     */
    public function all(): Collection;

    /**
     * Get paginated posts.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a post by ID.
     */
    public function find(int $id);

    /**
     * Find a post by slug.
     */
    public function findBySlug(string $slug);

    /**
     * Create a new post.
     */
    public function create(array $data);

    /**
     * Update a post.
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a post.
     */
    public function delete(int $id): bool;

    /**
     * Get published posts.
     */
    public function getPublished(): Collection;

    /**
     * Get published posts with pagination.
     */
    public function getPublishedPaginated(int $perPage = 15): LengthAwarePaginator;
}

