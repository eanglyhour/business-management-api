<?php

require_once __DIR__ . '/../models/Import.php';
require_once __DIR__ . '/../models/ImportDetail.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/ImportPayment.php';

class ImportController
{
       private mysqli $db;

    private Import $import;
    private ImportDetail $importDetail;
    private Product $product;
    private ImportPayment $importPayment;

    public function __construct(mysqli $db)
    {
        $this->db = $db;

        $this->import =
            new Import($db);

        $this->importDetail =
            new ImportDetail($db);

        $this->product =
            new Product($db);

        $this->importPayment =
            new ImportPayment($db);
    }
    public function index(): void
    {
        $data = $this->import->getAll();

        $this->response(200, [
            'success' => true,
            'data' => $data
        ]);
    }

    public function show(int $id): void
    {
        $import = $this->import->findById($id);

        if (!$import) {
            $this->response(404, [
                'success' => false,
                'message' => 'Import not found'
            ]);
            return;
        }

        $import['details'] =
            $this->importDetail
            ->getByImportId($id);

        $this->response(200, [
            'success' => true,
            'data' => $import
        ]);
    }

    public function store(): void
    {
        $input = json_decode(
            file_get_contents('php://input'),
            true
        );

        if (!is_array($input)) {
            $this->response(400, [
                'success' => false,
                'message' => 'Invalid JSON'
            ]);
            return;
        }

        $supplyLocationId =
            (int)($input['supply_location_id'] ?? 0);

        $description =
            $input['description'] ?? null;

        $details =
            $input['details'] ?? [];

        if ($supplyLocationId <= 0) {
            $this->response(422, [
                'success' => false,
                'message' =>
                'supply_location_id is required'
            ]);
            return;
        }

        if (!is_array($details) || empty($details)) {
            $this->response(422, [
                'success' => false,
                'message' =>
                'Import details are required'
            ]);
            return;
        }

        try {

            $this->db->begin_transaction();

            $importCode =
                'IMP-' . date('YmdHis');

            $importId =
                $this->import->create(
                    $importCode,
                    $supplyLocationId,
                    0,
                    $description
                );

            $totalAmount = 0;

            foreach ($details as $detail) {

                $productId =
                    (int)($detail['product_id'] ?? 0);

                $quantity =
                    (int)($detail['quantity'] ?? 0);

                $constPrice =
                    (float)($detail['const_price'] ?? 0);

                if ($productId <= 0) {
                    throw new Exception(
                        'Invalid product_id'
                    );
                }

                if ($quantity <= 0) {
                    throw new Exception(
                        'Quantity must be greater than 0'
                    );
                }

                if ($constPrice < 0) {
                    throw new Exception(
                        'const_price cannot be negative'
                    );
                }

                $sellPrice =
                    round(
                        $constPrice * 1.05,
                        2
                    );

                $subtotal =
                    $quantity * $constPrice;

                $totalAmount += $subtotal;

                $this->importDetail->create(
                    $importId,
                    $productId,
                    $quantity,
                    $constPrice,
                    $sellPrice
                );
            }

            $this->import->updateTotal(
                $importId,
                $totalAmount
            );

            $this->db->commit();

            $this->response(201, [
                'success' => true,
                'message' =>
                'Import created successfully',
                'data' => [
                    'id' => $importId,
                    'import_code' => $importCode,
                    'total_amount' =>
                    number_format(
                        $totalAmount,
                        2,
                        '.',
                        ''
                    ),
                    'status' => 'pending'
                ]
            ]);
        } catch (Throwable $e) {

            $this->db->rollback();

            $this->response(500, [
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function update(int $id): void
    {
        $input = json_decode(
            file_get_contents('php://input'),
            true
        );

        $import =
            $this->import->findById($id);

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
                'Only pending import can be updated'
            ]);
            return;
        }

        $supplyLocationId =
            (int)($input['supply_location_id'] ?? 0);

        $description =
            $input['description'] ?? null;

        if ($supplyLocationId <= 0) {
            $this->response(422, [
                'success' => false,
                'message' =>
                'supply_location_id is required'
            ]);
            return;
        }

        $success =
            $this->import->update(
                $id,
                $supplyLocationId,
                $description
            );

        $this->response(
            $success ? 200 : 500,
            [
                'success' => $success,
                'message' =>
                $success
                    ? 'Import updated successfully'
                    : 'Failed to update import'
            ]
        );
    }


    public function destroy(int $id): void
    {
        $import =
            $this->import->findById($id);

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
                'Only pending import can be deleted'
            ]);
            return;
        }

        $success =
            $this->import->delete($id);

        $this->response(
            $success ? 200 : 500,
            [
                'success' => $success,
                'message' =>
                $success
                    ? 'Import deleted successfully'
                    : 'Failed to delete import'
            ]
        );
    }
public function pay(int $id): void
{
    try {

        $this->db->begin_transaction();

        $import = $this->import->findById($id);

        if (!$import) {
            throw new Exception('Import not found');
        }

        if (
            $import['status'] === 'paid' ||
            $import['status'] === 'cancelled'
        ) {
            throw new Exception(
                'Import cannot receive payment'
            );
        }

        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        if (!is_array($data)) {
            throw new Exception('Invalid JSON');
        }

        $paymentMethod = trim(
            $data['payment_method'] ?? ''
        );

        $cashReceived = isset($data['cash_received'])
            ? (float) $data['cash_received']
            : null;

        $note = $data['note'] ?? null;

        if ($paymentMethod === '') {
            throw new Exception(
                'Payment method is required'
            );
        }

        $totalAmount = (float) $import['total_amount'];

        $paidBefore =
            $this->importPayment->getTotalPaid($id);

        $remaining =
            $totalAmount - $paidBefore;

        if ($remaining <= 0) {
            throw new Exception(
                'Import is already fully paid'
            );
        }

        if ($paymentMethod === 'cash') {

            if ($cashReceived === null) {
                throw new Exception(
                    'Cash received is required'
                );
            }

            if ($cashReceived <= 0) {
                throw new Exception(
                    'Cash received must be greater than 0'
                );
            }

            $paidAmount =
                min($cashReceived, $remaining);

            $changeAmount =
                max(
                    0,
                    $cashReceived - $remaining
                );

        } else {

            $paidAmount = isset($data['paid_amount'])
                ? (float) $data['paid_amount']
                : 0;

            if ($paidAmount <= 0) {
                throw new Exception(
                    'Paid amount must be greater than 0'
                );
            }

            if ($paidAmount > $remaining) {
                throw new Exception(
                    'Payment exceeds remaining amount'
                );
            }

            $cashReceived = null;
            $changeAmount = 0;
        }

        $paymentId =
            $this->importPayment->create(
                $id,
                $paymentMethod,
                $paidAmount,
                $cashReceived,
                $changeAmount,
                $note
            );

        if (!$paymentId) {
            throw new Exception(
                'Failed to create payment'
            );
        }

        $totalPaid =
            $paidBefore + $paidAmount;

        $remainingAfterPayment =
            max(
                0,
                $totalAmount - $totalPaid
            );

        if ($totalPaid >= $totalAmount) {

            $details =
                $this->importDetail
                    ->getByImportId($id);

            if (empty($details)) {
                throw new Exception(
                    'Import details not found'
                );
            }

            foreach ($details as $detail) {

                $success =
                    $this->product
                        ->updateStockAndPrice(
                            (int) $detail['product_id'],
                            (int) $detail['quantity'],
                            (float) $detail['const_price'],
                            (float) $detail['sell_price']
                        );

                if (!$success) {
                    throw new Exception(
                        'Failed to update product stock'
                    );
                }
            }

            $status = 'paid';

        } else {

            $status = 'partial';
        }

        if (!$this->import->updateStatus(
            $id,
            $status
        )) {
            throw new Exception(
                'Failed to update import status'
            );
        }

        $this->db->commit();

        $this->response(200, [
            'success' => true,
            'message' =>
                $status === 'paid'
                    ? 'Payment completed and stock updated'
                    : 'Partial payment successful',

            'data' => [
                'import_id' => $id,
                'payment_id' => $paymentId,
                'total_amount' => $totalAmount,
                'paid_before' => $paidBefore,
                'paid_amount' => $paidAmount,
                'total_paid' => $totalPaid,
                'remaining_amount' => $remainingAfterPayment,
                'status' => $status,
                'cash_received' => $cashReceived,
                'change_amount' => $changeAmount,
                'stock_updated' => $status === 'paid'
            ]
        ]);

    } catch (Throwable $e) {

        $this->db->rollback();

        $this->response(500, [
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
    // public function pay(int $id): void
    // {
    //     try {

    //         $this->db->begin_transaction();

    //         $import =
    //             $this->import->findById($id);

    //         if (!$import) {
    //             throw new Exception(
    //                 'Import not found'
    //             );
    //         }

    //         if ($import['status'] !== 'pending') {
    //             throw new Exception(
    //                 'Import is not pending'
    //             );
    //         }

    //         $details =
    //             $this->importDetail
    //                 ->getByImportId($id);

    //         if (empty($details)) {
    //             throw new Exception(
    //                 'Import details not found'
    //             );
    //         }


    //         foreach ($details as $detail) {

    //             $success =
    //                 $this->product
    //                     ->updateStockAndPrice(
    //                         (int)$detail['product_id'],
    //                         (int)$detail['quantity'],
    //                         (float)$detail['const_price'],
    //                         (float)$detail['sell_price']
    //                     );

    //             if (!$success) {
    //                 throw new Exception(
    //                     'Failed to update product'
    //                 );
    //             }
    //         }

    //         $this->import->updateStatus(
    //             $id,
    //             'paid'
    //         );

    //         $this->db->commit();

    //         $this->response(200, [
    //             'success' => true,
    //             'message' =>
    //                 'Payment successful and stock updated'
    //         ]);

    //     } catch (Throwable $e) {

    //         $this->db->rollback();

    //         $this->response(500, [
    //             'success' => false,
    //             'message' => $e->getMessage()
    //         ]);
    //     }
    // }

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
