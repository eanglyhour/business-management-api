<?php

class Import
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        $sql = "
            SELECT
                i.id,
                i.import_code,
                i.supply_location_id,
                s.name AS supply_location_name,
                i.total_amount,
                i.status,
                i.import_date,
                i.description,
                i.created_at,
                i.updated_at
            FROM imports i
            INNER JOIN supply_locations s
                ON s.id = i.supply_location_id
            ORDER BY i.id DESC
        ";

        $result = $this->db->query($sql);

        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        return $data;
    }


    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                i.id,
                i.import_code,
                i.supply_location_id,
                s.name AS supply_location_name,
                i.total_amount,
                i.status,
                i.import_date,
                i.description,
                i.created_at,
                i.updated_at
            FROM imports i
            INNER JOIN supply_locations s
                ON s.id = i.supply_location_id
            WHERE i.id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $result = $stmt->get_result();

        return $result->fetch_assoc() ?: null;
    }

    public function create(
        string $importCode,
        int $supplyLocationId,
        float $totalAmount,
        ?string $description
    ): int {

        $stmt = $this->db->prepare("
            INSERT INTO imports (
                import_code,
                supply_location_id,
                total_amount,
                status,
                description
            )
            VALUES (?, ?, ?, 'pending', ?)
        ");

        $stmt->bind_param(
            "sids",
            $importCode,
            $supplyLocationId,
            $totalAmount,
            $description
        );

        $stmt->execute();

        return $this->db->insert_id;
    }

    public function update(
        int $id,
        int $supplyLocationId,
        ?string $description
    ): bool {

        $stmt = $this->db->prepare("
            UPDATE imports
            SET
                supply_location_id = ?,
                description = ?
            WHERE id = ?
              AND status = 'pending'
        ");

        $stmt->bind_param(
            "isi",
            $supplyLocationId,
            $description,
            $id
        );

        return $stmt->execute();
    }

    public function updateTotal(
        int $id,
        float $totalAmount
    ): bool {

        $stmt = $this->db->prepare("
            UPDATE imports
            SET total_amount = ?
            WHERE id = ?
              AND status = 'pending'
        ");

        $stmt->bind_param(
            "di",
            $totalAmount,
            $id
        );

        return $stmt->execute();
    }

    public function updateStatus(
        int $id,
        string $status
    ): bool {

        $stmt = $this->db->prepare("
            UPDATE imports
            SET status = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "si",
            $status,
            $id
        );

        return $stmt->execute();
    }

    public function getPaidAmount(int $importId): float
{
    $stmt = $this->db->prepare("
        SELECT
            COALESCE(SUM(paid_amount), 0) AS paid_amount
        FROM import_payments
        WHERE import_id = ?
    ");

    $stmt->bind_param("i", $importId);

    $stmt->execute();

    $result = $stmt->get_result();

    $row = $result->fetch_assoc();

    return (float) $row['paid_amount'];
}

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM imports
            WHERE id = ?
              AND status = 'pending'
        ");

        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }
}