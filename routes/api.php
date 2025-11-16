<?php

use Illuminate\Support\Facades\Route;
use Ceygenic\Blog\Http\Controllers\Api\Public\PostController as PublicPostController;
use Ceygenic\Blog\Http\Controllers\Api\Public\CategoryController as PublicCategoryController;
use Ceygenic\Blog\Http\Controllers\Api\Public\TagController as PublicTagController;
use Ceygenic\Blog\Http\Controllers\Api\Public\AuthorController as PublicAuthorController;
use Ceygenic\Blog\Http\Controllers\Api\Admin\PostController as AdminPostController;
use Ceygenic\Blog\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use Ceygenic\Blog\Http\Controllers\Api\Admin\MediaController as AdminMediaController;

/* Public API Routes */


Route::prefix('api/blog')->name('blog.api.')->middleware('throttle:120,1')->group(function () {
    // Posts
    Route::get('/posts', [PublicPostController::class, 'index'])->name('posts.index');
    Route::get('/posts/search', [PublicPostController::class, 'search'])->name('posts.search');
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
Route::prefix('api/blog/admin')->name('blog.api.admin.')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
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
    
    // Media
    Route::post('/media/upload', [AdminMediaController::class, 'upload'])->name('media.upload');
});

