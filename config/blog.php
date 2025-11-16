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
];



