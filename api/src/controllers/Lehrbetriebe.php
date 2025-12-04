<?php

require_once __DIR__ . '/../system/BaseController.php';

$rules = [
    'firma'   => 'required|string',
    'strasse' => 'required|string',
    'plz'     => 'required|string',
    'ort'     => 'required|string',
    'land_id' => 'required|int|positive'
];

$controller = new BaseController('tbl_lehrbetriebe', 'id_lehrbetrieb', $rules);
$controller->handleRequest();