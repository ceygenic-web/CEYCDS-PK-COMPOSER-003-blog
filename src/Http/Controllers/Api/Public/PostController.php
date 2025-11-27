<?php

namespace Ceygenic\Blog\Http\Controllers\Api\Public;

use Ceygenic\Blog\Facades\Blog;
use Ceygenic\Blog\Http\Controllers\Controller;
use Ceygenic\Blog\Http\Resources\PostResource;
use Ceygenic\Blog\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PostController extends Controller
{
    // List all posts (paginated, filterable, sortable)
    public function index(Request $request, PostService $postService): AnonymousResourceCollection
    {
        $filters = $request->only([
            'category_id',
            'tag_id',
            'tag_ids',
            'author_id',
            'start_date',
            'end_date',
            'status',
        ]);

        $perPage = $this->getPerPage($request);

        $posts = $postService->getPublicPosts($filters, $perPage);

        $resourceClass = $this->getResourceClass('post');
        return $resourceClass::collection($posts);
    }


    // Get single post
    public function show(string $slug): PostResource|JsonResponse
    {
        $post = Blog::posts()->findBySlug($slug);

        if (!$post) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '404',
                        'title' => 'Not Found',
                        'detail' => 'Post not found',
                    ]
                ]
            ], 404);
        }

        $resourceClass = $this->getResourceClass('post');
        return new $resourceClass($post);
    }

    // Search posts (full-text search with relevance sorting)
    public function search(Request $request): AnonymousResourceCollection
    {
        $searchQuery = $request->get('q', '');
        
        if (empty(trim($searchQuery))) {
            return $this->index($request);
        }

        // Use repository search method for full-text search with relevance
        $posts = Blog::posts()->search($searchQuery, $this->getPerPage($request));

        $resourceClass = $this->getResourceClass('post');
        return $resourceClass::collection($posts);
    }
}

