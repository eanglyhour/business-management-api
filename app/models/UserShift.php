<?php

class UserShift
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
            us.id,

            us.user_id,
            ep.name AS user_name,
            u.username,
            u.email AS user_email,

            r.id AS role_id,
            r.name AS role_name,

            us.shift_id,
            s.name AS shift_name,
            s.start_time,
            s.end_time,
            s.type AS shift_type,

            s.company_id,
            c.name AS company_name,

            us.effective_from,
            us.effective_to,

            us.created_at,
            us.updated_at

        FROM user_shifts us

        INNER JOIN users u
            ON u.id = us.user_id

        LEFT JOIN employee_profiles ep
            ON ep.user_id = us.user_id

        LEFT JOIN roles r
            ON r.id = u.role_id

        INNER JOIN shifts s
            ON s.id = us.shift_id

        INNER JOIN companies c
            ON c.id = s.company_id

        ORDER BY us.id DESC
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
            us.id,

            us.user_id,
            ep.name AS user_name,
            u.username,
            u.email AS user_email,

            r.id AS role_id,
            r.name AS role_name,

            us.shift_id,
            s.name AS shift_name,
            s.start_time,
            s.end_time,
            s.type AS shift_type,

            s.company_id,
            c.name AS company_name,

            us.effective_from,
            us.effective_to,

            us.created_at,
            us.updated_at

        FROM user_shifts us

        INNER JOIN users u
            ON u.id = us.user_id

        INNER JOIN employee_profiles ep
            ON ep.user_id = us.user_id

        LEFT JOIN roles r
            ON r.id = u.role_id

        INNER JOIN shifts s
            ON s.id = us.shift_id

        INNER JOIN companies c
            ON c.id = s.company_id

        WHERE us.id = ?
        LIMIT 1
    ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new Exception($this->db->error);
        }

        $stmt->bind_param('i', $id);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return null;
        }

        return $this->format($result->fetch_assoc());
    }
    public function getByUser(int $userId): array
    {
        $sql = "
            SELECT
                us.id,

                us.user_id,
                ep.name AS user_name,
                u.username,
                u.email AS user_email,

                us.shift_id,
                s.name AS shift_name,
                s.start_time,
                s.end_time,
                s.type AS shift_type,

                s.company_id,
                c.name AS company_name,

                us.effective_from,
                us.effective_to,

                us.created_at,
                us.updated_at

            FROM user_shifts us

            INNER JOIN users u
                ON u.id = us.user_id

            INNER JOIN employee_profiles ep
                ON ep.user_id = us.user_id

            INNER JOIN shifts s
                ON s.id = us.shift_id

            INNER JOIN companies c
                ON c.id = s.company_id

            WHERE us.user_id = ?

            ORDER BY us.effective_from DESC
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
    public function getCurrentByUser(
        int $userId,
        string $date
    ): ?array {

        $sql = "
            SELECT
                us.id,

                us.user_id,
                ep.name AS user_name,
                u.username,
                u.email AS user_email,

                us.shift_id,
                s.name AS shift_name,
                s.start_time,
                s.end_time,
                s.type AS shift_type,

                s.company_id,
                c.name AS company_name,

                us.effective_from,
                us.effective_to,

                us.created_at,
                us.updated_at

            FROM user_shifts us

            INNER JOIN users u
                ON u.id = us.user_id

            INNER JOIN employee_profiles ep
                ON ep.user_id = us.user_id

            INNER JOIN shifts s
                ON s.id = us.shift_id

            INNER JOIN companies c
                ON c.id = s.company_id

            WHERE us.user_id = ?

            AND us.effective_from <= ?

            AND (
                us.effective_to IS NULL
                OR us.effective_to >= ?
            )

            ORDER BY us.effective_from DESC

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param(
            "iss",
            $userId,
            $date,
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
    public function userExists(int $userId): bool
    {
        $sql = "
            SELECT id
            FROM users
            WHERE id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $userId);

        $stmt->execute();

        $result = $stmt->get_result();

        return $result->num_rows > 0;
    }
    public function shiftExists(int $shiftId): bool
    {
        $sql = "
            SELECT id
            FROM shifts
            WHERE id = ?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $shiftId);

        $stmt->execute();

        $result = $stmt->get_result();

        return $result->num_rows > 0;
    }
    public function hasOverlap(
        int $userId,
        string $from,
        ?string $to,
        ?int $excludeId = null
    ): bool {
        $sql = "
            SELECT id
            FROM user_shifts
            WHERE user_id = ?

            AND effective_from <= ?

            AND (
                effective_to IS NULL
                OR effective_to >= ?
            )
        ";
        if ($to === null) {

            $sql = "
                SELECT id
                FROM user_shifts
                WHERE user_id = ?

                AND effective_from <= ?

                AND (
                    effective_to IS NULL
                    OR effective_to >= ?
                )
            ";

            if ($excludeId !== null) {
                $sql .= " AND id != ?";
            }

            $sql .= " LIMIT 1";

            $stmt = $this->db->prepare($sql);

            if (!$stmt) {
                return false;
            }

            if ($excludeId !== null) {

                $stmt->bind_param(
                    "issi",
                    $userId,
                    $from,
                    $from,
                    $excludeId
                );
            } else {

                $stmt->bind_param(
                    "iss",
                    $userId,
                    $from,
                    $from
                );
            }
        } else {

            if ($excludeId !== null) {
                $sql .= " AND id != ?";
            }

            $sql .= " LIMIT 1";

            $stmt = $this->db->prepare($sql);

            if (!$stmt) {
                return false;
            }

            if ($excludeId !== null) {

                $stmt->bind_param(
                    "issi",
                    $userId,
                    $to,
                    $from,
                    $excludeId
                );
            } else {

                $stmt->bind_param(
                    "iss",
                    $userId,
                    $to,
                    $from
                );
            }
        }

        $stmt->execute();

        $result = $stmt->get_result();

        return $result->num_rows > 0;
    }
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO user_shifts
            (
                user_id,
                shift_id,
                effective_from,
                effective_to
            )
            VALUES (?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new RuntimeException(
                "Failed to prepare user shift create query"
            );
        }

        $userId = (int) $data["user_id"];
        $shiftId = (int) $data["shift_id"];

        $effectiveFrom = $data["effective_from"];
        $effectiveTo = $data["effective_to"] ?? null;

        $stmt->bind_param(
            "iiss",
            $userId,
            $shiftId,
            $effectiveFrom,
            $effectiveTo
        );

        $stmt->execute();

        return $this->db->insert_id;
    }
    public function update(
        int $id,
        array $data
    ): bool {

        $sql = "
            UPDATE user_shifts

            SET
                user_id = ?,
                shift_id = ?,
                effective_from = ?,
                effective_to = ?

            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $userId = (int) $data["user_id"];
        $shiftId = (int) $data["shift_id"];

        $effectiveFrom = $data["effective_from"];
        $effectiveTo = $data["effective_to"] ?? null;

        $stmt->bind_param(
            "iissi",
            $userId,
            $shiftId,
            $effectiveFrom,
            $effectiveTo,
            $id
        );

        return $stmt->execute();
    }
    public function delete(int $id): bool
    {
        $sql = "
            DELETE FROM user_shifts
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
            'id' => (int) $row['id'],

            'user' => [
                'id' => (int) $row['user_id'],
                'name' => $row['user_name'],
                'username' => $row['username'],
                'email' => $row['user_email'],

                'role' => [
                    'id' => $row['role_id'] !== null
                        ? (int) $row['role_id']
                        : null,

                    'name' => $row['role_name'],
                ],
            ],

            'shift' => [
                'id' => (int) $row['shift_id'],
                'name' => $row['shift_name'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time'],
                'type' => $row['shift_type'],
            ],

            'company' => [
                'id' => (int) $row['company_id'],
                'name' => $row['company_name'],
            ],

            'effective_from' => $row['effective_from'],
            'effective_to' => $row['effective_to'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ];
    }
}
