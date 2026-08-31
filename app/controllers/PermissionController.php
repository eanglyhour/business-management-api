<?php

require_once __DIR__ . '/../models/Permission.php';

class PermissionController
{
    private Permission $permission;

    public function __construct(mysqli $db)
    {
        $this->permission = new Permission($db);
    }

    public function index(): void
    {
        $search = trim($_GET['search'] ?? '');

        $page = isset($_GET['page'])
            ? (int) $_GET['page']
            : 1;

        $limit = isset($_GET['limit'])
            ? (int) $_GET['limit']
            : 10;

        $sortBy = $_GET['sortBy'] ?? 'id';
        $sortOrder = $_GET['sortOrder'] ?? 'DESC';

        $result = $this->permission->findAll(
            $search,
            $page,
            $limit,
            $sortBy,
            $sortOrder
        );

        $this->json([
            'success' => true,
            'data' => $result['data'],
            'pagination' => $result['pagination']
        ]);
    }

    public function show(int $id): void
    {
        $permission = $this->permission->findById($id);

        if (!$permission) {
            $this->json([
                'success' => false,
                'message' => 'Permission not found'
            ], 404);

            return;
        }

        $this->json([
            'success' => true,
            'data' => $permission
        ]);
    }

    public function store(): void
    {
        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        if (!is_array($data)) {
            $data = [];
        }

        $name = trim($data['name'] ?? '');
        $description = $data['description'] ?? null;
        $status = (bool) ($data['status'] ?? true);

        if ($name === '') {
            $this->json([
                'success' => false,
                'message' => 'Name is required'
            ], 422);

            return;
        }

        if ($this->permission->existsByName($name)) {
            $this->json([
                'success' => false,
                'message' => 'Permission name already exists'
            ], 409);

            return;
        }

        $id = $this->permission->create(
            $name,
            $description,
            $status
        );

        $this->json([
            'success' => true,
            'message' => 'Permission created successfully',
            'data' => $this->permission->findById($id)
        ], 201);
    }

    public function update(int $id): void
    {
        $existing = $this->permission->findById($id);

        if (!$existing) {
            $this->json([
                'success' => false,
                'message' => 'Permission not found'
            ], 404);

            return;
        }

        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        if (!is_array($data)) {
            $data = [];
        }

        $name = trim($data['name'] ?? '');
        $description = $data['description'] ?? null;
        $status = (bool) ($data['status'] ?? true);

        if ($name === '') {
            $this->json([
                'success' => false,
                'message' => 'Name is required'
            ], 422);

            return;
        }

        if (
            $this->permission->existsByName(
                $name,
                $id
            )
        ) {
            $this->json([
                'success' => false,
                'message' => 'Permission name already exists'
            ], 409);

            return;
        }

        $updated = $this->permission->update(
            $id,
            $name,
            $description,
            $status
        );

        if (!$updated) {
            $this->json([
                'success' => false,
                'message' => 'Failed to update permission'
            ], 500);

            return;
        }

        $this->json([
            'success' => true,
            'message' => 'Permission updated successfully',
            'data' => $this->permission->findById($id)
        ]);
    }

    public function destroy(int $id): void
    {
        if (!$this->permission->delete($id)) {
            $this->json([
                'success' => false,
                'message' => 'Permission not found'
            ], 404);

            return;
        }

        $this->json([
            'success' => true,
            'message' => 'Permission deleted successfully'
        ]);
    }

    private function json(
        array $data,
        int $status = 200
    ): void {
        http_response_code($status);

        header('Content-Type: application/json');

        echo json_encode(
            $data,
            JSON_UNESCAPED_UNICODE
        );
    }
}