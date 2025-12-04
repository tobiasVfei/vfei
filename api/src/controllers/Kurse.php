<?php

/**
 * Controller for managing Courses (Kurse).
 * Utilizes BaseController for standard CRUD operations on 'tbl_kurse'.
 */

require_once __DIR__ . '/../system/BaseController.php';

$rules = [
    'kursnummer'   => 'required|string',
    'kursthema'    => 'required|string',
    'nr_dozent' => 'required|int|positive',
    'inhalt'       => 'string',
    'startdatum'   => 'date',
    'enddatum'     => 'date',
    'dauer'        => 'string'
];

$controller = new BaseController('tbl_kurse', 'id_kurs', $rules);
$controller->handleRequest();