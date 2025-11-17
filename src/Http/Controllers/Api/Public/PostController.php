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
        $query = \Ceygenic\Blog\Models\Post::query()
            ->with(['category', 'tags', 'author']);

        // Apply filters
        if ($request->has('category_id')) {
            $query->byCategory($request->get('category_id'));
        }

        if ($request->has('tag_id')) {
            $query->byTag($request->get('tag_id'));
        }

        if ($request->has('tag_ids')) {
            $tagIds = is_array($request->get('tag_ids')) 
                ? $request->get('tag_ids') 
                : explode(',', $request->get('tag_ids'));
            $query->byTags(array_map('intval', $tagIds));
        }

        if ($request->has('author_id')) {
            $query->byAuthor($request->get('author_id'));
        }

        if ($request->has('start_date') || $request->has('end_date')) {
            $query->byDateRange(
                $request->get('start_date'),
                $request->get('end_date')
            );
        }

        // Status filter (only for published in public API, unless admin)
        if ($request->has('status')) {
            // In public API, only show published posts
            // Admin endpoints can filter by any status
            if ($request->get('status') === 'published') {
                $query->published();
            }
        } else {
            // Default: only show published posts
            $query->published();
        }

        $posts = QueryBuilder::for($query)
            ->allowedFilters(['title', 'status', 'category_id', 'author_id'])
            ->allowedSorts(['title', 'published_at', 'created_at', 'reading_time'])
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

    // Search posts (full-text search with relevance sorting)
    public function search(Request $request): AnonymousResourceCollection
    {
        $searchQuery = $request->get('q', '');
        
        if (empty(trim($searchQuery))) {
            return $this->index($request);
        }

        // Use repository search method for full-text search with relevance
        $posts = Blog::posts()->search($searchQuery, $request->get('per_page', 15));

        return PostResource::collection($posts);
    }
}

