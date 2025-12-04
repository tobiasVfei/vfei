<?php

require_once __DIR__ . '/BaseController.php';

$rules = [
    'vorname'      => 'required|string',
    'nachname'     => 'required|string',
    'strasse'      => 'required|string',
    'plz'          => 'required|string',
    'ort'          => 'required|string',
    'fk_id_land'   => 'required|int|positive',
    'email'        => 'required|email',
    'birthdate'    => 'required|date',
    'geschlecht'   => 'string',
    'telefon'      => 'string',
    'handy'        => 'string',
    'email_privat' => 'email'
];

$controller = new BaseController('tbl_lernende', 'id_lernende', $rules);
$controller->handleRequest();