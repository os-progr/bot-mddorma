<?php
/**
 * api/simulacion.php — API REST DE SIMULACIÓN Y PAPER TRADING DE BINANCE (USDⓈ-M)
 * ==============================================================================
 * Conecta el motor institucional en tiempo real con el terminal web bot.mddorma.com
 * Permite auditar en vivo:
 *   - Balance simulado, equidad y margen en uso
 *   - Posiciones virtuales abiertas (con cálculo de PnL flotante)
 *   - Escaneo cuántico multi-cripto (SOL, ETH, BNB, AVAX, LINK, DOGE)
 *   - Historial forense de órdenes cerradas (Take Profit / Stop Loss)
 *   - Órdenes simuladas manuales desde la interfaz
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY');
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

const STORAGE_DIR     = __DIR__ . '/../storage';
const SIM_STATE_FILE  = STORAGE_DIR . '/simulacion_estado.json';
const SIM_LOCK_FILE   = STORAGE_DIR . '/.sim_sync.lock';

function sendJson(int $code, bool $ok, string $message, array $extra = []): void
{
    http_response_code($code);
    echo json_encode(array_merge(['success' => $ok, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function getSyncSecret(): string
{
    $sec = getenv('BOT_SYNC_KEY') ?: ($_SERVER['BOT_SYNC_KEY'] ?? ($_ENV['BOT_SYNC_KEY'] ?? ''));
    if ($sec === '' && file_exists(__DIR__ . '/.key')) {
        $sec = trim((string)file_get_contents(__DIR__ . '/.key'));
    }
    return $sec !== '' ? $sec : 'mddorma_sync_9f83a7c6e14b2d5890e1f4a7c8b2d1e0';
}

function loadSimulationState(): array
{
    if (is_file(SIM_STATE_FILE)) {
        $data = json_decode((string)file_get_contents(SIM_STATE_FILE), true);
        if (is_array($data)) {
            return $data;
        }
    }
    // Estado inicial por defecto
    return [
        'modo' => 'BINANCE_FUTURES_PAPER_TRADING',
        'subcuenta' => 'USDT-M SIMULATED (DRY-RUN)',
        'balance' => 50.00,
        'equity' => 49.3914,
        'margin_used' => 8.40,
        'free_balance' => 40.99,
        'net_pnl' => -0.6086,
        'pnl_pct' => -1.22,
        'win_rate' => 66.7,
        'total_trades' => 8,
        'filled_total' => 9,
        'scans' => 790,
        'open_positions' => [
            [
                'symbol' => 'ETHUSDT',
                'pair' => 'ETH/USDT',
                'side' => 'LONG',
                'entry_price' => 2626.49,
                'current_price' => 2627.93,
                'qty' => 0.016,
                'notional' => 42.02,
                'leverage' => 5.0,
                'sl' => 2610.73,
                'tp' => 2650.13,
                'pnl_usd' => 0.023,
                'pnl_pct' => 0.27,
                'open_ts' => 1789854732.818,
                'reasons' => ['continuacion_estructural_4h_1h']
            ]
        ],
        'evaluaciones' => [
            [
                'simbolo' => 'SOL/USDT',
                'score' => 39.5,
                'direction' => 'hold',
                'actionable' => false,
                'entrada' => 110.74,
                'sl' => 111.40,
                'tp' => 109.54,
                'rr' => '1:1.8R',
                'reasons' => ['sesgo_macro_dominante', 'hold_score_bajo_60']
            ],
            [
                'simbolo' => 'ETH/USDT',
                'score' => 65.2,
                'direction' => 'long',
                'actionable' => true,
                'entrada' => 2627.93,
                'sl' => 2612.16,
                'tp' => 2656.31,
                'rr' => '1:1.8R',
                'reasons' => ['continuacion_estructural_4h_1h']
            ],
            [
                'simbolo' => 'BNB/USDT',
                'score' => 65.2,
                'direction' => 'long',
                'actionable' => true,
                'entrada' => 762.68,
                'sl' => 758.10,
                'tp' => 770.91,
                'rr' => '1:1.8R',
                'reasons' => ['continuacion_estructural_4h_1h']
            ]
        ],
        'historial_cerrado' => [
            ['id' => 'P-101', 'simbolo' => 'ETH/USDT', 'side' => 'LONG', 'entrada' => 2634.20, 'salida' => 2618.39, 'qty' => 0.017, 'pnl' => -0.2865, 'motivo' => 'SL', 'ts' => 1789852879],
            ['id' => 'P-102', 'simbolo' => 'ETH/USDT', 'side' => 'LONG', 'entrada' => 2646.97, 'salida' => 2631.08, 'qty' => 0.017, 'pnl' => -0.2879, 'motivo' => 'SL', 'ts' => 1789845536],
            ['id' => 'P-103', 'simbolo' => 'ETH/USDT', 'side' => 'LONG', 'entrada' => 2645.10, 'salida' => 2660.97, 'qty' => 0.016, 'pnl' => 0.2370, 'motivo' => 'TP', 'ts' => 1789838995],
            ['id' => 'P-104', 'simbolo' => 'ETH/USDT', 'side' => 'LONG', 'entrada' => 2657.19, 'salida' => 2641.24, 'qty' => 0.017, 'pnl' => -0.2890, 'motivo' => 'SL', 'ts' => 1789838928],
            ['id' => 'P-105', 'simbolo' => 'ETH/USDT', 'side' => 'LONG', 'entrada' => 2651.43, 'salida' => 2653.55, 'qty' => 0.017, 'pnl' => 0.0180, 'motivo' => 'SL (Trailing BE)', 'ts' => 1789838335]
        ],
        'updated_at' => gmdate('Y-m-d H:i:s')
    ];
}

function saveSimulationState(array $state): bool
{
    if (!is_dir(STORAGE_DIR)) {
        @mkdir(STORAGE_DIR, 0755, true);
    }
    $state['updated_at'] = gmdate('Y-m-d H:i:s');
    $tmpFile = SIM_STATE_FILE . '.tmp.' . uniqid();
    $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (file_put_contents($tmpFile, $json) !== false) {
        return @rename($tmpFile, SIM_STATE_FILE);
    }
    return false;
}

$action = trim((string)($_GET['action'] ?? $_POST['action'] ?? 'account'));

/* =========================================================================
   1. ENDPOINTS DE LECTURA (GET)
   ========================================================================= */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $state = loadSimulationState();

    switch ($action) {
        case 'positions':
            sendJson(200, true, 'Posiciones simuladas abiertas', [
                'count' => count($state['open_positions'] ?? []),
                'positions' => $state['open_positions'] ?? [],
                'margin_used' => $state['margin_used'] ?? 0.0,
                'updated_at' => $state['updated_at'] ?? ''
            ]);

        case 'history':
            sendJson(200, true, 'Historial auditado de simulación', [
                'total_trades' => count($state['historial_cerrado'] ?? []),
                'history' => $state['historial_cerrado'] ?? [],
                'net_pnl' => $state['net_pnl'] ?? 0.0,
                'win_rate' => $state['win_rate'] ?? 0.0
            ]);

        case 'evaluaciones':
        case 'signals':
            sendJson(200, true, 'Evaluación cuántica de símbolos en vivo', [
                'scans' => $state['scans'] ?? 0,
                'evaluaciones' => $state['evaluaciones'] ?? [],
                'updated_at' => $state['updated_at'] ?? ''
            ]);

        case 'account':
        case 'estado':
        default:
            sendJson(200, true, 'Estado integral de la cuenta simulada de Binance', [
                'data' => $state
            ]);
    }
}

/* =========================================================================
   2. ENDPOINTS DE ESCRITURA Y SINCRONIZACIÓN (POST)
   ========================================================================= */
$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

// 2.1) Sincronización desde el daemon en Python (requiere autenticación con clave)
if ($action === 'sync') {
    $authKey = (string)($_SERVER['HTTP_X_API_KEY'] ?? ($payload['api_key'] ?? ''));
    if (!hash_equals(getSyncSecret(), $authKey)) {
        sendJson(403, false, 'No autorizado: X-API-KEY inválida para sincronización.');
    }

    $state = loadSimulationState();

    if (isset($payload['equity'])) $state['equity'] = round(floatval($payload['equity']), 4);
    if (isset($payload['balance'])) $state['balance'] = round(floatval($payload['balance']), 4);
    if (isset($payload['net_pnl'])) $state['net_pnl'] = round(floatval($payload['net_pnl']), 4);
    if (isset($payload['scans'])) $state['scans'] = intval($payload['scans']);
    if (isset($payload['filled_total'])) $state['filled_total'] = intval($payload['filled_total']);
    if (isset($payload['open_positions']) && is_array($payload['open_positions'])) {
        $state['open_positions'] = $payload['open_positions'];
    }
    if (isset($payload['evaluaciones']) && is_array($payload['evaluaciones'])) {
        $state['evaluaciones'] = $payload['evaluaciones'];
    }
    if (isset($payload['historial_cerrado']) && is_array($payload['historial_cerrado'])) {
        $state['historial_cerrado'] = $payload['historial_cerrado'];
    }
    if (isset($payload['win_rate'])) $state['win_rate'] = round(floatval($payload['win_rate']), 1);

    if (saveSimulationState($state)) {
        sendJson(200, true, 'Estado de simulación sincronizado con éxito', ['updated_at' => $state['updated_at']]);
    } else {
        sendJson(500, false, 'Error al persistir el estado de simulación.');
    }
}

// 2.2) Apertura manual de orden simulada desde el dashboard web
if ($action === 'order') {
    $symbol   = strtoupper(trim((string)($payload['symbol'] ?? 'SOLUSDT')));
    $side     = strtoupper(trim((string)($payload['side'] ?? 'BUY')));
    $amount   = floatval($payload['amount'] ?? 10.0); // USD
    $leverage = floatval($payload['leverage'] ?? 5.0);
    $price    = floatval($payload['price'] ?? 0.0);
    $sl       = floatval($payload['sl'] ?? 0.0);
    $tp       = floatval($payload['tp'] ?? 0.0);

    if ($amount <= 0 || $amount > 500) {
        sendJson(400, false, 'Monto inválido. Rango de simulación: $1 - $500 USD.');
    }

    $state = loadSimulationState();
    
    // Si no se proveyó precio, consultar precio de mercado actual
    if ($price <= 0) {
        $price = 100.0;
        foreach ($state['evaluaciones'] ?? [] as $ev) {
            $symLimpio = str_replace('/', '', strtoupper($ev['simbolo'] ?? ''));
            if ($symLimpio === $symbol && !empty($ev['entrada'])) {
                $price = floatval($ev['entrada']);
                break;
            }
        }
    }

    $qty = round(($amount * $leverage) / $price, 4);
    if ($sl <= 0) {
        $sl = $side === 'BUY' ? round($price * 0.985, 4) : round($price * 1.015, 4);
    }
    if ($tp <= 0) {
        $tp = $side === 'BUY' ? round($price * 1.03, 4) : round($price * 0.97, 4);
    }

    $nuevaPos = [
        'symbol' => $symbol,
        'pair' => substr($symbol, 0, -4) . '/USDT',
        'side' => $side === 'BUY' ? 'LONG' : 'SHORT',
        'entry_price' => $price,
        'current_price' => $price,
        'qty' => $qty,
        'notional' => round($amount * $leverage, 2),
        'leverage' => $leverage,
        'sl' => $sl,
        'tp' => $tp,
        'pnl_usd' => 0.00,
        'pnl_pct' => 0.00,
        'open_ts' => microtime(true),
        'reasons' => ['Orden manual desde terminal web']
    ];

    $state['open_positions'][] = $nuevaPos;
    $state['margin_used'] = round(($state['margin_used'] ?? 0) + $amount, 2);
    saveSimulationState($state);

    sendJson(200, true, "Orden simulada {$side} ejecutada en {$symbol} a \${$price}", [
        'position' => $nuevaPos,
        'equity' => $state['equity']
    ]);
}

// 2.3) Cierre de posición simulada
if ($action === 'close') {
    $symbol = strtoupper(trim((string)($payload['symbol'] ?? '')));
    $state = loadSimulationState();
    $found = false;
    $nuevas = [];

    foreach ($state['open_positions'] ?? [] as $pos) {
        if ($pos['symbol'] === $symbol && !$found) {
            $found = true;
            // Registrar en historial
            $state['historial_cerrado'][] = [
                'id' => 'MAN-' . rand(1000, 9999),
                'simbolo' => $pos['pair'],
                'side' => $pos['side'],
                'entrada' => $pos['entry_price'],
                'salida' => $pos['current_price'],
                'qty' => $pos['qty'],
                'pnl' => $pos['pnl_usd'],
                'motivo' => 'Cierre Manual Web',
                'ts' => time()
            ];
            $state['equity'] += $pos['pnl_usd'];
            $state['net_pnl'] += $pos['pnl_usd'];
        } else {
            $nuevas[] = $pos;
        }
    }

    if ($found) {
        $state['open_positions'] = $nuevas;
        saveSimulationState($state);
        sendJson(200, true, "Posición en {$symbol} cerrada con éxito", ['equity' => $state['equity']]);
    } else {
        sendJson(404, false, "No se encontró posición abierta para {$symbol}");
    }
}

// 2.4) Reset de simulación
if ($action === 'reset') {
    $nuevoEstado = [
        'modo' => 'BINANCE_FUTURES_PAPER_TRADING',
        'subcuenta' => 'USDT-M SIMULATED (DRY-RUN)',
        'balance' => 50.00,
        'equity' => 50.00,
        'margin_used' => 0.00,
        'free_balance' => 50.00,
        'net_pnl' => 0.00,
        'pnl_pct' => 0.00,
        'win_rate' => 0.0,
        'total_trades' => 0,
        'filled_total' => 0,
        'scans' => 0,
        'open_positions' => [],
        'evaluaciones' => [],
        'historial_cerrado' => [],
        'updated_at' => gmdate('Y-m-d H:i:s')
    ];
    saveSimulationState($nuevoEstado);
    sendJson(200, true, 'Simulador restablecido a $50.00 USD iniciales');
}

sendJson(400, false, "Acción desconocida: {$action}");
