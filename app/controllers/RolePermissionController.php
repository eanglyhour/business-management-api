<?php

require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../models/Permission.php';
require_once __DIR__ . '/../models/RolePermission.php';

class RolePermissionController
{
    private mysqli $db;

    private Role $role;
    private Permission $permission;
    private RolePermission $rolePermission;

    public function __construct(mysqli $db)
    {
        $this->db = $db;

        $this->role = new Role($db);
        $this->permission = new Permission($db);
        $this->rolePermission = new RolePermission($db);
    }

    public function index(int $roleId): void
    {
        $role = $this->role->findById($roleId);

        if (!$role) {
            $this->json([
                'success' => false,
                'message' => 'Role not found'
            ], 404);

            return;
        }

        $permissions = $this->rolePermission
            ->getByRoleId($roleId);

        $this->json([
            'success' => true,
            'message' => 'Permissions retrieved successfully',
            'data' => [
                'role' => $role,
                'permissions' => $permissions
            ]
        ]);
    }

    public function assignMultiple(int $roleId): void
    {
        $role = $this->role->findById($roleId);

        if (!$role) {
            $this->json([
                'success' => false,
                'message' => 'Role not found'
            ], 404);

            return;
        }

        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        if (!is_array($data)) {
            $this->json([
                'success' => false,
                'message' => 'Invalid JSON'
            ], 400);

            return;
        }

        $permissionIds = $data['permission_ids'] ?? null;

        if (!is_array($permissionIds)) {
            $this->json([
                'success' => false,
                'message' => 'permission_ids must be an array'
            ], 422);

            return;
        }

        $permissionIds = array_map(
            'intval',
            $permissionIds
        );

        $permissionIds = array_values(
            array_unique(
                array_filter(
                    $permissionIds,
                    fn ($id) => $id > 0
                )
            )
        );

        if (empty($permissionIds)) {
            $this->json([
                'success' => false,
                'message' => 'permission_ids cannot be empty'
            ], 422);

            return;
        }

        try {
            $this->db->begin_transaction();

            foreach ($permissionIds as $permissionId) {
                $permission = $this->permission
                    ->findById($permissionId);

                if (!$permission) {
                    $this->db->rollback();

                    $this->json([
                        'success' => false,
                        'message' => "Permission {$permissionId} not found"
                    ], 404);

                    return;
                }
            }

            $assigned = $this->rolePermission
                ->assignMultiple(
                    $roleId,
                    $permissionIds
                );

            $this->db->commit();

            $permissions = $this->rolePermission
                ->getByRoleId($roleId);

            $this->json([
                'success' => true,
                'message' => 'Permissions assigned successfully',
                'data' => [
                    'role_id' => $roleId,
                    'assigned_count' => $assigned,
                    'permissions' => $permissions
                ]
            ], 201);

        } catch (Throwable $e) {
            $this->db->rollback();

            $this->json([
                'success' => false,
                'message' => 'Failed to assign permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function remove(
        int $roleId,
        int $permissionId
    ): void {
        $role = $this->role->findById($roleId);

        if (!$role) {
            $this->json([
                'success' => false,
                'message' => 'Role not found'
            ], 404);

            return;
        }

        $permission = $this->permission
            ->findById($permissionId);

        if (!$permission) {
            $this->json([
                'success' => false,
                'message' => 'Permission not found'
            ], 404);

            return;
        }

        if (!$this->rolePermission->exists(
            $roleId,
            $permissionId
        )) {
            $this->json([
                'success' => false,
                'message' => 'Permission is not assigned to this role'
            ], 404);

            return;
        }

        $deleted = $this->rolePermission
            ->remove(
                $roleId,
                $permissionId
            );

        if (!$deleted) {
            $this->json([
                'success' => false,
                'message' => 'Failed to remove permission'
            ], 500);

            return;
        }

        $this->json([
            'success' => true,
            'message' => 'Permission removed successfully'
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

        exit;
    }
}