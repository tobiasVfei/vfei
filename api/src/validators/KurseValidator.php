<?php

class KursValidator
{
    public static function validateAndPrepare(?object $data): array
    {
        if (!$data) {
            throw new Exception("Ungültige Eingabedaten.", 400);
        }

        $required = ['kursnummer', 'kursthema', 'fk_id_dozent'];
        foreach ($required as $f) {
            if (!isset($data->$f) || (is_string($data->$f) && empty(trim($data->$f)))) {
                throw new Exception("Das Feld '$f' ist erforderlich und darf nicht leer sein.", 400);
            }
        }

        if (!filter_var($data->fk_id_dozent, FILTER_VALIDATE_INT) || $data->fk_id_dozent <= 0) {
            throw new Exception("Ungültige 'fk_id_dozent'. Muss eine positive Zahl sein.", 400);
        }

        $fields = [
            'kursnummer'    => strip_tags(trim($data->kursnummer)),
            'kursthema'     => strip_tags(trim($data->kursthema)),
            'fk_id_dozent'  => filter_var($data->fk_id_dozent, FILTER_VALIDATE_INT),
            'inhalt'        => isset($data->inhalt) ? strip_tags(trim($data->inhalt)) : null,
            'startdatum'    => isset($data->startdatum) ? strip_tags(trim($data->startdatum)) : null,
            'enddatum'      => isset($data->enddatum) ? strip_tags(trim($data->enddatum)) : null,
            'dauer'         => isset($data->dauer) ? filter_var($data->dauer, FILTER_VALIDATE_FLOAT) : null,
        ];

        return array_filter($fields, fn($value) => !is_null($value));
    }
}