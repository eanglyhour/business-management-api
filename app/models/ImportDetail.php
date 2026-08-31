<?php

class ImportDetail
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function getByImportId(
        int $importId
    ): array {

        $stmt = $this->db->prepare("
            SELECT
                d.id,
                d.import_id,
                d.product_id,
                p.name AS product_name,
                d.quantity,
                d.const_price,
                d.sell_price,
                d.subtotal,
                d.created_at
            FROM import_details d
            INNER JOIN products p
                ON p.id = d.product_id
            WHERE d.import_id = ?
            ORDER BY d.id ASC
        ");

        $stmt->bind_param("i", $importId);

        $stmt->execute();

        $result = $stmt->get_result();

        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        return $data;
    }

    public function findById(
        int $importId,
        int $id
    ): ?array {

        $stmt = $this->db->prepare("
            SELECT
                d.id,
                d.import_id,
                d.product_id,
                p.name AS product_name,
                d.quantity,
                d.const_price,
                d.sell_price,
                d.subtotal,
                d.created_at
            FROM import_details d
            INNER JOIN products p
                ON p.id = d.product_id
            WHERE d.id = ?
              AND d.import_id = ?
            LIMIT 1
        ");

        $stmt->bind_param(
            "ii",
            $id,
            $importId
        );

        $stmt->execute();

        $result = $stmt->get_result();

        return $result->fetch_assoc() ?: null;
    }

    public function create(
        int $importId,
        int $productId,
        int $quantity,
        float $constPrice,
        float $sellPrice
    ): int {

        $stmt = $this->db->prepare("
            INSERT INTO import_details (
                import_id,
                product_id,
                quantity,
                const_price,
                sell_price
            )
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "iiidd",
            $importId,
            $productId,
            $quantity,
            $constPrice,
            $sellPrice
        );

        $stmt->execute();

        return $this->db->insert_id;
    }

    public function deleteByImportId(
        int $importId
    ): bool {

        $stmt = $this->db->prepare("
            DELETE FROM import_details
            WHERE import_id = ?
        ");

        $stmt->bind_param("i", $importId);

        return $stmt->execute();
    }

    public function delete(
        int $importId,
        int $id
    ): bool {

        $stmt = $this->db->prepare("
            DELETE FROM import_details
            WHERE id = ?
              AND import_id = ?
        ");

        $stmt->bind_param(
            "ii",
            $id,
            $importId
        );

        return $stmt->execute();
    }
}