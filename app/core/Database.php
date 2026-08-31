<?php

class Database
{
    private string $host = 'mysql';
    private string $username = 'php_user';
    private string $password = 'php_password';
    private string $database = 'php_api';

    public function connect(): mysqli
    {
        $db = new mysqli(
            $this->host,
            $this->username,
            $this->password,
            $this->database
        );

        if ($db->connect_error) {
            die('Database connection failed: ' . $db->connect_error);
        }

        $db->set_charset('utf8mb4');

        return $db;
    }
}