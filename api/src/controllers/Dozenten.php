<?php

require_once __DIR__ . '/BaseController.php';

$rules = [
    'vorname'    => 'required|string',
    'nachname'   => 'required|string',
    'strasse'    => 'required|string',
    'plz'        => 'required|string',
    'ort'        => 'required|string',
    'fk_id_land' => 'required|int|positive',
    'email'      => 'required|email',
    'telefon'    => 'string',
    'handy'      => 'string',
    'birthdate'  => 'date'
];

$controller = new BaseController('tbl_dozenten', 'id_dozent', $rules);
$controller->handleRequest();