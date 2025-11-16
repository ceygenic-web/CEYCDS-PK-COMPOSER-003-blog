<?php

namespace Ceygenic\Blog\Http\Controllers\Api\Admin;

use Ceygenic\Blog\Facades\Blog;
use Ceygenic\Blog\Http\Controllers\Controller;
use Ceygenic\Blog\Http\Resources\CategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    // Display a listing of categories
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $categories = Blog::categories()->paginate($request->get('per_page', 15));
        return CategoryResource::collection($categories);
    }

    // Store a newly created category

    public function store(Request $request): CategoryResource|JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
        ]);

        $category = Blog::categories()->create($validated);

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    // Display the specified category
    public function show(int $id): CategoryResource|JsonResponse
    {
        $category = Blog::categories()->find($id);

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

        return new CategoryResource($category);
    }

    // Update the specified category
    public function update(Request $request, int $id): CategoryResource|JsonResponse
    {
        $category = Blog::categories()->find($id);

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

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($id)],
            'description' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
        ]);

        Blog::categories()->update($id, $validated);
        $category = Blog::categories()->find($id);

        return new CategoryResource($category);
    }

    // Remove the specified category
    public function destroy(int $id): JsonResponse
    {
        $category = Blog::categories()->find($id);

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

        Blog::categories()->delete($id);

        return response()->json(null, 204);
    }

    // Move category up in order
    public function moveUp(int $id): CategoryResource|JsonResponse
    {
        $category = Blog::categories()->find($id);

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

        Blog::moveCategoryUp($id);
        $category = Blog::categories()->find($id);

        return new CategoryResource($category);
    }

    // Move category down in order
    public function moveDown(int $id): CategoryResource|JsonResponse
    {
        $category = Blog::categories()->find($id);

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

        Blog::moveCategoryDown($id);
        $category = Blog::categories()->find($id);

        return new CategoryResource($category);
    }

    // Set category order
    public function setOrder(Request $request, int $id): CategoryResource|JsonResponse
    {
        $category = Blog::categories()->find($id);

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

        $validated = $request->validate([
            'order' => 'required|integer|min:0',
        ]);

        Blog::setCategoryOrder($id, $validated['order']);
        $category = Blog::categories()->find($id);

        return new CategoryResource($category);
    }
}

