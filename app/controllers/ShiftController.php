<?php

require_once __DIR__ . '/../models/Shift.php';


class ShiftController
{
    private Shift $model;

    public function __construct(mysqli $db)
    {
        $this->model = new Shift($db);
    }

    // GET /api/shifts
    public function index(): void
    {
        $data = $this->model->getAll();

        echo json_encode([
            "success" => true,
            "data" => $data
        ]);
    }

    // GET /api/shifts/{id}
    public function show(int $id): void
    {
        $data = $this->model->findById($id);

        if (!$data) {
            http_response_code(404);

            echo json_encode([
                "success" => false,
                "message" => "Shift not found"
            ]);

            return;
        }

        echo json_encode([
            "success" => true,
            "data" => $data
        ]);
    }

    // GET /api/companies/{companyId}/shifts
    public function companyShifts(int $companyId): void
    {
        $data = $this->model->getByCompany($companyId);

        echo json_encode([
            "success" => true,
            "data" => $data
        ]);
    }

    // POST /api/shifts
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
            "start_time",
            "end_time",
            "type"
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

        if (!$this->validTime($data["start_time"])) {
            http_response_code(422);

            echo json_encode([
                "success" => false,
                "message" => "Invalid start_time"
            ]);

            return;
        }

        if (!$this->validTime($data["end_time"])) {
            http_response_code(422);

            echo json_encode([
                "success" => false,
                "message" => "Invalid end_time"
            ]);

            return;
        }

        $allowedTypes = [
            "Normal",
            "Overnight",
            "Part-time"
        ];

        if (!in_array($data["type"], $allowedTypes)) {

            http_response_code(422);

            echo json_encode([
                "success" => false,
                "message" => "Invalid shift type"
            ]);

            return;
        }

        $id = $this->model->create($data);

        $result = $this->model->findById($id);

        http_response_code(201);

        echo json_encode([
            "success" => true,
            "message" => "Shift created successfully",
            "data" => $result
        ]);
    }

    // PUT /api/shifts/{id}
    public function update(int $id): void
    {
        $existing = $this->model->findById($id);

        if (!$existing) {
            http_response_code(404);

            echo json_encode([
                "success" => false,
                "message" => "Shift not found"
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
            "start_time",
            "end_time",
            "type"
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

        if (!$this->validTime($data["start_time"])) {
            http_response_code(422);

            echo json_encode([
                "success" => false,
                "message" => "Invalid start_time"
            ]);

            return;
        }

        if (!$this->validTime($data["end_time"])) {
            http_response_code(422);

            echo json_encode([
                "success" => false,
                "message" => "Invalid end_time"
            ]);

            return;
        }

        $allowedTypes = [
            "Normal",
            "Overnight",
            "Part-time"
        ];

        if (!in_array($data["type"], $allowedTypes)) {

            http_response_code(422);

            echo json_encode([
                "success" => false,
                "message" => "Invalid shift type"
            ]);

            return;
        }

        $this->model->update($id, $data);

        $result = $this->model->findById($id);

        echo json_encode([
            "success" => true,
            "message" => "Shift updated successfully",
            "data" => $result
        ]);
    }

    // DELETE /api/shifts/{id}
    public function destroy(int $id): void
    {
        $existing = $this->model->findById($id);

        if (!$existing) {
            http_response_code(404);

            echo json_encode([
                "success" => false,
                "message" => "Shift not found"
            ]);

            return;
        }

        $this->model->delete($id);

        echo json_encode([
            "success" => true,
            "message" => "Shift deleted successfully"
        ]);
    }

    private function validTime(string $time): bool
    {
        $format = 'H:i';

        $date = DateTime::createFromFormat(
            $format,
            $time
        );

        return $date !== false
            && $date->format($format) === $time;
    }
}