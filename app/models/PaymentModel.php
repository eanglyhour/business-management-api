<?php

class PaymentModel
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    /**
     * Create pending payment
     */
    public function create(
        int $orderId,
        float $amount,
        string $currency,
        string $md5
    ): int {

        $sql = "
            INSERT INTO payments
            (
                order_id,
                amount,
                currency,
                md5,
                payment_method,
                status
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                'bakong',
                'pending'
            )
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                'Prepare failed: ' . $this->db->error
            );
        }

        $stmt->bind_param(
            'idss',
            $orderId,
            $amount,
            $currency,
            $md5
        );

        if (!$stmt->execute()) {
            throw new Exception(
                'Execute failed: ' . $stmt->error
            );
        }

        return $stmt->insert_id;
    }


    /**
     * Find payment by MD5
     */
    public function findByMd5(
        string $md5
    ): ?array {

        $sql = "
            SELECT
                id,
                order_id,
                amount,
                currency,
                md5,
                transaction_hash,
                from_account_id,
                to_account_id,
                payment_method,
                status,
                payment_date,
                created_at
            FROM payments
            WHERE md5 = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                'Prepare failed: ' . $this->db->error
            );
        }

        $stmt->bind_param(
            's',
            $md5
        );

        if (!$stmt->execute()) {
            throw new Exception(
                'Execute failed: ' . $stmt->error
            );
        }

        $result = $stmt->get_result();

        $payment = $result->fetch_assoc();

        return $payment ?: null;
    }


    /**
     * Mark payment as success
     */
    public function markPaid(
        int $paymentId,
        string $transactionHash,
        string $fromAccount,
        string $toAccount,
        string $paymentDate
    ): bool {

        $sql = "
            UPDATE payments
            SET
                status = 'success',
                transaction_hash = ?,
                from_account_id = ?,
                to_account_id = ?,
                payment_date = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                'Prepare failed: ' . $this->db->error
            );
        }

        $stmt->bind_param(
            'ssssi',
            $transactionHash,
            $fromAccount,
            $toAccount,
            $paymentDate,
            $paymentId
        );

        if (!$stmt->execute()) {
            throw new Exception(
                'Execute failed: ' . $stmt->error
            );
        }

        return true;
    }


    /**
     * Mark payment as failed
     */
    public function markFailed(
        int $paymentId
    ): bool {

        $sql = "
            UPDATE payments
            SET status = 'failed'
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                'Prepare failed: ' . $this->db->error
            );
        }

        $stmt->bind_param(
            'i',
            $paymentId
        );

        return $stmt->execute();
    }
}