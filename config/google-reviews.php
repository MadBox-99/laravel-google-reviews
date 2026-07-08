<?php

return [
    'api_key' => env('GOOGLE_PLACES_API_KEY'),
    'place_id' => env('GOOGLE_PLACES_PLACE_ID'),
    'driver' => env('GOOGLE_REVIEWS_DRIVER', 'database'),
    'cache_ttl' => (int) env('GOOGLE_REVIEWS_CACHE_TTL', 86400),
    'language' => env('GOOGLE_REVIEWS_LANGUAGE', 'hu'),
    'settings_table' => 'google_review_settings',
];
