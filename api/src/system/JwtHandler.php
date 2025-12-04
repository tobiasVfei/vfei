<?php

/**
 * Handles JSON Web Token (JWT) generation and validation.
 * Used to secure the API by ensuring only authenticated users can access protected endpoints.
 */
class JwtHandler
{
    /**
     * @var string The secret key used for signing the token (HS256).
     */
    private string $secret = 'passwort!';

    /**
     * Verifies the 'Authorization' header from the incoming HTTP request.
     * The extracted Bearer token is validated against the signature and expiration time.
     *
     * @return array|null The decoded payload if valid, otherwise terminates script with 401.
     */
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
            echo json_encode(['message' => 'Access denied. No token provided.']);
            exit;
        }

        $jwt = new JwtHandler();
        $decoded = $jwt->validateToken($token);

        if (!$decoded) {
            http_response_code(401);
            echo json_encode(['message' => 'Access denied. Invalid or expired token.']);
            exit;
        }

        return $decoded;
    }

    /**
     * Generates a new JWT for a given payload.
     *
     * @param array $payload The user data to encode (e.g., user ID, email).
     * @return string The complete, signed JWT string (Header.Payload.Signature).
     */
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

    /**
     * Validates a given JWT string.
     * Checks the structure, verifies the signature, and ensures the token hasn't expired.
     *
     * @param string $token The JWT string to validate.
     * @return array|null The decoded payload if the token is valid, null otherwise.
     */
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

    /**
     * Encodes data to Base64URL format (URL-safe Base64).
     *
     * @param string $data The raw data to encode.
     * @return string The Base64URL encoded string.
     */
    private function base64UrlEncode($data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    /**
     * Decodes a Base64URL encoded string.
     *
     * @param string $data The Base64URL encoded string.
     * @return string The raw decoded data.
     */
    private function base64UrlDecode($data): string
    {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}