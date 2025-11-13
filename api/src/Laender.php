<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/APICore.php';

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
                $core->readAll('country');
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
            break;
    }

} catch (PDOException $e) {
    // Fehler bei Datenbankverbindung (PDOException)
    http_response_code(500);
    echo json_encode(['message' => "Datenbankfehler: " . $e->getMessage()]);
} catch (Exception $e) {
    // Fehler bei Validierung (geworfene Exception) oder API-Core-Fehler
    $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
    http_response_code($statusCode);
    echo json_encode(['message' => $e->getMessage()]);
}

function validateAndPrepareCountryData(?object $data): array
{
    if (!$data) {
        throw new Exception("Ungültige Eingabedaten.", 400);
    }

    // Pflichtfeld prüfen (country)
    if (!isset($data->country) || empty(trim($data->country))) {
         throw new Exception("Das Feld 'country' ist erforderlich und darf nicht leer sein.", 400);
    }

    // Daten bereinigen (Sanitize) und Felder zusammenstellen
    $fields = [
        'country' => htmlspecialchars(strip_tags(trim($data->country))),
    ];

    return $fields; // Keine optionalen Felder in tbl_countries
}