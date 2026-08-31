<?php

require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/ProductImage.php';

class ProductController
{
    private Product $product;
    private ProductImage $productImage;

    public function __construct(mysqli $db)
    {
        $this->product = new Product($db);
        $this->productImage = new ProductImage($db);
    }

    // GET /api/products
    public function index(): void
    {
        $products = $this->product->findAll();

        $this->json([
            "success" => true,
            "data" => $products
        ]);
    }

    // GET /api/products/{id}
    public function show(int $id): void
    {
        $product = $this->product->findById($id);

        if (!$product) {
            $this->json([
                "success" => false,
                "message" => "Product not found"
            ], 404);

            return;
        }

        $this->json([
            "success" => true,
            "data" => $product
        ]);
    }

    // POST /api/products
    public function store(): void
    {
        $data = json_decode(
            file_get_contents("php://input"),
            true
        );

        if (
            empty($data['category_id']) ||
            empty($data['name'])
        ) {
            $this->json([
                "success" => false,
                "message" => "category_id and name are required"
            ], 422);

            return;
        }

        $productId = $this->product->create([
            "category_id" => (int) $data['category_id'],
            "name" => trim($data['name']),
            "barcode" => $data['barcode'] ?? null,
            "des" => $data['des'] ?? null,
            "status" => isset($data['status'])
                ? (bool) $data['status']
                : true
        ]);

        $product = $this->product->findById($productId);

        $this->json([
            "success" => true,
            "message" => "Product created successfully",
            "data" => $product
        ], 201);
    }

    // PUT /api/products/{id}
    public function update(int $id): void
    {
        $product = $this->product->findById($id);

        if (!$product) {
            $this->json([
                "success" => false,
                "message" => "Product not found"
            ], 404);

            return;
        }

        $data = json_decode(
            file_get_contents("php://input"),
            true
        );

        if (
            empty($data['category_id']) ||
            empty($data['name'])
        ) {
            $this->json([
                "success" => false,
                "message" => "category_id and name are required"
            ], 422);

            return;
        }

        $this->product->update($id, [
            "category_id" => (int) $data['category_id'],
            "name" => trim($data['name']),
            "barcode" => $data['barcode'] ?? null,
            "des" => $data['des'] ?? null,
            "status" => isset($data['status'])
                ? (bool) $data['status']
                : true
        ]);

        $product = $this->product->findById($id);

        $this->json([
            "success" => true,
            "message" => "Product updated successfully",
            "data" => $product
        ]);
    }

    // DELETE /api/products/{id}
    public function destroy(int $id): void
    {
        $product = $this->product->findById($id);

        if (!$product) {
            $this->json([
                "success" => false,
                "message" => "Product not found"
            ], 404);

            return;
        }

        $this->product->delete($id);

        $this->json([
            "success" => true,
            "message" => "Product deleted successfully"
        ]);
    }

    // POST /api/products/{id}/images
    public function uploadImages(int $productId): void
    {
        $product = $this->product->findById($productId);

        if (!$product) {
            $this->json([
                "success" => false,
                "message" => "Product not found"
            ], 404);

            return;
        }

        if (
            !isset($_FILES['images']) ||
            !is_array($_FILES['images']['name'])
        ) {
            $this->json([
                "success" => false,
                "message" => "Images are required"
            ], 422);

            return;
        }

        $uploadDir =
            __DIR__ . "/../../public/uploads/products/";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $files = $_FILES['images'];

        $uploaded = [];

        $allowed = [
            'jpg',
            'jpeg',
            'png',
            'webp'
        ];

        for (
            $i = 0;
            $i < count($files['name']);
            $i++
        ) {

            if (
                $files['error'][$i]
                !== UPLOAD_ERR_OK
            ) {
                continue;
            }

            $extension = strtolower(
                pathinfo(
                    $files['name'][$i],
                    PATHINFO_EXTENSION
                )
            );

            if (!in_array($extension, $allowed)) {
                continue;
            }

            $fileName =
                uniqid("product_", true)
                . "." . $extension;

            $destination =
                $uploadDir . $fileName;

            if (
                move_uploaded_file(
                    $files['tmp_name'][$i],
                    $destination
                )
            ) {

                $imagePath =
                    "/uploads/products/"
                    . $fileName;

                $isPrimary =
                    count($uploaded) === 0;

                $imageId =
                    $this->productImage->create(
                        $productId,
                        $imagePath,
                        $isPrimary
                    );

                $uploaded[] = [
                    "id" => $imageId,
                    "image" => $imagePath,
                    "is_primary" => $isPrimary
                ];
            }
        }

        $this->json([
            "success" => true,
            "message" => "Images uploaded successfully",
            "data" => $uploaded
        ], 201);
    }

    // DELETE /api/products/images/{imageId}
    public function deleteImage(int $imageId): void
    {
        $image =
            $this->productImage->findById($imageId);

        if (!$image) {
            $this->json([
                "success" => false,
                "message" => "Image not found"
            ], 404);

            return;
        }

        $filePath =
            __DIR__
            . "/../../public"
            . $image['image'];

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $this->productImage->delete($imageId);

        $this->json([
            "success" => true,
            "message" => "Image deleted successfully"
        ]);
    }

    private function json(
        array $data,
        int $status = 200
    ): void {
        http_response_code($status);

        header("Content-Type: application/json");

        echo json_encode(
            $data,
            JSON_PRETTY_PRINT
        );
    }
}