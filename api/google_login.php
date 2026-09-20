<?php
/**
 * api/google_login.php — Endpoint directo de autenticación Google OAuth
 */
declare(strict_types=1);

$_GET['action'] = 'google';
require_once __DIR__ . '/auth.php';
