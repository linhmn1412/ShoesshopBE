<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*'], // Các đường dẫn mà CORS sẽ áp dụng
    'allowed_methods' => ['*'], // Phương thức HTTP được phép
    'allowed_origins' => ['*'], // Domain cho phép truy cập
    'allowed_origins_patterns' => [], // Mẫu domain cho phép truy cập
    'allowed_headers' => ['*'], // Header được phép
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,

];
