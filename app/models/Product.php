<?php

class Product
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function findAll(): array
    {
        $sql = "
            SELECT
                p.id,
                p.category_id,
                c.name AS category_name,
                p.name,
                p.const_price,
                p.sell_price,
                p.stock,
                p.barcode,
                p.des,
                p.status,
                p.created_at,
                p.updated_at
            FROM products p
            INNER JOIN categories c
                ON c.id = p.category_id
            ORDER BY p.id DESC
        ";

        $result = $this->db->query($sql);

        $products = [];

        while ($row = $result->fetch_assoc()) {

            $row['status'] = (bool) $row['status'];

            $row['stock'] = (int) $row['stock'];

            $row['const_price'] = (float) $row['const_price'];

            $row['sell_price'] = (float) $row['sell_price'];

            $row['images'] = $this->getImages(
                (int) $row['id']
            );

            $products[] = $row;
        }

        return $products;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                p.id,
                p.category_id,
                c.name AS category_name,
                p.name,
                p.const_price,
                p.sell_price,
                p.stock,
                p.barcode,
                p.des,
                p.status,
                p.created_at,
                p.updated_at
            FROM products p
            INNER JOIN categories c
                ON c.id = p.category_id
            WHERE p.id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $id);

        $stmt->execute();

        $result = $stmt->get_result();

        $product = $result->fetch_assoc();

        if (!$product) {
            return null;
        }

        $product['status'] = (bool) $product['status'];

        $product['stock'] = (int) $product['stock'];

        $product['const_price'] =
            (float) $product['const_price'];

        $product['sell_price'] =
            (float) $product['sell_price'];

        $product['images'] =
            $this->getImages($id);

        return $product;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO products
            (
                category_id,
                name,
                const_price,
                sell_price,
                stock,
                barcode,
                des,
                status
            )
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $categoryId =
            (int) $data['category_id'];

        $name =
            trim($data['name']);

        $constPrice =
            (float) ($data['const_price'] ?? 0);

        $sellPrice =
            (float) ($data['sell_price'] ?? 0);

        $stock =
            (int) ($data['stock'] ?? 0);

        $barcode =
            $data['barcode'] ?? null;

        $des =
            $data['des'] ?? null;

        $status =
            !empty($data['status']) ? 1 : 0;

        $stmt->bind_param(
            "isddissi",
            $categoryId,
            $name,
            $constPrice,
            $sellPrice,
            $stock,
            $barcode,
            $des,
            $status
        );

        $stmt->execute();

        return $this->db->insert_id;
    }

    public function update(
        int $id,
        array $data
    ): bool {

        $stmt = $this->db->prepare("
            UPDATE products
            SET
                category_id = ?,
                name = ?,
                const_price = ?,
                sell_price = ?,
                barcode = ?,
                des = ?,
                status = ?
            WHERE id = ?
        ");

        $categoryId =
            (int) $data['category_id'];

        $name =
            trim($data['name']);

        $constPrice =
            (float) ($data['const_price'] ?? 0);

        $sellPrice =
            (float) ($data['sell_price'] ?? 0);

        $barcode =
            $data['barcode'] ?? null;

        $des =
            $data['des'] ?? null;

        $status =
            !empty($data['status']) ? 1 : 0;

        $stmt->bind_param(
            "isddssii",
            $categoryId,
            $name,
            $constPrice,
            $sellPrice,
            $barcode,
            $des,
            $status,
            $id
        );

        return $stmt->execute();
    }

    public function updateStockAndPrice(
        int $productId,
        int $quantity,
        float $constPrice,
        float $sellPrice
    ): bool {

        $stmt = $this->db->prepare("
            UPDATE products
            SET
                stock = stock + ?,
                const_price = ?,
                sell_price = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "iddi",
            $quantity,
            $constPrice,
            $sellPrice,
            $productId
        );

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM products
            WHERE id = ?
        ");

        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }

    private function getImages(
        int $productId
    ): array {

        $stmt = $this->db->prepare("
            SELECT
                id,
                image,
                is_primary,
                created_at
            FROM product_images
            WHERE product_id = ?
            ORDER BY is_primary DESC, id ASC
        ");

        $stmt->bind_param(
            "i",
            $productId
        );

        $stmt->execute();

        $result =
            $stmt->get_result();

        $images = [];

        while ($row = $result->fetch_assoc()) {

            $row['is_primary'] =
                (bool) $row['is_primary'];

            $images[] = $row;
        }

        return $images;
    }
}