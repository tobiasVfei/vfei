<?php

/**
 * Authentication Controller.
 * Handles user login by verifying credentials (email/password) and issuing a JWT token.
 * This endpoint does NOT require authentication to access.
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../system/JwtHandler.php';

// Set headers for CORS and JSON content type
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed. Only POST is supported.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email) || !isset($data->password)) {
    http_response_code(400);
    echo json_encode(['message' => 'Incomplete data. Email and password are required.']);
    exit;
}

try {
    $pdo = Database::connect();

    // Fetch user by email
    $stmt = $pdo->prepare("SELECT id_benutzer, password_hash FROM tbl_benutzer WHERE email = ?");
    $stmt->execute([trim($data->email)]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify password
    if ($user && password_verify($data->password, $user['password_hash'])) {

        $jwtHandler = new JwtHandler();
        $token = $jwtHandler->generateToken([
            'id' => $user['id_benutzer'],
            'email' => $data->email
        ]);

        http_response_code(200);
        echo json_encode([
            'message' => 'Login successful.',
            'token' => $token,
            'expires_in' => 3600
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['message' => 'Login failed. Invalid email or password.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Server error: ' . $e->getMessage()]);
}