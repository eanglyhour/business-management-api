<?php

class BakongConfig
{
    public static function baseUrl(): string
    {
        return rtrim(
            $_ENV['BAKONG_API_URL'] ?? '',
            '/'
        );
    }

    public static function token(): string
    {
        return $_ENV['BAKONG_TOKEN'] ?? '';
    }

    public static function accountId(): string
    {
        return $_ENV['BAKONG_ACCOUNT_ID'] ?? '';
    }

    public static function merchantName(): string
    {
        return $_ENV['BAKONG_MERCHANT_NAME'] ?? '';
    }

    public static function city(): string
    {
        return $_ENV['BAKONG_CITY'] ?? '';
    }
}