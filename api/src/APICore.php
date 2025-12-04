<?php

/**
 * Core class for REST API processing.
 * Provides generic CRUD (Create, Read, Update, Delete) operations for a single database table.
 * Manages HTTP headers and standard error responses.
 */
class APICore
{
    /**
     * @var PDO The PDO connection object to the database.
     */
    private PDO $pdo;

    /**
     * @var string The name of the database table (e.g., 'tbl_dozenten').
     */
    private string $tableName;

    /**
     * @var string The name of the primary key field (e.g., 'id_dozent').
     */
    private string $idField;

    /**
     * Constructor for initializing the APICore.
     * Sets the PDO connection, table name, and primary key field.
     * Automatically sets the required HTTP headers.
     *
     * @param PDO $pdo The active PDO connection.
     * @param string $tableName The target table name.
     * @param string $idField The name of the primary key field.
     */
    public function __construct(PDO $pdo, string $tableName, string $idField)
    {
        $this->pdo = $pdo;
        $this->tableName = $tableName;
        $this->idField = $idField;

        self::setupHeaders();
    }

    /**
     * Sets up necessary HTTP headers for API communication (CORS, Content-Type).
     * Handles the preflight OPTIONS request by sending a 204 response and exiting.
     *
     * @return void
     */
    public static function setupHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    /**
     * Sends a standardized JSON error response and terminates script execution.
     *
     * @param int $statusCode The HTTP status code (e.g., 400, 404, 500).
     * @param string $message The error message to be displayed.
     * @return void
     */
    private static function sendErrorResponse(int $statusCode, string $message): void
    {
        http_response_code($statusCode);
        echo json_encode(['message' => $message]);
        exit;
    }

    /**
     * Retrieves all records from the target table.
     * Accepts an optional whitelist for sorting columns to prevent SQL injection.
     *
     * @param string|null $orderBy Optional: Column name to sort by.
     * @param array $allowedSortColumns Optional: List of allowed columns for sorting.
     * @return void Sends JSON output and HTTP 200 code.
     */
    public function readAll(?string $orderBy = null, array $allowedSortColumns = []): void
    {
        try {
            $orderSql = "";

            if ($orderBy && !empty($allowedSortColumns)) {
                if (in_array($orderBy, $allowedSortColumns)) {
                    $orderSql = "ORDER BY " . $orderBy;
                }
            } elseif ($orderBy && empty($allowedSortColumns)) {
                // Fallback for legacy calls without whitelist
                $orderSql = "ORDER BY " . $orderBy;
            }

            $stmt = $this->pdo->prepare("SELECT * FROM $this->tableName $orderSql");
            $stmt->execute();
            $data = $stmt->fetchAll();

            http_response_code(200);
            echo json_encode($data);
        } catch (PDOException $e) {
            self::sendErrorResponse(500, "Server error while retrieving data: " . $e->getMessage());
        }
    }

    /**
     * Retrieves a single record by its primary key ID.
     *
     * @param int $id The ID of the record to retrieve.
     * @return void Sends JSON output and HTTP 200 or 404 code.
     */
    public function readById(int $id): void
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM $this->tableName WHERE $this->idField = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch();

            if ($item) {
                http_response_code(200);
                echo json_encode($item);
            } else {
                self::sendErrorResponse(404, "No entry found with ID $id.");
            }
        } catch (PDOException $e) {
            self::sendErrorResponse(500, "Server error while retrieving entry.");
        }
    }

    /**
     * Creates a new record in the database using prepared statement.
     * Validates column names against allowed characters.
     *
     * @param array<string, mixed> $fields An associative array of field names and values.
     * @return void Sends JSON output and HTTP 201 code with the new ID.
     */
    public function create(array $fields): void
    {
        $this->validateColumnNames(array_keys($fields));

        $fieldNames = implode(', ', array_keys($fields));
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));

        try {
            $sql = "INSERT INTO $this->tableName ($fieldNames) VALUES ($placeholders)";
            $stmt = $this->pdo->prepare($sql);

            if ($stmt->execute(array_values($fields))) {
                $lastId = $this->pdo->lastInsertId();
                http_response_code(201);
                echo json_encode([
                    'message' => 'Entry successfully created.',
                    $this->idField => $lastId
                ]);
            } else {
                self::sendErrorResponse(500, "Error creating entry.");
            }
        } catch (PDOException $e) {
            $this->handlePDOException($e, "creation");
        }
    }

    /**
     * Updates an existing record identified by its ID.
     * Validates column names against allowed characters.
     *
     * @param int $id The ID of the record to update.
     * @param array<string, mixed> $fields An associative array of field names and new values.
     * @return void Sends JSON output and HTTP 200 or 404 code.
     */
    public function update(int $id, array $fields): void
    {
        if (empty($fields)) {
            self::sendErrorResponse(400, "No fields provided for update.");
        }

        $this->validateColumnNames(array_keys($fields));

        $setClauses = array_map(fn($key) => "$key = ?", array_keys($fields));
        $setClause = implode(', ', $setClauses);
        $params = array_values($fields);
        $params[] = $id;

        try {
            if (!$this->checkIfIdExists($id)) {
                self::sendErrorResponse(404, "No entry found with ID $id. Update not possible.");
            }

            $sql = "UPDATE $this->tableName SET $setClause WHERE $this->idField = ?";
            $stmt = $this->pdo->prepare($sql);

            if ($stmt->execute($params)) {
                http_response_code(200);
                echo json_encode(['message' => 'Entry successfully updated.']);
            } else {
                self::sendErrorResponse(500, "Error updating entry.");
            }
        } catch (PDOException $e) {
            $this->handlePDOException($e, "update");
        }
    }

    /**
     * Deletes a record by its primary key ID.
     *
     * @param int $id The ID of the record to delete.
     * @return void Sends JSON output and HTTP 200 or 404/409 code.
     */
    public function delete(int $id): void
    {
        try {
            if (!$this->checkIfIdExists($id)) {
                self::sendErrorResponse(404, "No entry found with ID $id. Delete not possible.");
            }

            $sql = "DELETE FROM $this->tableName WHERE $this->idField = ?";
            $stmt = $this->pdo->prepare($sql);

            if ($stmt->execute([$id])) {
                http_response_code(200);
                echo json_encode(['message' => 'Entry successfully deleted.']);
            } else {
                self::sendErrorResponse(500, "Error deleting entry.");
            }
        } catch (PDOException $e) {
            $msg = "Server error while deleting.";
            if ($e->getCode() == 23000) {
                $msg = "Conflict: Entry cannot be deleted because it is still referenced.";
                self::sendErrorResponse(409, $msg);
            }
            self::sendErrorResponse(500, $msg);
        }
    }

    /**
     * Checks if a record with the given ID exists in the current table.
     *
     * @param int $id The ID to check.
     * @return bool True if the record exists, otherwise False.
     */
    private function checkIfIdExists(int $id): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM $this->tableName WHERE $this->idField = ?");
        $stmt->execute([$id]);
        return $stmt->fetchColumn() !== false;
    }

    /**
     * Ensures that column names only contain valid characters (a-z, A-Z, 0-9, _).
     * Prevents SQL Injection through array keys.
     *
     * @param array $columns List of column names to check.
     * @throws void Terminates with 500 error if invalid column is found.
     */
    private function validateColumnNames(array $columns): void
    {
        foreach ($columns as $col) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $col)) {
                self::sendErrorResponse(500, "Security Violation: Invalid column name '$col'.");
            }
        }
    }

    /**
     * Centralized exception handling to hide internal DB errors from the client.
     *
     * @param PDOException $e
     * @param string $action Context (e.g. "creation", "update")
     */
    private function handlePDOException(PDOException $e, string $action): void
    {
        $msg = "Server error during $action.";
        if ($e->getCode() == 23000) {
            $msg = "Data conflict: A unique value already exists or a relationship is invalid.";
        }
        self::sendErrorResponse(500, $msg);
    }
}