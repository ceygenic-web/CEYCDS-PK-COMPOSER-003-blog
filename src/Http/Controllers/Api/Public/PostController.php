<?php

namespace Ceygenic\Blog\Http\Controllers\Api\Public;

use Ceygenic\Blog\Facades\Blog;
use Ceygenic\Blog\Http\Controllers\Controller;
use Ceygenic\Blog\Http\Resources\PostResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\QueryBuilder;

class PostController extends Controller
{
    // List all posts (paginated, filterable, sortable)
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = \Ceygenic\Blog\Models\Post::query();
        
        $posts = QueryBuilder::for($query)
            ->allowedFilters(['title', 'status', 'category_id'])
            ->allowedSorts(['title', 'published_at', 'created_at'])
            ->defaultSort('-published_at')
            ->paginate($request->get('per_page', 15));

        return PostResource::collection($posts);
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

        return new PostResource($post);
    }

    // Search posts
    public function search(Request $request): AnonymousResourceCollection
    {
        $searchQuery = $request->get('q', '');
        
        $query = \Ceygenic\Blog\Models\Post::query()
            ->where('title', 'like', "%{$searchQuery}%")
            ->orWhere('content', 'like', "%{$searchQuery}%");
        
        $posts = QueryBuilder::for($query)
            ->allowedSorts(['title', 'published_at', 'created_at'])
            ->defaultSort('-published_at')
            ->paginate($request->get('per_page', 15));

        return PostResource::collection($posts);
    }
}

