<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../APICore.php';
require_once __DIR__ . '/../validators/Validator.php';

$requestMethod = $_SERVER["REQUEST_METHOD"];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    $pdo = Database::connect();
    $core = new APICore($pdo, 'tbl_lehrbetriebe', 'id_lehrbetrieb');

    $rules = [
        'firma'   => 'required|string',
        'strasse' => 'required|string',
        'plz'     => 'required|string',
        'ort'     => 'required|string',
        'land_id' => 'required|int|positive'
    ];

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
            $fields = Validator::validate($data, $rules);
            $core->create($fields);
            break;

        case 'PUT':
            if ($id === false || $id <= 0) {
                throw new Exception("Ungültige ID.", 400);
            }
            $data = json_decode(file_get_contents("php://input"));
            $fields = Validator::validate($data, $rules);
            $core->update($id, $fields);
            break;

        case 'DELETE':
            if ($id === false || $id <= 0) {
                throw new Exception("Ungültige ID.", 400);
            }
            $core->delete($id);
            break;

        default:
            throw new Exception("Methode nicht erlaubt.", 405);
    }

} catch (Exception $e) {
    $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
    http_response_code($code);
    echo json_encode(['message' => $e->getMessage()]);
}