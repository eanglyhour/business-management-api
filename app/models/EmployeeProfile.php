<?php

class EmployeeProfile
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
                ep.id,
                ep.user_id,
                ep.company_id,
                ep.department_id,

                ep.name,
                ep.phone,
                ep.address,
                ep.image,
                ep.status,

                u.username,
                u.email AS login_email,

                c.name AS company_name,
                d.name AS department_name,

                ep.created_at,
                ep.updated_at

            FROM employee_profiles ep

            INNER JOIN users u
                ON u.id = ep.user_id

            INNER JOIN companies c
                ON c.id = ep.company_id

            INNER JOIN departments d
                ON d.id = ep.department_id

            ORDER BY ep.id DESC
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
                ep.id,
                ep.user_id,
                ep.company_id,
                ep.department_id,

                ep.name,
                ep.phone,
                ep.address,
                ep.image,
                ep.status,

                u.username,
                u.email AS login_email,

                c.name AS company_name,
                d.name AS department_name,

                ep.created_at,
                ep.updated_at

            FROM employee_profiles ep

            INNER JOIN users u
                ON u.id = ep.user_id

            INNER JOIN companies c
                ON c.id = ep.company_id

            INNER JOIN departments d
                ON d.id = ep.department_id

            WHERE ep.id = ?

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

    public function findByUserId(int $userId): ?array
    {
        $sql = "
            SELECT
                ep.id,
                ep.user_id,
                ep.company_id,
                ep.department_id,

                ep.name,
                ep.phone,
                ep.address,
                ep.image,
                ep.status,

                u.username,
                u.email AS login_email,

                c.name AS company_name,
                d.name AS department_name

            FROM employee_profiles ep

            INNER JOIN users u
                ON u.id = ep.user_id

            INNER JOIN companies c
                ON c.id = ep.company_id

            INNER JOIN departments d
                ON d.id = ep.department_id

            WHERE ep.user_id = ?

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param("i", $userId);

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
            INSERT INTO employee_profiles
            (
                user_id,
                company_id,
                department_id,
                name,
                phone,
                address,
                image,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);

        $userId = (int) $data["user_id"];
        $companyId = (int) $data["company_id"];
        $departmentId = (int) $data["department_id"];

        $name = trim($data["name"]);

        $phone = $data["phone"] ?? null;
        $address = $data["address"] ?? null;
        $image = $data["image"] ?? null;

        $status = isset($data["status"])
            ? (int) $data["status"]
            : 1;

        $stmt->bind_param(
            "iiissssi",
            $userId,
            $companyId,
            $departmentId,
            $name,
            $phone,
            $address,
            $image,
            $status
        );

        $stmt->execute();

        return $this->db->insert_id;
    }

    public function update(
        int $id,
        array $data
    ): bool {

        $sql = "
            UPDATE employee_profiles
            SET
                company_id = ?,
                department_id = ?,
                name = ?,
                phone = ?,
                address = ?,
                image = ?,
                status = ?

            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        $companyId = (int) $data["company_id"];
        $departmentId = (int) $data["department_id"];

        $name = trim($data["name"]);

        $phone = $data["phone"] ?? null;
        $address = $data["address"] ?? null;
        $image = $data["image"] ?? null;

        $status = isset($data["status"])
            ? (int) $data["status"]
            : 1;

        $stmt->bind_param(
            "iissssii",
            $companyId,
            $departmentId,
            $name,
            $phone,
            $address,
            $image,
            $status,
            $id
        );

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $sql = "
            DELETE FROM employee_profiles
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

            "user" => [
                "id" => (int) $row["user_id"],
                "username" => $row["username"],
                "email" => $row["login_email"]
            ],

            "company" => [
                "id" => (int) $row["company_id"],
                "name" => $row["company_name"]
            ],

            "department" => [
                "id" => (int) $row["department_id"],
                "name" => $row["department_name"]
            ],

            "profile" => [
                "name" => $row["name"],
                "phone" => $row["phone"],
                "address" => $row["address"],
                "image" => $row["image"]
            ],

            "status" => (bool) $row["status"],

            "created_at" => $row["created_at"] ?? null,
            "updated_at" => $row["updated_at"] ?? null
        ];
    }
}