<?php

require_once __DIR__ . '/../system/BaseController.php';

$rules = [
    'kursnummer'   => 'required|string',
    'kursthema'    => 'required|string',
    'fk_id_dozent' => 'required|int|positive',
    'inhalt'       => 'string',
    'startdatum'   => 'date',
    'enddatum'     => 'date',
    'dauer'        => 'float'
];

$controller = new BaseController('tbl_kurse', 'id_kurs', $rules);
$controller->handleRequest();