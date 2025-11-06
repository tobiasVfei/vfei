<?php

class Database {
    
    private string $host = '127.0.0.1';
    private string $db_name = 'db_ausbildung';
    private string $username = 'root';
    private string $password = '';
    private string $charset = 'utf8mb4';

    private static ?PDO $conn = null;

    public static function connect(): PDO {
        if (self::$conn) {
            return self::$conn;
        }

        $dsn = "mysql:host=" . (new self)->host . ";dbname=" . (new self)->db_name . ";charset=" . (new self)->charset;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            self::$conn = new PDO($dsn, (new self)->username, (new self)->password, $options);
            return self::$conn;
        } catch (PDOException $e) {
            throw new PDOException("Connection Error: " . $e->getMessage(), (int)$e->getCode());
        }
    }
}