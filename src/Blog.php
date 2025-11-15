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
}



