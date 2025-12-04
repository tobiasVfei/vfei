<?php

require_once __DIR__ . '/../system/BaseController.php';

$rules = [
    'fk_id_lehrbetrieb' => 'required|int|positive',
    'fk_id_lernende'    => 'required|int|positive',
    'start'             => 'required|date',
    'ende'              => 'required|date',
    'beruf'             => 'string'
];

$controller = new BaseController('tbl_lehrbetrieb_lernende', 'id_lehrbetrieb_lernende', $rules);
$controller->handleRequest();