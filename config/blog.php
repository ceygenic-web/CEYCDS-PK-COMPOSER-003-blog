<?php

return [
    'enabled' => env('BLOG_ENABLED', true),
    'default_prefix' => env('BLOG_PREFIX', '[Blog]'),

    // Storage Driver - You can switch between 'db' (database/Eloquent) and 'sanity' (Sanity CMS).
  
    'driver' => env('BLOG_DRIVER', 'db'),

    // Route Configuration - Customize route prefixes and middleware
    'routes' => [
        'prefix' => env('BLOG_ROUTE_PREFIX', 'api/blog'),
        'middleware' => [
            'public' => env('BLOG_PUBLIC_MIDDLEWARE', 'throttle:120,1'),
            'admin' => env('BLOG_ADMIN_MIDDLEWARE', 'auth:sanctum,throttle:60,1'),
        ],
    ],

    // Table Configuration - Customize table names to avoid conflicts
    'tables' => [
        'posts' => env('BLOG_TABLE_POSTS', 'blog_posts'),
        'categories' => env('BLOG_TABLE_CATEGORIES', 'blog_categories'),
        'tags' => env('BLOG_TABLE_TAGS', 'blog_tags'),
        'author_profiles' => env('BLOG_TABLE_AUTHOR_PROFILES', 'blog_author_profiles'),
        'media' => env('BLOG_TABLE_MEDIA', 'blog_media'),
        'post_tag' => env('BLOG_TABLE_POST_TAG', 'blog_post_tag'),
    ],

    // Feature Toggles - Enable/disable specific features
    'features' => [
        'media' => env('BLOG_FEATURE_MEDIA', true),
        'admin_api' => env('BLOG_FEATURE_ADMIN_API', true),
        'sanity' => env('BLOG_FEATURE_SANITY', true),
        'search' => env('BLOG_FEATURE_SEARCH', true),
        'rate_limiting' => env('BLOG_FEATURE_RATE_LIMITING', true),
        'events' => env('BLOG_FEATURE_EVENTS', true),
    ],

    // Resource Configuration - Customize API resource classes
    'resources' => [
        'post' => env('BLOG_RESOURCE_POST', \Ceygenic\Blog\Http\Resources\PostResource::class),
        'category' => env('BLOG_RESOURCE_CATEGORY', \Ceygenic\Blog\Http\Resources\CategoryResource::class),
        'tag' => env('BLOG_RESOURCE_TAG', \Ceygenic\Blog\Http\Resources\TagResource::class),
        'author' => env('BLOG_RESOURCE_AUTHOR', \Ceygenic\Blog\Http\Resources\AuthorResource::class),
        'media' => env('BLOG_RESOURCE_MEDIA', \Ceygenic\Blog\Http\Resources\MediaResource::class),
    ],

    // Validation Rules Configuration - Customize validation rules
    'validation' => [
        'post' => [
            'title' => env('BLOG_VALIDATION_POST_TITLE', 'required|string|max:255'),
            'slug' => env('BLOG_VALIDATION_POST_SLUG', 'nullable|string|max:255|unique:blog_posts,slug'),
            'content' => env('BLOG_VALIDATION_POST_CONTENT', 'required|string'),
            'status' => env('BLOG_VALIDATION_POST_STATUS', 'nullable|in:draft,published,archived'),
        ],
        'category' => [
            'name' => env('BLOG_VALIDATION_CATEGORY_NAME', 'required|string|max:255'),
            'slug' => env('BLOG_VALIDATION_CATEGORY_SLUG', 'nullable|string|max:255|unique:blog_categories,slug'),
        ],
        'tag' => [
            'name' => env('BLOG_VALIDATION_TAG_NAME', 'required|string|max:255'),
            'slug' => env('BLOG_VALIDATION_TAG_SLUG', 'nullable|string|max:255|unique:blog_tags,slug'),
        ],
    ],

    // Pagination Configuration
    'pagination' => [
        'per_page' => env('BLOG_PAGINATION_PER_PAGE', 15),
        'max_per_page' => env('BLOG_PAGINATION_MAX_PER_PAGE', 100),
    ],

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



