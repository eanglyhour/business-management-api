<?php

require_once __DIR__ . '/../services/AuthService.php';

class AuthController
{
    private AuthService $authService;

    public function __construct(mysqli $db)
    {
        $this->authService = new AuthService($db);
    }

    public function login(): void
    {
        try {

            $data = json_decode(
                file_get_contents('php://input'),
                true
            );

            if (!is_array($data)) {
                throw new Exception('Invalid JSON request body');
            }

            $username = trim($data['username'] ?? '');
            $password = $data['password'] ?? '';

            if ($username === '' || $password === '') {
                throw new Exception(
                    'Username and password are required'
                );
            }

            $result = $this->authService->login(
                $username,
                $password
            );

            $this->setRefreshCookie(
                $result['refresh_token']
            );

            unset($result['refresh_token']);

            http_response_code(200);

            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'data' => $result
            ]);

        } catch (Exception $e) {

            http_response_code(401);

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ]);
        }
    }

    public function refresh(): void
    {
        try {

            $config = require __DIR__ . '/../config/auth.php';

            $cookieName = $config['refresh_cookie_name'];

            $refreshToken = $_COOKIE[$cookieName] ?? null;

            if (!$refreshToken) {
                throw new Exception(
                    'Refresh token not found'
                );
            }

            $result = $this->authService->refresh(
                $refreshToken
            );

            http_response_code(200);

            echo json_encode([
                'success' => true,
                'message' => 'Token refreshed',
                'data' => $result
            ]);

        } catch (Exception $e) {

            http_response_code(401);

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ]);
        }
    }

    public function logout(): void
    {
        try {

            $config = require __DIR__ . '/../config/auth.php';

            $cookieName = $config['refresh_cookie_name'];

            $refreshToken = $_COOKIE[$cookieName] ?? null;

            if ($refreshToken) {
                $this->authService->logout(
                    $refreshToken
                );
            }

            setcookie(
                $cookieName,
                '',
                [
                    'expires' => time() - 3600,
                    'path' => '/api/auth',
                    'secure' => $config['cookie_secure'],
                    'httponly' => $config['cookie_http_only'],
                    'samesite' => $config['cookie_same_site']
                ]
            );

            http_response_code(200);

            echo json_encode([
                'success' => true,
                'message' => 'Logout successful',
                'data' => null
            ]);

        } catch (Exception $e) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ]);
        }
    }

    private function setRefreshCookie(
        string $refreshToken
    ): void {

        $config = require __DIR__ . '/../config/auth.php';

        setcookie(
            $config['refresh_cookie_name'],
            $refreshToken,
            [
                'expires' =>
                    time() + $config['refresh_token_expire'],

                'path' => '/api/auth',

                'secure' =>
                    $config['cookie_secure'],

                'httponly' =>
                    $config['cookie_http_only'],

                'samesite' =>
                    $config['cookie_same_site']
            ]
        );
    }
}