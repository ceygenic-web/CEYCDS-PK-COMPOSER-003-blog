<?php

return [
    'enabled' => env('BLOG_ENABLED', true),
    'default_prefix' => env('BLOG_PREFIX', '[Blog]'),

    // Storage Driver - You can switch between 'db' (database/Eloquent) and 'sanity' (Sanity CMS).
  
    'driver' => env('BLOG_DRIVER', 'db'),

    // Sanity Configuration - Configuration for Sanity CMS integration. These values are only used when the driver is set to 'sanity'.
    'sanity' => [
        'project_id' => env('SANITY_PROJECT_ID', ''),
        'dataset' => env('SANITY_DATASET', 'production'),
        'token' => env('SANITY_TOKEN', null),
    ],

    // Reading Time Configuration - Configuration for automatic reading time calculation.
    'reading_time' => [
        'words_per_minute' => env('BLOG_READING_TIME_WPM', 200),
    ],

    // Author Configuration - Configuration for author management.
    'author' => [
        // User model class - defaults to Laravel's default User model
        'user_model' => env('BLOG_USER_MODEL', config('auth.providers.users.model', 'App\\Models\\User')),
    ],

    // Model Configuration - Override default models if needed.
    'models' => [
        'post' => \Ceygenic\Blog\Models\Post::class,
        'category' => \Ceygenic\Blog\Models\Category::class,
        'tag' => \Ceygenic\Blog\Models\Tag::class,
        'author_profile' => \Ceygenic\Blog\Models\AuthorProfile::class,
    ],
];



