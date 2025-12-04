<?php

require_once __DIR__ . '/Validator.php';

class BenutzerValidator
{
    /**
     * Validates and prepares the user data, including email validation and password hashing.
     *
     * @param object|null $data The raw input data (JSON object).
     * @param bool $isUpdate True if the operation is an update (password is optional).
     * @return array The sanitized and prepared data array.
     * @throws Exception If required fields are missing or formats are invalid (HTTP 400 Bad Request).
     */
    public static function validateAndPrepareBenutzerData(?object $data, bool $isUpdate): array
    {
        if (!$data) {
            throw new Exception("Invalid input data.", 400);
        }

        $fields = [];

        if (isset($data->email)) {
            if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email address.", 400);
            }
            $fields['email'] = trim($data->email);
        } elseif (!$isUpdate) {
            throw new Exception("The 'email' field is required.", 400);
        }

        if (isset($data->password)) {
            $pw = trim($data->password);
            if (strlen($pw) < 8) {
                throw new Exception("The password must be at least 8 characters long.", 400);
            }
            $fields['password_hash'] = password_hash($pw, PASSWORD_DEFAULT);
        } elseif (!$isUpdate && !isset($data->password_hash)) {
            // A password or hash must be present during creation
            throw new Exception("The 'password' field is required.", 400);
        }

        return $fields;
    }
}