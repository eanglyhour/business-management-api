<?php

require_once __DIR__ . '/../models/Department.php';

class DepartmentController
{
    private Department $department;

    public function __construct(mysqli $db)
    {
        $this->department = new Department($db);
    }

    // GET /api/departments
    public function index(): void
    {
        $data = $this->department->getAll();

        http_response_code(200);

        echo json_encode([
            "success" => true,
            "data" => $data
        ]);
    }

    // GET /api/departments/{id}
    public function show(int $id): void
    {
        $data = $this->department->findById($id);

        if (!$data) {
            http_response_code(404);

            echo json_encode([
                "success" => false,
                "message" => "Department not found"
            ]);

            return;
        }

        echo json_encode([
            "success" => true,
            "data" => $data
        ]);
    }

    // POST /api/departments
    public function store(): void
    {
        $data = json_decode(
            file_get_contents("php://input"),
            true
        );

        if (!is_array($data)) {
            http_response_code(400);

            echo json_encode([
                "success" => false,
                "message" => "Invalid JSON"
            ]);

            return;
        }

        $required = [
            "company_id",
            "name",
            "latitude",
            "longitude"
        ];

        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === "") {
                http_response_code(422);

                echo json_encode([
                    "success" => false,
                    "message" => "$field is required"
                ]);

                return;
            }
        }

        if (
            $data["latitude"] < -90 ||
            $data["latitude"] > 90
        ) {
            http_response_code(422);

            echo json_encode([
                "success" => false,
                "message" => "Invalid latitude"
            ]);

            return;
        }

        if (
            $data["longitude"] < -180 ||
            $data["longitude"] > 180
        ) {
            http_response_code(422);

            echo json_encode([
                "success" => false,
                "message" => "Invalid longitude"
            ]);

            return;
        }

        $id = $this->department->create($data);

        $result = $this->department->findById($id);

        http_response_code(201);

        echo json_encode([
            "success" => true,
            "message" => "Department created successfully",
            "data" => $result
        ]);
    }

    // PUT /api/departments/{id}
    public function update(int $id): void
    {
        $existing = $this->department->findById($id);

        if (!$existing) {
            http_response_code(404);

            echo json_encode([
                "success" => false,
                "message" => "Department not found"
            ]);

            return;
        }

        $data = json_decode(
            file_get_contents("php://input"),
            true
        );

        if (!is_array($data)) {
            http_response_code(400);

            echo json_encode([
                "success" => false,
                "message" => "Invalid JSON"
            ]);

            return;
        }

        $required = [
            "company_id",
            "name",
            "latitude",
            "longitude"
        ];

        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === "") {
                http_response_code(422);

                echo json_encode([
                    "success" => false,
                    "message" => "$field is required"
                ]);

                return;
            }
        }

        $this->department->update($id, $data);

        $result = $this->department->findById($id);

        echo json_encode([
            "success" => true,
            "message" => "Department updated successfully",
            "data" => $result
        ]);
    }

    // DELETE /api/departments/{id}
    public function destroy(int $id): void
    {
        $existing = $this->department->findById($id);

        if (!$existing) {
            http_response_code(404);

            echo json_encode([
                "success" => false,
                "message" => "Department not found"
            ]);

            return;
        }

        $this->department->delete($id);

        echo json_encode([
            "success" => true,
            "message" => "Department deleted successfully"
        ]);
    }
}