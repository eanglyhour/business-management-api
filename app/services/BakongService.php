<?php

require_once __DIR__ .
    '/../config/BakongConfig.php';

class BakongService
{
    private string $baseUrl;
    private string $token;

    public function __construct()
    {
        $this->baseUrl =
            BakongConfig::baseUrl();

        $this->token =
            BakongConfig::token();
    }


    private function request(
        string $endpoint,
        array $data
    ): array {

        $url =
            $this->baseUrl .
            '/' .
            ltrim($endpoint, '/');

        $ch =
            curl_init($url);

        curl_setopt_array($ch, [

            CURLOPT_RETURNTRANSFER =>
                true,

            CURLOPT_POST =>
                true,

            CURLOPT_POSTFIELDS =>
                json_encode($data),

            CURLOPT_HTTPHEADER => [

                'Content-Type: application/json',

                'Authorization: Bearer ' .
                    $this->token
            ],

            CURLOPT_TIMEOUT =>
                30,

            CURLOPT_CONNECTTIMEOUT =>
                10
        ]);

        $response =
            curl_exec($ch);

        if ($response === false) {

            $error =
                curl_error($ch);

            curl_close($ch);

            throw new Exception(
                'Bakong API error: ' .
                $error
            );
        }

        $httpCode =
            curl_getinfo(
                $ch,
                CURLINFO_HTTP_CODE
            );

        curl_close($ch);

        $result =
            json_decode(
                $response,
                true
            );

        if (!is_array($result)) {

            throw new Exception(
                'Invalid Bakong response'
            );
        }

        return [
            'http_code' =>
                $httpCode,

            'data' =>
                $result
        ];
    }


    public function checkTransactionByMd5(
        string $md5
    ): array {

        return $this->request(
            '/v1/check_transaction_by_md5',
            [
                'md5' => $md5
            ]
        );
    }

    public function checkTransactionByHash(
        string $hash
    ): array {

        return $this->request(
            '/v1/check_transaction_by_hash',
            [
                'hash' => $hash
            ]
        );
    }
}