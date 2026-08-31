<?php

require_once __DIR__ . '/../models/Attendance.php';

class AttendanceController
{
    private Attendance $model;

    public function __construct(mysqli $db)
    {
        $this->model = new Attendance($db);
    }

    // GET /api/attendance
    public function index(): void
    {
        $data = $this->model->getAll();

        $this->json([
            "success" => true,
            "data" => $data
        ]);
    }

    // GET /api/attendance/{id}
    public function show(int $id): void
    {
        $data = $this->model->findById($id);

        if (!$data) {
            $this->json([
                "success" => false,
                "message" => "Attendance not found"
            ], 404);

            return;
        }

        $this->json([
            "success" => true,
            "data" => $data
        ]);
    }

    // GET /api/attendance/user/{userId}
    public function byUser(int $userId): void
    {
        $data = $this->model->getByUser($userId);

        $this->json([
            "success" => true,
            "data" => $data
        ]);
    }

    // GET /api/attendance/date/{date}
    public function byDate(string $date): void
    {
        $data = $this->model->getByDate($date);

        $this->json([
            "success" => true,
            "data" => $data
        ]);
    }

    // POST /api/attendance
    public function store(): void
    {
        $input = json_decode(
            file_get_contents("php://input"),
            true
        );

        if (!is_array($input)) {
            $this->json([
                "success" => false,
                "message" => "Invalid JSON"
            ], 400);

            return;
        }

        $userId = (int) ($input["user_id"] ?? 0);
        $shiftId = (int) ($input["shift_id"] ?? 0);

        $workDate =
            $input["work_date"] ?? null;

        $checkInAt =
            $input["check_in_at"] ?? null;

        $status =
            $input["status"] ?? "present";

        $note =
            $input["note"] ?? null;

        if (
            $userId <= 0 ||
            $shiftId <= 0 ||
            !$workDate ||
            !$checkInAt
        ) {
            $this->json([
                "success" => false,
                "message" =>
                    "user_id, shift_id, work_date and check_in_at are required"
            ], 422);

            return;
        }

        // Prevent duplicate check-in
        $existing =
            $this->model->findByUserDate(
                $userId,
                $workDate
            );

        if ($existing) {
            $this->json([
                "success" => false,
                "message" =>
                    "User already has attendance for this date",
                "data" => $existing
            ], 409);

            return;
        }

        try {

            $id = $this->model->create([
                "user_id" => $userId,
                "shift_id" => $shiftId,
                "work_date" => $workDate,
                "check_in_at" => $checkInAt,
                "status" => $status,
                "note" => $note
            ]);

            $this->json([
                "success" => true,
                "message" =>
                    "Check-in created successfully",
                "data" =>
                    $this->model->findById($id)
            ], 201);

        } catch (Exception $e) {

            $this->json([
                "success" => false,
                "message" => $e->getMessage()
            ], 500);
        }
    }

    // PUT /api/attendance/{id}
    public function update(int $id): void
    {
        $existing =
            $this->model->findById($id);

        if (!$existing) {
            $this->json([
                "success" => false,
                "message" => "Attendance not found"
            ], 404);

            return;
        }

        $input = json_decode(
            file_get_contents("php://input"),
            true
        );

        if (!is_array($input)) {
            $this->json([
                "success" => false,
                "message" => "Invalid JSON"
            ], 400);

            return;
        }

        $success = $this->model->update(
            $id,
            $input
        );

        if (!$success) {
            $this->json([
                "success" => false,
                "message" =>
                    "Failed to update attendance"
            ], 500);

            return;
        }

        $this->json([
            "success" => true,
            "message" =>
                "Attendance updated successfully",
            "data" =>
                $this->model->findById($id)
        ]);
    }

    // PATCH /api/attendance/{id}/checkout
    public function checkout(int $id): void
    {
        $existing =
            $this->model->findById($id);

        if (!$existing) {
            $this->json([
                "success" => false,
                "message" => "Attendance not found"
            ], 404);

            return;
        }

        if ($existing["check_out_at"] !== null) {
            $this->json([
                "success" => false,
                "message" =>
                    "Already checked out"
            ], 409);

            return;
        }

        $input = json_decode(
            file_get_contents("php://input"),
            true
        );

        $checkOutAt =
            $input["check_out_at"]
            ?? date("Y-m-d H:i:s");

        $success =
            $this->model->checkOut(
                $id,
                $checkOutAt
            );

        if (!$success) {
            $this->json([
                "success" => false,
                "message" =>
                    "Failed to check out"
            ], 500);

            return;
        }

        $this->json([
            "success" => true,
            "message" =>
                "Check-out successful",
            "data" =>
                $this->model->findById($id)
        ]);
    }

    // DELETE /api/attendance/{id}
    public function destroy(int $id): void
    {
        $existing =
            $this->model->findById($id);

        if (!$existing) {
            $this->json([
                "success" => false,
                "message" => "Attendance not found"
            ], 404);

            return;
        }

        $success =
            $this->model->delete($id);

        if (!$success) {
            $this->json([
                "success" => false,
                "message" =>
                    "Failed to delete attendance"
            ], 500);

            return;
        }

        $this->json([
            "success" => true,
            "message" =>
                "Attendance deleted successfully"
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
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );
    }
}