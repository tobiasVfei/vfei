<?php

/**
 * Controller for managing User accounts (Benutzer).
 * Supports CRUD operations. Custom logic is used here instead of BaseController
 * to handle specific security requirements like password hashing and selective field exposure.
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../system/APICore.php';
require_once __DIR__ . '/../system/JwtHandler.php';
require_once __DIR__ . '/../validators/BenutzerValidator.php';

$requestMethod = $_SERVER["REQUEST_METHOD"];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    $pdo = Database::connect();
    $core = new APICore($pdo, 'tbl_benutzer', 'id_benutzer', false);

    switch ($requestMethod) {
        case 'GET':
            JwtHandler::verifyAuthHeader();
            if ($id !== false && $id > 0) {
                $stmt = $pdo->prepare("SELECT id_benutzer, email, created_at FROM tbl_benutzer WHERE id_benutzer = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch();

                if ($item) {
                    http_response_code(200);
                    echo json_encode($item);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => "User with ID $id not found."]);
                }
            } else {
                $stmt = $pdo->prepare("SELECT id_benutzer, email, created_at FROM tbl_benutzer ORDER BY id_benutzer");
                $stmt->execute();
                $data = $stmt->fetchAll();

                http_response_code(200);
                echo json_encode($data);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"));
            $fields = BenutzerValidator::validateAndPrepareBenutzerData($data, false);
            $core->create($fields);
            break;

        case 'PUT':
            JwtHandler::verifyAuthHeader();
            if ($id === false || $id <= 0) {
                throw new Exception("Invalid ID for update.", 400);
            }
            $data = json_decode(file_get_contents("php://input"));
            $fields = BenutzerValidator::validateAndPrepareBenutzerData($data, true);
            $core->update($id, $fields);
            break;

        case 'DELETE':
            JwtHandler::verifyAuthHeader();
            if ($id === false || $id <= 0) {
                throw new Exception("Invalid ID for deletion.", 400);
            }
            $core->delete($id);
            break;

        default:
            throw new Exception("Method not allowed.", 405);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => "Database error: " . $e->getMessage()]);
} catch (Exception $e) {
    $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
    http_response_code($statusCode);
    echo json_encode(['message' => $e->getMessage()]);
}