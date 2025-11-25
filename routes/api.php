<?php

use Illuminate\Support\Facades\Route;
use Ceygenic\Blog\Http\Controllers\Api\Public\PostController as PublicPostController;
use Ceygenic\Blog\Http\Controllers\Api\Public\CategoryController as PublicCategoryController;
use Ceygenic\Blog\Http\Controllers\Api\Public\TagController as PublicTagController;
use Ceygenic\Blog\Http\Controllers\Api\Public\AuthorController as PublicAuthorController;
use Ceygenic\Blog\Http\Controllers\Api\Admin\PostController as AdminPostController;
use Ceygenic\Blog\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use Ceygenic\Blog\Http\Controllers\Api\Admin\TagController as AdminTagController;
use Ceygenic\Blog\Http\Controllers\Api\Admin\MediaController as AdminMediaController;

/* Public API Routes */

// Get route configuration
$routePrefix = config('blog.routes.prefix', 'api/blog');
$publicMiddleware = config('blog.routes.middleware.public', 'throttle:120,1');
$adminMiddleware = config('blog.routes.middleware.admin', 'auth:sanctum,throttle:60,1');

// Parse middleware configuration into normalized arrays
$parseMiddleware = function ($middleware) {
    if (empty($middleware)) {
        return [];
    }

    if (is_array($middleware)) {
        return array_values(array_filter(array_map('trim', $middleware), fn ($m) => $m !== ''));
    }

    if (is_string($middleware)) {
        $middleware = trim($middleware);

        if ($middleware === '') {
            return [];
        }

        if (str_contains($middleware, '|')) {
            $parts = preg_split('/\s*\|\s*/', $middleware);
        } elseif (str_contains($middleware, ',')) {
            // Split on commas that are not followed by a numeric value (to keep throttle parameters intact)
            $parts = preg_split('/,(?=\s*[^\d])/', $middleware);
        } else {
            $parts = [$middleware];
        }

        return array_values(array_filter(array_map('trim', $parts ?: []), fn ($m) => $m !== ''));
    }

    return array_filter([(string) $middleware]);
};

// Apply rate limiting feature toggle
$publicMiddlewareArray = $parseMiddleware($publicMiddleware);
if (!config('blog.features.rate_limiting', true)) {
    $publicMiddlewareArray = array_filter($publicMiddlewareArray, fn($m) => !str_starts_with($m, 'throttle:'));
}

$adminMiddlewareArray = $parseMiddleware($adminMiddleware);
if (!config('blog.features.rate_limiting', true)) {
    $adminMiddlewareArray = array_filter($adminMiddlewareArray, fn($m) => !str_starts_with($m, 'throttle:'));
}

Route::prefix($routePrefix)->name('blog.api.')->middleware($publicMiddlewareArray)->group(function () {
    // Posts
    Route::get('/posts', [PublicPostController::class, 'index'])->name('posts.index');
    if (config('blog.features.search', true)) {
        Route::get('/posts/search', [PublicPostController::class, 'search'])->name('posts.search');
    }
    Route::get('/posts/{post}', [PublicPostController::class, 'show'])->name('posts.show');
    
    // Categories
    Route::get('/categories', [PublicCategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/{category}/posts', [PublicCategoryController::class, 'posts'])->name('categories.posts');
    
    // Tags
    Route::get('/tags', [PublicTagController::class, 'index'])->name('tags.index');
    Route::get('/tags/popular', [PublicTagController::class, 'popular'])->name('tags.popular');
    Route::get('/tags/{tag}/posts', [PublicTagController::class, 'posts'])->name('tags.posts');
    
    // Authors
    Route::get('/authors/{author}', [PublicAuthorController::class, 'show'])->name('authors.show');
    Route::get('/authors/{author}/posts', [PublicAuthorController::class, 'posts'])->name('authors.posts');
});

// Admin API Routes (Protected)
// Only register admin routes if admin API feature is enabled
if (config('blog.features.admin_api', true)) {
    Route::prefix($routePrefix . '/admin')->name('blog.api.admin.')->middleware($adminMiddlewareArray)->group(function () {
    // Posts CRUD
    Route::apiResource('posts', AdminPostController::class);
    
    // Post Management Actions
    Route::post('/posts/{id}/publish', [AdminPostController::class, 'publish'])->name('posts.publish');
    Route::post('/posts/{id}/unpublish', [AdminPostController::class, 'unpublish'])->name('posts.unpublish');
    Route::post('/posts/{id}/toggle-status', [AdminPostController::class, 'toggleStatus'])->name('posts.toggle-status');
    Route::post('/posts/{id}/schedule', [AdminPostController::class, 'schedule'])->name('posts.schedule');
    Route::post('/posts/{id}/duplicate', [AdminPostController::class, 'duplicate'])->name('posts.duplicate');
    Route::post('/posts/{id}/archive', [AdminPostController::class, 'archive'])->name('posts.archive');
    Route::post('/posts/{id}/restore', [AdminPostController::class, 'restore'])->name('posts.restore');
    
    // Categories CRUD
    Route::apiResource('categories', AdminCategoryController::class);
    
    // Category Order Management
    Route::post('/categories/{id}/move-up', [AdminCategoryController::class, 'moveUp'])->name('categories.move-up');
    Route::post('/categories/{id}/move-down', [AdminCategoryController::class, 'moveDown'])->name('categories.move-down');
    Route::post('/categories/{id}/set-order', [AdminCategoryController::class, 'setOrder'])->name('categories.set-order');
    
    // Tags CRUD
    Route::apiResource('tags', AdminTagController::class);
    
    // Media (only if feature is enabled)
    if (config('blog.features.media', true)) {
        Route::get('/media', [AdminMediaController::class, 'index'])->name('media.index');
        Route::post('/media/upload', [AdminMediaController::class, 'upload'])->name('media.upload');
        Route::get('/media/{id}', [AdminMediaController::class, 'show'])->name('media.show');
        Route::put('/media/{id}', [AdminMediaController::class, 'update'])->name('media.update');
        Route::patch('/media/{id}', [AdminMediaController::class, 'update'])->name('media.update');
        Route::delete('/media/{id}', [AdminMediaController::class, 'destroy'])->name('media.destroy');
    }
    });
}

