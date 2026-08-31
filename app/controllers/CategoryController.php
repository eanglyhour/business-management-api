<?php

require_once __DIR__ . '/../models/Category.php';

class CategoryController
{
    private Category $category;

    public function __construct()
    {
        $this->category = new Category();
    }

    // GET /api/categories
public function index(): void
{
    $search = trim($_GET['search'] ?? '');

    $page = isset($_GET['page'])
        ? (int) $_GET['page']
        : 1;

    $limit = isset($_GET['limit'])
        ? (int) $_GET['limit']
        : 10;

    $sortBy = $_GET['sortBy'] ?? 'id';

    $sortOrder = $_GET['sortOrder'] ?? 'DESC';

    $result = $this->category->findAll(
        $search,
        $page,
        $limit,
        $sortBy,
        $sortOrder
    );

    
    $this->json([
        'success' => true,
        'data' => $result['data'],
        'pagination' => $result['pagination']
    ]);
}

    // GET /api/categories/{id}
    public function show(int $id): void
    {
        $category = $this->category->findById($id);

        if (!$category) {
            $this->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);

            return;
        }

        $this->json([
            'success' => true,
            'data' => $category
        ]);
    }

    // POST /api/categories
    public function store(): void
    {
        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        $name = trim($data['name'] ?? '');
        $des = $data['des'] ?? null;
        $status = (bool) ($data['status'] ?? true);

        if ($name === '') {
            $this->json([
                'success' => false,
                'message' => 'Name is required'
            ], 422);

            return;
        }

        if ($this->category->existsByName($name)) {
            $this->json([
                'success' => false,
                'message' => 'Category name already exists'
            ], 409);

            return;
        }

        $id = $this->category->create(
            $name,
            $des,
            $status
        );

        $this->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $this->category->findById($id)
        ], 201);
    }

    // PUT /api/categories/{id}
    public function update(int $id): void
    {
        $existing = $this->category->findById($id);

        if (!$existing) {
            $this->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);

            return;
        }

        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        $name = trim($data['name'] ?? '');
        $des = $data['des'] ?? null;
        $status = (bool) ($data['status'] ?? true);

        if ($name === '') {
            $this->json([
                'success' => false,
                'message' => 'Name is required'
            ], 422);

            return;
        }

        $this->category->update(
            $id,
            $name,
            $des,
            $status
        );

        $this->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $this->category->findById($id)
        ]);
    }

    // DELETE /api/categories/{id}
    public function destroy(int $id): void
    {
        if (!$this->category->delete($id)) {
            $this->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);

            return;
        }

        $this->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    }

    private function json(
        array $data,
        int $status = 200
    ): void {

        http_response_code($status);

        header('Content-Type: application/json');

        echo json_encode($data);
    }
}