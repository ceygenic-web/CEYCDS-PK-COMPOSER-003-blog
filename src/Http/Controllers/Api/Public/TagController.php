<?php

namespace Ceygenic\Blog\Http\Controllers\Api\Public;

use Ceygenic\Blog\Facades\Blog;
use Ceygenic\Blog\Http\Controllers\Controller;
use Ceygenic\Blog\Http\Resources\PostResource;
use Ceygenic\Blog\Http\Resources\TagResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\QueryBuilder;

class TagController extends Controller
{
   //List all tags
    public function index(Request $request): AnonymousResourceCollection
    {
        // If search parameter is provided, use auto-complete search
        if ($request->has('search') && !empty($request->get('search'))) {
            $limit = $request->get('limit', 10);
            $tags = Blog::tags()->search($request->get('search'), $limit);
            $resourceClass = $this->getResourceClass('tag');
            return $resourceClass::collection($tags);
        }

        $query = \Ceygenic\Blog\Models\Tag::query();
        
        $tags = QueryBuilder::for($query)
            ->allowedFilters(['name', 'slug'])
            ->allowedSorts(['name', 'created_at'])
            ->defaultSort('name')
            ->paginate($this->getPerPage($request));

        $resourceClass = $this->getResourceClass('tag');
        return $resourceClass::collection($tags);
    }

    // Get popular tags
    public function popular(Request $request): AnonymousResourceCollection
    {
        $limit = $request->get('limit', 10);
        $tags = Blog::tags()->getPopular($limit);
        $resourceClass = $this->getResourceClass('tag');
        return $resourceClass::collection($tags);
    }

    // Get posts by tag
     
    public function posts(string $slug, Request $request): AnonymousResourceCollection|JsonResponse
    {
        $tag = Blog::tags()->findBySlug($slug);

        if (!$tag) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '404',
                        'title' => 'Not Found',
                        'detail' => 'Tag not found',
                    ]
                ]
            ], 404);
        }

        // Get posts by tag using relationship
        $query = \Ceygenic\Blog\Models\Post::query()
            ->whereHas('tags', function ($q) use ($tag) {
                $q->where('tags.id', $tag->id);
            });
        
        $posts = QueryBuilder::for($query)
            ->allowedFilters(['title', 'status'])
            ->allowedSorts(['title', 'published_at', 'created_at'])
            ->defaultSort('-published_at')
            ->paginate($this->getPerPage($request));

        $resourceClass = $this->getResourceClass('post');
        return $resourceClass::collection($posts);
    }
}

