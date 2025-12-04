<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/APICore.php';

/**
 * Validates and prepares the data for a new course (Kurs) entry.
 *
 * Checks for required fields ('kursnummer', 'kursthema', 'fk_id_dozent'), validates the Dozent ID,
 * and sanitizes all string fields. Optional date fields are passed through as strings.
 *
 * @param object|null $data The raw input data from json_decode.
 * @return array<string, mixed> A sanitized, associative array ready for database insertion.
 * @throws \Exception On invalid or missing data (HTTP 400).
 */
function validateAndPrepareKursData(?object $data): array
{
    if (!$data) {
        throw new Exception("Ungültige Eingabedaten.", 400);
    }

    $required = ['kursnummer', 'kursthema', 'fk_id_dozent'];
    foreach ($required as $f) {
        if (!isset($data->$f) || (is_string($data->$f) && empty(trim($data->$f)))) {
            throw new Exception("Das Feld '$f' ist erforderlich und darf nicht leer sein.", 400);
        }
    }

    if (!filter_var($data->fk_id_dozent, FILTER_VALIDATE_INT) || $data->fk_id_dozent <= 0) {
        throw new Exception("Ungültige 'fk_id_dozent'. Muss eine positive Zahl sein.", 400);
    }

    $fields = [
        'kursnummer'    => strip_tags(trim($data->kursnummer)),
        'kursthema'     => strip_tags(trim($data->kursthema)),
        'fk_id_dozent'  => filter_var($data->fk_id_dozent, FILTER_VALIDATE_INT),
        'inhalt'        => isset($data->inhalt) ? strip_tags(trim($data->inhalt)) : null,
        'startdatum'    => isset($data->startdatum) ? strip_tags(trim($data->startdatum)) : null,
        'enddatum'      => isset($data->enddatum) ? strip_tags(trim($data->enddatum)) : null,
        'dauer'         => isset($data->dauer) ? filter_var($data->dauer, FILTER_VALIDATE_FLOAT) : null,
    ];

    return array_filter($fields, fn($value) => !is_null($value));
}


$requestMethod = $_SERVER["REQUEST_METHOD"];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    $pdo = Database::connect();
    $core = new APICore($pdo, 'tbl_kurse', 'id_kurs');

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
            $fields = validateAndPrepareKursData($data);
            $core->create($fields);
            break;

        case 'PUT':
            if ($id === false || $id <= 0) {
                throw new Exception("Ungültige ID für Update angegeben.", 400);
            }
            $data = json_decode(file_get_contents("php://input"));
            $fields = validateAndPrepareKursData($data);
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