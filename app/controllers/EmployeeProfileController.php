<?php

require_once __DIR__ . '/../models/EmployeeProfile.php';

class EmployeeProfileController
{
    private EmployeeProfile $model;

    public function __construct(mysqli $db)
    {
        $this->model = new EmployeeProfile($db);
    }

    public function index(): void
    {
        $data = $this->model->getAll();

        http_response_code(200);

        echo json_encode([
            "success" => true,
            "data" => $data
        ]);
    }

    public function show(int $id): void
    {
        $data = $this->model->findById($id);

        if (!$data) {

            http_response_code(404);

            echo json_encode([
                "success" => false,
                "message" => "Employee profile not found"
            ]);

            return;
        }

        echo json_encode([
            "success" => true,
            "data" => $data
        ]);
    }

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
            "user_id",
            "company_id",
            "department_id",
            "name"
        ];

        foreach ($required as $field) {

            if (
                !isset($data[$field]) ||
                $data[$field] === ""
            ) {

                http_response_code(422);

                echo json_encode([
                    "success" => false,
                    "message" => "$field is required"
                ]);

                return;
            }
        }

        $existing = $this->model->findByUserId(
            (int) $data["user_id"]
        );

        if ($existing) {

            http_response_code(409);

            echo json_encode([
                "success" => false,
                "message" => "This user already has a profile"
            ]);

            return;
        }

        $id = $this->model->create($data);

        $result = $this->model->findById($id);

        http_response_code(201);

        echo json_encode([
            "success" => true,
            "message" => "Employee profile created successfully",
            "data" => $result
        ]);
    }

    public function update(int $id): void
    {
        $existing = $this->model->findById($id);

        if (!$existing) {

            http_response_code(404);

            echo json_encode([
                "success" => false,
                "message" => "Employee profile not found"
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
            "department_id",
            "name"
        ];

        foreach ($required as $field) {

            if (
                !isset($data[$field]) ||
                $data[$field] === ""
            ) {

                http_response_code(422);

                echo json_encode([
                    "success" => false,
                    "message" => "$field is required"
                ]);

                return;
            }
        }

        $this->model->update($id, $data);

        $result = $this->model->findById($id);

        echo json_encode([
            "success" => true,
            "message" => "Employee profile updated successfully",
            "data" => $result
        ]);
    }

    public function destroy(int $id): void
    {
        $existing = $this->model->findById($id);

        if (!$existing) {

            http_response_code(404);

            echo json_encode([
                "success" => false,
                "message" => "Employee profile not found"
            ]);

            return;
        }

        $this->model->delete($id);

        echo json_encode([
            "success" => true,
            "message" => "Employee profile deleted successfully"
        ]);
    }
}