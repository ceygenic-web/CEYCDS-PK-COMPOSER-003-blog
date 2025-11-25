<?php

namespace Ceygenic\Blog\Http\Controllers\Api\Public;

use Ceygenic\Blog\Facades\Blog;
use Ceygenic\Blog\Http\Controllers\Controller;
use Ceygenic\Blog\Http\Resources\CategoryResource;
use Ceygenic\Blog\Http\Resources\PostResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\QueryBuilder;

class CategoryController extends Controller
{
    
    // List all categories
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = \Ceygenic\Blog\Models\Category::query();
        
        $categories = QueryBuilder::for($query)
            ->allowedFilters(['name', 'slug'])
            ->allowedSorts(['name', 'created_at'])
            ->defaultSort('name')
            ->paginate($this->getPerPage($request));

        $resourceClass = $this->getResourceClass('category');
        return $resourceClass::collection($categories);
    }

    // Get posts by category 
    public function posts(string $slug, Request $request): AnonymousResourceCollection|JsonResponse
    {
        $category = Blog::categories()->findBySlug($slug);

        if (!$category) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '404',
                        'title' => 'Not Found',
                        'detail' => 'Category not found',
                    ]
                ]
            ], 404);
        }

        $query = \Ceygenic\Blog\Models\Post::query()
            ->where('category_id', $category->id);
        
        $posts = QueryBuilder::for($query)
            ->allowedFilters(['title', 'status'])
            ->allowedSorts(['title', 'published_at', 'created_at'])
            ->defaultSort('-published_at')
            ->paginate($this->getPerPage($request));

        $resourceClass = $this->getResourceClass('post');
        return $resourceClass::collection($posts);
    }
}

