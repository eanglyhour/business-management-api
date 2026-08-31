<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/RefreshToken.php';
require_once __DIR__ . '/TokenService.php';

class AuthService
{
    private User $user;
    private RefreshToken $refreshToken;
    private TokenService $tokenService;

    public function __construct(mysqli $db)
    {
        $this->user = new User($db);
        $this->refreshToken = new RefreshToken($db);
        $this->tokenService = new TokenService();
    }

    public function login(
        string $username,
        string $password
    ): array {

        $user = $this->user->findByUsername($username);

        if (!$user) {
            throw new Exception("Invalid username or password");
        }

        if (!$user['status']) {
            throw new Exception("Account is disabled");
        }

        if (!password_verify($password, $user['password'])) {
            throw new Exception("Invalid username or password");
        }

        $accessToken =
            $this->tokenService->createAccessToken(
                (int) $user['id']
            );

        $refreshToken =
            $this->tokenService->createRefreshToken();

        $tokenHash =
            $this->tokenService->hashRefreshToken(
                $refreshToken
            );

        $expiresAt = date(
            'Y-m-d H:i:s',
            time() + $this->tokenService->getRefreshExpire()
        );

        $this->refreshToken->create(
            (int) $user['id'],
            $tokenHash,
            $expiresAt
        );

        $permissions =
            $this->user->getPermissions(
                (int) $user['id']
            );

        return [
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => 900,

            'refresh_token' => $refreshToken,

            'user' => [
                'id' => (int) $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role_id' => (int) $user['role_id'],
                'role_name' => $user['role_name'],
                'permissions' => $permissions
            ]
        ];
    }

    public function refresh(string $refreshToken): array
    {
        $hash =
            $this->tokenService->hashRefreshToken(
                $refreshToken
            );

        $stored =
            $this->refreshToken->findValid($hash);

        if (!$stored) {
            throw new Exception("Invalid or expired refresh token");
        }

        $accessToken =
            $this->tokenService->createAccessToken(
                (int) $stored['user_id']
            );

        return [
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => 900
        ];
    }

    public function logout(string $refreshToken): void
    {
        $hash =
            $this->tokenService->hashRefreshToken(
                $refreshToken
            );

        $this->refreshToken->revoke($hash);
    }
}