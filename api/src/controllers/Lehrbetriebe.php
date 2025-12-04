<?php

/**
 * Controller for managing Companies/Apprenticeship Providers (Lehrbetriebe).
 * Utilizes BaseController for standard CRUD operations on 'tbl_lehrbetriebe'.
 */

require_once __DIR__ . '/../system/BaseController.php';

$rules = [
    'firma'   => 'required|string',
    'strasse' => 'required|string',
    'plz'     => 'required|string',
    'ort'     => 'required|string'
];

$controller = new BaseController('tbl_lehrbetriebe', 'id_lehrbetrieb', $rules);
$controller->handleRequest();