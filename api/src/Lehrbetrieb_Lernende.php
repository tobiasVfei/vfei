<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/APICore.php';
require_once __DIR__ . '/Validation.php';


function validateAndPrepareBeziehungData(?object $data): array
{
    if (!$data) {
        throw new Exception("Ungültige Eingabedaten.", 400);
    }

    $required = ['fk_id_lehrbetrieb', 'fk_id_lernende', 'start', 'ende', 'beruf'];
    foreach ($required as $f) {
        if (!isset($data->$f) || (is_string($data->$f) && empty(trim($data->$f)) && $f !== 'beruf')) {
             throw new Exception("Das Feld '$f' ist erforderlich und darf nicht leer sein.", 400);
        }
    }

    if (!filter_var($data->fk_id_lehrbetrieb, FILTER_VALIDATE_INT) || $data->fk_id_lehrbetrieb <= 0 ||
        !filter_var($data->fk_id_lernende, FILTER_VALIDATE_INT) || $data->fk_id_lernende <= 0) {
        throw new Exception("Ungültige 'fk_id_lehrbetrieb' oder 'fk_id_lernende'. Muss eine positive Zahl sein.", 400);
    }

    if (!Validation::validateDate(trim($data->start)) || !Validation::validateDate(trim($data->ende))) {
        throw new Exception("Ungültiges 'start' oder 'ende' Datum. Erwartetes Format: YYYY-MM-DD.", 400);
    }

    $fields = [
        'fk_id_lehrbetrieb' => filter_var($data->fk_id_lehrbetrieb, FILTER_VALIDATE_INT),
        'fk_id_lernende'    => filter_var($data->fk_id_lernende, FILTER_VALIDATE_INT),
        'start'             => trim($data->start),
        'ende'              => trim($data->ende),
        'beruf'             => htmlspecialchars(strip_tags(trim($data->beruf))),
    ];

    return array_filter($fields, fn($value) => !is_null($value));
}

$requestMethod = $_SERVER["REQUEST_METHOD"];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    $pdo = Database::connect();
    $core = new APICore($pdo, 'tbl_lehrbetrieb_lernende', 'id_lehrbetrieb_lernende');

    switch ($requestMethod) {
        case 'GET':
            if ($id !== false && $id > 0) {
                $core->readById($id);
            } else {
                // Erweiterte Filterung, die wir vorher besprochen hatten
                $lehrbetrieb_id = filter_input(INPUT_GET, 'lehrbetrieb_id', FILTER_VALIDATE_INT);
                $lernender_id = filter_input(INPUT_GET, 'lernender_id', FILTER_VALIDATE_INT);

                if ($lehrbetrieb_id || $lernender_id) {
                    throw new Exception("Spezifische Filter-GETs müssen in APICore/einem Model implementiert werden.", 501);
                }

                $core->readAll('id_lehrbetrieb_lernende');
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"));
            $fields = validateAndPrepareBeziehungData($data);
            $core->create($fields);
            break;

        case 'PUT':
            if ($id === false || $id <= 0) {
                throw new Exception("Ungültige ID für Update angegeben.", 400);
            }
            $data = json_decode(file_get_contents("php://input"));
            $fields = validateAndPrepareBeziehungData($data);
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
    http_response_code(500);
    echo json_encode(['message' => "Datenbankfehler: " . $e->getMessage()]);
} catch (Exception $e) {
    $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
    http_response_code($statusCode);
    echo json_encode(['message' => $e->getMessage()]);
}