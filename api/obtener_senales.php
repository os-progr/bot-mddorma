<?php
/**
 * api/obtener_senales.php — LECTOR PÚBLICO (el "Spoke" del dashboard).
 *
 * Devuelve las señales activas emitidas por el bot en JSON.
 * El dashboard (dashboard_subdominio/index.html) hace polling ligero
 * cada 3-5 segundos.
 *
 * Cabeceras:
 *  - Cache-Control: no-store  => el navegador/proxy nunca cachea.
 *  - CORS controlado: por defecto * (señales públicas de producto).
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Access-Control-Allow-Origin: *');
header('X-Content-Type-Options: nosniff');

const STORAGE_DIR    = __DIR__ . '/../storage';
const ACTIVAS_FILE   = STORAGE_DIR . '/senales_activas.json';
const HISTORIAL_FILE = STORAGE_DIR . '/historial.json';

$activas = array();
if (is_file(ACTIVAS_FILE)) {
    $tmp = json_decode((string)file_get_contents(ACTIVAS_FILE), true);
    if (is_array($tmp)) { $activas = $tmp; }
}

$historial = array();
if (is_file(HISTORIAL_FILE)) {
    $tmp = json_decode((string)file_get_contents(HISTORIAL_FILE), true);
    if (is_array($tmp)) { $historial = $tmp; }
}
$historial = array_slice(array_reverse($historial), 0, 10); // últimas 10 cerradas

echo json_encode(
    array(
        'señales'             => $activas,
        'historial_reciente'  => $historial,
        'ultima_actualizacion'=> gmdate('Y-m-d H:i:s'),
    ),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
exit;