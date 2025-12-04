<?php

require_once __DIR__ . '/Validation.php';

/**
 * Validator class for Learner (Lernender) data.
 * Provides a static method for rigorous validation and preparation of learner records.
 */
class LernenderValidator
{
    /**
     * Validates and prepares the data for a new learner entry.
     *
     * Performs strict checks on required fields, including 'birthdate', email format, and positive foreign key ID.
     * Sanitizes string fields and handles optional fields like 'geschlecht' and 'email_privat'.
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

        $required = ['vorname', 'nachname', 'strasse', 'plz', 'ort', 'fk_id_land', 'email', 'birthdate'];
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
        if (!Validation::validateDate(trim($data->birthdate))) {
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
            'birthdate'     => trim($data->birthdate),
            'geschlecht'    => !empty($data->geschlecht) ? strip_tags(trim($data->geschlecht)) : null,
            'telefon'       => isset($data->telefon) ? strip_tags(trim($data->telefon)) : null,
            'handy'         => isset($data->handy) ? strip_tags(trim($data->handy)) : null,
            'email_privat'  => filter_var(trim($data->email_privat ?? ''), FILTER_VALIDATE_EMAIL) ?: null,
        ];

        return array_filter($fields, fn($value) => !is_null($value));
    }
}