<?php
require_once __DIR__ . '/../models/User.php';

class UserController
{
    private User $user;

    public function __construct(mysqli $db)
    {
        $this->user = new User($db);
    }

    private function response(
        bool $success,
        string $message,
        mixed $data = null,
        int $statusCode = 200
    ): void {

        http_response_code($statusCode);

        header(
            'Content-Type: application/json'
        );

        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);

        exit;
    }

    public function index(): void
    {
        try {

            $users = $this->user->findAll();

            $this->response(
                true,
                'Users retrieved successfully',
                $users
            );

        } catch (Throwable $e) {

            $this->response(
                false,
                $e->getMessage(),
                null,
                500
            );
        }
    }

    public function show(int $id): void
    {
        try {

            if ($id <= 0) {
                $this->response(
                    false,
                    'Invalid user ID',
                    null,
                    400
                );
            }

            $user = $this->user->findById($id);

            if (!$user) {
                $this->response(
                    false,
                    'User not found',
                    null,
                    404
                );
            }

            $this->response(
                true,
                'User retrieved successfully',
                $user
            );

        } catch (Throwable $e) {

            $this->response(
                false,
                $e->getMessage(),
                null,
                500
            );
        }
    }

    public function store(): void
    {
        try {

            $data = json_decode(
                file_get_contents('php://input'),
                true
            );

            if (!is_array($data)) {
                $this->response(
                    false,
                    'Invalid JSON',
                    null,
                    400
                );
            }

            $username = trim(
                $data['username'] ?? ''
            );

            $email = trim(
                $data['email'] ?? ''
            );

            $password = $data['password'] ?? '';

            $roleId = (int) (
                $data['role_id'] ?? 0
            );

            $status = (bool) (
                $data['status'] ?? true
            );

            if ($username === '') {
                $this->response(
                    false,
                    'Username is required',
                    null,
                    422
                );
            }

            if ($email === '') {
                $this->response(
                    false,
                    'Email is required',
                    null,
                    422
                );
            }

            if (!filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )) {
                $this->response(
                    false,
                    'Invalid email format',
                    null,
                    422
                );
            }

            if ($password === '') {
                $this->response(
                    false,
                    'Password is required',
                    null,
                    422
                );
            }

            if (strlen($password) < 6) {
                $this->response(
                    false,
                    'Password must be at least 6 characters',
                    null,
                    422
                );
            }

            if ($roleId <= 0) {
                $this->response(
                    false,
                    'Role is required',
                    null,
                    422
                );
            }

            if (
                $this->user->usernameExists(
                    $username
                )
            ) {
                $this->response(
                    false,
                    'Username already exists',
                    null,
                    409
                );
            }

            if (
                $this->user->emailExists(
                    $email
                )
            ) {
                $this->response(
                    false,
                    'Email already exists',
                    null,
                    409
                );
            }

            if (
                !$this->user->roleExists(
                    $roleId
                )
            ) {
                $this->response(
                    false,
                    'Role not found or inactive',
                    null,
                    400
                );
            }

            $id = $this->user->create(
                $username,
                $email,
                $password,
                $roleId,
                $status
            );

            $user = $this->user->findById($id);

            $this->response(
                true,
                'User created successfully',
                $user,
                201
            );

        } catch (Throwable $e) {

            $this->response(
                false,
                $e->getMessage(),
                null,
                500
            );
        }
    }

    public function update(int $id): void
    {
        try {

            $existing = $this->user->findById($id);

            if (!$existing) {
                $this->response(
                    false,
                    'User not found',
                    null,
                    404
                );
            }

            $data = json_decode(
                file_get_contents('php://input'),
                true
            );

            if (!is_array($data)) {
                $this->response(
                    false,
                    'Invalid JSON',
                    null,
                    400
                );
            }

            $username = trim(
                $data['username'] ?? ''
            );

            $email = trim(
                $data['email'] ?? ''
            );

            $roleId = (int) (
                $data['role_id'] ?? 0
            );

            $status = (bool) (
                $data['status'] ?? true
            );

            if ($username === '') {
                $this->response(
                    false,
                    'Username is required',
                    null,
                    422
                );
            }

            if (
                !filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL
                )
            ) {
                $this->response(
                    false,
                    'Invalid email format',
                    null,
                    422
                );
            }

            if ($roleId <= 0) {
                $this->response(
                    false,
                    'Role is required',
                    null,
                    422
                );
            }

            if (
                $this->user->usernameExists(
                    $username,
                    $id
                )
            ) {
                $this->response(
                    false,
                    'Username already exists',
                    null,
                    409
                );
            }

            if (
                $this->user->emailExists(
                    $email,
                    $id
                )
            ) {
                $this->response(
                    false,
                    'Email already exists',
                    null,
                    409
                );
            }

            if (
                !$this->user->roleExists(
                    $roleId
                )
            ) {
                $this->response(
                    false,
                    'Role not found or inactive',
                    null,
                    400
                );
            }

            $this->user->update(
                $id,
                $username,
                $email,
                $roleId,
                $status
            );

            $user = $this->user->findById($id);

            $this->response(
                true,
                'User updated successfully',
                $user
            );

        } catch (Throwable $e) {

            $this->response(
                false,
                $e->getMessage(),
                null,
                500
            );
        }
    }

    public function destroy(int $id): void
    {
        try {

            $existing = $this->user->findById($id);

            if (!$existing) {
                $this->response(
                    false,
                    'User not found',
                    null,
                    404
                );
            }

            $this->user->delete($id);

            $this->response(
                true,
                'User deleted successfully'
            );

        } catch (Throwable $e) {

            $this->response(
                false,
                $e->getMessage(),
                null,
                500
            );
        }
    }
}