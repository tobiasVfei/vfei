<?php

class Validator
{
    /**
     * Validates and sanitizes input data based on a defined set of rules.
     *
     * @param object|null $data The raw input data (usually from json_decode).
     * @param array $rules Associative array of field names and rules (e.g. 'email' => 'required|email').
     * @return array The sanitized array, ready for database insertion.
     * @throws Exception If validation fails or input is missing.
     */
    public static function validate(?object $data, array $rules): array
    {
        if (!$data) {
            throw new Exception("Keine Eingabedaten vorhanden.", 400);
        }

        $validated = [];

        foreach ($rules as $field => $ruleString) {
            $ruleList = explode('|', $ruleString);
            $value = isset($data->$field) ? $data->$field : null;

            if (in_array('required', $ruleList)) {
                if ($value === null || $value === '' || (is_string($value) && trim($value) === '')) {
                    throw new Exception("Das Feld '$field' ist erforderlich.", 400);
                }
            }

            if (($value === null || $value === '') && !in_array('required', $ruleList)) {
                $validated[$field] = null;
                continue;
            }

            if (is_string($value)) {
                $value = trim($value);
            }

            if (in_array('email', $ruleList)) {
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Das Feld '$field' muss eine gültige E-Mail-Adresse sein.", 400);
                }
                $validated[$field] = filter_var($value, FILTER_VALIDATE_EMAIL);
            }
            elseif (in_array('int', $ruleList)) {
                if (!filter_var($value, FILTER_VALIDATE_INT) && $value !== '0' && $value !== 0) {
                    throw new Exception("Das Feld '$field' muss eine Ganzzahl sein.", 400);
                }
                if (in_array('positive', $ruleList) && (int)$value <= 0) {
                    throw new Exception("Das Feld '$field' muss grösser als 0 sein.", 400);
                }
                $validated[$field] = (int)$value;
            }
            elseif (in_array('float', $ruleList)) {
                if (!filter_var($value, FILTER_VALIDATE_FLOAT)) {
                    throw new Exception("Das Feld '$field' muss eine Zahl sein.", 400);
                }
                $validated[$field] = (float)$value;
            }
            elseif (in_array('date', $ruleList)) {
                $d = DateTime::createFromFormat('Y-m-d', $value);
                if (!$d || $d->format('Y-m-d') !== $value) {
                    throw new Exception("Das Feld '$field' ist kein gültiges Datum (Format: YYYY-MM-DD).", 400);
                }
                $validated[$field] = $value;
            }
            else {
                $validated[$field] = strip_tags($value);
            }
        }

        return $validated;
    }
}