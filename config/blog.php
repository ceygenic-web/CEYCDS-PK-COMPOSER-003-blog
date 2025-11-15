<?php

return [
    'enabled' => env('BLOG_ENABLED', true),
    'default_prefix' => env('BLOG_PREFIX', '[Blog]'),

    /*
    |--------------------------------------------------------------------------
    | Storage Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default storage driver used by the blog package.
    | You can switch between 'db' (database/Eloquent) and 'sanity' (Sanity CMS).
    |
    */
    'driver' => env('BLOG_DRIVER', 'db'),

    /*
    |--------------------------------------------------------------------------
    | Sanity Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Sanity CMS integration. These values are only used
    | when the driver is set to 'sanity'.
    |
    */
    'sanity' => [
        'project_id' => env('SANITY_PROJECT_ID', ''),
        'dataset' => env('SANITY_DATASET', 'production'),
        'token' => env('SANITY_TOKEN', null),
    ],
];



