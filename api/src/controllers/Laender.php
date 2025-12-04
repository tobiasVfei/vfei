<?php

/**
 * Controller for managing Countries (Laender).
 * Utilizes BaseController for standard CRUD operations on 'tbl_countries'.
 */

require_once __DIR__ . '/../system/BaseController.php';

$rules = [
    'country' => 'required|string'
];

$controller = new BaseController('tbl_countries', 'id_country', $rules);
$controller->handleRequest();