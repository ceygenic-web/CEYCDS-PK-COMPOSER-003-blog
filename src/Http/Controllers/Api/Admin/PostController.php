<?php

namespace Ceygenic\Blog\Http\Controllers\Api\Admin;

use Ceygenic\Blog\Facades\Blog;
use Ceygenic\Blog\Http\Controllers\Controller;
use Ceygenic\Blog\Http\Resources\PostResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PostController extends Controller
{
   //Display a listing of posts
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $posts = Blog::posts()->paginate($this->getPerPage($request));
        $resourceClass = $this->getResourceClass('post');
        return $resourceClass::collection($posts);
    }

    // Store a newly created post

    public function store(Request $request): PostResource|JsonResponse
    {
        // Get validation rules from config or use defaults
        $configRules = $this->getValidationRules('post', 'store');
        $rules = !empty($configRules) ? $configRules : [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:' . config('blog.tables.posts', 'posts') . ',slug',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'featured_image' => 'nullable|string',
            'category_id' => 'nullable|exists:' . config('blog.tables.categories', 'categories') . ',id',
            'author_id' => 'nullable|exists:users,id',
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
            'published_at' => 'nullable|date',
            'reading_time' => 'nullable|integer|min:0',
            'index' => 'nullable|integer|min:0',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:' . config('blog.tables.tags', 'tags') . ',id',
        ];

        $validated = $request->validate($rules);

        $post = Blog::posts()->create($validated);

        $resourceClass = $this->getResourceClass('post');
        return (new $resourceClass($post))->response()->setStatusCode(201);
    }

    // Display the specified post
    public function show(int $id): PostResource|JsonResponse
    {
        $post = Blog::posts()->find($id);

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

    // Update the specified post
    public function update(Request $request, int $id): PostResource|JsonResponse
    {
        $post = Blog::posts()->find($id);

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

        // Get validation rules from config or use defaults
        $configRules = $this->getValidationRules('post', 'update');
        $postsTable = config('blog.tables.posts', 'posts');
        $categoriesTable = config('blog.tables.categories', 'categories');
        $tagsTable = config('blog.tables.tags', 'tags');
        
        $rules = !empty($configRules) ? $configRules : [
            'title' => 'sometimes|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique($postsTable, 'slug')->ignore($id)],
            'author_id' => 'nullable|exists:users,id',
            'excerpt' => 'nullable|string',
            'content' => 'sometimes|string',
            'featured_image' => 'nullable|string',
            'category_id' => 'nullable|exists:' . $categoriesTable . ',id',
            'status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
            'published_at' => 'nullable|date',
            'reading_time' => 'nullable|integer|min:0',
            'index' => 'nullable|integer|min:0',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:' . $tagsTable . ',id',
        ];

        $validated = $request->validate($rules);

        Blog::posts()->update($id, $validated);
        $post = Blog::posts()->find($id);

        $resourceClass = $this->getResourceClass('post');
        return new $resourceClass($post);
    }

    // Remove the specified post
    public function destroy(int $id): JsonResponse
    {
        $post = Blog::posts()->find($id);

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

        Blog::posts()->delete($id);

        return response()->json(null, 204);
    }

    // Publish a post
    public function publish(int $id): PostResource|JsonResponse
    {
        $post = Blog::posts()->find($id);

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

        $post = Blog::publishPost($id);

        $resourceClass = $this->getResourceClass('post');
        return new $resourceClass($post);
    }

    // Unpublish a post
    public function unpublish(int $id): PostResource|JsonResponse
    {
        $post = Blog::posts()->find($id);

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

        $post = Blog::unpublishPost($id);

        $resourceClass = $this->getResourceClass('post');
        return new $resourceClass($post);
    }

    // Toggle post status
    public function toggleStatus(int $id): PostResource|JsonResponse
    {
        $post = Blog::posts()->find($id);

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

        $post = Blog::togglePostStatus($id);

        $resourceClass = $this->getResourceClass('post');
        return new $resourceClass($post);
    }

    // Schedule a post
    public function schedule(Request $request, int $id): PostResource|JsonResponse
    {
        $post = Blog::posts()->find($id);

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

        $validated = $request->validate([
            'published_at' => 'required|date|after:now',
        ]);

        $post = Blog::schedulePost($id, new \DateTime($validated['published_at']));

        $resourceClass = $this->getResourceClass('post');
        return new $resourceClass($post);
    }

    // Duplicate a post
    public function duplicate(Request $request, int $id): PostResource|JsonResponse
    {
        $post = Blog::posts()->find($id);

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

        $newTitle = $request->get('title');

        $newPost = Blog::duplicatePost($id, $newTitle);

        $resourceClass = $this->getResourceClass('post');
        return (new $resourceClass($newPost))->response()->setStatusCode(201);
    }

    // Archive a post
    public function archive(int $id): PostResource|JsonResponse
    {
        $post = Blog::posts()->find($id);

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

        $post = Blog::archivePost($id);

        $resourceClass = $this->getResourceClass('post');
        return new $resourceClass($post);
    }

    // Restore a post from archive
    public function restore(int $id): PostResource|JsonResponse
    {
        $post = Blog::posts()->find($id);

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

        $post = Blog::restorePost($id);

        $resourceClass = $this->getResourceClass('post');
        return new $resourceClass($post);
    }
}

