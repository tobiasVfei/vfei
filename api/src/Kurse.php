<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/APICore.php';
require_once __DIR__ . '/Validation.php';


/**
 * Validiert die Eingabedaten für Kurse und gibt ein sauberes, bereinigtes Array zurück.
 * @param object|null $data
 * @return array
 * @throws Exception
 */
function validateAndPrepareKursData(?object $data): array
{
    if (!$data) {
        throw new Exception("Ungültige Eingabedaten.", 400);
    }

    $required = ['kursnummer', 'kursthema', 'inhalt', 'fk_id_dozent', 'startdatum', 'enddatum', 'dauer'];
    foreach ($required as $f) {
        if (!isset($data->$f) || (is_string($data->$f) && empty(trim($data->$f)))) {
             throw new Exception("Das Feld '$f' ist erforderlich und darf nicht leer sein.", 400);
        }
    }

    // Typ- und Format-Validierung
    if (!filter_var($data->fk_id_dozent, FILTER_VALIDATE_INT) || $data->fk_id_dozent <= 0) {
        throw new Exception("Ungültige 'fk_id_dozent'. Muss eine positive Zahl sein.", 400);
    }

    if (!Validation::validateDate(trim($data->startdatum)) || !Validation::validateDate(trim($data->enddatum))) {
        throw new Exception("Ungültiges 'startdatum' oder 'enddatum'. Erwartetes Format: YYYY-MM-DD.", 400);
    }

    // Daten bereinigen (Sanitize) und Felder zusammenstellen
    $fields = [
        'kursnummer'    => htmlspecialchars(strip_tags(trim($data->kursnummer))),
        'kursthema'     => htmlspecialchars(strip_tags(trim($data->kursthema))),
        'inhalt'        => htmlspecialchars(strip_tags(trim($data->inhalt))),
        'fk_id_dozent'  => filter_var($data->fk_id_dozent, FILTER_VALIDATE_INT),
        'startdatum'    => trim($data->startdatum),
        'enddatum'      => trim($data->enddatum),
        'dauer'         => htmlspecialchars(strip_tags(trim($data->dauer))),
    ];

    return $fields;
}

// --- HAUPT-DISPATCHER (LOKAL) ---

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(204);
    exit;
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
                $core->readAll('startdatum DESC'); // Standardsortierung wie im alten Code
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