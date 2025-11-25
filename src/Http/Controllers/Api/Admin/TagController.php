<?php

namespace Ceygenic\Blog\Http\Controllers\Api\Admin;

use Ceygenic\Blog\Facades\Blog;
use Ceygenic\Blog\Http\Controllers\Controller;
use Ceygenic\Blog\Http\Resources\TagResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TagController extends Controller
{
    // Display a listing of tags
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $tags = Blog::tags()->paginate($this->getPerPage($request));
        $resourceClass = $this->getResourceClass('tag');
        return $resourceClass::collection($tags);
    }

    // Store a newly created tag
    public function store(Request $request): TagResource|JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tags,slug',
            'description' => 'nullable|string',
        ]);

        $tag = Blog::tags()->create($validated);

        $resourceClass = $this->getResourceClass('tag');
        return (new $resourceClass($tag))->response()->setStatusCode(201);
    }

    // Display the specified tag
    public function show(int $id): TagResource|JsonResponse
    {
        $tag = Blog::tags()->find($id);

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

        $resourceClass = $this->getResourceClass('tag');
        return new $resourceClass($tag);
    }

    // Update the specified tag
    public function update(Request $request, int $id): TagResource|JsonResponse
    {
        $tag = Blog::tags()->find($id);

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

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('tags', 'slug')->ignore($id)],
            'description' => 'nullable|string',
        ]);

        Blog::tags()->update($id, $validated);
        $tag = Blog::tags()->find($id);

        $resourceClass = $this->getResourceClass('tag');
        return new $resourceClass($tag);
    }

    // Remove the specified tag
    public function destroy(int $id): JsonResponse
    {
        $tag = Blog::tags()->find($id);

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

        Blog::tags()->delete($id);

        return response()->json(null, 204);
    }
}

