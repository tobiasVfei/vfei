<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../APICore.php';

$requestMethod = $_SERVER["REQUEST_METHOD"];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    $pdo = Database::connect();
    $core = new APICore($pdo, 'tbl_benutzer', 'id_benutzer', false);

    switch ($requestMethod) {
        case 'GET':
            if ($id !== false && $id > 0) {
                $stmt = $pdo->prepare("SELECT id_benutzer, email, created_at FROM tbl_benutzer WHERE id_benutzer = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch();

                if ($item) {
                    http_response_code(200);
                    echo json_encode($item);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => "Benutzer mit ID $id nicht gefunden."]);
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
            $fields = validateAndPrepareBenutzerData($data, false);
            $core->create($fields);
            break;

        case 'PUT':
            if ($id === false || $id <= 0) {
                throw new Exception("Ungültige ID für Update angegeben.", 400);
            }
            $data = json_decode(file_get_contents("php://input"));
            $fields = validateAndPrepareBenutzerData($data, true);
            $core->update($id, $fields);
            break;

        case 'DELETE':
            if ($id === false || $id <= 0) {
                throw new Exception("Ungültige ID für Löschen angegeben.", 400);
            }
            $core->delete($id);
            break;

        default:
            throw new Exception("Methode nicht erlaubt.", 405);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => "Datenbankfehler: " . $e->getMessage()]);
} catch (Exception $e) {
    $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
    http_response_code($statusCode);
    echo json_encode(['message' => $e->getMessage()]);
}

function validateAndPrepareBenutzerData(?object $data, bool $isUpdate): array
{
    if (!$data) {
        throw new Exception("Ungültige Eingabedaten.", 400);
    }

    $fields = [];

    if (isset($data->email)) {
        if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Ungültige E-Mail-Adresse.", 400);
        }
        $fields['email'] = trim($data->email);
    } elseif (!$isUpdate) {
        throw new Exception("Das Feld 'email' ist erforderlich.", 400);
    }

    if (isset($data->password)) {
        $pw = trim($data->password);
        if (strlen($pw) < 8) {
            throw new Exception("Das Passwort muss mindestens 8 Zeichen lang sein.", 400);
        }
        $fields['password_hash'] = password_hash($pw, PASSWORD_DEFAULT);
    } elseif (!$isUpdate) {
        throw new Exception("Das Feld 'password' ist erforderlich.", 400);
    }

    return $fields;
}