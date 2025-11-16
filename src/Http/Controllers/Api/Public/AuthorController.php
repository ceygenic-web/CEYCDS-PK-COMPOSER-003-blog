<?php

namespace Ceygenic\Blog\Http\Controllers\Api\Public;

use Ceygenic\Blog\Http\Controllers\Controller;
use Ceygenic\Blog\Http\Resources\AuthorResource;
use Ceygenic\Blog\Http\Resources\PostResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\QueryBuilder;

class AuthorController extends Controller
{
    // Get author with posts
    public function show(string $id, Request $request): AuthorResource|JsonResponse
    {
        $userClass = config('blog.author.user_model', config('auth.providers.users.model', 'App\\Models\\User'));
        
        $query = $userClass::query();
        
        // Try to eager load relationships if they exist (BlogAuthor trait)
        if (method_exists($userClass, 'authorProfile')) {
            $query->with('authorProfile');
        }
        
        if (method_exists($userClass, 'blogPosts')) {
            $query->with(['blogPosts' => function ($q) {
                $q->where('status', 'published')
                  ->whereNotNull('published_at')
                  ->where('published_at', '<=', now());
            }]);
        }
        
        $author = $query->find($id);

        if (!$author) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '404',
                        'title' => 'Not Found',
                        'detail' => 'Author not found',
                    ]
                ]
            ], 404);
        }

        return new AuthorResource($author);
    }

    // Get author's posts (paginated)
    public function posts(string $id, Request $request): AnonymousResourceCollection|JsonResponse
    {
        $userClass = config('blog.author.user_model', config('auth.providers.users.model', 'App\\Models\\User'));
        
        $author = $userClass::find($id);

        if (!$author) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '404',
                        'title' => 'Not Found',
                        'detail' => 'Author not found',
                    ]
                ]
            ], 404);
        }

        // Use blogPosts relationship if available, otherwise query posts directly
        if (method_exists($author, 'blogPosts')) {
            $query = $author->blogPosts()
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now());
        } else {
            // Fallback: query posts directly by author_id
            $postClass = config('blog.models.post', 'Ceygenic\\Blog\\Models\\Post');
            $query = $postClass::where('author_id', $author->id)
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now());
        }

        $posts = QueryBuilder::for($query)
            ->allowedFilters(['title', 'category_id'])
            ->allowedSorts(['title', 'published_at', 'created_at'])
            ->defaultSort('-published_at')
            ->paginate($request->get('per_page', 15));

        return PostResource::collection($posts);
    }
}
