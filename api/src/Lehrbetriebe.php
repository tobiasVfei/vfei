<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/APICore.php';

$requestMethod = $_SERVER["REQUEST_METHOD"];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    $pdo = Database::connect();
    $core = new APICore($pdo, 'tbl_lehrbetriebe', 'id_lehrbetrieb');

    switch ($requestMethod) {
        case 'GET':
            if ($id !== false && $id > 0) {
                $core->readById($id);
            } else {
                $core->readAll('firma');
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"));
            $fields = validateAndPrepareLehrbetriebData($data);
            $core->create($fields);
            break;

        case 'PUT':
            if ($id === false || $id <= 0) {
                throw new Exception("Ungültige ID für Update angegeben.", 400);
            }
            $data = json_decode(file_get_contents("php://input"));
            $fields = validateAndPrepareLehrbetriebData($data);
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
    echo json;
    }

function validateAndPrepareLehrbetriebData(?object $data): array
{
    if (!$data) {
        throw new Exception("Ungültige Eingabedaten.", 400);
    }

    $required = ['firma', 'strasse', 'plz', 'ort', 'land_id'];
    foreach ($required as $f) {
        if (!isset($data->$f) || (is_string($data->$f) && empty(trim($data->$f)))) {
             throw new Exception("Das Feld '$f' ist erforderlich und darf nicht leer sein.", 400);
        }
    }

    if (!filter_var($data->land_id, FILTER_VALIDATE_INT) || $data->land_id <= 0) {
        throw new Exception("Ungültige 'land_id'. Muss eine positive Zahl sein.", 400);
    }

    $fields = [
        'firma'     => htmlspecialchars(strip_tags(trim($data->firma))),
        'strasse'   => htmlspecialchars(strip_tags(trim($data->strasse))),
        'plz'       => htmlspecialchars(strip_tags(trim($data->plz))),
        'ort'       => htmlspecialchars(strip_tags(trim($data->ort))),
        'land_id'   => filter_var($data->land_id, FILTER_VALIDATE_INT),
    ];

    return array_filter($fields, fn($value) => !is_null($value));
}