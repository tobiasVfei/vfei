<?php

class JwtHandler
{
    private string $secret = 'passwort!';

    public static function verifyAuthHeader(): ?array
    {
        $headers = apache_request_headers();
        $token = null;

        if (isset($headers['Authorization'])) {
            $matches = [];
            if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
                $token = $matches[1];
            }
        }

        if (!$token) {
            http_response_code(401);
            echo json_encode(['message' => 'Zugriff verweigert. Kein Token vorhanden.']);
            exit;
        }

        $jwt = new JwtHandler();
        $decoded = $jwt->validateToken($token);

        if (!$decoded) {
            http_response_code(401);
            echo json_encode(['message' => 'Zugriff verweigert. Token ungültig oder abgelaufen.']);
            exit;
        }

        return $decoded;
    }

    public function generateToken(array $payload): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload['iat'] = time();
        $payload['exp'] = time() + (60 * 60);

        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secret, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public function validateToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;

        $validSignature = hash_hmac('sha256', $header . "." . $payload, $this->secret, true);
        $validSignatureEncoded = $this->base64UrlEncode($validSignature);

        if (!hash_equals($validSignatureEncoded, $signature)) {
            return null;
        }

        $data = json_decode($this->base64UrlDecode($payload), true);

        if ($data['exp'] < time()) {
            return null;
        }

        return $data;
    }

    private function base64UrlEncode($data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private function base64UrlDecode($data): string
    {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}