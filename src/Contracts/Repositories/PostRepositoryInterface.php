<?php

namespace Ceygenic\Blog\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PostRepositoryInterface
{
    //Get all posts.
    public function all(): Collection;

    // Get paginated posts.
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    // Find a post by ID.
    public function find(int $id);

    // Find a post by slug.
    public function findBySlug(string $slug);

    // Create a new post.
    public function create(array $data);

    // Update a post.
    public function update(int $id, array $data): bool;

    // Delete a post.
    public function delete(int $id): bool;

    // Get published posts.
    public function getPublished(): Collection;

    // Get published posts with pagination.
    public function getPublishedPaginated(int $perPage = 15): LengthAwarePaginator;

    // Create a draft post.
    public function createDraft(array $data);

    // Publish a post.
    public function publish(int $id, ?\DateTime $publishedAt = null);

    // Unpublish a post.
    public function unpublish(int $id);

    // Toggle post status.
    public function toggleStatus(int $id);

    // Schedule a post for future publication.
    public function schedule(int $id, \DateTime $date);

    // Duplicate a post.
    public function duplicate(int $id, ?string $newTitle = null);

    // Archive a post.
    public function archive(int $id);

    // Restore a post from archive.
    public function restore(int $id);

    // Get all draft posts.
    public function getDrafts(): Collection;

    // Get all scheduled posts.
    public function getScheduled(): Collection;

    // Get all archived posts.
    public function getArchived(): Collection;

    // Search posts by query string (full-text search on title and content).
    public function search(string $query, int $perPage = 15): LengthAwarePaginator;
}

