<?php
/**
 * api/actualizar_senales.php — RECEPTOR SEGURO de señales y contexto macro del bot (Hub).
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

const STORAGE_DIR      = __DIR__ . '/../storage';
const ACTIVAS_FILE     = STORAGE_DIR . '/senales_activas.json';
const HISTORIAL_FILE   = STORAGE_DIR . '/historial.json';
const CONTEXTO_FILE    = STORAGE_DIR . '/contexto_global.json';
const LOCK_FILE        = STORAGE_DIR . '/.sync.lock';

function jsonOut(int $codigo, bool $ok, string $mensaje, array $extra = []): void
{
    http_response_code($codigo);
    echo json_encode(array_merge(['success' => $ok, 'message' => $mensaje], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ---------- 1) Clave secreta ---------- */
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

/* ---------- 4) Leer payload ---------- */
$entrada = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($entrada)) {
    $entrada = $_POST;
}
if (!is_array($entrada)) {
    jsonOut(400, false, 'Payload JSON inválido.');
}

if (!is_dir(STORAGE_DIR)) {
    @mkdir(STORAGE_DIR, 0755, true);
}

$lock = @fopen(LOCK_FILE, 'c');
if ($lock === false || !flock($lock, LOCK_EX)) {
    if (is_resource($lock)) { fclose($lock); }
    jsonOut(503, false, 'Servidor ocupado, reintenta en unos segundos.');
}

$ahora = gmdate('Y-m-d H:i:s');

/* ---------- 4.5) Conexión Resiliente a MySQL (Persistencia Oficial) ---------- */
$pdo = null;
try {
    $possible_paths = [
        '/home/qtenqbhl/public_html/api/db_connect.php',
        dirname(dirname(__DIR__)) . '/public_html/api/db_connect.php',
        dirname(dirname(__DIR__)) . '/api/db_connect.php',
        __DIR__ . '/../../api/db_connect.php'
    ];
    foreach ($possible_paths as $p) {
        if (file_exists($p)) {
            require_once $p;
            break;
        }
    }

    if (empty($pdo)) {
        $env_loader = '/home/qtenqbhl/public_html/api/env_loader.php';
        if (!file_exists($env_loader)) {
            $env_loader = dirname(dirname(__DIR__)) . '/public_html/api/env_loader.php';
        }
        if (!file_exists($env_loader)) {
            $env_loader = dirname(dirname(__DIR__)) . '/api/env_loader.php';
        }
        if (file_exists($env_loader)) {
            require_once $env_loader;
        }

        $db_host = $_ENV['DB_HOST'] ?? (getenv('DB_HOST') ?: 'localhost');
        $db_name = $_ENV['DB_NAME'] ?? (getenv('DB_NAME') ?: 'qtenqbhl_Dorama2026');
        $db_user = $_ENV['DB_USER'] ?? (getenv('DB_USER') ?: 'qtenqbhl_YJEZdEZVCxAR');
        $db_pass = $_ENV['DB_PASS'] ?? (getenv('DB_PASS') ?: '');

        $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 2,
        ]);
    }

    // Tabla histórica de señales
    $pdo->exec("CREATE TABLE IF NOT EXISTS bot_senales_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        simbolo VARCHAR(20) NOT NULL,
        tipo VARCHAR(10) NOT NULL,
        entrada DECIMAL(16, 6) NOT NULL,
        sl DECIMAL(16, 6) NOT NULL,
        tp DECIMAL(16, 6) NOT NULL,
        probabilidad DECIMAL(5, 2) DEFAULT 0,
        rr VARCHAR(20) DEFAULT '',
        etiqueta VARCHAR(100) DEFAULT '',
        resultado VARCHAR(100) DEFAULT '',
        estado VARCHAR(20) NOT NULL,
        analisis TEXT,
        fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_simbolo_estado (simbolo, estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de telemetría de escaneos y ciclos
    $pdo->exec("CREATE TABLE IF NOT EXISTS bot_telemetria (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ciclo INT NOT NULL,
        equity DECIMAL(12, 4) NOT NULL,
        posiciones_abiertas INT DEFAULT 0,
        ordenes_pendientes INT DEFAULT 0,
        net_pnl DECIMAL(12, 4) DEFAULT 0,
        ancla_btc VARCHAR(50) DEFAULT '',
        analisis TEXT,
        fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ciclo (ciclo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Throwable $e) {
    $pdo = null;
}

// Función de despacho de alertas automáticas a bots de Telegram personales de usuarios
function despachar_alertas_telegram_usuarios(?PDO $pdo, ?array $candidatos_o_senales): void
{
    if (!$pdo || empty($candidatos_o_senales) || !is_array($candidatos_o_senales)) return;

    try {
        if (!function_exists('calcular_acceso_usuario')) {
            if (file_exists(__DIR__ . '/auth.php')) {
                define('AUTH_LIB_ONLY', true);
                require_once __DIR__ . '/auth.php';
            }
        }

        $st = $pdo->query("SELECT u.id_usuario, u.nombre, u.correo, u.rol, u.es_premium, u.fecha_registro,
                                  a.bot_token, a.chat_id, a.moneda_filtro, a.score_minimo
                           FROM usuario_telegram_alertas a
                           INNER JOIN usuarios u ON u.id_usuario = a.id_usuario
                           WHERE a.activo = 1");
        $usuarios = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
        if (empty($usuarios)) return;

        foreach ($candidatos_o_senales as $item) {
            if (!is_array($item)) continue;
            $simbolo = strtoupper(trim((string)($item['simbolo'] ?? '')));
            if ($simbolo === '') continue;

            $isActionable = !empty($item['actionable']) || (($item['estado'] ?? '') === 'activa');
            $score = intval($item['score'] ?? ($item['probabilidad'] ?? 60));
            if (!$isActionable || $score < 60) continue;

            $baseCoin = explode('/', $simbolo)[0];
            $cleanSym = str_replace('/', '', $simbolo);
            $tipoRaw = strtolower((string)($item['tipo'] ?? ($item['direction'] ?? 'buy')));
            $isBuy = ($tipoRaw === 'buy' || $tipoRaw === 'compra' || $tipoRaw === 'long');
            $tipoStr = $isBuy ? '↗ COMPRA (LONG)' : '↘ VENTA (SHORT)';
            $emojiDir = $isBuy ? '🟢' : '🔴';
            $pEntrada = floatval($item['entrada'] ?? 0);
            $pSl = floatval($item['sl'] ?? 0);
            $pTp = floatval($item['tp'] ?? 0);
            $rr = (string)($item['rr'] ?? '1:1.8R');

            foreach ($usuarios as $u) {
                $idU = (int)$u['id_usuario'];

                // 1. Validar acceso (VIP o prueba gratis activa)
                $acc = function_exists('calcular_acceso_usuario') ? calcular_acceso_usuario($u) : ['acceso_total' => true];
                if (empty($acc['acceso_total'])) continue;

                // 2. Validar filtro de moneda del usuario
                $filtro = strtoupper(trim((string)($u['moneda_filtro'] ?? 'ALL')));
                if ($filtro !== 'ALL' && $filtro !== $baseCoin) {
                    continue;
                }

                // 3. Validar score mínimo
                $scoreMin = intval($u['score_minimo'] ?: 70);
                if ($score < $scoreMin) continue;

                // 4. Anti-spam: Verificar si ya se envió esta misma señal a este usuario en las últimas 2 horas
                $stDup = $pdo->prepare("SELECT id_log FROM bot_telegram_envios_log 
                                        WHERE id_usuario = ? AND simbolo = ? AND tipo = ? AND entrada = ? 
                                          AND fecha_envio > DATE_SUB(NOW(), INTERVAL 2 HOUR) LIMIT 1");
                $stDup->execute([$idU, $simbolo, $tipoStr, $pEntrada]);
                if ($stDup->fetchColumn()) {
                    continue;
                }

                // 5. Construir y enviar mensaje formateado
                $msg = "🟡 <b>SEÑAL BINANCE FUTUROS USDⓈ-M</b>\n"
                     . "══════════════════════════════════\n"
                     . "💎 <b>Par:</b> <code>{$simbolo}</code> (Contrato Perpetuo)\n"
                     . "{$emojiDir} <b>Dirección:</b> <b>{$tipoStr}</b>\n"
                     . "🎯 <b>Precio Entrada:</b> <code>\${$pEntrada}</code>\n"
                     . "🟢 <b>Take Profit (TP):</b> <code>\${$pTp}</code> (Objetivo)\n"
                     . "🔴 <b>Stop Loss (SL):</b>   <code>\${$pSl}</code> (Blindaje)\n"
                     . "⚖️ <b>Ratio R:R:</b> <code>{$rr}</code>\n"
                     . "🛡️ <b>Confluencia Cuántica:</b> <b>{$score}%</b>\n"
                     . "══════════════════════════════════\n"
                     . "👉 <a href=\"https://www.binance.com/es/futures/{$cleanSym}\">Abrir {$cleanSym} en Binance Futuros</a>\n"
                     . "💻 <a href=\"https://bot.mddorma.com\">Ver Análisis en Terminal Quantum.AI</a>";

                // Enviar cURL no bloqueante (timeout corto de 3s)
                $ch = curl_init("https://api.telegram.org/bot{$u['bot_token']}/sendMessage");
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => http_build_query([
                        'chat_id' => $u['chat_id'],
                        'text' => $msg,
                        'parse_mode' => 'HTML',
                        'disable_web_page_preview' => true
                    ]),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 3,
                    CURLOPT_CONNECTTIMEOUT => 2,
                    CURLOPT_SSL_VERIFYPEER => true
                ]);
                @curl_exec($ch);
                @curl_close($ch);

                // Registrar en log para no duplicar
                $stLog = $pdo->prepare("INSERT INTO bot_telegram_envios_log (id_usuario, simbolo, tipo, entrada) VALUES (?, ?, ?, ?)");
                $stLog->execute([$idU, $simbolo, $tipoStr, $pEntrada]);

                // Actualizar timestamp de último envío
                $stUpd = $pdo->prepare("UPDATE usuario_telegram_alertas SET ultimo_envio = NOW() WHERE id_usuario = ?");
                $stUpd->execute([$idU]);
            }
        }
    } catch (Throwable $e) {}
}

/* ---------- 5) Actualización de Contexto Global / Macro / Telemetría ---------- */
if (isset($entrada['global']) || (isset($entrada['tipo']) && $entrada['tipo'] === 'contexto_global') || isset($entrada['telemetria'])) {
    $global = $entrada['global'] ?? $entrada;
    $macro = is_array($global['macro'] ?? null) ? $global['macro'] : [];
    $analisis = trim((string)($global['analisis'] ?? ''));
    $diagnostico = trim((string)($global['diagnostico'] ?? ''));
    $telemetria = is_array($global['telemetria'] ?? null) ? $global['telemetria'] : ($entrada['telemetria'] ?? null);
    $candidatos = is_array($global['candidatos'] ?? null) ? $global['candidatos'] : null;

    // Preservar macro existente si no viene en el payload
    $existente = [];
    if (is_file(CONTEXTO_FILE)) {
        $existente = json_decode((string)file_get_contents(CONTEXTO_FILE), true) ?: [];
    }
    if (empty($macro) && !empty($existente['macro'])) {
        $macro = $existente['macro'];
    }
    if (empty($analisis) && !empty($existente['analisis'])) {
        $analisis = $existente['analisis'];
    }
    if (empty($diagnostico) && !empty($existente['diagnostico'])) {
        $diagnostico = $existente['diagnostico'];
    }
    if ($telemetria === null && !empty($existente['telemetria'])) {
        $telemetria = $existente['telemetria'];
    }

    // Sanitizar y limitar longitudes
    if (strlen($analisis) > 2000) $analisis = substr($analisis, 0, 2000);
    if (strlen($diagnostico) > 500) $diagnostico = substr($diagnostico, 0, 500);

    $contexto_data = [
        'fuente' => 'bot',
        'fecha_utc' => $ahora,
        'macro' => $macro,
        'analisis' => $analisis,
        'diagnostico' => $diagnostico,
        'telemetria' => $telemetria,
        'candidatos' => $candidatos
    ];

    $tmp = CONTEXTO_FILE . '.tmp';
    file_put_contents($tmp, json_encode($contexto_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    rename($tmp, CONTEXTO_FILE);

    // Persistencia en MySQL (Histórico de Telemetría)
    if ($pdo && !empty($telemetria) && is_array($telemetria)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO bot_telemetria (ciclo, equity, posiciones_abiertas, ordenes_pendientes, net_pnl, ancla_btc, analisis) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                intval($telemetria['ciclo'] ?? 0),
                floatval($telemetria['equity'] ?? 0),
                intval($telemetria['posiciones_abiertas'] ?? 0),
                intval($telemetria['ordenes_pendientes'] ?? 0),
                floatval($telemetria['net_pnl'] ?? 0),
                substr((string)($telemetria['ancla_btc'] ?? ''), 0, 50),
                $analisis ?: $diagnostico
            ]);
        } catch (Throwable $e) {
            // Silencioso y resiliente
        }
    }

    // Despacho de alertas a Telegram para usuarios que configuraron su bot personal
    if ($pdo && !empty($candidatos) && is_array($candidatos)) {
        despachar_alertas_telegram_usuarios($pdo, $candidatos);
    }

    flock($lock, LOCK_UN);
    fclose($lock);
    jsonOut(200, true, 'Contexto global y telemetría actualizados con éxito.');
}

/* ---------- 6) Actualización de Señal Individual ---------- */
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
$analisis = isset($entrada['analisis']) ? substr(trim((string)$entrada['analisis']), 0, 2000) : null;
$entradaP = $num($entrada['entrada'] ?? 0);
$tp       = $num($entrada['tp'] ?? 0);
$sl       = $num($entrada['sl'] ?? 0);
$prob     = $num($entrada['probabilidad'] ?? 0);
$rr       = $s('rr', 10);

if ($simbolo === '' || ($estado !== '' && $estado !== 'activa' && $estado !== 'cerrada')) {
    flock($lock, LOCK_UN);
    fclose($lock);
    jsonOut(422, false, 'Campos inválidos: simbolo / estado.');
}
if ($estado === '' || $estado === 'activa') {
    if ($entradaP <= 0 || $sl <= 0 || $tp <= 0) {
        flock($lock, LOCK_UN);
        fclose($lock);
        jsonOut(422, false, 'Entrada, SL y TP deben ser mayores que 0 para una señal activa.');
    }
    if ($tipo === '') {
        flock($lock, LOCK_UN);
        fclose($lock);
        jsonOut(422, false, 'Falta el campo tipo (compra/venta).');
    }
}
if ($prob < 0 || $prob > 100) {
    flock($lock, LOCK_UN);
    fclose($lock);
    jsonOut(422, false, 'probabilidad debe estar entre 0 y 100.');
}

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

if ($estado === 'cerrada') {
    $nuevas = [];
    foreach ($activas as $r) {
        if (is_array($r) && strtoupper((string)($r['simbolo'] ?? '')) !== $simbolo) {
            $nuevas[] = $r;
        }
    }
    $activas = $nuevas;
    $historial[] = [
        'simbolo'    => $simbolo,
        'etiqueta'   => $etiqueta !== '' ? $etiqueta : null,
        'resultado'  => $resultado !== '' ? $resultado : 'CERRADA',
        'fecha_utc'  => $ahora,
    ];
    if (count($historial) > 50) {
        $historial = array_slice($historial, -50);
    }
} else {
    $nuevas = [];
    foreach ($activas as $r) {
        if (is_array($r) && strtoupper((string)($r['simbolo'] ?? '')) !== $simbolo) {
            $nuevas[] = $r;
        }
    }
    $nuevas[] = [
        'simbolo'      => $simbolo,
        'tipo'         => $tipo,
        'etiqueta'     => $etiqueta !== '' ? $etiqueta : null,
        'analisis'     => $analisis,
        'entrada'      => $entradaP,
        'sl'           => $sl,
        'tp'           => $tp,
        'probabilidad' => $prob,
        'rr'           => $rr !== '' ? $rr : '1:2R',
        'estado'       => 'activa',
        'fecha_utc'    => $ahora,
    ];
    $activas = $nuevas;
}

// Escritura atómica
$tmpA = ACTIVAS_FILE . '.tmp';
file_put_contents($tmpA, json_encode($activas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
rename($tmpA, ACTIVAS_FILE);

$tmpH = HISTORIAL_FILE . '.tmp';
file_put_contents($tmpH, json_encode($historial, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
rename($tmpH, HISTORIAL_FILE);

// Persistencia en MySQL (Histórico de Señales Activas / Cerradas)
if ($pdo) {
    try {
        // Desactivar cualquier señal activa previa para este mismo símbolo para evitar duplicados
        $upd = $pdo->prepare("UPDATE bot_senales_log SET estado = 'reemplazada' WHERE simbolo = ? AND estado = 'activa'");
        $upd->execute([$simbolo]);

        $stmt = $pdo->prepare("INSERT INTO bot_senales_log (simbolo, tipo, entrada, sl, tp, probabilidad, rr, etiqueta, resultado, estado, analisis) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $simbolo,
            $tipo ?: ($estado === 'cerrada' ? 'N/A' : 'COMPRA'),
            $entradaP,
            $sl,
            $tp,
            $prob,
            $rr !== '' ? $rr : '1:2R',
            $etiqueta,
            $resultado ?: ($estado === 'cerrada' ? 'CERRADA' : ''),
            $estado ?: 'activa',
            $analisis
        ]);
    } catch (Throwable $e) {
        // Silencioso y resiliente
    }
}

// Despacho de alertas a Telegram para usuarios que configuraron su bot personal
if ($pdo && !empty($nuevas)) {
    despachar_alertas_telegram_usuarios($pdo, [$nuevas[count($nuevas) - 1]]);
}

flock($lock, LOCK_UN);
fclose($lock);

jsonOut(200, true, 'Señal guardada con éxito.');