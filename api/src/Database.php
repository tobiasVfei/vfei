<?php

/**
 * Singleton class for managing the database connection via PDO.
 * Ensures only one consistent PDO instance is available throughout the application.
 */
class Database {

    /**
     * @var string The database hostname.
     * @todo Consider making this property static and accessing it directly to avoid unnecessary object instantiation within connect().
     */
    private string $host = '127.0.0.1';
    /**
     * @var string The database name.
     */
    private string $db_name = 'db_ausbildung';
    /**
     * @var string The database username.
     */
    private string $username = 'root';
    /**
     * @var string The database password.
     */
    private string $password = '';
    /**
     * @var string The connection charset.
     */
    private string $charset = 'utf8mb4';

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