<?php

class RolePermission
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    /**
     * Get permissions by role
     */
    public function getByRoleId(
        int $roleId
    ): array {

        $sql = "
            SELECT
                p.id,
                p.name,
                p.description,
                p.status
            FROM role_permissions rp
            INNER JOIN permissions p
                ON p.id = rp.permission_id
            WHERE rp.role_id = ?
            ORDER BY p.id ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param(
            "i",
            $roleId
        );

        $stmt->execute();

        $result = $stmt->get_result();

        return $result->fetch_all(
            MYSQLI_ASSOC
        );
    }

    /**
     * Check relation exists
     */
    public function exists(
        int $roleId,
        int $permissionId
    ): bool {

        $sql = "
            SELECT COUNT(*) AS total
            FROM role_permissions
            WHERE role_id = ?
            AND permission_id = ?
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param(
            "ii",
            $roleId,
            $permissionId
        );

        $stmt->execute();

        $result = $stmt->get_result();

        $row = $result->fetch_assoc();

        return (int) $row['total'] > 0;
    }

    /**
     * Assign multiple permissions
     */
    public function assignMultiple(
        int $roleId,
        array $permissionIds
    ): int {

        $sql = "
            INSERT INTO role_permissions
                (role_id, permission_id)
            VALUES
                (?, ?)
        ";

        $stmt = $this->db->prepare($sql);

        $count = 0;

        foreach ($permissionIds as $permissionId) {

            if (
                $this->exists(
                    $roleId,
                    $permissionId
                )
            ) {
                continue;
            }

            $stmt->bind_param(
                "ii",
                $roleId,
                $permissionId
            );

            $stmt->execute();

            $count++;
        }

        return $count;
    }

    /**
     * Remove permission
     */
    public function remove(
        int $roleId,
        int $permissionId
    ): bool {

        $sql = "
            DELETE FROM role_permissions
            WHERE role_id = ?
            AND permission_id = ?
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param(
            "ii",
            $roleId,
            $permissionId
        );

        $stmt->execute();

        return $stmt->affected_rows > 0;
    }
}