<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../APICore.php';

$requestMethod = $_SERVER["REQUEST_METHOD"];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    $pdo = Database::connect();
    $core = new APICore($pdo, 'tbl_countries', 'id_country');

    switch ($requestMethod) {
        case 'GET':
            if ($id !== false && $id > 0) {
                $core->readById($id);
            } else {
                $core->readAll();
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"));
            $fields = validateAndPrepareCountryData($data);
            $core->create($fields);
            break;

        case 'PUT':
            if ($id === false || $id <= 0) {
                throw new Exception("Ungültige ID für Update angegeben.", 400);
            }
            $data = json_decode(file_get_contents("php://input"));
            $fields = validateAndPrepareCountryData($data);
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

/**
 * Validates and prepares the data for a new country (Land) entry.
 *
 * Checks for the required 'country' field and sanitizes its string value.
 *
 * @param object|null $data The raw input data from json_decode.
 * @return array<string, string> A sanitized array containing the 'country' field.
 * @throws \Exception On invalid or missing data (HTTP 400).
 */
function validateAndPrepareCountryData(?object $data): array
{
    if (!$data) {
        throw new Exception("Ungültige Eingabedaten.", 400);
    }

    if (!isset($data->country) || empty(trim($data->country))) {
        throw new Exception("Das Feld 'country' ist erforderlich und darf nicht leer sein.", 400);
    }

    $fields = [
        'country' => strip_tags(trim($data->country)),
    ];

    return $fields;
}