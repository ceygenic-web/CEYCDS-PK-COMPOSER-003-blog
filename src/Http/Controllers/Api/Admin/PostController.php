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
        $posts = Blog::posts()->paginate($request->get('per_page', 15));
        return PostResource::collection($posts);
    }

    // Store a newly created post

    public function store(Request $request): PostResource|JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:posts,slug',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'featured_image' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'author_id' => 'nullable|exists:users,id',
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
            'published_at' => 'nullable|date',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        $post = Blog::posts()->create($validated);

        return (new PostResource($post))->response()->setStatusCode(201);
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

        return new PostResource($post);
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

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('posts', 'slug')->ignore($id)],
            'author_id' => 'nullable|exists:users,id',
            'excerpt' => 'nullable|string',
            'content' => 'sometimes|string',
            'featured_image' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
            'published_at' => 'nullable|date',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        Blog::posts()->update($id, $validated);
        $post = Blog::posts()->find($id);

        return new PostResource($post);
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

        return new PostResource($post);
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

        return new PostResource($post);
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

        return new PostResource($post);
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

        return new PostResource($post);
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

        return (new PostResource($newPost))->response()->setStatusCode(201);
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

        return new PostResource($post);
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

        return new PostResource($post);
    }
}

