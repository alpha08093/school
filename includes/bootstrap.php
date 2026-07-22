<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0'); // passer à '1' temporairement pour déboguer en dev

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

auth_start_session();
