<?php

namespace Ceygenic\Blog;

use Ceygenic\Blog\Contracts\Repositories\CategoryRepositoryInterface;
use Ceygenic\Blog\Contracts\Repositories\PostRepositoryInterface;
use Ceygenic\Blog\Contracts\Repositories\TagRepositoryInterface;

class Blog
{
    protected PostRepositoryInterface $postRepository;
    protected CategoryRepositoryInterface $categoryRepository;
    protected TagRepositoryInterface $tagRepository;

    public function __construct(
        PostRepositoryInterface $postRepository,
        CategoryRepositoryInterface $categoryRepository,
        TagRepositoryInterface $tagRepository
    ) {
        $this->postRepository = $postRepository;
        $this->categoryRepository = $categoryRepository;
        $this->tagRepository = $tagRepository;
    }

    public function version(): string
    {
        return 'Blog v0.1.0';
    }

    public function posts(): PostRepositoryInterface
    {
        return $this->postRepository;
    }

    public function categories(): CategoryRepositoryInterface
    {
        return $this->categoryRepository;
    }

    public function tags(): TagRepositoryInterface
    {
        return $this->tagRepository;
    }

    // Create a draft post.
    public function createDraft(array $data)
    {
        return $this->postRepository->createDraft($data);
    }

    // Publish a post.
    public function publishPost(int $id, ?\DateTime $publishedAt = null)
    {
        return $this->postRepository->publish($id, $publishedAt);
    }

    // Unpublish a post.
    public function unpublishPost(int $id)
    {
        return $this->postRepository->unpublish($id);
    }

    // Toggle post status.
    public function togglePostStatus(int $id)
    {
        return $this->postRepository->toggleStatus($id);
    }

    // Schedule a post for future publication.
    public function schedulePost(int $id, \DateTime $date)
    {
        return $this->postRepository->schedule($id, $date);
    }

    // Duplicate a post.
    public function duplicatePost(int $id, ?string $newTitle = null)
    {
        return $this->postRepository->duplicate($id, $newTitle);
    }

    // Archive a post.
    public function archivePost(int $id)
    {
        return $this->postRepository->archive($id);
    }

    // Restore a post from archive.
    public function restorePost(int $id)
    {
        return $this->postRepository->restore($id);
    }

    // Get all draft posts.
    public function getDrafts()
    {
        return $this->postRepository->getDrafts();
    }

    // Get all scheduled posts.
    public function getScheduled()
    {
        return $this->postRepository->getScheduled();
    }

    // Get all archived posts.
    public function getArchived()
    {
        return $this->postRepository->getArchived();
    }

    // Get all categories ordered by order field.
    public function getCategoriesOrdered()
    {
        return $this->categoryRepository->allOrdered();
    }

    // Move category up in order.
    public function moveCategoryUp(int $id): bool
    {
        return $this->categoryRepository->moveUp($id);
    }

    // Move category down in order.
    public function moveCategoryDown(int $id): bool
    {
        return $this->categoryRepository->moveDown($id);
    }

    // Set category order position.
    public function setCategoryOrder(int $id, int $order): bool
    {
        return $this->categoryRepository->setOrder($id, $order);
    }

    //Search tags by query (for auto-complete).
    public function searchTags(string $query, int $limit = 10)
    {
        return $this->tagRepository->search($query, $limit);
    }

    // Get popular tags.
    public function getPopularTags(int $limit = 10)
    {
        return $this->tagRepository->getPopular($limit);
    }
}



