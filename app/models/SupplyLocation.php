<?php

class SupplyLocation
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    /**
     * Get all supply locations
     */
    public function getAll(
        string $search = '',
        int $limit = 10,
        int $offset = 0
    ): array {

        $where = '';
        $params = [];
        $types = '';

        if ($search !== '') {

            $where = "
                WHERE name LIKE ?
                   OR code LIKE ?
                   OR phone_number LIKE ?
                   OR address LIKE ?
            ";

            $keyword = "%{$search}%";

            $params = [
                $keyword,
                $keyword,
                $keyword,
                $keyword
            ];

            $types = "ssss";
        }

        /*
         * Count total
         */
        $countSql = "
            SELECT COUNT(*) AS total
            FROM supply_locations
            $where
        ";

        $countStmt = $this->db->prepare($countSql);

        if (!empty($params)) {
            $countStmt->bind_param(
                $types,
                ...$params
            );
        }

        $countStmt->execute();

        $countResult = $countStmt->get_result();

        $total = (int) $countResult
            ->fetch_assoc()['total'];


        /*
         * Get data
         */
        $sql = "
            SELECT
                id,
                code,
                name,
                phone_number,
                address,
                description,
                status,
                created_at,
                updated_at
            FROM supply_locations
            $where
            ORDER BY id DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!empty($params)) {

            $types .= "ii";

            $params[] = $limit;
            $params[] = $offset;

            $stmt->bind_param(
                $types,
                ...$params
            );

        } else {

            $stmt->bind_param(
                "ii",
                $limit,
                $offset
            );
        }

        $stmt->execute();

        $result = $stmt->get_result();

        return [
            'data' => $result->fetch_all(MYSQLI_ASSOC),
            'total' => $total
        ];
    }


    /**
     * Find by ID
     */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT
                id,
                code,
                name,
                phone_number,
                address,
                description,
                status,
                created_at,
                updated_at
            FROM supply_locations
            WHERE id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param(
            "i",
            $id
        );

        $stmt->execute();

        $result = $stmt->get_result();

        $data = $result->fetch_assoc();

        return $data ?: null;
    }


    /**
     * Find by code
     */
    public function findByCode(string $code): ?array
    {
        $sql = "
            SELECT
                id,
                code,
                name,
                phone_number,
                address,
                description,
                status
            FROM supply_locations
            WHERE code = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param(
            "s",
            $code
        );

        $stmt->execute();

        $result = $stmt->get_result();

        $data = $result->fetch_assoc();

        return $data ?: null;
    }


    /**
     * Create
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO supply_locations
            (
                code,
                name,
                phone_number,
                address,
                description,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param(
            "sssssi",
            $data['code'],
            $data['name'],
            $data['phone_number'],
            $data['address'],
            $data['description'],
            $data['status']
        );

        $stmt->execute();

        return $this->db->insert_id;
    }


    /**
     * Update
     */
    public function update(
        int $id,
        array $data
    ): bool {

        $sql = "
            UPDATE supply_locations
            SET
                code = ?,
                name = ?,
                phone_number = ?,
                address = ?,
                description = ?,
                status = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param(
            "ssssssi",
            $data['code'],
            $data['name'],
            $data['phone_number'],
            $data['address'],
            $data['description'],
            $data['status'],
            $id
        );

        return $stmt->execute();
    }


    /**
     * Delete
     */
    public function delete(int $id): bool
    {
        $sql = "
            DELETE FROM supply_locations
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param(
            "i",
            $id
        );

        return $stmt->execute();
    }
}