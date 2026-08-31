<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class TokenService
{
    private array $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../config/auth.php';
    }

    public function createAccessToken(int $userId): string
    {
        $now = time();

        $payload = [
            'iss' => 'php-api',
            'sub' => $userId,
            'iat' => $now,
            'exp' => $now + $this->config['access_token_expire']
        ];

        return JWT::encode(
            $payload,
            $this->config['jwt_secret'],
            'HS256'
        );
    }

    public function createRefreshToken(): string
    {
        return bin2hex(random_bytes(64));
    }

    public function hashRefreshToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function verifyAccessToken(string $token): object
    {
        return JWT::decode(
            $token,
            new Key(
                $this->config['jwt_secret'],
                'HS256'
            )
        );
    }

    public function getRefreshExpire(): int
    {
        return $this->config['refresh_token_expire'];
    }

    public function getCookieName(): string
    {
        return $this->config['refresh_cookie_name'];
    }
}