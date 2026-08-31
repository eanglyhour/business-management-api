<?php

require_once __DIR__ . '/../services/TokenService.php';

class AuthMiddleware
{
    private TokenService $tokenService;

    public function __construct()
    {
        $this->tokenService = new TokenService();
    }

    public function handle(): object
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        // Fallback for Apache / PHP-FPM
        if ($header === '' && function_exists('getallheaders')) {

            $headers = getallheaders();

            foreach ($headers as $key => $value) {

                if (strtolower($key) === 'authorization') {
                    $header = trim($value);
                    break;
                }
            }
        }

        if ($header === '') {

            http_response_code(401);

            echo json_encode([
                'success' => false,
                'message' => 'Access token required',
                'data' => null
            ]);

            exit;
        }

        if (!preg_match(
            '/^Bearer\s+(.+)$/i',
            trim($header),
            $matches
        )) {

            http_response_code(401);

            echo json_encode([
                'success' => false,
                'message' => 'Invalid authorization header',
                'data' => null
            ]);

            exit;
        }

        $token = trim($matches[1]);

        try {

            return $this->tokenService
                ->verifyAccessToken($token);

        } catch (Throwable $e) {

            // Debug only
            // error_log($e->getMessage());

            http_response_code(401);

            echo json_encode([
                'success' => false,
                'message' => 'Invalid or expired access token',
                'data' => null
            ]);

            exit;
        }
    }
}