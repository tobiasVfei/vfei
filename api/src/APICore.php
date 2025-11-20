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
     *
     * @param PDO $pdo The active PDO connection.
     * @param string $tableName The target table name.
     * @param string $idField The name of the primary key field.
     * * @todo CRITICAL: Remove the call to `self::setupHeaders()` from the constructor. Headers should be set globally once at the script entry point.
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
     *
     * @param string|null $orderBy Optional SQL clause to order the results (e.g., 'nachname, vorname').
     * @return void Sends JSON output and HTTP 200 code.
     */
    public function readAll(?string $orderBy = null): void
    {
        try {
            $orderSql = $orderBy ? "ORDER BY " . $orderBy : "";
            $stmt = $this->pdo->prepare("SELECT * FROM $this->tableName $orderSql");
            $stmt->execute();
            $data = $stmt->fetchAll();

            http_response_code(200);
            echo json_encode($data);
        } catch (PDOException $e) {
            self::sendErrorResponse(500, "Serverfehler beim Abrufen von $this->tableName: " . $e->getMessage());
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
                self::sendErrorResponse(404, "Kein Eintrag mit der ID $id gefunden.");
            }
        } catch (PDOException $e) {
            self::sendErrorResponse(500, "Serverfehler beim Abrufen des Eintrags: " . $e->getMessage());
        }
    }

    /**
     * Creates a new record in the database using prepared statement.
     *
     * @param array<string, mixed> $fields An associative array of field names and values for the new record.
     * @return void Sends JSON output and HTTP 201 code with the new ID.
     */
    public function create(array $fields): void
    {
        $fieldNames = implode(', ', array_keys($fields));
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));

        try {
            $sql = "INSERT INTO $this->tableName ($fieldNames) VALUES ($placeholders)";
            $stmt = $this->pdo->prepare($sql);

            if ($stmt->execute(array_values($fields))) {
                $lastId = $this->pdo->lastInsertId();
                http_response_code(201);
                echo json_encode([
                    'message' => 'Eintrag erfolgreich erstellt.',
                    $this->idField => $lastId
                ]);
            } else {
                self::sendErrorResponse(500, "Fehler beim Erstellen des Eintrags.");
            }
        } catch (PDOException $e) {
            $msg = "Serverfehler beim Erstellen: " . $e->getMessage();
            if ($e->getCode() == 23000) {
                $msg = "Konflikt: Foreign Key-Verletzung oder Unique-Constraint-Verletzung.";
            }
            self::sendErrorResponse(500, $msg);
        }
    }

    /**
     * Updates an existing record identified by its ID.
     *
     * @param int $id The ID of the record to update.
     * @param array<string, mixed> $fields An associative array of field names and new values.
     * @return void Sends JSON output and HTTP 200 or 404 code.
     */
    public function update(int $id, array $fields): void
    {
        if (empty($fields)) {
            self::sendErrorResponse(400, "Keine Felder zum Aktualisieren übermittelt.");
        }

        $setClauses = array_map(fn($key) => "$key = ?", array_keys($fields));
        $setClause = implode(', ', $setClauses);
        $params = array_values($fields);
        $params[] = $id;

        try {
            if (!$this->checkIfIdExists($id)) {
                self::sendErrorResponse(404, "Kein Eintrag mit der ID $id gefunden. Update nicht möglich.");
            }

            $sql = "UPDATE $this->tableName SET $setClause WHERE $this->idField = ?";
            $stmt = $this->pdo->prepare($sql);

            if ($stmt->execute($params)) {
                http_response_code(200);
                echo json_encode(['message' => 'Eintrag erfolgreich aktualisiert.']);
            } else {
                self::sendErrorResponse(500, "Fehler beim Aktualisieren des Eintrags.");
            }
        } catch (PDOException $e) {
            self::sendErrorResponse(500, "Serverfehler beim Aktualisieren: " . $e->getMessage());
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
                self::sendErrorResponse(404, "Kein Eintrag mit der ID $id gefunden. Löschen nicht möglich.");
            }

            $sql = "DELETE FROM $this->tableName WHERE $this->idField = ?";
            $stmt = $this->pdo->prepare($sql);

            if ($stmt->execute([$id])) {
                http_response_code(200);
                echo json_encode(['message' => 'Eintrag erfolgreich gelöscht.']);
            } else {
                self::sendErrorResponse(500, "Fehler beim Löschen des Eintrags.");
            }
        } catch (PDOException $e) {
            $msg = "Serverfehler beim Löschen: " . $e->getMessage();
            if ($e->getCode() == 23000) {
                $msg = "Konflikt: Der Eintrag kann nicht gelöscht werden, da er noch in Verwendung ist (Foreign Key Constraint).";
            }
            self::sendErrorResponse(409, $msg);
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
}