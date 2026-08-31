<?php

class AttendanceCheckout
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
                ac.id,

                ac.check_in_id,
                ac.user_id,

                ep.name AS user_name,
                u.username,
                u.email,

                a.work_date,
                a.check_in_at,

                ac.check_out_at,

                s.id AS shift_id,
                s.name AS shift_name,
                s.start_time,
                s.end_time,
                s.type AS shift_type,

                c.id AS company_id,
                c.name AS company_name,

                ac.created_at,
                ac.updated_at

            FROM attendance_checkouts ac

            INNER JOIN attendance a
                ON a.id = ac.check_in_id

            INNER JOIN users u
                ON u.id = ac.user_id

            INNER JOIN employee_profiles ep
                ON ep.user_id = ac.user_id

            INNER JOIN shifts s
                ON s.id = a.shift_id

            INNER JOIN companies c
                ON c.id = s.company_id

            ORDER BY ac.id DESC
        ";

        $result = $this->db->query($sql);

        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $this->format($row);
        }

        return $data;
    }

    public function findById(int $id): ?array
    {
        $sql = "
            SELECT
                ac.id,

                ac.check_in_id,
                ac.user_id,

                ep.name AS user_name,
                u.username,
                u.email,

                a.work_date,
                a.check_in_at,

                ac.check_out_at,

                s.id AS shift_id,
                s.name AS shift_name,
                s.start_time,
                s.end_time,
                s.type AS shift_type,

                c.id AS company_id,
                c.name AS company_name,

                ac.created_at,
                ac.updated_at

            FROM attendance_checkouts ac

            INNER JOIN attendance a
                ON a.id = ac.check_in_id

            INNER JOIN users u
                ON u.id = ac.user_id

            INNER JOIN employee_profiles ep
                ON ep.user_id = ac.user_id

            INNER JOIN shifts s
                ON s.id = a.shift_id

            INNER JOIN companies c
                ON c.id = s.company_id

            WHERE ac.id = ?

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("i", $id);

        $stmt->execute();

        $result = $stmt->get_result();

        $row = $result->fetch_assoc();

        if (!$row) {
            return null;
        }

        return $this->format($row);
    }

    public function getByUser(int $userId): array
    {
        $sql = "
            SELECT
                ac.id,

                ac.check_in_id,
                ac.user_id,

                ep.name AS user_name,
                u.username,
                u.email,

                a.work_date,
                a.check_in_at,

                ac.check_out_at,

                s.id AS shift_id,
                s.name AS shift_name,
                s.start_time,
                s.end_time,
                s.type AS shift_type,

                c.id AS company_id,
                c.name AS company_name,

                ac.created_at,
                ac.updated_at

            FROM attendance_checkouts ac

            INNER JOIN attendance a
                ON a.id = ac.check_in_id

            INNER JOIN users u
                ON u.id = ac.user_id

            INNER JOIN employee_profiles ep
                ON ep.user_id = ac.user_id

            INNER JOIN shifts s
                ON s.id = a.shift_id

            INNER JOIN companies c
                ON c.id = s.company_id

            WHERE ac.user_id = ?

            ORDER BY ac.check_out_at DESC
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("i", $userId);

        $stmt->execute();

        $result = $stmt->get_result();

        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $this->format($row);
        }

        return $data;
    }

    public function findCheckIn(int $checkInId): ?array
    {
        $sql = "
            SELECT
                a.id,
                a.user_id,
                a.shift_id,
                a.work_date,
                a.check_in_at
            FROM attendance a
            WHERE a.id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("i", $checkInId);

        $stmt->execute();

        $result = $stmt->get_result();

        $row = $result->fetch_assoc();

        return $row ?: null;
    }

    public function checkoutExists(
        int $checkInId
    ): bool {

        $sql = "
            SELECT id
            FROM attendance_checkouts
            WHERE check_in_id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $checkInId);

        $stmt->execute();

        $result = $stmt->get_result();

        return $result->num_rows > 0;
    }

    public function create(array $data): int
    {
        $sql = "
            INSERT INTO attendance_checkouts
            (
                check_in_id,
                user_id,
                check_out_at
            )
            VALUES (?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "Failed to prepare checkout"
            );
        }

        $checkInId = (int) $data["check_in_id"];
        $userId = (int) $data["user_id"];

        $checkOutAt = $data["check_out_at"];

        $stmt->bind_param(
            "iis",
            $checkInId,
            $userId,
            $checkOutAt
        );

        $stmt->execute();

        return $this->db->insert_id;
    }

    public function delete(int $id): bool
    {
        $sql = "
            DELETE FROM attendance_checkouts
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }

    private function format(array $row): array
    {
        return [
            "id" => (int) $row["id"],

            "check_in_id" =>
                (int) $row["check_in_id"],

            "user" => [
                "id" => (int) $row["user_id"],
                "name" => $row["user_name"],
                "username" => $row["username"],
                "email" => $row["email"]
            ],

            "attendance" => [
                "work_date" => $row["work_date"],
                "check_in_at" => $row["check_in_at"],
                "check_out_at" => $row["check_out_at"]
            ],

            "shift" => [
                "id" => (int) $row["shift_id"],
                "name" => $row["shift_name"],
                "start_time" => $row["start_time"],
                "end_time" => $row["end_time"],
                "type" => $row["shift_type"]
            ],

            "company" => [
                "id" => (int) $row["company_id"],
                "name" => $row["company_name"]
            ],

            "created_at" => $row["created_at"],
            "updated_at" => $row["updated_at"]
        ];
    }
}