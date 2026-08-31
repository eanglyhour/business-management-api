<?php

class ImportPayment
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function create(
        int $importId,
        string $paymentMethod,
        float $paidAmount,
        ?float $cashReceived,
        float $changeAmount,
        ?string $note
    ): int {

        $stmt = $this->db->prepare("
            INSERT INTO import_payments (
                import_id,
                payment_method,
                paid_amount,
                cash_received,
                change_amount,
                note
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "isddds",
            $importId,
            $paymentMethod,
            $paidAmount,
            $cashReceived,
            $changeAmount,
            $note
        );

        $stmt->execute();

        return $this->db->insert_id;
    }

    public function getAll(): array
    {
        $sql = "
            SELECT
                p.id,
                p.import_id,
                i.import_code,
                i.total_amount,

                p.payment_method,
                p.paid_amount,
                p.cash_received,
                p.change_amount,

                p.payment_date,
                p.note,
                p.created_at

            FROM import_payments p

            INNER JOIN imports i
                ON i.id = p.import_id

            ORDER BY p.id DESC
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
                p.id,
                p.import_id,
                i.import_code,
                i.total_amount,

                p.payment_method,
                p.paid_amount,
                p.cash_received,
                p.change_amount,

                p.payment_date,
                p.note,
                p.created_at

            FROM import_payments p

            INNER JOIN imports i
                ON i.id = p.import_id

            WHERE p.id = ?

            LIMIT 1
        ");

        $stmt->bind_param("i", $id);

        $stmt->execute();

        $result = $stmt->get_result();

        return $result->fetch_assoc() ?: null;
    }

    public function getByImportId(int $importId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                import_id,
                payment_method,
                paid_amount,
                cash_received,
                change_amount,
                payment_date,
                note,
                created_at

            FROM import_payments

            WHERE import_id = ?

            ORDER BY id DESC
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

    public function getTotalPaid(int $importId): float
    {
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(paid_amount), 0) AS total_paid

            FROM import_payments

            WHERE import_id = ?
        ");

        $stmt->bind_param("i", $importId);

        $stmt->execute();

        $result = $stmt->get_result();

        $row = $result->fetch_assoc();

        return (float) $row['total_paid'];
    }

    public function getRemainingAmount(
        int $importId,
        float $totalAmount
    ): float {

        $totalPaid = $this->getTotalPaid($importId);

        return max(0, $totalAmount - $totalPaid);
    }
}