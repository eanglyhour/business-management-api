<?php

require_once __DIR__ . '/../models/ImportPayment.php';

class ImportPaymentController
{
    private ImportPayment $payment;

    public function __construct()
    {
        global $db;

        $this->payment = new ImportPayment($db);
    }

    // GET /api/import-payments
    public function index(): void
    {
        try {

            $data = $this->payment->getAll();

            echo json_encode([
                'success' => true,
                'data' => $data
            ]);

        } catch (Throwable $e) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    // GET /api/import-payments/{id}
    public function show(int $id): void
    {
        try {

            $data = $this->payment->findById($id);

            if (!$data) {

                http_response_code(404);

                echo json_encode([
                    'success' => false,
                    'message' => 'Payment not found'
                ]);

                return;
            }

            echo json_encode([
                'success' => true,
                'data' => $data
            ]);

        } catch (Throwable $e) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    // GET /api/imports/{importId}/payments
    public function getByImportId(int $importId): void
    {
        try {

            global $db;

            /*
            |--------------------------------------------------------------------------
            | Get Import
            |--------------------------------------------------------------------------
            */

            $stmt = $db->prepare("
                SELECT
                    id,
                    import_code,
                    total_amount,
                    status
                FROM imports
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->bind_param("i", $importId);

            $stmt->execute();

            $result = $stmt->get_result();

            $import = $result->fetch_assoc();

            if (!$import) {

                http_response_code(404);

                echo json_encode([
                    'success' => false,
                    'message' => 'Import not found'
                ]);

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Get Payment History
            |--------------------------------------------------------------------------
            */

            $payments = $this->payment->getByImportId($importId);

            /*
            |--------------------------------------------------------------------------
            | Calculate Payment Summary
            |--------------------------------------------------------------------------
            */

            $totalAmount = (float) $import['total_amount'];

            $totalPaid = $this->payment->getTotalPaid($importId);

            $remainingAmount = max(
                0,
                $totalAmount - $totalPaid
            );

            /*
            |--------------------------------------------------------------------------
            | Payment Status
            |--------------------------------------------------------------------------
            */

            if ($remainingAmount <= 0) {

                $paymentStatus = 'paid';

            } elseif ($totalPaid > 0) {

                $paymentStatus = 'partial';

            } else {

                $paymentStatus = 'unpaid';
            }

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            echo json_encode([
                'success' => true,
                'data' => [
                    'import_id' => (int) $import['id'],
                    'import_code' => $import['import_code'],

                    'total_amount' => $totalAmount,
                    'total_paid' => $totalPaid,
                    'remaining_amount' => $remainingAmount,

                    'payment_status' => $paymentStatus,

                    'payments' => $payments
                ]
            ]);

        } catch (Throwable $e) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    // GET /api/imports/{importId}/payments/summary
    public function summary(int $importId): void
    {
        try {

            global $db;

            $stmt = $db->prepare("
                SELECT
                    id,
                    import_code,
                    total_amount,
                    status
                FROM imports
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->bind_param("i", $importId);

            $stmt->execute();

            $result = $stmt->get_result();

            $import = $result->fetch_assoc();

            if (!$import) {

                http_response_code(404);

                echo json_encode([
                    'success' => false,
                    'message' => 'Import not found'
                ]);

                return;
            }

            $totalAmount = (float) $import['total_amount'];

            $totalPaid = $this->payment->getTotalPaid($importId);

            $remainingAmount = max(
                0,
                $totalAmount - $totalPaid
            );

            if ($remainingAmount <= 0) {

                $paymentStatus = 'paid';

            } elseif ($totalPaid > 0) {

                $paymentStatus = 'partial';

            } else {

                $paymentStatus = 'unpaid';
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'import_id' => (int) $import['id'],
                    'import_code' => $import['import_code'],
                    'total_amount' => $totalAmount,
                    'total_paid' => $totalPaid,
                    'remaining_amount' => $remainingAmount,
                    'payment_status' => $paymentStatus
                ]
            ]);

        } catch (Throwable $e) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}