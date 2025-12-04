<?php

/**
 * Validator class for Apprenticeship Company (Lehrbetrieb) data.
 */
class LehrbetriebeValidator
{
    /**
     * Validates and prepares the data for an apprenticeship company (Lehrbetrieb) entry.
     *
     * Checks for required fields (company name, address, foreign key ID) and ensures the foreign key is a positive integer.
     * Sanitizes all string fields.
     *
     * @param object|null $data The raw input data from json_decode.
     * @return array<string, mixed> A sanitized, associative array ready for database insertion.
     * @throws \Exception On invalid or missing data (HTTP 400).
     */
    public static function validateAndPrepare(?object $data): array
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
            'firma'     => strip_tags(trim($data->firma)),
            'strasse'   => strip_tags(trim($data->strasse)),
            'plz'       => strip_tags(trim($data->plz)),
            'ort'       => strip_tags(trim($data->ort)),
            'land_id'   => filter_var($data->land_id, FILTER_VALIDATE_INT),
        ];

        return array_filter($fields, fn($value) => !is_null($value));
    }
}