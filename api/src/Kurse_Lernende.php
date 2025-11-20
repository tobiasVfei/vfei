<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/APICore.php';

/**
 * Validates and prepares the junction data for Courses and Learners (tbl_kurse_lernende).
 *
 * Checks that both 'nr_kurs' and 'nr_lernende' are present and positive integers.
 * The 'note' field is optional and sanitized if present.
 *
 * @param object|null $data The raw input data from json_decode.
 * @return array<string, mixed> A sanitized, associative array ready for database insertion.
 * @throws \Exception On invalid or missing data (HTTP 400).
 */
function validateAndPrepareKursLernenderData(?object $data): array
{
    if (!$data) {
        throw new Exception("Ungültige Eingabedaten.", 400);
    }

    $required = ['nr_kurs', 'nr_lernende'];
    foreach ($required as $f) {
        if (!isset($data->$f) || empty(trim($data->$f))) {
            throw new Exception("Das Feld '$f' ist erforderlich und darf nicht leer sein.", 400);
        }
    }

    $nr_kurs = filter_var($data->nr_kurs, FILTER_VALIDATE_INT);
    $nr_lernende = filter_var($data->nr_lernende, FILTER_VALIDATE_INT);

    if (!$nr_kurs || $nr_kurs <= 0) {
        throw new Exception("Ungültige 'nr_kurs'. Muss eine positive Zahl sein.", 400);
    }
    if (!$nr_lernende || $nr_lernende <= 0) {
        throw new Exception("Ungültige 'nr_lernende'. Muss eine positive Zahl sein.", 400);
    }

    $fields = [
        'nr_kurs'     => $nr_kurs,
        'nr_lernende' => $nr_lernende,
        'note'        => isset($data->note) ? htmlspecialchars(strip_tags(trim($data->note))) : null,
    ];

    return array_filter($fields, fn($value) => !is_null($value));
}

$requestMethod = $_SERVER["REQUEST_METHOD"];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    $pdo = Database::connect();
    $core = new APICore($pdo, 'tbl_kurse_lernende', 'id_kurse_lernende');

    switch ($requestMethod) {
        case 'GET':
            if ($id !== false && $id > 0) {
                $core->readById($id);
            } else {
                $core->readAll('nr_kurs, nr_lernende');
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"));
            $fields = validateAndPrepareKursLernenderData($data);
            $core->create($fields);
            break;

        case 'PUT':
            if ($id === false || $id <= 0) {
                throw new Exception("Ungültige ID für Update angegeben.", 400);
            }
            $data = json_decode(file_get_contents("php://input"));
            $fields = validateAndPrepareKursLernenderData($data);
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