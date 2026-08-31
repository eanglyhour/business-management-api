<?php

class Attendance
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
                a.id,

                a.user_id,
                ep.name AS user_name,
                u.username,

                a.shift_id,
                s.name AS shift_name,
                s.start_time,
                s.end_time,
                s.type AS shift_type,

                s.company_id,
                c.name AS company_name,

                a.work_date,
                a.check_in_at,
                a.check_out_at,

                a.status,
                a.note,

                a.created_at,
                a.updated_at

            FROM attendance a

            INNER JOIN users u
                ON u.id = a.user_id

            INNER JOIN employee_profiles ep
                ON ep.user_id = a.user_id

            INNER JOIN shifts s
                ON s.id = a.shift_id

            INNER JOIN companies c
                ON c.id = s.company_id

            ORDER BY a.work_date DESC, a.id DESC
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
                a.id,

                a.user_id,
                ep.name AS user_name,
                u.username,

                a.shift_id,
                s.name AS shift_name,
                s.start_time,
                s.end_time,
                s.type AS shift_type,

                s.company_id,
                c.name AS company_name,

                a.work_date,
                a.check_in_at,
                a.check_out_at,

                a.status,
                a.note,

                a.created_at,
                a.updated_at

            FROM attendance a

            INNER JOIN users u
                ON u.id = a.user_id

            INNER JOIN employee_profiles ep
                ON ep.user_id = a.user_id

            INNER JOIN shifts s
                ON s.id = a.shift_id

            INNER JOIN companies c
                ON c.id = s.company_id

            WHERE a.id = ?

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
                a.id,

                a.user_id,
                ep.name AS user_name,
                u.username,

                a.shift_id,
                s.name AS shift_name,
                s.start_time,
                s.end_time,
                s.type AS shift_type,

                s.company_id,
                c.name AS company_name,

                a.work_date,
                a.check_in_at,
                a.check_out_at,

                a.status,
                a.note,

                a.created_at,
                a.updated_at

            FROM attendance a

            INNER JOIN users u
                ON u.id = a.user_id

            INNER JOIN employee_profiles ep
                ON ep.user_id = a.user_id

            INNER JOIN shifts s
                ON s.id = a.shift_id

            INNER JOIN companies c
                ON c.id = s.company_id

            WHERE a.user_id = ?

            ORDER BY a.work_date DESC
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
    public function getByDate(string $date): array
    {
        $sql = "
            SELECT
                a.id,

                a.user_id,
                ep.name AS user_name,
                u.username,

                a.shift_id,
                s.name AS shift_name,
                s.start_time,
                s.end_time,
                s.type AS shift_type,

                s.company_id,
                c.name AS company_name,

                a.work_date,
                a.check_in_at,
                a.check_out_at,

                a.status,
                a.note,

                a.created_at,
                a.updated_at

            FROM attendance a

            INNER JOIN users u
                ON u.id = a.user_id

            INNER JOIN employee_profiles ep
                ON ep.user_id = a.user_id

            INNER JOIN shifts s
                ON s.id = a.shift_id

            INNER JOIN companies c
                ON c.id = s.company_id

            WHERE a.work_date = ?

            ORDER BY a.id ASC
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("s", $date);

        $stmt->execute();

        $result = $stmt->get_result();

        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $this->format($row);
        }

        return $data;
    }
    public function findByUserDate(
        int $userId,
        string $date
    ): ?array {

        $sql = "
            SELECT
                a.id,

                a.user_id,
                ep.name AS user_name,
                u.username,

                a.shift_id,
                s.name AS shift_name,
                s.start_time,
                s.end_time,
                s.type AS shift_type,

                s.company_id,
                c.name AS company_name,

                a.work_date,
                a.check_in_at,
                a.check_out_at,

                a.status,
                a.note,

                a.created_at,
                a.updated_at

            FROM attendance a

            INNER JOIN users u
                ON u.id = a.user_id

            INNER JOIN employee_profiles ep
                ON ep.user_id = a.user_id

            INNER JOIN shifts s
                ON s.id = a.shift_id

            INNER JOIN companies c
                ON c.id = s.company_id

            WHERE a.user_id = ?
            AND a.work_date = ?

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param(
            "is",
            $userId,
            $date
        );

        $stmt->execute();

        $result = $stmt->get_result();

        $row = $result->fetch_assoc();

        if (!$row) {
            return null;
        }

        return $this->format($row);
    }
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO attendance
            (
                user_id,
                shift_id,
                work_date,
                check_in_at,
                status,
                note
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "Failed to prepare attendance insert"
            );
        }

        $userId = (int) $data["user_id"];
        $shiftId = (int) $data["shift_id"];

        $workDate = $data["work_date"];
        $checkInAt = $data["check_in_at"];

        $status = $data["status"] ?? "present";
        $note = $data["note"] ?? null;

        $stmt->bind_param(
            "iissss",
            $userId,
            $shiftId,
            $workDate,
            $checkInAt,
            $status,
            $note
        );

        $stmt->execute();

        return $this->db->insert_id;
    }
    public function checkOut(
        int $id,
        string $checkOutAt
    ): bool {

        $sql = "
            UPDATE attendance

            SET
                check_out_at = ?

            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            "si",
            $checkOutAt,
            $id
        );

        return $stmt->execute();
    }
    public function update(
        int $id,
        array $data
    ): bool {

        $sql = "
            UPDATE attendance

            SET
                user_id = ?,
                shift_id = ?,
                work_date = ?,
                check_in_at = ?,
                check_out_at = ?,
                status = ?,
                note = ?

            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $userId = (int) $data["user_id"];
        $shiftId = (int) $data["shift_id"];

        $workDate = $data["work_date"];
        $checkInAt = $data["check_in_at"];

        $checkOutAt = $data["check_out_at"] ?? null;
        $status = $data["status"] ?? "present";
        $note = $data["note"] ?? null;

        $stmt->bind_param(
            "iisssssi",
            $userId,
            $shiftId,
            $workDate,
            $checkInAt,
            $checkOutAt,
            $status,
            $note,
            $id
        );

        return $stmt->execute();
    }
    public function delete(int $id): bool
    {
        $sql = "
            DELETE FROM attendance
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

            "user" => [
                "id" => (int) $row["user_id"],
                "name" => $row["user_name"],
                "username" => $row["username"]
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

            "work_date" => $row["work_date"],

            "check_in_at" => $row["check_in_at"],

            "check_out_at" => $row["check_out_at"],

            "status" => $row["status"],

            "note" => $row["note"],

            "created_at" => $row["created_at"],
            "updated_at" => $row["updated_at"]
        ];
    }
}