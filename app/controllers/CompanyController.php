<?php

require_once __DIR__ . '/../models/Company.php';

class CompanyController
{
      private Company $company;

    public function __construct(mysqli $db)
    {
        $this->company = new Company($db);
    }

    public function index(): void
    {
        $companies = $this->company->getAll();

        $this->json([
            "success" => true,
            "data" => $companies
        ]);
    }

    public function show(string $id): void
    {
        $company = $this->company->getById(
            (int) $id
        );

        if (!$company) {

            $this->json([
                "success" => false,
                "message" => "Company not found"
            ], 404);

            return;
        }

        $this->json([
            "success" => true,
            "data" => $company
        ]);
    }

    public function store(): void
    {
        $data = $this->getJson();

        if (
            empty($data["name"]) ||
            empty($data["address"]) ||
            empty($data["phone"])
        ) {

            $this->json([
                "success" => false,
                "message" => "name, address and phone are required"
            ], 422);

            return;
        }

        $id = $this->company->create(
            $data["name"],
            $data["address"],
            $data["phone"]
        );

        $this->json([
            "success" => true,
            "message" => "Company created successfully",
            "data" => $this->company->getById($id)
        ], 201);
    }

    public function update(string $id): void
    {
        $id = (int) $id;

        $existing = $this->company->getById($id);

        if (!$existing) {

            $this->json([
                "success" => false,
                "message" => "Company not found"
            ], 404);

            return;
        }

        $data = $this->getJson();

        if (
            empty($data["name"]) ||
            empty($data["address"]) ||
            empty($data["phone"])
        ) {

            $this->json([
                "success" => false,
                "message" => "name, address and phone are required"
            ], 422);

            return;
        }

        $this->company->update(
            $id,
            $data["name"],
            $data["address"],
            $data["phone"]
        );

        $this->json([
            "success" => true,
            "message" => "Company updated successfully",
            "data" => $this->company->getById($id)
        ]);
    }

    public function destroy(string $id): void
    {
        $id = (int) $id;

        $existing = $this->company->getById($id);

        if (!$existing) {

            $this->json([
                "success" => false,
                "message" => "Company not found"
            ], 404);

            return;
        }

        $this->company->delete($id);

        $this->json([
            "success" => true,
            "message" => "Company deleted successfully"
        ]);
    }

    private function getJson(): array
    {
        return json_decode(
            file_get_contents("php://input"),
            true
        ) ?? [];
    }

    private function json(
        array $data,
        int $status = 200
    ): void {

        http_response_code($status);

        header("Content-Type: application/json");

        echo json_encode(
            $data,
            JSON_UNESCAPED_UNICODE
        );
    }
}