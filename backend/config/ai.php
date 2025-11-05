<?php

return [
    // AI service base URL
    'url' => env('AI_SERVICE_URL', 'http://127.0.0.1:5000'),

    // Timeout in seconds for AI HTTP requests
    'timeout' => env('AI_SERVICE_TIMEOUT', 5),
];
