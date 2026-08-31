<?php

class RefreshToken
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function create(
        int $userId,
        string $tokenHash,
        string $expiresAt
    ): bool {

        $sql = "
            INSERT INTO refresh_tokens
            (
                user_id,
                token_hash,
                expires_at
            )
            VALUES (?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param(
            "iss",
            $userId,
            $tokenHash,
            $expiresAt
        );

        return $stmt->execute();
    }

    public function findValid(string $tokenHash): ?array
    {
        $sql = "
            SELECT *
            FROM refresh_tokens
            WHERE token_hash = ?
              AND revoked_at IS NULL
              AND expires_at > NOW()
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param("s", $tokenHash);

        $stmt->execute();

        $result = $stmt->get_result();

        $token = $result->fetch_assoc();

        return $token ?: null;
    }

    public function revoke(string $tokenHash): bool
    {
        $sql = "
            UPDATE refresh_tokens
            SET revoked_at = NOW()
            WHERE token_hash = ?
              AND revoked_at IS NULL
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param("s", $tokenHash);

        return $stmt->execute();
    }
}