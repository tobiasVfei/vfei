<?php

require_once __DIR__ . '/BaseController.php';

$rules = [
    'country' => 'required|string'
];

$controller = new BaseController('tbl_countries', 'id_country', $rules);
$controller->handleRequest();