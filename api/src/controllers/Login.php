<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../system/JwtHandler.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Methode nicht erlaubt. Nur POST ist zulässig.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email) || !isset($data->password)) {
    http_response_code(400);
    echo json_encode(['message' => 'Unvollständige Daten. Email und Passwort werden benötigt.']);
    exit;
}

try {
    $pdo = Database::connect();

    $stmt = $pdo->prepare("SELECT id_benutzer, password_hash FROM tbl_benutzer WHERE email = ?");
    $stmt->execute([trim($data->email)]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($data->password, $user['password_hash'])) {

        $jwtHandler = new JwtHandler();
        $token = $jwtHandler->generateToken([
            'id' => $user['id_benutzer'],
            'email' => $data->email
        ]);

        http_response_code(200);
        echo json_encode([
            'message' => 'Login erfolgreich.',
            'token' => $token,
            'expires_in' => 3600
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['message' => 'Login fehlgeschlagen. Email oder Passwort falsch.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Serverfehler: ' . $e->getMessage()]);
}