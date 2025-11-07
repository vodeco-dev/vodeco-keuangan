<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PDF Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configure how PDF files are cached and stored. When cache is enabled,
    | PDF files will be temporarily stored for faster access. Cache files
    | older than the specified TTL will be automatically cleaned up.
    |
    */

    'cache' => [
        // Enable or disable PDF caching
        'enabled' => env('PDF_CACHE_ENABLED', true),

        // Cache time-to-live in minutes (default: 24 hours)
        'ttl' => env('PDF_CACHE_TTL', 1440),

        // Disk to use for PDF cache (should be 'public' for web access)
        'disk' => env('PDF_CACHE_DISK', 'public'),

        // Directory path for cached PDFs
        'path' => env('PDF_CACHE_PATH', 'invoices/cache'),
    ],

    /*
    |--------------------------------------------------------------------------
    | PDF Generation Settings
    |--------------------------------------------------------------------------
    |
    | Configure how PDF files are generated and served.
    |
    */

    'generation' => [
        // Strategy: 'on_demand' or 'persistent'
        // - on_demand: Generate PDF on each request (no storage, slower)
        // - persistent: Store PDF files permanently (more storage, faster)
        'strategy' => env('PDF_GENERATION_STRATEGY', 'on_demand'),

        // Default paper size
        'paper' => env('PDF_PAPER_SIZE', 'a4'),
    ],

];
