<?php

class Shift
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    // GET ALL
    public function getAll(): array
    {
        $sql = "
            SELECT
                s.id,
                s.company_id,
                c.name AS company_name,

                s.name,
                s.start_time,
                s.end_time,
                s.type,
                s.status,

                s.created_at,
                s.updated_at

            FROM shifts s

            INNER JOIN companies c
                ON c.id = s.company_id

            ORDER BY s.id DESC
        ";

        $result = $this->db->query($sql);

        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $this->format($row);
        }

        return $data;
    }

    // GET ONE
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT
                s.id,
                s.company_id,
                c.name AS company_name,

                s.name,
                s.start_time,
                s.end_time,
                s.type,
                s.status,

                s.created_at,
                s.updated_at

            FROM shifts s

            INNER JOIN companies c
                ON c.id = s.company_id

            WHERE s.id = ?

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param("i", $id);

        $stmt->execute();

        $result = $stmt->get_result();

        $row = $result->fetch_assoc();

        if (!$row) {
            return null;
        }

        return $this->format($row);
    }

    // GET BY COMPANY
    public function getByCompany(int $companyId): array
    {
        $sql = "
            SELECT
                s.id,
                s.company_id,
                c.name AS company_name,

                s.name,
                s.start_time,
                s.end_time,
                s.type,
                s.status,

                s.created_at,
                s.updated_at

            FROM shifts s

            INNER JOIN companies c
                ON c.id = s.company_id

            WHERE s.company_id = ?

            ORDER BY s.start_time ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param("i", $companyId);

        $stmt->execute();

        $result = $stmt->get_result();

        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $this->format($row);
        }

        return $data;
    }

    // CREATE
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO shifts
            (
                company_id,
                name,
                start_time,
                end_time,
                type,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);

        $companyId = (int) $data["company_id"];
        $name = trim($data["name"]);
        $startTime = $data["start_time"];
        $endTime = $data["end_time"];
        $type = $data["type"];
        $status = isset($data["status"])
            ? (int) $data["status"]
            : 1;

        $stmt->bind_param(
            "issssi",
            $companyId,
            $name,
            $startTime,
            $endTime,
            $type,
            $status
        );

        $stmt->execute();

        return $this->db->insert_id;
    }

    // UPDATE
    public function update(int $id, array $data): bool
    {
        $sql = "
            UPDATE shifts
            SET
                company_id = ?,
                name = ?,
                start_time = ?,
                end_time = ?,
                type = ?,
                status = ?

            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        $companyId = (int) $data["company_id"];
        $name = trim($data["name"]);
        $startTime = $data["start_time"];
        $endTime = $data["end_time"];
        $type = $data["type"];
        $status = isset($data["status"])
            ? (int) $data["status"]
            : 1;

        $stmt->bind_param(
            "issssii",
            $companyId,
            $name,
            $startTime,
            $endTime,
            $type,
            $status,
            $id
        );

        return $stmt->execute();
    }

    // DELETE
    public function delete(int $id): bool
    {
        $sql = "
            DELETE FROM shifts
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }

    private function format(array $row): array
    {
        return [
            "id" => (int) $row["id"],

            "company" => [
                "id" => (int) $row["company_id"],
                "name" => $row["company_name"]
            ],

            "name" => $row["name"],

            "start_time" => $row["start_time"],
            "end_time" => $row["end_time"],

            "type" => $row["type"],

            "status" => (bool) $row["status"],

            "created_at" => $row["created_at"],
            "updated_at" => $row["updated_at"]
        ];
    }
}