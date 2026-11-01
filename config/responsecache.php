<?php

use Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequests;
use Spatie\ResponseCache\Hasher\DefaultHasher;
use Spatie\ResponseCache\Replacers\CsrfTokenReplacer;
use Spatie\ResponseCache\Serializers\JsonSerializer;

return [
    /*
     * Determine if the response cache middleware should be enabled.
     */
    'enabled' => env('RESPONSE_CACHE_ENABLED', true),

    'cache' => [
        /*
         * Here you may define the cache store that should be used to store
         * requests. This can be the name of any store that is configured in
         * config/cache.php.
         */
        'store' => env('RESPONSE_CACHE_DRIVER', 'file'),

        /*
         * When using the default CacheRequestFilter this setting controls the
         * default number of seconds responses must be cached.
         */
        'lifetime_in_seconds' => (int) env('RESPONSE_CACHE_LIFETIME', 60 * 60 * 24 * 7),

        /*
         * If the cache driver you configured supports tags, you may specify a tag
         * here. All responses will be tagged. When clearing the responsecache only
         * items with that tag will be flushed.
         */
        'tag' => env('RESPONSE_CACHE_TAG', ''),
    ],

    /*
     * Only cache a response after it has been requested this many times.
     */
    'minimum_requests' => [
        'count' => (int) env('RESPONSE_CACHE_MINIMUM_REQUESTS', 1),
        'lifetime_in_seconds' => (int) env('RESPONSE_CACHE_MINIMUM_REQUESTS_LIFETIME', 60 * 60 * 24),
        'store' => env('RESPONSE_CACHE_MINIMUM_REQUESTS_STORE'),
    ],

    /*
     * A request carrying this header and value bypasses the cache.
     */
    'bypass' => [
        'header_name' => env('CACHE_BYPASS_HEADER_NAME'),
        'header_value' => env('CACHE_BYPASS_HEADER_VALUE'),
    ],

    /*
     * Debug headers added to cached responses.
     */
    'debug' => [
        'enabled' => env('APP_DEBUG', true),
        'cache_time_header_name' => env('RESPONSE_CACHE_HEADER_NAME', 'laravel-responsecache'),
        'cache_status_header_name' => 'X-Cache-Status',
        'cache_age_header_name' => 'X-Cache-Age',
        'cache_key_header_name' => 'X-Cache-Key',
    ],

    /*
     * These query parameters are stripped when generating the cache key.
     */
    'ignored_query_parameters' => [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'gclid',
        'fbclid',
    ],

    /*
     * Requests carrying any of these headers are never cached.
     */
    'excluded_request_headers' => [
        'X-Livewire',
        'X-Livewire-Navigate',
        'X-Inertia',
    ],

    /*
     *  The given class will determinate if a request should be cached. The
     *  default class will cache all successful GET-requests.
     *
     *  You can provide your own class given that it implements the
     *  CacheProfile interface.
     */
    'cache_profile' => CacheAllSuccessfulGetRequests::class,

    /*
     * This class is responsible for generating a hash for a request. This hash
     * is used to look up an cached response.
     */
    'hasher' => DefaultHasher::class,

    /*
     * This class is responsible for serializing responses.
     */
    'serializer' => JsonSerializer::class,

    /*
     * Here you may define replacers that dynamically replace content from the response.
     * Each replacer must implement the Replacer interface.
     */
    'replacers' => [
        CsrfTokenReplacer::class,
    ],
];
