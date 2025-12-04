<?php

/**
 * Controller for managing the Many-to-Many relationship between Courses and Students (Kurse <-> Lernende).
 * Tracks which student is enrolled in which course and their grades.
 */

require_once __DIR__ . '/../system/BaseController.php';

$rules = [
    'nr_kurs'     => 'required|int|positive',
    'nr_lernende' => 'required|int|positive',
    'note'        => 'string'
];

$controller = new BaseController('tbl_kurse_lernende', 'id_kurse_lernende', $rules);
$controller->handleRequest();