<?php

require_once __DIR__ . '/../models/Import.php';
require_once __DIR__ . '/../models/ImportDetail.php';

class ImportDetailController
{
    private ImportDetail $importDetail;
    private Import $import;

    public function __construct(mysqli $db)
    {
        $this->importDetail =
            new ImportDetail($db);

        $this->import =
            new Import($db);
    }

    public function index(int $importId): void
    {
        $import =
            $this->import->findById($importId);

        if (!$import) {
            $this->response(404, [
                'success' => false,
                'message' => 'Import not found'
            ]);
            return;
        }

        $data =
            $this->importDetail
                ->getByImportId($importId);

        $this->response(200, [
            'success' => true,
            'data' => $data
        ]);
    }

    public function show(
        int $importId,
        int $id
    ): void {

        $data =
            $this->importDetail
                ->findById(
                    $importId,
                    $id
                );

        if (!$data) {
            $this->response(404, [
                'success' => false,
                'message' =>
                    'Import detail not found'
            ]);
            return;
        }

        $this->response(200, [
            'success' => true,
            'data' => $data
        ]);
    }

    public function destroy(
        int $importId,
        int $id
    ): void {

        $import =
            $this->import->findById($importId);

        if (!$import) {
            $this->response(404, [
                'success' => false,
                'message' => 'Import not found'
            ]);
            return;
        }

        if ($import['status'] !== 'pending') {
            $this->response(400, [
                'success' => false,
                'message' =>
                    'Cannot modify paid import'
            ]);
            return;
        }

        $detail =
            $this->importDetail
                ->findById(
                    $importId,
                    $id
                );

        if (!$detail) {
            $this->response(404, [
                'success' => false,
                'message' =>
                    'Import detail not found'
            ]);
            return;
        }

        $success =
            $this->importDetail->delete(
                $importId,
                $id
            );

        $this->response(
            $success ? 200 : 500,
            [
                'success' => $success,
                'message' =>
                    $success
                        ? 'Import detail deleted successfully'
                        : 'Failed to delete import detail'
            ]
        );
    }

    private function response(
        int $status,
        array $data
    ): void {

        http_response_code($status);

        header(
            'Content-Type: application/json'
        );

        echo json_encode(
            $data,
            JSON_UNESCAPED_UNICODE
        );
    }
}