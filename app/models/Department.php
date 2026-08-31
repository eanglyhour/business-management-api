<?php

class Department
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
                d.id,
                d.company_id,
                c.name AS company_name,
                d.name,
                d.address,
                d.latitude,
                d.longitude,
                d.place_id,
                d.status,
                d.created_at,
                d.updated_at
            FROM departments d
            INNER JOIN companies c
                ON c.id = d.company_id
            ORDER BY d.id DESC
        ";

        $result = $this->db->query($sql);

        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $this->format($row);
        }

        return $data;
    }

    // GET BY ID
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT
                d.id,
                d.company_id,
                c.name AS company_name,
                d.name,
                d.address,
                d.latitude,
                d.longitude,
                d.place_id,
                d.status,
                d.created_at,
                d.updated_at
            FROM departments d
            INNER JOIN companies c
                ON c.id = d.company_id
            WHERE d.id = ?
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

    // CREATE
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO departments
            (
                company_id,
                name,
                address,
                latitude,
                longitude,
                place_id,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);

        $companyId = (int) $data["company_id"];
        $name = trim($data["name"]);
        $address = $data["address"] ?? null;
        $latitude = (float) $data["latitude"];
        $longitude = (float) $data["longitude"];
        $placeId = $data["place_id"] ?? null;
        $status = isset($data["status"])
            ? (int) $data["status"]
            : 1;

        $stmt->bind_param(
            "issddsi",
            $companyId,
            $name,
            $address,
            $latitude,
            $longitude,
            $placeId,
            $status
        );

        $stmt->execute();

        return $this->db->insert_id;
    }

    // UPDATE
    public function update(int $id, array $data): bool
    {
        $sql = "
            UPDATE departments
            SET
                company_id = ?,
                name = ?,
                address = ?,
                latitude = ?,
                longitude = ?,
                place_id = ?,
                status = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        $companyId = (int) $data["company_id"];
        $name = trim($data["name"]);
        $address = $data["address"] ?? null;
        $latitude = (float) $data["latitude"];
        $longitude = (float) $data["longitude"];
        $placeId = $data["place_id"] ?? null;
        $status = isset($data["status"])
            ? (int) $data["status"]
            : 1;

        $stmt->bind_param(
            "issddsii",
            $companyId,
            $name,
            $address,
            $latitude,
            $longitude,
            $placeId,
            $status,
            $id
        );

        return $stmt->execute();
    }

    // DELETE
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM departments WHERE id = ?";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }

    // FORMAT RESPONSE
    private function format(array $row): array
    {
        return [
            "id" => (int) $row["id"],

            "name" => $row["name"],

            "company" => [
                "id" => (int) $row["company_id"],
                "name" => $row["company_name"]
            ],

            "location" => [
                "address" => $row["address"],
                "latitude" => (float) $row["latitude"],
                "longitude" => (float) $row["longitude"],
                "place_id" => $row["place_id"]
            ],

            "status" => (bool) $row["status"],

            "created_at" => $row["created_at"],
            "updated_at" => $row["updated_at"]
        ];
    }
}