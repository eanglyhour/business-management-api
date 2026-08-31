<?php

class Role
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function findAll(
        string $search = '',
        int $page = 1,
        int $limit = 10,
        string $sortBy = 'id',
        string $sortOrder = 'DESC'
    ): array {
        $page = max(1, $page);
        $limit = max(1, min($limit, 100));

        $offset = ($page - 1) * $limit;

        $allowedSortBy = [
            'id',
            'name',
            'description',
            'status',
            'created_at',
            'updated_at'
        ];

        if (!in_array($sortBy, $allowedSortBy, true)) {
            $sortBy = 'id';
        }

        $sortOrder = strtoupper($sortOrder);

        if (!in_array($sortOrder, ['ASC', 'DESC'], true)) {
            $sortOrder = 'DESC';
        }

        $where = '';
        $searchValue = '';

        if ($search !== '') {
            $where = "
                WHERE
                    name LIKE ?
                    OR description LIKE ?
            ";

            $searchValue = "%{$search}%";
        }

        $countSql = "
            SELECT COUNT(*) AS total
            FROM roles
            {$where}
        ";

        $countStmt = $this->db->prepare($countSql);

        if ($search !== '') {
            $countStmt->bind_param(
                'ss',
                $searchValue,
                $searchValue
            );
        }

        $countStmt->execute();

        $countResult = $countStmt->get_result();
        $countRow = $countResult->fetch_assoc();

        $total = (int) $countRow['total'];

        $sql = "
            SELECT
                id,
                name,
                description,
                status,
                created_at,
                updated_at
            FROM roles
            {$where}
            ORDER BY {$sortBy} {$sortOrder}
            LIMIT ? OFFSET ?
        ";

        $stmt = $this->db->prepare($sql);

        if ($search !== '') {
            $stmt->bind_param(
                'ssii',
                $searchValue,
                $searchValue,
                $limit,
                $offset
            );
        } else {
            $stmt->bind_param(
                'ii',
                $limit,
                $offset
            );
        }

        $stmt->execute();

        $result = $stmt->get_result();

        $data = $result->fetch_all(MYSQLI_ASSOC);

        $totalPages = $limit > 0
            ? (int) ceil($total / $limit)
            : 0;

        return [
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => $totalPages
            ]
        ];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                name,
                description,
                status,
                created_at,
                updated_at
            FROM roles
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->bind_param('i', $id);

        $stmt->execute();

        $result = $stmt->get_result();

        $role = $result->fetch_assoc();

        return $role ?: null;
    }

    public function existsByName(
        string $name,
        ?int $excludeId = null
    ): bool {
        if ($excludeId !== null) {
            $stmt = $this->db->prepare("
                SELECT id
                FROM roles
                WHERE name = ?
                AND id != ?
                LIMIT 1
            ");

            $stmt->bind_param(
                'si',
                $name,
                $excludeId
            );
        } else {
            $stmt = $this->db->prepare("
                SELECT id
                FROM roles
                WHERE name = ?
                LIMIT 1
            ");

            $stmt->bind_param(
                's',
                $name
            );
        }

        $stmt->execute();

        $result = $stmt->get_result();

        return $result->num_rows > 0;
    }

    public function create(
        string $name,
        ?string $description,
        bool $status
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO roles
            (
                name,
                description,
                status
            )
            VALUES
            (
                ?,
                ?,
                ?
            )
        ");

        $statusValue = $status ? 1 : 0;

        $stmt->bind_param(
            'ssi',
            $name,
            $description,
            $statusValue
        );

        $stmt->execute();

        return $this->db->insert_id;
    }

    public function update(
        int $id,
        string $name,
        ?string $description,
        bool $status
    ): bool {
        $stmt = $this->db->prepare("
            UPDATE roles
            SET
                name = ?,
                description = ?,
                status = ?
            WHERE id = ?
        ");

        $statusValue = $status ? 1 : 0;

        $stmt->bind_param(
            'ssii',
            $name,
            $description,
            $statusValue,
            $id
        );

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM roles
            WHERE id = ?
        ");

        $stmt->bind_param('i', $id);

        $stmt->execute();

        return $stmt->affected_rows > 0;
    }
}