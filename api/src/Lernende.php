<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/APICore.php';

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
    $core = new APICore($pdo, 'tbl_lernende', 'id_lernende');

    switch ($requestMethod) {
        case 'GET':
            if ($id !== false && $id > 0) {
                $core->readById($id);
            } else {
                $core->readAll('nachname, vorname');
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"));
            $fields = validateAndPrepareLernenderData($data);
            $core->create($fields);
            break;

        case 'PUT':
            if ($id === false || $id <= 0) {
                throw new Exception("Ungültige ID für Update angegeben.", 400);
            }
            $data = json_decode(file_get_contents("php://input"));
            $fields = validateAndPrepareLernenderData($data);
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

function validateDate(string $date, string $format = 'Y-m-d'): bool
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function validateAndPrepareLernenderData(?object $data): array
{
    if (!$data) {
        throw new Exception("Ungültige Eingabedaten.", 400);
    }

    $required = ['vorname', 'nachname', 'strasse', 'plz', 'ort', 'fk_id_land', 'email', 'birthdate'];
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
    if (!validateDate(trim($data->birthdate))) {
        throw new Exception("Ungültiges Geburtsdatum. Erwartetes Format: YYYY-MM-DD.", 400);
    }

    $email_privat = isset($data->email_privat) && filter_var(trim($data->email_privat), FILTER_VALIDATE_EMAIL) ? filter_var(trim($data->email_privat), FILTER_VALIDATE_EMAIL) : null;
    $geschlecht = isset($data->geschlecht) && !empty(trim($data->geschlecht)) ? htmlspecialchars(strip_tags(trim($data->geschlecht))) : null;

    $fields = [
        'vorname'       => htmlspecialchars(strip_tags(trim($data->vorname))),
        'nachname'      => htmlspecialchars(strip_tags(trim($data->nachname))),
        'strasse'       => htmlspecialchars(strip_tags(trim($data->strasse))),
        'plz'           => htmlspecialchars(strip_tags(trim($data->plz))),
        'ort'           => htmlspecialchars(strip_tags(trim($data->ort))),
        'fk_id_land'    => filter_var($data->fk_id_land, FILTER_VALIDATE_INT),
        'email'         => filter_var(trim($data->email), FILTER_VALIDATE_EMAIL),
        'birthdate'     => trim($data->birthdate),
        'geschlecht'    => $geschlecht,
        'telefon'       => isset($data->telefon) ? htmlspecialchars(strip_tags(trim($data->telefon))) : null,
        'handy'         => isset($data->handy) ? htmlspecialchars(strip_tags(trim($data->handy))) : null,
        'email_privat'  => $email_privat,
    ];

    return array_filter($fields, fn($value) => !is_null($value));
}