<?php

class APICore
{
    private PDO $pdo;
    private string $tableName;
    private string $idField;

    public function __construct(PDO $pdo, string $tableName, string $idField)
    {
        $this->pdo = $pdo;
        $this->tableName = $tableName;
        $this->idField = $idField;
        self::setupHeaders();
    }

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

    private static function sendErrorResponse(int $statusCode, string $message): void
    {
        http_response_code($statusCode);
        echo json_encode(['message' => $message]);
        exit;
    }

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

    private function checkIfIdExists(int $id): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM $this->tableName WHERE $this->idField = ?");
        $stmt->execute([$id]);
        return $stmt->fetchColumn() !== false;
    }
}