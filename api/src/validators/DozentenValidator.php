<?php

require_once __DIR__ . '/Validation.php';

/**
 * Validator class for Lecturer (Dozent) data.
 */
class DozentValidator
{
    /**
     * Validates and prepares the data for a new lecturer (Dozent) entry.
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

        $required = ['vorname', 'nachname', 'strasse', 'plz', 'ort', 'fk_id_land', 'email'];
        foreach ($required as $f) {
            if (!isset($data->$f) || (is_string($data->$f) && empty(trim($data->$f)))) {
                throw new Exception("Das Feld '$f' ist erforderlich und darf nicht leer sein.", 400);
            }
        }

        if (!filter_var($data->fk_id_land, FILTER_VALIDATE_INT) || $data->fk_id_land <= 0) {
            throw new Exception("Ungültige 'fk_id_land'. Muss eine positive Zahl sein.", 400);
        }

        if (!filter_var(trim($data->email), FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Ungültige E-Mail-Adresse.", 400);
        }

        $birthdate = isset($data->birthdate) ? trim($data->birthdate) : null;
        if ($birthdate && !Validation::validateDate($birthdate)) {
            throw new Exception("Ungültiges Geburtsdatum. Erwartetes Format: YYYY-MM-DD.", 400);
        }

        $fields = [
            'vorname'       => strip_tags(trim($data->vorname)),
            'nachname'      => strip_tags(trim($data->nachname)),
            'strasse'       => strip_tags(trim($data->strasse)),
            'plz'           => strip_tags(trim($data->plz)),
            'ort'           => strip_tags(trim($data->ort)),
            'fk_id_land'    => filter_var($data->fk_id_land, FILTER_VALIDATE_INT),
            'email'         => filter_var(trim($data->email), FILTER_VALIDATE_EMAIL),
            'telefon'       => isset($data->telefon) ? strip_tags(trim($data->telefon)) : null,
            'handy'         => isset($data->handy) ? strip_tags(trim($data->handy)) : null,
            'birthdate'     => $birthdate,
        ];

        return array_filter($fields, fn($value) => !is_null($value));
    }
}