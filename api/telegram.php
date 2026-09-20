<?php
/**
 * api/telegram.php — GESTOR DE BOT PERSONAL DE TELEGRAM
 * 
 * Permite a cada usuario (en Prueba Gratis o VIP) configurar su propio Bot de Telegram
 * para recibir alertas automatizadas de Binance Futuros directamente en su celular
 * filtradas por su criptomoneda elegida.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

define('AUTH_LIB_ONLY', true);
require_once __DIR__ . '/auth.php';

// 1. Verificar si el usuario ha iniciado sesión
if (empty($_SESSION['id_usuario']) || empty($pdo)) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Debes iniciar sesión para configurar tus alertas de Telegram.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$id_usuario = (int)$_SESSION['id_usuario'];

// 2. Comprobar que el usuario tenga acceso válido (Prueba Gratis o VIP)
try {
    $stU = $pdo->prepare("SELECT id_usuario, nombre, correo, rol, es_premium, fecha_registro FROM usuarios WHERE id_usuario = ? LIMIT 1");
    $stU->execute([$id_usuario]);
    $uData = $stU->fetch(PDO::FETCH_ASSOC);
    $acceso = calcular_acceso_usuario($uData);

    if (!$acceso['acceso_total']) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Tu prueba gratuita de 7 días ha finalizado. Activa tu membresía VIP ($19 USD) para conectar tu bot de Telegram.',
            'requiere_vip' => true
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al validar membresía.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. Crear tablas necesarias si aún no existen
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuario_telegram_alertas (
        id_alerta INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL UNIQUE,
        bot_token VARCHAR(120) NOT NULL,
        chat_id VARCHAR(60) NOT NULL,
        moneda_filtro VARCHAR(20) DEFAULT 'ALL',
        score_minimo INT DEFAULT 70,
        activo TINYINT(1) DEFAULT 1,
        ultimo_envio DATETIME NULL,
        fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user (id_usuario),
        INDEX idx_activo (activo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS bot_telegram_envios_log (
        id_log INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        simbolo VARCHAR(30) NOT NULL,
        tipo VARCHAR(10) NOT NULL,
        entrada DECIMAL(16, 6) NOT NULL,
        fecha_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_dedup (id_usuario, simbolo, tipo, fecha_envio)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Throwable $e) {}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// Función auxiliar para enviar mensajes a Telegram vía cURL
function enviarMensajeTelegram(string $token, string $chatId, string $mensajeHtml): array
{
    $token = trim($token);
    $chatId = trim($chatId);
    if ($token === '' || $chatId === '') {
        return ['ok' => false, 'error' => 'Token o Chat ID vacíos'];
    }

    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $payload = [
        'chat_id' => $chatId,
        'text' => $mensajeHtml,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => false
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 6,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err !== '') {
        return ['ok' => false, 'error' => "Error de red cURL: {$err}"];
    }

    $json = json_decode((string)$resp, true);
    if (!is_array($json) || empty($json['ok'])) {
        $desc = $json['description'] ?? 'Error desconocido de la API de Telegram';
        return ['ok' => false, 'error' => "Telegram: {$desc} (HTTP {$httpCode})"];
    }

    return ['ok' => true, 'response' => $json];
}

// ==========================================
// 1) OBTENER CONFIGURACIÓN ACTUAL
// ==========================================
if ($action === 'get_config') {
    try {
        $stmt = $pdo->prepare("SELECT bot_token, chat_id, moneda_filtro, score_minimo, activo, ultimo_envio FROM usuario_telegram_alertas WHERE id_usuario = ? LIMIT 1");
        $stmt->execute([$id_usuario]);
        $cfg = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cfg) {
            echo json_encode([
                'success' => true,
                'config' => [
                    'configurado' => false,
                    'bot_token' => '',
                    'chat_id' => '',
                    'moneda_filtro' => 'ALL',
                    'score_minimo' => 70,
                    'activo' => 1,
                    'ultimo_envio' => null
                ]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Ocultar parcialmente el token por seguridad en el frontend
        $token = $cfg['bot_token'];
        $tokenMasked = strlen($token) > 10 ? substr($token, 0, 6) . '...' . substr($token, -4) : $token;

        echo json_encode([
            'success' => true,
            'config' => [
                'configurado' => true,
                'bot_token' => $tokenMasked,
                'token_raw' => $token,
                'chat_id' => $cfg['chat_id'],
                'moneda_filtro' => $cfg['moneda_filtro'] ?: 'ALL',
                'score_minimo' => (int)($cfg['score_minimo'] ?: 70),
                'activo' => (int)$cfg['activo'],
                'ultimo_envio' => $cfg['ultimo_envio']
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al cargar configuración.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ==========================================
// 2) GUARDAR / ACTUALIZAR CONFIGURACIÓN
// ==========================================
if ($action === 'save_config') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
        exit;
    }

    $raw = json_decode((string)file_get_contents('php://input'), true) ?: $_POST;

    $bot_token = trim((string)($raw['bot_token'] ?? ''));
    $chat_id = trim((string)($raw['chat_id'] ?? ''));
    $moneda_filtro = strtoupper(trim((string)($raw['moneda_filtro'] ?? 'ALL')));
    $score_minimo = max(50, min(95, (int)($raw['score_minimo'] ?? 70)));
    $activo = !empty($raw['activo']) ? 1 : 0;

    // Si viene enmascarado con '...', recuperar el token original si existía
    if (strpos($bot_token, '...') !== false) {
        $stOld = $pdo->prepare("SELECT bot_token FROM usuario_telegram_alertas WHERE id_usuario = ? LIMIT 1");
        $stOld->execute([$id_usuario]);
        $oldTok = $stOld->fetchColumn();
        if (!empty($oldTok)) {
            $bot_token = $oldTok;
        }
    }

    if (empty($bot_token) || strlen($bot_token) < 20 || strpos($bot_token, ':') === false) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'Token de bot inválido. Debe tener el formato provisto por @BotFather (ejemplo: 123456789:ABCdefGhIjk...).'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (empty($chat_id) || strlen($chat_id) < 4) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'Chat ID inválido. Debe ser tu ID numérico de Telegram (obtenlo buscando @userinfobot en Telegram).'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Permitir solo monedas válidas o ALL
    $monedasValidas = ['ALL', 'BTC', 'ETH', 'SOL', 'BNB', 'LINK', 'AVAX', 'DOGE'];
    if (!in_array($moneda_filtro, $monedasValidas, true)) {
        $moneda_filtro = 'ALL';
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO usuario_telegram_alertas 
            (id_usuario, bot_token, chat_id, moneda_filtro, score_minimo, activo)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            bot_token = VALUES(bot_token),
            chat_id = VALUES(chat_id),
            moneda_filtro = VALUES(moneda_filtro),
            score_minimo = VALUES(score_minimo),
            activo = VALUES(activo),
            fecha_actualizacion = NOW()");
        
        $stmt->execute([
            $id_usuario,
            $bot_token,
            $chat_id,
            $moneda_filtro,
            $score_minimo,
            $activo
        ]);

        echo json_encode([
            'success' => true,
            'message' => '¡Configuración de tu bot de Telegram guardada correctamente!'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al guardar en base de datos: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ==========================================
// 3) PROBAR ALERTA DE TELEGRAM (TEST EN VIVO)
// ==========================================
if ($action === 'test_alert') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
        exit;
    }

    $raw = json_decode((string)file_get_contents('php://input'), true) ?: $_POST;
    $bot_token = trim((string)($raw['bot_token'] ?? ''));
    $chat_id = trim((string)($raw['chat_id'] ?? ''));
    $moneda = strtoupper(trim((string)($raw['moneda_filtro'] ?? 'SOL')));

    // Si viene enmascarado, usar el guardado en base de datos
    if (strpos($bot_token, '...') !== false || empty($bot_token)) {
        $stOld = $pdo->prepare("SELECT bot_token, chat_id, moneda_filtro FROM usuario_telegram_alertas WHERE id_usuario = ? LIMIT 1");
        $stOld->execute([$id_usuario]);
        $row = $stOld->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $bot_token = $row['bot_token'];
            if (empty($chat_id)) $chat_id = $row['chat_id'];
            if (empty($moneda)) $moneda = $row['moneda_filtro'];
        }
    }

    if (empty($bot_token) || empty($chat_id)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Falta el Token del Bot o el Chat ID.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $monedaNombre = ($moneda === 'ALL' || $moneda === '') ? 'TODAS LAS MONEDAS' : "{$moneda}/USDT";
    $fechaActual = date('Y-m-d H:i:s');

    $mensajePrueba = "🟡 <b>QUANTUM.AI — PRUEBA DE CONEXIÓN EXITOSA</b>\n"
        . "══════════════════════════════════\n"
        . "✅ <b>Tu bot personal de Telegram está activo y enlazado.</b>\n"
        . "👤 <b>Trader:</b> " . htmlspecialchars($uData['nombre'] ?: $uData['correo']) . "\n"
        . "🎯 <b>Filtro configurado:</b> <code>{$monedaNombre}</code>\n"
        . "🛡️ <b>Membresía:</b> " . ($acceso['tipo_acceso'] === 'vip' ? '👑 Quantum VIP' : '🎁 Prueba Gratuita (7 Días)') . "\n"
        . "⏱️ <b>Fecha/Hora:</b> <code>{$fechaActual}</code>\n"
        . "══════════════════════════════════\n"
        . "🚀 <i>A partir de ahora recibirás cada ruptura institucional y señal de alta confluencia de Binance Futuros directamente en este chat.</i>\n\n"
        . "👉 <a href=\"https://bot.mddorma.com\">Abrir Terminal Quantum.AI</a>";

    $res = enviarMensajeTelegram($bot_token, $chat_id, $mensajePrueba);

    if ($res['ok']) {
        echo json_encode([
            'success' => true,
            'message' => '🎉 ¡Mensaje de prueba enviado con éxito! Revisa tu Telegram ahora mismo.'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No pudimos enviar el mensaje. Detalle de Telegram: ' . $res['error'] . '. Recuerda abrir tu bot en Telegram y pulsar "Iniciar" (/start) antes de enviar la prueba.'
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Acción no reconocida.'], JSON_UNESCAPED_UNICODE);
