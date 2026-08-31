<?php

require_once __DIR__ . '/../models/SupplyLocation.php';

class SupplyLocationController
{
    private SupplyLocation $supplyLocation;

    public function __construct(mysqli $db)
    {
        $this->supplyLocation = new SupplyLocation($db);
    }

    /**
     * GET /api/supply-locations
     */
    public function index(): void
    {
        $search = trim($_GET['search'] ?? '');

        $page = isset($_GET['page'])
            ? max(1, (int) $_GET['page'])
            : 1;

        $limit = isset($_GET['limit'])
            ? max(1, (int) $_GET['limit'])
            : 10;

        $offset = ($page - 1) * $limit;

        $result = $this->supplyLocation->getAll(
            $search,
            $limit,
            $offset
        );

        $this->json([
            'success' => true,
            'data' => $result['data'],
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $result['total'],
                'totalPages' => ceil($result['total'] / $limit)
            ]
        ]);
    }

    /**
     * GET /api/supply-locations/{id}
     */
    public function show(int $id): void
    {
        $supplyLocation = $this->supplyLocation->findById($id);

        if (!$supplyLocation) {
            $this->json([
                'success' => false,
                'message' => 'Supply location not found'
            ], 404);

            return;
        }

        $this->json([
            'success' => true,
            'data' => $supplyLocation
        ]);
    }

    /**
     * POST /api/supply-locations
     */
    public function store(): void
    {
        $data = $this->getInput();

        $code = trim($data['code'] ?? '');
        $name = trim($data['name'] ?? '');

        if ($code === '') {
            $this->json([
                'success' => false,
                'message' => 'Code is required'
            ], 422);

            return;
        }

        if ($name === '') {
            $this->json([
                'success' => false,
                'message' => 'Name is required'
            ], 422);

            return;
        }

        // Check duplicate code
        if ($this->supplyLocation->findByCode($code)) {
            $this->json([
                'success' => false,
                'message' => 'Code already exists'
            ], 409);

            return;
        }

        $id = $this->supplyLocation->create([
            'code' => $code,
            'name' => $name,
            'phone_number' => trim($data['phone_number'] ?? ''),
            'address' => trim($data['address'] ?? ''),
            'description' => trim($data['description'] ?? ''),
            'status' => isset($data['status'])
                ? (int) $data['status']
                : 1
        ]);

        $this->json([
            'success' => true,
            'message' => 'Supply location created successfully',
            'data' => [
                'id' => $id
            ]
        ], 201);
    }

    /**
     * PUT /api/supply-locations/{id}
     */
    public function update(int $id): void
    {
        $existing = $this->supplyLocation->findById($id);

        if (!$existing) {
            $this->json([
                'success' => false,
                'message' => 'Supply location not found'
            ], 404);

            return;
        }

        $data = $this->getInput();

        $code = trim($data['code'] ?? '');
        $name = trim($data['name'] ?? '');

        if ($code === '') {
            $this->json([
                'success' => false,
                'message' => 'Code is required'
            ], 422);

            return;
        }

        if ($name === '') {
            $this->json([
                'success' => false,
                'message' => 'Name is required'
            ], 422);

            return;
        }

        // Check duplicate code except current record
        $duplicate = $this->supplyLocation->findByCode($code);

        if ($duplicate && (int) $duplicate['id'] !== $id) {
            $this->json([
                'success' => false,
                'message' => 'Code already exists'
            ], 409);

            return;
        }

        $updated = $this->supplyLocation->update(
            $id,
            [
                'code' => $code,
                'name' => $name,
                'phone_number' => trim(
                    $data['phone_number'] ?? ''
                ),
                'address' => trim(
                    $data['address'] ?? ''
                ),
                'description' => trim(
                    $data['description'] ?? ''
                ),
                'status' => isset($data['status'])
                    ? (int) $data['status']
                    : 1
            ]
        );

        if (!$updated) {
            $this->json([
                'success' => false,
                'message' => 'Failed to update supply location'
            ], 500);

            return;
        }

        $this->json([
            'success' => true,
            'message' => 'Supply location updated successfully'
        ]);
    }

    /**
     * DELETE /api/supply-locations/{id}
     */
    public function destroy(int $id): void
    {
        $existing = $this->supplyLocation->findById($id);

        if (!$existing) {
            $this->json([
                'success' => false,
                'message' => 'Supply location not found'
            ], 404);

            return;
        }

        $deleted = $this->supplyLocation->delete($id);

        if (!$deleted) {
            $this->json([
                'success' => false,
                'message' => 'Failed to delete supply location'
            ], 500);

            return;
        }

        $this->json([
            'success' => true,
            'message' => 'Supply location deleted successfully'
        ]);
    }

    /**
     * Read JSON request body
     */
    private function getInput(): array
    {
        $input = json_decode(
            file_get_contents('php://input'),
            true
        );

        return is_array($input) ? $input : [];
    }

    /**
     * JSON Response
     */
    private function json(
        array $data,
        int $status = 200
    ): void {
        http_response_code($status);

        header('Content-Type: application/json');

        echo json_encode(
            $data,
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }
}