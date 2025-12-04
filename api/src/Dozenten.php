<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/APICore.php';
require_once __DIR__ . '/Validation.php';

/**
 * Validates and prepares the data for a new lecturer (Dozent) entry.
 *
 * Checks for required fields, validates email and foreign key ID, and sanitizes string inputs.
 * Note: 'birthdate' is optional but must be in YYYY-MM-DD format if provided.
 *
 * @param object|null $data The raw input data from json_decode.
 * @return array<string, mixed> A sanitized, associative array ready for database insertion.
 * @throws \Exception On invalid or missing data (HTTP 400).
 */
function validateAndPrepareDozentData(?object $data): array
{
    if (!$data) {
        throw new Exception("Ungültige Eingabedaten.", 400);
    }

    $required = ['vorname', 'nachname', 'strasse', 'plz', 'ort', 'fk_id_land', 'email'];
    foreach ($required as $f) {
        if (!isset($data->$f) || (is_string($data->$f) && empty(trim($data->$f)))) {
            throw new Exception("Das Feld '$f' ist erforderlich und darf nicht leer sein.", 400);
        }
    }

    if (!filter_var($data->fk_id_land, FILTER_VALIDATE_INT) || $data->fk_id_land <= 0) {
        throw new Exception("Ungültige 'fk_id_land'. Muss eine positive Zahl sein.", 400);
    }

    if (!filter_var(trim($data->email), FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Ungültige E-Mail-Adresse.", 400);
    }

    $birthdate = isset($data->birthdate) ? trim($data->birthdate) : null;
    if ($birthdate && !Validation::validateDate($birthdate)) {
        throw new Exception("Ungültiges Geburtsdatum. Erwartetes Format: YYYY-MM-DD.", 400);
    }

    $fields = [
        'vorname'       => htmlspecialchars(strip_tags(trim($data->vorname))),
        'nachname'      => htmlspecialchars(strip_tags(trim($data->nachname))),
        'strasse'       => htmlspecialchars(strip_tags(trim($data->strasse))),
        'plz'           => htmlspecialchars(strip_tags(trim($data->plz))),
        'ort'           => htmlspecialchars(strip_tags(trim($data->ort))),
        'fk_id_land'    => filter_var($data->fk_id_land, FILTER_VALIDATE_INT),
        'email'         => filter_var(trim($data->email), FILTER_VALIDATE_EMAIL),
        'telefon'       => isset($data->telefon) ? htmlspecialchars(strip_tags(trim($data->telefon))) : null,
        'handy'         => isset($data->handy) ? htmlspecialchars(strip_tags(trim($data->handy))) : null,
        'birthdate'     => $birthdate,
    ];

    return array_filter($fields, fn($value) => !is_null($value));
}

$requestMethod = $_SERVER["REQUEST_METHOD"];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    $pdo = Database::connect();
    // Die Header werden hier im APICore-Konstruktor gesetzt
    $core = new APICore($pdo, 'tbl_dozenten', 'id_dozent');

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
            $fields = validateAndPrepareDozentData($data);
            $core->create($fields);
            break;

        case 'PUT':
            if ($id === false || $id <= 0) {
                throw new Exception("Ungültige ID für Update angegeben.", 400);
            }
            $data = json_decode(file_get_contents("php://input"));
            $fields = validateAndPrepareDozentData($data);
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