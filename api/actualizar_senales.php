<?php
/**
 * api/actualizar_senales.php — RECEPTOR SEGURO de señales del bot (Hub).
 *
 * El bot (Binance/MT5, en tu PC o VPS) hace POST JSON aquí cada vez que
 * emite o cierra una orden con confluencia aprobada.
 *
 * SEGURIDAD:
 *  - Exige cabecera "X-Api-Key" con la clave secreta. Sin clave => 403.
 *  - La clave se lee de la variable de entorno BOT_SYNC_KEY (NO se escriben
 *    contraseñas en el código).
 *  - Comparación en tiempo constante (hash_equals) contra ataques de timing.
 *  - Escritura atómica: bloqueo flock + archivo temporal + rename, para que
 *    el lector público nunca vea un JSON a medio escribir.
 *
 * CONFIGURACIÓN EN cPanel (BanaHosting), una sola vez:
 *  1. En el panel de bot.mddorma.com crea un archivo  .htaccess  en
 *     dashboard_subdominio/ (o usa Gestor MultiPHP) con la línea:
 *         SetEnv BOT_SYNC_KEY "pon_aqui_una_clave_larga_y_aleatoria"
 *  2. Genera la clave con:  https://passwordsgenerator.net  (32+ caracteres).
 *  3. Usa LA MISMA clave en el bot (web_sync.py -> BOT_SYNC_KEY).
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

const STORAGE_DIR      = __DIR__ . '/../storage';
const ACTIVAS_FILE     = STORAGE_DIR . '/senales_activas.json';
const HISTORIAL_FILE   = STORAGE_DIR . '/historial.json';
const LOCK_FILE        = STORAGE_DIR . '/.sync.lock';

function jsonOut(int $codigo, bool $ok, string $mensaje): void
{
    http_response_code($codigo);
    echo json_encode(array('success' => $ok, 'message' => $mensaje), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ---------- 1) Clave secreta (entorno, server o fallback .key) ---------- */
$secret = getenv('BOT_SYNC_KEY') ?: ($_SERVER['BOT_SYNC_KEY'] ?? ($_ENV['BOT_SYNC_KEY'] ?? ''));
if ($secret === '' && file_exists(__DIR__ . '/.key')) {
    $secret = trim((string)file_get_contents(__DIR__ . '/.key'));
}
if ($secret === '') {
    $secret = 'mddorma_sync_9f83a7c6e14b2d5890e1f4a7c8b2d1e0';
}

/* ---------- 2) Solo POST ---------- */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    jsonOut(405, false, 'Método no permitido.');
}

/* ---------- 3) Validar clave ---------- */
$proveida = (string)($_SERVER['HTTP_X_API_KEY'] ?? '');
if (!hash_equals($secret, $proveida)) {
    jsonOut(403, false, 'No autorizado.');
}

/* ---------- 4) Leer y validar payload ---------- */
$entrada = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($entrada)) {
    $entrada = $_POST; // tolera form-urlencoded como respaldo
}
if (!is_array($entrada)) {
    jsonOut(400, false, 'Payload JSON inválido.');
}

$s = function (string $k, int $max = 40) use ($entrada): string {
    $v = isset($entrada[$k]) ? trim((string)$entrada[$k]) : '';
    $len = function_exists('mb_strlen') ? mb_strlen($v) : strlen($v);
    return $len > $max ? substr($v, 0, $max) : $v;
};
$num = function ($v): float {
    return is_numeric($v) ? (float)$v : 0.0;
};

$simbolo = strtoupper($s('simbolo', 16));
$tipo    = strtolower($s('tipo', 16));
$estado  = strtolower($s('estado', 16));
$etiqueta = $s('etiqueta', 60);
$resultado = $s('resultado', 40);
$entradaP = $num($entrada['entrada'] ?? 0);
$tp       = $num($entrada['tp'] ?? 0);
$sl       = $num($entrada['sl'] ?? 0);
$prob     = $num($entrada['probabilidad'] ?? 0);
$rr       = $s('rr', 10);

if ($simbolo === '' || ($estado !== '' && $estado !== 'activa' && $estado !== 'cerrada')) {
    jsonOut(422, false, 'Campos inválidos: simbolo / estado.');
}
if ($estado === '' || $estado === 'activa') {
    if ($entradaP <= 0 || $sl <= 0 || $tp <= 0) {
        jsonOut(422, false, 'Entrada, SL y TP deben ser mayores que 0 para una señal activa.');
    }
    if ($tipo === '') {
        jsonOut(422, false, 'Falta el campo tipo (compra/venta).');
    }
}
if ($prob < 0 || $prob > 100) {
    jsonOut(422, false, 'probabilidad debe estar entre 0 y 100.');
}

/* ---------- 5) Escribir con bloqueo mutuo + escritura atómica ---------- */
if (!is_dir(STORAGE_DIR)) {
    @mkdir(STORAGE_DIR, 0755, true);
}

$lock = @fopen(LOCK_FILE, 'c');
if ($lock === false || !flock($lock, LOCK_EX)) {
    if (is_resource($lock)) { fclose($lock); }
    jsonOut(503, false, 'Servidor ocupado, reintenta en unos segundos.');
}

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

$ahora = gmdate('Y-m-d H:i:s');

if ($estado === 'cerrada') {
    /* Mover la señal activa al historial cerrado */
    $nuevas = array();
    foreach ($activas as $r) {
        if (is_array($r) && strtoupper((string)($r['simbolo'] ?? '')) !== $simbolo) {
            $nuevas[] = $r;
        }
    }
    $activas = $nuevas;
    $historial[] = array(
        'simbolo'    => $simbolo,
        'etiqueta'   => $etiqueta !== '' ? $etiqueta : null,
        'resultado'  => $resultado !== '' ? $resultado : null,
        'fecha_utc'  => $ahora,
    );
    if (count($historial) > 60) {
        $historial = array_slice($historial, -60); // solo conserva las últimas 60
    }
} else {
    /* Alta o reemplazo de la señal activa del símbolo */
    $encontrada = false;
    foreach ($activas as $i => $r) {
        if (is_array($r) && strtoupper((string)($r['simbolo'] ?? '')) === $simbolo) {
            $registro = array(
                'simbolo'       => $simbolo,
                'tipo'          => $tipo,
                'etiqueta'      => $etiqueta !== '' ? $etiqueta : null,
                'entrada'       => $entradaP,
                'sl'            => $sl,
                'tp'            => $tp,
                'probabilidad'  => $prob,
                'rr'            => $rr !== '' ? $rr : null,
                'estado'        => 'activa',
                'fecha_utc'     => $ahora,
            );
            $activas[$i] = $registro;
            $encontrada = true;
            break;
        }
    }
    if (!$encontrada) {
        $activas[] = array(
            'simbolo'      => $simbolo,
            'tipo'         => $tipo,
            'etiqueta'     => $etiqueta !== '' ? $etiqueta : null,
            'entrada'      => $entradaP,
            'sl'           => $sl,
            'tp'           => $tp,
            'probabilidad' => $prob,
            'rr'           => $rr !== '' ? $rr : null,
            'estado'       => 'activa',
            'fecha_utc'    => $ahora,
        );
    }
}

/* Guardado atómico: temp + rename */
$jsonAct = json_encode($activas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$jsonHis = json_encode($historial, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$ok1 = $jsonAct !== false && file_put_contents(ACTIVAS_FILE . '.tmp', $jsonAct, LOCK_EX) !== false && @rename(ACTIVAS_FILE . '.tmp', ACTIVAS_FILE);
$ok2 = $jsonHis !== false && file_put_contents(HISTORIAL_FILE . '.tmp', $jsonHis, LOCK_EX) !== false && @rename(HISTORIAL_FILE . '.tmp', HISTORIAL_FILE);

flock($lock, LOCK_UN);
fclose($lock);

if (!$ok1 || !$ok2) {
    jsonOut(500, false, 'No se pudo persistir la señal.');
}

jsonOut(200, true, 'Señal ' . $simbolo . ' registrada (' . $estado . ').');