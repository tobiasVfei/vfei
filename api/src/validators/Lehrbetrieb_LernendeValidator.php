<?php

require_once __DIR__ . '/Validation.php';

class LehrbetriebLernendeValidator
{
    public static function validateAndPrepare(?object $data): array
    {
        if (!$data) {
            throw new Exception("Ungültige Eingabedaten.", 400);
        }

        $required = ['fk_id_lehrbetrieb', 'fk_id_lernende', 'start', 'ende', 'beruf'];
        foreach ($required as $f) {
            if (!isset($data->$f) || (is_string($data->$f) && empty(trim($data->$f)) && $f !== 'beruf')) {
                throw new Exception("Das Feld '$f' ist erforderlich und darf nicht leer sein.", 400);
            }
        }

        if (!filter_var($data->fk_id_lehrbetrieb, FILTER_VALIDATE_INT) || $data->fk_id_lehrbetrieb <= 0 ||
            !filter_var($data->fk_id_lernende, FILTER_VALIDATE_INT) || $data->fk_id_lernende <= 0) {
            throw new Exception("Ungültige 'fk_id_lehrbetrieb' oder 'fk_id_lernende'. Muss eine positive Zahl sein.", 400);
        }

        if (!Validation::validateDate(trim($data->start)) || !Validation::validateDate(trim($data->ende))) {
            throw new Exception("Ungültiges 'start' oder 'ende' Datum. Erwartetes Format: YYYY-MM-DD.", 400);
        }

        $fields = [
            'fk_id_lehrbetrieb' => filter_var($data->fk_id_lehrbetrieb, FILTER_VALIDATE_INT),
            'fk_id_lernende'    => filter_var($data->fk_id_lernende, FILTER_VALIDATE_INT),
            'start'             => trim($data->start),
            'ende'              => trim($data->ende),
            'beruf'             => strip_tags(trim($data->beruf)),
        ];

        return array_filter($fields, fn($value) => !is_null($value));
    }
}