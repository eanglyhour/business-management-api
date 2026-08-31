<?php

require_once __DIR__ . '/../models/PaymentModel.php';
require_once __DIR__ . '/../services/BakongService.php';
require_once __DIR__ . '/../config/BakongConfig.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use KHQR\BakongKHQR;
use KHQR\Models\IndividualInfo;
use KHQR\Helpers\KHQRData;

class PaymentController_Bakong
{
    private PaymentModel $paymentModel;
    private BakongService $bakong;

    public function __construct(mysqli $db)
    {
        $this->paymentModel = new PaymentModel($db);
        $this->bakong = new BakongService();
    }

    public function create(): void
    {
        try {
            $body = json_decode(
                file_get_contents('php://input'),
                true
            );

            if (!is_array($body)) {
                $this->json([
                    'success' => false,
                    'message' => 'Invalid JSON body'
                ], 400);

                return;
            }

            $orderId = $body['order_id'] ?? null;

            if (
                $orderId === null ||
                !is_numeric($orderId)
            ) {
                $this->json([
                    'success' => false,
                    'message' => 'order_id is required'
                ], 400);

                return;
            }

            $amount = $body['amount'] ?? null;

            if (
                $amount === null ||
                !is_numeric($amount) ||
                (float)$amount <= 0
            ) {
                $this->json([
                    'success' => false,
                    'message' => 'amount is required'
                ], 400);

                return;
            }

            $currency = strtoupper(
                trim(
                    $body['currency'] ?? 'KHR'
                )
            );

            if (
                $currency !== 'KHR' &&
                $currency !== 'USD'
            ) {
                $this->json([
                    'success' => false,
                    'message' => 'Currency must be KHR or USD'
                ], 400);

                return;
            }

            $khqr = $this->generateKHQR(
                (float)$amount,
                $currency
            );

            $qr = $khqr['qr'];

            $md5 = strtolower(
                $khqr['md5']
            );

            $existing = $this->paymentModel
                ->findByMd5($md5);

            if ($existing) {
                $this->json([
                    'success' => true,
                    'message' => 'Payment already exists',
                    'data' => [
                        'payment_id' => (int)$existing['id'],
                        'order_id' => (int)$existing['order_id'],
                        'amount' => (float)$existing['amount'],
                        'currency' => $existing['currency'],
                        'md5' => $existing['md5'],
                        'qr' => $qr,
                        'status' => $existing['status']
                    ]
                ]);

                return;
            }

            $paymentId = $this->paymentModel->create(
                (int)$orderId,
                (float)$amount,
                $currency,
                $md5
            );

            $this->json([
                'success' => true,
                'message' => 'Payment created',
                'data' => [
                    'payment_id' => $paymentId,
                    'order_id' => (int)$orderId,
                    'amount' => (float)$amount,
                    'currency' => $currency,
                    'recipient' => BakongConfig::accountId(),
                    'md5' => $md5,
                    'qr' => $qr,
                    'status' => 'pending'
                ]
            ], 201);

        } catch (Throwable $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function verify(): void
    {
        try {
            $body = json_decode(
                file_get_contents('php://input'),
                true
            );

            if (!is_array($body)) {
                $this->json([
                    'success' => false,
                    'message' => 'Invalid JSON body'
                ], 400);

                return;
            }

            $md5 = strtolower(
                trim(
                    $body['md5'] ?? ''
                )
            );

            if ($md5 === '') {
                $this->json([
                    'success' => false,
                    'message' => 'md5 is required'
                ], 400);

                return;
            }

            $payment = $this->paymentModel
                ->findByMd5($md5);

            if (!$payment) {
                $this->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);

                return;
            }

            if ($payment['status'] === 'success') {
                $this->json([
                    'success' => true,
                    'message' => 'Payment already verified',
                    'data' => [
                        'payment_id' => (int)$payment['id'],
                        'order_id' => (int)$payment['order_id'],
                        'amount' => (float)$payment['amount'],
                        'currency' => $payment['currency'],
                        'md5' => $payment['md5'],
                        'transaction_hash' => $payment['transaction_hash'],
                        'from_account_id' => $payment['from_account_id'],
                        'to_account_id' => $payment['to_account_id'],
                        'status' => 'success'
                    ]
                ]);

                return;
            }

            $response = $this->bakong
                ->checkTransactionByMd5($md5);

            $bakong = $response['data'] ?? [];

            $responseCode = (int)(
                $bakong['responseCode'] ?? 1
            );

            if ($responseCode !== 0) {
                $this->json([
                    'success' => false,
                    'message' =>
                        $bakong['responseMessage']
                        ?? 'Payment not completed',
                    'status' => 'pending',
                    'data' => $bakong
                ]);

                return;
            }

            $transaction = $bakong['data'] ?? null;

            if (!$transaction) {
                $this->json([
                    'success' => false,
                    'message' => 'Transaction data not found',
                    'status' => 'pending'
                ]);

                return;
            }

            $paidAmount = (float)(
                $transaction['amount'] ?? 0
            );

            $expectedAmount = (float)$payment['amount'];

            if (
                round($paidAmount, 2) !==
                round($expectedAmount, 2)
            ) {
                $this->json([
                    'success' => false,
                    'message' => 'Payment amount mismatch',
                    'expected' => $expectedAmount,
                    'received' => $paidAmount
                ], 400);

                return;
            }

            $transactionHash =
                $transaction['hash']
                ??
                $transaction['transactionHash']
                ??
                $transaction['transactionId']
                ??
                '';

            $fromAccount =
                $transaction['fromAccountId']
                ?? '';

            $toAccount =
                $transaction['toAccountId']
                ??
                BakongConfig::accountId();

            $paymentDate = date(
                'Y-m-d H:i:s'
            );

            $this->paymentModel->markPaid(
                (int)$payment['id'],
                $transactionHash,
                $fromAccount,
                $toAccount,
                $paymentDate
            );

            $this->json([
                'success' => true,
                'message' => 'Payment verified successfully',
                'data' => [
                    'payment_id' => (int)$payment['id'],
                    'order_id' => (int)$payment['order_id'],
                    'amount' => $paidAmount,
                    'currency' =>
                        $transaction['currency']
                        ??
                        $payment['currency'],
                    'md5' => $payment['md5'],
                    'transaction_hash' => $transactionHash,
                    'from_account_id' => $fromAccount,
                    'to_account_id' => $toAccount,
                    'status' => 'success'
                ]
            ]);

        } catch (Throwable $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function generateKHQR(
        float $amount,
        string $currency
    ): array {
        $khqrCurrency =
            $currency === 'USD'
            ? KHQRData::CURRENCY_USD
            : KHQRData::CURRENCY_KHR;

        $billNumber =
            'PAY-'
            . date('YmdHis')
            . '-'
            . random_int(100, 999);

        $expirationTimestamp =
            (int)(microtime(true) * 1000)
            + (10 * 60 * 1000);

        $merchant = new IndividualInfo(
            bakongAccountID: BakongConfig::accountId(),
            merchantName: BakongConfig::merchantName(),
            merchantCity: BakongConfig::city(),
            currency: $khqrCurrency,
            amount: $amount,
            billNumber: $billNumber,
            storeLabel: 'POS Store',
            expirationTimestamp: $expirationTimestamp
        );

        $response = BakongKHQR::generateIndividual(
            $merchant
        );

        if (is_object($response)) {
            $response = json_decode(
                json_encode($response),
                true
            );
        }

        if (!is_array($response)) {
            throw new Exception(
                'Invalid KHQR response'
            );
        }

        $qr =
            $response['data']['qr']
            ?? null;

        $md5 =
            $response['data']['md5']
            ?? null;

        if (!$qr) {
            throw new Exception(
                'KHQR QR code was not generated'
            );
        }

        if (!$md5) {
            throw new Exception(
                'KHQR MD5 was not generated'
            );
        }

        return [
            'qr' => $qr,
            'md5' => strtolower($md5)
        ];
    }

    private function json(
        array $data,
        int $status = 200
    ): void {
        http_response_code($status);

        header(
            'Content-Type: application/json; charset=utf-8'
        );

        echo json_encode(
            $data,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );
    }
}