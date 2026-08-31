<?php

return [
    'jwt_secret' => $_ENV['JWT_SECRET']
        ?? throw new RuntimeException(
            'JWT_SECRET is not configured'
        ),

    'access_token_expire' =>
        (int) ($_ENV['ACCESS_TOKEN_EXPIRE'] ?? 900),

    'refresh_token_expire' =>
        (int) ($_ENV['REFRESH_TOKEN_EXPIRE'] ?? 604800),

    'refresh_cookie_name' =>
        $_ENV['REFRESH_COOKIE_NAME'] ?? 'refresh_token',

    'cookie_secure' =>
        filter_var(
            $_ENV['COOKIE_SECURE'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        ),

    'cookie_http_only' =>
        filter_var(
            $_ENV['COOKIE_HTTP_ONLY'] ?? true,
            FILTER_VALIDATE_BOOLEAN
        ),

    'cookie_same_site' =>
        $_ENV['COOKIE_SAME_SITE'] ?? 'Strict',
];