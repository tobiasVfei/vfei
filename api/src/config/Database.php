<?php

/**
 * Singleton class for managing the database connection via PDO.
 * Ensures only one consistent PDO instance is available throughout the application.
 */
class Database {

    /**
     * @var string The database hostname.
     */
    private static string $host = '127.0.0.1';
    /**
     * @var string The database name.
     */
    private static string $db_name = 'db_ausbildung';
    /**
     * @var string The database username.
     */
    private static string $username = 'root';
    /**
     * @var string The database password.
     */
    private static string $password = '';
    /**
     * @var string The connection charset.
     */
    private static string $charset = 'utf8mb4';

    /**
     * @var PDO|null The static instance of the PDO connection (Singleton).
     */
    private static ?PDO $conn = null;

    /**
     * Establishes a PDO connection to the database or returns the existing connection.
     * Sets important PDO options like ERRMODE_EXCEPTION for robust error handling.
     *
     * @return PDO The active PDO connection instance.
     * @throws \PDOException If the connection attempt fails.
     */
    public static function connect(): PDO {
        if (self::$conn) {
            return self::$conn;
        }

        // Use static properties for DSN construction
        $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db_name . ";charset=" . self::$charset;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            self::$conn = new PDO($dsn, self::$username, self::$password, $options);
            return self::$conn;
        } catch (PDOException $e) {
            // Log error message or handle more gracefully in production
            throw new PDOException("Connection Error: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Prevents external cloning of the instance (part of the Singleton pattern).
     */
    private function __clone() {}

    /**
     * Prevents external instantiation (part of the Singleton pattern).
     */
    private function __construct() {}
}