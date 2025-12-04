<?php

/**
 * Validator class for Country (Land) data.
 */
class CountryValidator
{
    /**
     * Validates and prepares the data for a new country (Land) entry.
     *
     * Checks for the required 'country' field and sanitizes its string value.
     *
     * @param object|null $data The raw input data from json_decode.
     * @return array<string, string> A sanitized array containing the 'country' field.
     * @throws \Exception On invalid or missing data (HTTP 400).
     */
    public static function validateAndPrepare(?object $data): array
    {
        if (!$data) {
            throw new Exception("Ungültige Eingabedaten.", 400);
        }

        if (!isset($data->country) || empty(trim($data->country))) {
            throw new Exception("Das Feld 'country' ist erforderlich und darf nicht leer sein.", 400);
        }

        $fields = [
            'country' => strip_tags(trim($data->country)),
        ];

        return $fields;
    }
}