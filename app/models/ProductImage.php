<?php

class ProductImage
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function create(
        int $productId,
        string $image,
        bool $isPrimary = false
    ): int {

        if ($isPrimary) {
            $stmt = $this->db->prepare("
                UPDATE product_images
                SET is_primary = FALSE
                WHERE product_id = ?
            ");

            $stmt->bind_param("i", $productId);
            $stmt->execute();
        }

        $stmt = $this->db->prepare("
            INSERT INTO product_images
                (product_id, image, is_primary)
            VALUES
                (?, ?, ?)
        ");

        $primary = $isPrimary ? 1 : 0;

        $stmt->bind_param(
            "isi",
            $productId,
            $image,
            $primary
        );

        $stmt->execute();

        return $this->db->insert_id;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                product_id,
                image,
                is_primary,
                created_at
            FROM product_images
            WHERE id = ?
        ");

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $result = $stmt->get_result();

        $image = $result->fetch_assoc();

        if (!$image) {
            return null;
        }

        $image['is_primary'] = (bool) $image['is_primary'];

        return $image;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM product_images
            WHERE id = ?
        ");

        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }
}