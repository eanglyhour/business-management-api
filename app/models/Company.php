<?php

class Company
{
    public function __construct(
        private mysqli $db
    ) {}

    public function getAll(): array
    {
        $result = $this->db->query(
            "SELECT id, name, address, phone
             FROM companies
             ORDER BY id ASC"
        );

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, name, address, phone
             FROM companies
             WHERE id = ?"
        );

        $stmt->bind_param("i", $id);

        $stmt->execute();

        $result = $stmt->get_result();

        $data = $result->fetch_assoc();

        return $data ?: null;
    }

    public function create(
        string $name,
        string $address,
        string $phone
    ): int {

        $stmt = $this->db->prepare(
            "INSERT INTO companies
             (name, address, phone)
             VALUES (?, ?, ?)"
        );

        $stmt->bind_param(
            "sss",
            $name,
            $address,
            $phone
        );

        $stmt->execute();

        return $this->db->insert_id;
    }

    public function update(
        int $id,
        string $name,
        string $address,
        string $phone
    ): bool {

        $stmt = $this->db->prepare(
            "UPDATE companies
             SET name = ?,
                 address = ?,
                 phone = ?
             WHERE id = ?"
        );

        $stmt->bind_param(
            "sssi",
            $name,
            $address,
            $phone,
            $id
        );

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM companies
             WHERE id = ?"
        );

        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }
}