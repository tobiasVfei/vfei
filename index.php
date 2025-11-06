<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS'); 
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include_once './api/config/Database.php';
include_once './api/models/Laender.php';
include_once './api/models/Lehrbetriebe.php'; // <-- HINZUGEFÜGT

try {
    $db = Database::connect();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Datenbankverbindung fehlgeschlagen: ' . $e->getMessage()]);
    exit;
}

$path_info = $_GET['path'] ?? ''; 
$path_parts = explode('/', $path_info);

$resource = $path_parts[0] ?? null; 
$id = (int)($path_parts[1] ?? 0);     
$method = $_SERVER['REQUEST_METHOD']; 

$data = ($method === 'POST' || $method === 'PUT') ? json_decode(file_get_contents('php://input')) : (object)[];


switch ($resource) {
    
    case 'laender':
        // **Laender-Logik ist hier komplett**
        $laenderModel = new Laender($db);
        
        if ($method === 'GET') {
            if ($id > 0) {
                $result = $laenderModel->readOne($id);
                if ($result) {
                    http_response_code(200);
                    echo json_encode($result);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Land nicht gefunden.']);
                }
            } else {
                $result = $laenderModel->readAll();
                http_response_code(200);
                echo json_encode($result);
            }
        } 
        elseif ($method === 'POST') {
            if (!$data || empty(trim($data->country))) {
                http_response_code(400); 
                echo json_encode(['message' => 'Fehlende oder ungültige Daten. "country" wird benötigt.']);
            } else {
                $newId = $laenderModel->create($data);
                
                if ($newId) {
                    http_response_code(201); 
                    echo json_encode(['message' => 'Land erfolgreich erstellt.', 'id_country' => $newId, 'country' => $data->country]);
                } else {
                    http_response_code(500); 
                    echo json_encode(['message' => 'Land konnte nicht erstellt werden.']);
                }
            }
        }
        elseif ($method === 'PUT') {
            if ($id <= 0 || !$data || empty(trim($data->country))) {
                http_response_code(400);
                echo json_encode(['message' => 'Ungültige ID oder fehlende Daten für Update.']);
            } elseif ($laenderModel->update($id, $data)) {
                http_response_code(200);
                echo json_encode(['message' => 'Land erfolgreich aktualisiert.']);
            } else {
                http_response_code(404);
                echo json_encode(['message' => 'Land nicht gefunden oder Update fehlgeschlagen.']);
            }
        }
        elseif ($method === 'DELETE') {
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['message' => 'Ungültige ID angegeben.']);
            } elseif ($laenderModel->delete($id)) {
                http_response_code(204); 
            } else {
                http_response_code(409); 
                echo json_encode(['message' => 'Land kann nicht gelöscht werden, da es noch verwendet wird.']);
            }
        }
        else {
            http_response_code(405);
            echo json_encode(['message' => 'Methode ' . $method . ' ist für /laender nicht erlaubt.']);
        }
        break; // ENDE des 'laender' Case

    case 'lehrbetriebe':
        // START des Lehrbetriebe CRUD
        $lehrbetriebeModel = new Lehrbetriebe($db);

        if ($method === 'GET') {
            if ($id > 0) {
                $result = $lehrbetriebeModel->readOne($id);
                if ($result) {
                    http_response_code(200);
                    echo json_encode($result);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Lehrbetrieb nicht gefunden.']);
                }
            } else {
                $result = $lehrbetriebeModel->readAll();
                http_response_code(200);
                echo json_encode($result);
            }
        }
        elseif ($method === 'POST') {
            // Validierung: Firma ist das NOT NULL Feld
            if (!$data || empty(trim($data->firma))) {
                http_response_code(400);
                echo json_encode(['message' => 'Fehlende oder ungültige Daten. "firma" wird benötigt.']);
            } else {
                $newId = $lehrbetriebeModel->create($data);
                
                if ($newId) {
                    http_response_code(201);
                    echo json_encode(['message' => 'Lehrbetrieb erfolgreich erstellt.', 'id_lehrbetrieb' => $newId, 'firma' => $data->firma]);
                } else {
                    http_response_code(500);
                    echo json_encode(['message' => 'Lehrbetrieb konnte nicht erstellt werden.']);
                }
            }
        }
        elseif ($method === 'PUT') {
            // Validierung: ID und Firma sind PFLICHT
            if ($id <= 0 || !$data || empty(trim($data->firma))) {
                http_response_code(400);
                echo json_encode(['message' => 'Ungültige ID oder fehlende Daten für Update. "firma" wird benötigt.']);
            } elseif ($lehrbetriebeModel->update($id, $data)) {
                http_response_code(200);
                echo json_encode(['message' => 'Lehrbetrieb erfolgreich aktualisiert.']);
            } else {
                http_response_code(404);
                echo json_encode(['message' => 'Lehrbetrieb nicht gefunden oder Update fehlgeschlagen.']);
            }
        }
        elseif ($method === 'DELETE') {
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['message' => 'Ungültige ID angegeben.']);
            } elseif ($lehrbetriebeModel->delete($id)) {
                http_response_code(204); 
            } else {
                // Konflikt, da Lernende zugeordnet sein könnten (FOREIGN KEY)
                http_response_code(409); 
                echo json_encode(['message' => 'Lehrbetrieb kann nicht gelöscht werden, da er noch Lernende zugeordnet hat.']);
            }
        }
        else {
            http_response_code(405);
            echo json_encode(['message' => 'Methode ' . $method . ' ist für /lehrbetriebe nicht erlaubt.']);
        }
        break; // ENDE des 'lehrbetriebe' Case

    case 'lernende':
        http_response_code(501);
        echo json_encode(['message' => 'Endpunkt Lernende noch nicht implementiert.']);
        break;

    default:
        http_response_code(404);
        echo json_encode(['message' => 'Endpunkt ' . $resource . ' nicht gefunden.']);
        break;
}