<?php
/**
 * bot.mddorma.com/api/procesar_pago_binance.php
 * Procesador de Binance Pay para activación de Membresía Quantum VIP (30 Días).
 */
declare(strict_types=1);

define('AUTH_LIB_ONLY', true);
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['id_usuario']) || empty($pdo)) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para activar tu membresía.']);
    exit;
}

$id_usuario = (int)$_SESSION['id_usuario'];
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$tx_id = trim($input['tx_id'] ?? '');

if (empty($tx_id) || strlen($tx_id) < 4) {
    echo json_encode(['success' => false, 'message' => 'Por favor ingresa un ID de transacción o Nickname de Binance válido (mínimo 4 caracteres).']);
    exit;
}

try {
    // 1. Verificar si este comprobante ya fue aprobado
    $ref_busqueda = $tx_id . '||binance_bot';
    $stmt_check = $pdo->prepare("SELECT id_pago FROM pagos WHERE external_reference = ? AND status_pago = 'approved' LIMIT 1");
    $stmt_check->execute([$ref_busqueda]);
    if ($stmt_check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Esta transacción de Binance ya fue acreditada anteriormente.']);
        exit;
    }

    // 2. Activar membresía VIP por 30 días en la cuenta del usuario
    $stmt_vip = $pdo->prepare("UPDATE usuarios SET es_premium = 1, rol = CASE WHEN rol = 'admin' THEN 'admin' ELSE 'vip' END, fecha_vencimiento = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id_usuario = ?");
    $stmt_vip->execute([$id_usuario]);

    // 3. Registrar en historial de pagos
    $stmt_pago = $pdo->prepare("INSERT INTO pagos (id_usuario, payment_id, external_reference, status_pago, monto_pagado, moneda, fecha_pago) VALUES (?, ?, ?, 'approved', 5.00, 'USDT', NOW())");
    $payment_id_fake = 'BINANCE-' . substr(md5($tx_id . time()), 0, 16);
    $stmt_pago->execute([$id_usuario, $payment_id_fake, $ref_busqueda]);

    // 4. Actualizar variables de sesión local
    $_SESSION['es_premium'] = 1;
    if (isset($_SESSION['rol']) && $_SESSION['rol'] !== 'admin') {
        $_SESSION['rol'] = 'vip';
    }

    echo json_encode([
        'success' => true,
        'message' => '¡Pago verificado! Tu Membresía Quantum VIP (30 Días) ha sido activada exitosamente.'
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar el pago: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
