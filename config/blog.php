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
        'media' => \Ceygenic\Blog\Models\Media::class,
    ],

    // Media Configuration - Configuration for media storage.
    'media' => [
        // Storage disk to use (local, s3, cloudinary, etc.)
        // This should be configured in config/filesystems.php
        'disk' => env('BLOG_MEDIA_DISK', 'public'),
        
        // Directory within the disk where media files are stored
        'directory' => env('BLOG_MEDIA_DIRECTORY', 'blog/media'),
        
        // Maximum file size in bytes (default: 10MB)
        'max_file_size' => env('BLOG_MEDIA_MAX_SIZE', 10485760),
        
        // Allowed MIME types
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'application/pdf',
            'video/mp4',
            'video/webm',
        ],
    ],

    // Performance Configuration - Configuration for caching and query optimization.
    'cache' => [
        // Enable/disable caching
        'enabled' => env('BLOG_CACHE_ENABLED', true),
        
        // Cache TTL in seconds
        'ttl' => env('BLOG_CACHE_TTL', 3600), 
        
        // Cache prefix for all blog cache keys
        'prefix' => env('BLOG_CACHE_PREFIX', 'blog'),
        
        // Cache specific queries
        'queries' => [
            'posts' => [
                'enabled' => env('BLOG_CACHE_POSTS', true),
                'ttl' => env('BLOG_CACHE_POSTS_TTL', 3600),
            ],
            'categories' => [
                'enabled' => env('BLOG_CACHE_CATEGORIES', true),
                'ttl' => env('BLOG_CACHE_CATEGORIES_TTL', 7200), 
            ],
            'tags' => [
                'enabled' => env('BLOG_CACHE_TAGS', true),
                'ttl' => env('BLOG_CACHE_TAGS_TTL', 7200),
            ],
        ],
    ],
];



