<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../APICore.php';
require_once __DIR__ . '/../validators/Lehrbetrieb_LernendeValidator.php';

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
                $lehrbetrieb_id = filter_input(INPUT_GET, 'lehrbetrieb_id', FILTER_VALIDATE_INT);
                $lernender_id = filter_input(INPUT_GET, 'lernender_id', FILTER_VALIDATE_INT);

                if ($lehrbetrieb_id || $lernender_id) {
                    throw new Exception("Spezifische Filter-GETs müssen in APICore/einem Model implementiert werden.", 501);
                }

                $core->readAll();
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"));
            $fields = LehrbetriebLernendeValidator::validateAndPrepare($data);
            $core->create($fields);
            break;

        case 'PUT':
            if ($id === false || $id <= 0) {
                throw new Exception("Ungültige ID für Update angegeben.", 400);
            }
            $data = json_decode(file_get_contents("php://input"));
            $fields = LehrbetriebLernendeValidator::validateAndPrepare($data);
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