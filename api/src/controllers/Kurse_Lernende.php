<?php

require_once __DIR__ . '/BaseController.php';

$rules = [
    'nr_kurs'     => 'required|int|positive',
    'nr_lernende' => 'required|int|positive',
    'note'        => 'string'
];

$controller = new BaseController('tbl_kurse_lernende', 'id_kurse_lernende', $rules);
$controller->handleRequest();