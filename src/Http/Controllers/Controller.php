<?php

namespace Ceygenic\Blog\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    /**
     * Get resource class from config or default
     */
    protected function getResourceClass(string $type): string
    {
        return config("blog.resources.{$type}", match($type) {
            'post' => \Ceygenic\Blog\Http\Resources\PostResource::class,
            'category' => \Ceygenic\Blog\Http\Resources\CategoryResource::class,
            'tag' => \Ceygenic\Blog\Http\Resources\TagResource::class,
            'author' => \Ceygenic\Blog\Http\Resources\AuthorResource::class,
            'media' => \Ceygenic\Blog\Http\Resources\MediaResource::class,
            default => throw new \InvalidArgumentException("Unknown resource type: {$type}"),
        });
    }

    /**
     * Get validation rules from config or default
     */
    protected function getValidationRules(string $type, string $action = 'store'): array
    {
        $rules = config("blog.validation.{$type}", []);

        // Return empty array if no rules configured
        if (empty($rules)) {
            return [];
        }

        // For update action, make fields 'sometimes' instead of 'required'
        if ($action === 'update') {
            return array_map(function ($rule) {
                return str_replace('required|', 'sometimes|', $rule);
            }, $rules);
        }

        return $rules;
    }

    /**
     * Get pagination per page value from config or request
     */
    protected function getPerPage(\Illuminate\Http\Request $request): int
    {
        $default = config('blog.pagination.per_page', 15);
        $max = config('blog.pagination.max_per_page', 100);
        $requested = (int) $request->get('per_page', $default);
        
        return min($requested, $max);
    }
}

