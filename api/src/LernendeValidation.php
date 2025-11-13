<?php

// Stellt sicher, dass wir auf die zentrale Validation::validateDate zugreifen können
require_once __DIR__ . '/Validation.php';

class LernenderValidator
{
    /**
     * Führt alle spezifischen Prüfungen für die Lernenden-Ressource durch.
     * @param object|null $data
     * @return array
     * @throws Exception
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

        // Typ- und Format-Validierung (Achtung: nutzt Validation::validateDate!)
        if (!filter_var($data->fk_id_land, FILTER_VALIDATE_INT) || $data->fk_id_land <= 0) {
            throw new Exception("Ungültige 'fk_id_land'. Muss eine positive Zahl sein.", 400);
        }
        if (!filter_var(trim($data->email), FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Ungültige E-Mail-Adresse.", 400);
        }
        if (!Validation::validateDate(trim($data->birthdate))) {
            throw new Exception("Ungültiges Geburtsdatum. Erwartetes Format: YYYY-MM-DD.", 400);
        }

        $email_privat = isset($data->email_privat) && filter_var(trim($data->email_privat), FILTER_VALIDATE_EMAIL) ? filter_var(trim($data->email_privat), FILTER_VALIDATE_EMAIL) : null;
        $geschlecht = isset($data->geschlecht) && !empty(trim($data->geschlecht)) ? htmlspecialchars(strip_tags(trim($data->geschlecht))) : null;

        $fields = [
            'vorname'       => htmlspecialchars(strip_tags(trim($data->vorname))),
            'nachname'      => htmlspecialchars(strip_tags(trim($data->nachname))),
            'strasse'       => htmlspecialchars(strip_tags(trim($data->strasse))),
            'plz'           => htmlspecialchars(strip_tags(trim($data->plz))),
            'ort'           => htmlspecialchars(strip_tags(trim($data->ort))),
            'fk_id_land'    => filter_var($data->fk_id_land, FILTER_VALIDATE_INT),
            'email'         => filter_var(trim($data->email), FILTER_VALIDATE_EMAIL),
            'birthdate'     => trim($data->birthdate),
            'geschlecht'    => $geschlecht,
            'telefon'       => isset($data->telefon) ? htmlspecialchars(strip_tags(trim($data->telefon))) : null,
            'handy'         => isset($data->handy) ? htmlspecialchars(strip_tags(trim($data->handy))) : null,
            'email_privat'  => $email_privat,
        ];

        return array_filter($fields, fn($value) => !is_null($value));
    }
}