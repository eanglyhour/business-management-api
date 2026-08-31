<?php

require_once __DIR__ . '/../models/UserShift.php';

class UserShiftController
{
    private UserShift $model;

    public function __construct(mysqli $db)
    {
        $this->model = new UserShift($db);
    }

    // GET /api/user-shifts
    public function index(): void
    {
        $data = $this->model->getAll();

        echo json_encode([
            "success" => true,
            "data" => $data
        ]);
    }

    // GET /api/user-shifts/{id}
    public function show(int $id): void
    {
        $data = $this->model->findById($id);

        if (!$data) {
            http_response_code(404);

            echo json_encode([
                "success" => false,
                "message" => "User shift assignment not found"
            ]);

            return;
        }

        echo json_encode([
            "success" => true,
            "data" => $data
        ]);
    }

    // GET /api/users/{userId}/shifts
    public function userShifts(int $userId): void
    {
        $data = $this->model->getByUser($userId);

        echo json_encode([
            "success" => true,
            "data" => $data
        ]);
    }

    // GET /api/users/{userId}/current-shift?date=2026-08-17
    public function currentShift(int $userId): void
    {
        $date = $_GET["date"] ?? date("Y-m-d");

        if (!$this->validDate($date)) {

            http_response_code(422);

            echo json_encode([
                "success" => false,
                "message" => "Invalid date"
            ]);

            return;
        }

        $data = $this->model->getCurrentByUser(
            $userId,
            $date
        );

        if (!$data) {

            http_response_code(404);

            echo json_encode([
                "success" => false,
                "message" => "No active shift found for this date"
            ]);

            return;
        }

        echo json_encode([
            "success" => true,
            "data" => $data
        ]);
    }

    // POST /api/user-shifts
    public function store(): void
    {
        $data = $this->getJson();

        if ($data === null) {
            return;
        }

        $required = [
            "user_id",
            "shift_id",
            "effective_from"
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

        $userId = (int) $data["user_id"];
        $shiftId = (int) $data["shift_id"];

        $effectiveFrom = $data["effective_from"];

        $effectiveTo =
            isset($data["effective_to"]) &&
            $data["effective_to"] !== ""
                ? $data["effective_to"]
                : null;

        // Validate IDs
        if (!$this->model->userExists($userId)) {

            http_response_code(422);

            echo json_encode([
                "success" => false,
                "message" => "User not found"
            ]);

            return;
        }

        if (!$this->model->shiftExists($shiftId)) {

            http_response_code(422);

            echo json_encode([
                "success" => false,
                "message" => "Shift not found"
            ]);

            return;
        }

        // Validate dates
        if (!$this->validDate($effectiveFrom)) {

            http_response_code(422);

            echo json_encode([
                "success" => false,
                "message" => "Invalid effective_from"
            ]);

            return;
        }

        if (
            $effectiveTo !== null &&
            !$this->validDate($effectiveTo)
        ) {

            http_response_code(422);

            echo json_encode([
                "success" => false,
                "message" => "Invalid effective_to"
            ]);

            return;
        }

        // From must be <= To
        if (
            $effectiveTo !== null &&
            $effectiveFrom > $effectiveTo
        ) {

            http_response_code(422);

            echo json_encode([
                "success" => false,
                "message" =>
                    "effective_from cannot be greater than effective_to"
            ]);

            return;
        }

        // Prevent overlapping assignments
        if (
            $this->model->hasOverlap(
                $userId,
                $effectiveFrom,
                $effectiveTo
            )
        ) {

            http_response_code(409);

            echo json_encode([
                "success" => false,
                "message" =>
                    "User already has a shift assignment in this period"
            ]);

            return;
        }

        $data["user_id"] = $userId;
        $data["shift_id"] = $shiftId;
        $data["effective_from"] = $effectiveFrom;
        $data["effective_to"] = $effectiveTo;

        $id = $this->model->create($data);

        $result = $this->model->findById($id);

        http_response_code(201);

        echo json_encode([
            "success" => true,
            "message" =>
                "User shift assigned successfully",
            "data" => $result
        ]);
    }

    // PUT /api/user-shifts/{id}
    public function update(int $id): void
    {
        $existing = $this->model->findById($id);

        if (!$existing) {

            http_response_code(404);

            echo json_encode([
                "success" => false,
                "message" =>
                    "User shift assignment not found"
            ]);

            return;
        }

        $data = $this->getJson();

        if ($data === null) {
            return;
        }

        $required = [
            "user_id",
            "shift_id",
            "effective_from"
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

        $userId = (int) $data["user_id"];
        $shiftId = (int) $data["shift_id"];

        $effectiveFrom = $data["effective_from"];

        $effectiveTo =
            isset($data["effective_to"]) &&
            $data["effective_to"] !== ""
                ? $data["effective_to"]
                : null;

        if (!$this->model->userExists($userId)) {

            http_response_code(422);

            echo json_encode([
                "success" => false,
                "message" => "User not found"
            ]);

            return;
        }

        if (!$this->model->shiftExists($shiftId)) {

            http_response_code(422);

            echo json_encode([
                "success" => false,
                "message" => "Shift not found"
            ]);

            return;
        }

        if (!$this->validDate($effectiveFrom)) {

            http_response_code(422);

            echo json_encode([
                "success" => false,
                "message" =>
                    "Invalid effective_from"
            ]);

            return;
        }

        if (
            $effectiveTo !== null &&
            !$this->validDate($effectiveTo)
        ) {

            http_response_code(422);

            echo json_encode([
                "success" => false,
                "message" =>
                    "Invalid effective_to"
            ]);

            return;
        }

        if (
            $effectiveTo !== null &&
            $effectiveFrom > $effectiveTo
        ) {

            http_response_code(422);

            echo json_encode([
                "success" => false,
                "message" =>
                    "effective_from cannot be greater than effective_to"
            ]);

            return;
        }

        if (
            $this->model->hasOverlap(
                $userId,
                $effectiveFrom,
                $effectiveTo,
                $id
            )
        ) {

            http_response_code(409);

            echo json_encode([
                "success" => false,
                "message" =>
                    "User already has another shift assignment in this period"
            ]);

            return;
        }

        $data["user_id"] = $userId;
        $data["shift_id"] = $shiftId;
        $data["effective_from"] = $effectiveFrom;
        $data["effective_to"] = $effectiveTo;

        $this->model->update($id, $data);

        $result = $this->model->findById($id);

        echo json_encode([
            "success" => true,
            "message" =>
                "User shift updated successfully",
            "data" => $result
        ]);
    }

    // DELETE /api/user-shifts/{id}
    public function destroy(int $id): void
    {
        $existing = $this->model->findById($id);

        if (!$existing) {

            http_response_code(404);

            echo json_encode([
                "success" => false,
                "message" =>
                    "User shift assignment not found"
            ]);

            return;
        }

        $this->model->delete($id);

        echo json_encode([
            "success" => true,
            "message" =>
                "User shift deleted successfully"
        ]);
    }

    private function getJson(): ?array
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

            return null;
        }

        return $data;
    }

    private function validDate(string $date): bool
    {
        $format = "Y-m-d";

        $d = DateTime::createFromFormat(
            $format,
            $date
        );

        return $d !== false
            && $d->format($format) === $date;
    }
}