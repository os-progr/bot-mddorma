<?php
/**
 * api/obtener_senales.php — LECTOR PÚBLICO ULTRA-ESCALABLE (Hub & Spoke).
 * Diseñado para soportar miles de usuarios concurrentes mediante Micro-Caching en Cloudflare y ETag 304.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('X-Content-Type-Options: nosniff');

const STORAGE_DIR     = __DIR__ . '/../storage';
const ACTIVAS_FILE    = STORAGE_DIR . '/senales_activas.json';
const HISTORIAL_FILE  = STORAGE_DIR . '/historial.json';
const CONTEXTO_FILE   = STORAGE_DIR . '/contexto_global.json';

// 1. Cálculo de ETag basado en mtime de los 3 archivos
$m1 = is_file(ACTIVAS_FILE) ? (int)filemtime(ACTIVAS_FILE) : 0;
$m2 = is_file(HISTORIAL_FILE) ? (int)filemtime(HISTORIAL_FILE) : 0;
$m3 = is_file(CONTEXTO_FILE) ? (int)filemtime(CONTEXTO_FILE) : 0;
$etag = '"' . md5("{$m1}-{$m2}-{$m3}") . '"';

// 2. Cabeceras de micro-caché para proteger el servidor de miles de usuarios
// Cloudflare Edge Cache absorbe el 99.9% de peticiones (s-maxage=3s)
header('Cache-Control: public, max-age=2, s-maxage=3, stale-while-revalidate=5');
header('ETag: ' . $etag);

// 3. Respuesta 304 Not Modified inmediata si no hubo cambios
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim((string)$_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
    http_response_code(304);
    exit;
}

// 4. Lectura de datos
$activas = [];
if (is_file(ACTIVAS_FILE)) {
    $tmp = json_decode((string)file_get_contents(ACTIVAS_FILE), true);
    if (is_array($tmp)) { $activas = $tmp; }
}

$historial = [];
if (is_file(HISTORIAL_FILE)) {
    $tmp = json_decode((string)file_get_contents(HISTORIAL_FILE), true);
    if (is_array($tmp)) { $historial = $tmp; }
}
$historial = array_slice(array_reverse($historial), 0, 10); // últimas 10 cerradas

$contexto = null;
if (is_file(CONTEXTO_FILE)) {
    $tmpC = json_decode((string)file_get_contents(CONTEXTO_FILE), true);
    if (is_array($tmpC)) { $contexto = $tmpC; }
}

// Respaldo de Alta Disponibilidad: Si los archivos JSON aún no existen, consultar MySQL
if (empty($activas) && empty($historial)) {
    try {
        $possible_paths = [
            '/home/qtenqbhl/public_html/api/db_connect.php',
            dirname(dirname(__DIR__)) . '/public_html/api/db_connect.php',
            dirname(dirname(__DIR__)) . '/api/db_connect.php',
            __DIR__ . '/../../api/db_connect.php'
        ];
        $pdo = null;
        foreach ($possible_paths as $p) {
            if (file_exists($p)) {
                require_once $p;
                break;
            }
        }
        if ($pdo) {
            $stmt = $pdo->query("SELECT t1.simbolo, t1.tipo, t1.entrada, t1.sl, t1.tp, t1.probabilidad, t1.rr, t1.etiqueta, t1.analisis, t1.fecha_registro as fecha_utc 
                                 FROM bot_senales_log t1 
                                 INNER JOIN (
                                     SELECT simbolo, MAX(id) as max_id 
                                     FROM bot_senales_log 
                                     WHERE estado = 'activa' 
                                     GROUP BY simbolo
                                 ) t2 ON t1.id = t2.max_id 
                                 ORDER BY t1.id DESC LIMIT 6");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $activas = $rows;
            }
            $stmtH = $pdo->query("SELECT simbolo, etiqueta, resultado, fecha_registro as fecha_utc FROM bot_senales_log WHERE estado = 'cerrada' ORDER BY id DESC LIMIT 10");
            $rowsH = $stmtH->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rowsH)) {
                $historial = $rowsH;
            }
        }
    } catch (Throwable $e) {
        // Silencioso
    }
}

// Deduplicación estricta en PHP (garantía matemática de par único)
$vistos = [];
$activas_unicas = [];
foreach ($activas as $item) {
    if (!is_array($item) || empty($item['simbolo'])) continue;
    $sym = strtoupper(trim((string)$item['simbolo']));
    if (!isset($vistos[$sym])) {
        $vistos[$sym] = true;
        $activas_unicas[] = $item;
    }
}
$activas = $activas_unicas;

echo json_encode([
    'success'             => true,
    'senales'             => $activas,
    'señales'             => $activas,
    'historial_reciente'  => $historial,
    'contexto'            => $contexto,
    'telemetria'          => $contexto['telemetria'] ?? null,
    'candidatos'          => $contexto['candidatos'] ?? null,
    'ultima_actualizacion'=> gmdate('Y-m-d H:i:s'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;