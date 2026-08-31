<?php

class Category
{
    private mysqli $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    // GET ALL
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

        // Allowed columns for sorting
        $allowedSortColumns = [
            'id',
            'name',
            'status'
        ];

        if (!in_array($sortBy, $allowedSortColumns, true)) {
            $sortBy = 'id';
        }

        $sortOrder = strtoupper($sortOrder);

        if (!in_array($sortOrder, ['ASC', 'DESC'], true)) {
            $sortOrder = 'DESC';
        }

        // Search
        $searchValue = "%{$search}%";

        // Count total
        $countStmt = $this->db->prepare("
        SELECT COUNT(*) AS total
        FROM categories
        WHERE name LIKE ?
    ");

        $countStmt->bind_param('s', $searchValue);
        $countStmt->execute();

        $countResult = $countStmt->get_result();
        $total = (int) $countResult->fetch_assoc()['total'];

        // Get data
        $sql = "
        SELECT id, name, des, status
        FROM categories
        WHERE name LIKE ?
        ORDER BY $sortBy $sortOrder
        LIMIT ? OFFSET ?
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param(
            'sii',
            $searchValue,
            $limit,
            $offset
        );

        $stmt->execute();

        $result = $stmt->get_result();

        $categories = [];

        while ($row = $result->fetch_assoc()) {
            $row['status'] = (bool) $row['status'];

            $categories[] = $row;
        }

        return [
            'data' => $categories,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => (int) ceil($total / $limit)
            ]
        ];
    }

    // GET BY ID
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, name, des, status
            FROM categories
            WHERE id = ?
        ");

        $stmt->bind_param('i', $id);
        $stmt->execute();

        $result = $stmt->get_result();
        $category = $result->fetch_assoc();

        if (!$category) {
            return null;
        }

        $category['status'] = (bool) $category['status'];

        return $category;
    }

    // CREATE
    public function create(
        string $name,
        ?string $des,
        bool $status
    ): int {

        $stmt = $this->db->prepare("
            INSERT INTO categories (name, des, status)
            VALUES (?, ?, ?)
        ");

        $statusValue = $status ? 1 : 0;

        $stmt->bind_param(
            'ssi',
            $name,
            $des,
            $statusValue
        );

        $stmt->execute();

        return $this->db->insert_id;
    }

    // UPDATE
    public function update(
        int $id,
        string $name,
        ?string $des,
        bool $status
    ): bool {

        $stmt = $this->db->prepare("
            UPDATE categories
            SET name = ?, des = ?, status = ?
            WHERE id = ?
        ");

        $statusValue = $status ? 1 : 0;

        $stmt->bind_param(
            'ssii',
            $name,
            $des,
            $statusValue,
            $id
        );

        $stmt->execute();

        return $stmt->affected_rows > 0;
    }

    // DELETE
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM categories
            WHERE id = ?
        ");

        $stmt->bind_param('i', $id);

        $stmt->execute();

        return $stmt->affected_rows > 0;
    }

    // CHECK NAME
    public function existsByName(string $name): bool
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM categories
            WHERE name = ?
            LIMIT 1
        ");

        $stmt->bind_param('s', $name);
        $stmt->execute();

        $result = $stmt->get_result();

        return $result->num_rows > 0;
    }
}
