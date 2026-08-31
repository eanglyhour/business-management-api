<?php

require_once __DIR__ . '/../models/AttendanceCheckout.php';

class AttendanceCheckoutController
{
    private AttendanceCheckout $model;

    public function __construct(mysqli $db)
    {
        $this->model = new AttendanceCheckout($db);
    }

    public function index(): void
    {
        $data = $this->model->getAll();

        $this->json([
            "success" => true,
            "data" => $data
        ]);
    }

    public function show(int $id): void
    {
        $data = $this->model->findById($id);

        if (!$data) {
            $this->json([
                "success" => false,
                "message" => "Check-out not found"
            ], 404);

            return;
        }

        $this->json([
            "success" => true,
            "data" => $data
        ]);
    }

    public function byUser(int $userId): void
    {
        $data = $this->model->getByUser($userId);

        $this->json([
            "success" => true,
            "data" => $data
        ]);
    }

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

        $checkInId =
            (int) ($input["check_in_id"] ?? 0);

        $checkOutAt =
            $input["check_out_at"] ?? null;

        if (
            $checkInId <= 0 ||
            !$checkOutAt
        ) {
            $this->json([
                "success" => false,
                "message" =>
                    "check_in_id and check_out_at are required"
            ], 422);

            return;
        }

        $checkIn =
            $this->model->findCheckIn(
                $checkInId
            );

        if (!$checkIn) {
            $this->json([
                "success" => false,
                "message" =>
                    "Check-in not found"
            ], 404);

            return;
        }

        if (
            $this->model->checkoutExists(
                $checkInId
            )
        ) {
            $this->json([
                "success" => false,
                "message" =>
                    "This check-in already has a check-out"
            ], 409);

            return;
        }

        if (
            strtotime($checkOutAt)
            <
            strtotime($checkIn["check_in_at"])
        ) {
            $this->json([
                "success" => false,
                "message" =>
                    "Check-out time cannot be before check-in time"
            ], 422);

            return;
        }

        try {

            $id = $this->model->create([
                "check_in_id" => $checkInId,

                "user_id" =>
                    (int) $checkIn["user_id"],

                "check_out_at" =>
                    $checkOutAt
            ]);

            $this->json([
                "success" => true,
                "message" =>
                    "Check-out created successfully",

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

    public function destroy(int $id): void
    {
        $existing =
            $this->model->findById($id);

        if (!$existing) {
            $this->json([
                "success" => false,
                "message" =>
                    "Check-out not found"
            ], 404);

            return;
        }

        $success =
            $this->model->delete($id);

        if (!$success) {
            $this->json([
                "success" => false,
                "message" =>
                    "Failed to delete check-out"
            ], 500);

            return;
        }

        $this->json([
            "success" => true,
            "message" =>
                "Check-out deleted successfully"
        ]);
    }

    private function json(
        array $data,
        int $status = 200
    ): void {

        http_response_code($status);

        header(
            "Content-Type: application/json"
        );

        echo json_encode(
            $data,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );
    }
}