<?php

require_once __DIR__ . '/../models/User.php';

class PermissionMiddleware
{
    private User $user;

    public function __construct(mysqli $db)
    {
        $this->user = new User($db);
    }

    public function handle(int $userId, string $permission): void
    {
        $permissions = $this->user->getPermissions($userId);

        if (!in_array($permission, $permissions, true)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Permission denied',
                'data'    => null
            ]);
            exit;
        }
    }
}