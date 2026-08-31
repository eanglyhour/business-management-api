<?php

class User
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function findAll(): array
    {
        $sql = "
        SELECT
            u.id,
            u.username,
            u.email,
            u.role_id,
            r.name AS role_name,

            GROUP_CONCAT(
                DISTINCT p.name
                ORDER BY p.name
                SEPARATOR ','
            ) AS permissions,

            u.status,
            u.created_at,
            u.updated_at

        FROM users u

        INNER JOIN roles r
            ON r.id = u.role_id

        LEFT JOIN role_permissions rp
            ON rp.role_id = r.id

        LEFT JOIN permissions p
            ON p.id = rp.permission_id
            AND p.status = 1

        GROUP BY
            u.id,
            u.username,
            u.email,
            u.role_id,
            r.name,
            u.status,
            u.created_at,
            u.updated_at

        ORDER BY u.id DESC
    ";

        $result = $this->db->query($sql);

        $users = [];

        while ($row = $result->fetch_assoc()) {

            $row['id'] = (int) $row['id'];
            $row['role_id'] = (int) $row['role_id'];
            $row['status'] = (bool) $row['status'];

            $row['permissions'] = $row['permissions']
                ? explode(',', $row['permissions'])
                : [];

            $users[] = $row;
        }

        return $users;
    }

    public function findById(int $id): ?array
    {
        $sql = "
        SELECT
            u.id,
            u.username,
            u.email,
            u.role_id,
            r.name AS role_name,

            GROUP_CONCAT(
                DISTINCT p.name
                ORDER BY p.name
                SEPARATOR ','
            ) AS permissions,

            u.status,
            u.created_at,
            u.updated_at

        FROM users u

        INNER JOIN roles r
            ON r.id = u.role_id

        LEFT JOIN role_permissions rp
            ON rp.role_id = r.id

        LEFT JOIN permissions p
            ON p.id = rp.permission_id
            AND p.status = 1

        WHERE u.id = ?

        GROUP BY
            u.id,
            u.username,
            u.email,
            u.role_id,
            r.name,
            u.status,
            u.created_at,
            u.updated_at
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param("i", $id);

        $stmt->execute();

        $result = $stmt->get_result();

        $user = $result->fetch_assoc();

        if (!$user) {
            return null;
        }

        $user['id'] = (int) $user['id'];
        $user['role_id'] = (int) $user['role_id'];
        $user['status'] = (bool) $user['status'];

        $user['permissions'] = $user['permissions']
            ? explode(',', $user['permissions'])
            : [];

        return $user;
    }

    public function usernameExists(
        string $username,
        ?int $excludeId = null
    ): bool {

        if ($excludeId !== null) {

            $sql = "
                SELECT id
                FROM users
                WHERE username = ?
                AND id != ?
                LIMIT 1
            ";

            $stmt = $this->db->prepare($sql);

            $stmt->bind_param(
                "si",
                $username,
                $excludeId
            );
        } else {

            $sql = "
                SELECT id
                FROM users
                WHERE username = ?
                LIMIT 1
            ";

            $stmt = $this->db->prepare($sql);

            $stmt->bind_param(
                "s",
                $username
            );
        }

        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }

    public function emailExists(
        string $email,
        ?int $excludeId = null
    ): bool {

        if ($excludeId !== null) {

            $sql = "
                SELECT id
                FROM users
                WHERE email = ?
                AND id != ?
                LIMIT 1
            ";

            $stmt = $this->db->prepare($sql);

            $stmt->bind_param(
                "si",
                $email,
                $excludeId
            );
        } else {

            $sql = "
                SELECT id
                FROM users
                WHERE email = ?
                LIMIT 1
            ";

            $stmt = $this->db->prepare($sql);

            $stmt->bind_param(
                "s",
                $email
            );
        }

        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }

    public function roleExists(int $roleId): bool
    {
        $sql = "
            SELECT id
            FROM roles
            WHERE id = ?
            AND status = 1
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param("i", $roleId);

        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }

    public function create(
        string $username,
        string $email,
        string $password,
        int $roleId,
        bool $status
    ): int {

        $hashedPassword = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $statusValue = $status ? 1 : 0;

        $sql = "
            INSERT INTO users
            (
                username,
                email,
                password,
                role_id,
                status
            )
            VALUES (?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param(
            "sssii",
            $username,
            $email,
            $hashedPassword,
            $roleId,
            $statusValue
        );

        $stmt->execute();

        return $this->db->insert_id;
    }

    public function update(
        int $id,
        string $username,
        string $email,
        int $roleId,
        bool $status
    ): bool {

        $statusValue = $status ? 1 : 0;

        $sql = "
            UPDATE users
            SET
                username = ?,
                email = ?,
                role_id = ?,
                status = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param(
            "ssiii",
            $username,
            $email,
            $roleId,
            $statusValue,
            $id
        );

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $sql = "
            DELETE FROM users
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }

    public function findByUsername(string $username): ?array
    {
        $sql = "
        SELECT
            u.id,
            u.username,
            u.email,
            u.password,
            u.role_id,
            r.name AS role_name,

            u.status,
            u.created_at,
            u.updated_at

        FROM users u

        INNER JOIN roles r
            ON r.id = u.role_id

        WHERE u.username = ?

        LIMIT 1
    ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "Prepare failed: " . $this->db->error
            );
        }

        $stmt->bind_param("s", $username);

        $stmt->execute();

        $result = $stmt->get_result();

        $user = $result->fetch_assoc();

        if (!$user) {
            return null;
        }

        $user['id'] = (int) $user['id'];
        $user['role_id'] = (int) $user['role_id'];
        $user['status'] = (bool) $user['status'];

        return $user;
    }

    public function getPermissions(int $userId): array
    {
        $sql = "
        SELECT DISTINCT
            p.name
        FROM users u

        INNER JOIN roles r
            ON r.id = u.role_id

        INNER JOIN role_permissions rp
            ON rp.role_id = r.id

        INNER JOIN permissions p
            ON p.id = rp.permission_id

        WHERE u.id = ?
        AND p.status = 1

        ORDER BY p.name
    ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "Prepare failed: " . $this->db->error
            );
        }

        $stmt->bind_param("i", $userId);

        $stmt->execute();

        $result = $stmt->get_result();

        $permissions = [];

        while ($row = $result->fetch_assoc()) {
            $permissions[] = $row['name'];
        }

        return $permissions;
    }
}
