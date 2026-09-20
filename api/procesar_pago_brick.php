<?php
/**
 * bot.mddorma.com/api/procesar_pago_brick.php
 * Procesa el cobro con Tarjeta Débito/Crédito mediante Card Brick de Mercado Pago ($5 USD / S/ 19.00 PEN).
 */
declare(strict_types=1);

define('AUTH_LIB_ONLY', true);
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['id_usuario']) || empty($pdo)) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión.']);
    exit;
}

$id_usuario = (int)$_SESSION['id_usuario'];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['token'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos de la tarjeta.']);
    exit;
}

$mp_access_token = $_ENV['MP_ACCESS_TOKEN_LIVE'] ?? getenv('MP_ACCESS_TOKEN_LIVE') ?: 'APP_USR-5802049572993180-081111-1e9bb0cbbea0412f11a4325ef2e26e1b-3496449440';
$transaction_amount = 19.00; // S/ 19.00 PEN ≈ $5.00 USD

$payment_data = [
    "transaction_amount" => (float)$transaction_amount,
    "token" => $data['token'],
    "description" => "Membresía Quantum VIP (30 Días)",
    "installments" => (int)($data['installments'] ?? 1),
    "payment_method_id" => $data['payment_method_id'] ?? '',
    "issuer_id" => $data['issuer_id'] ?? null,
    "payer" => [
        "email" => $data['payer']['email'] ?? ($_SESSION['correo'] ?? 'cliente@mddorma.com'),
        "identification" => [
            "type" => $data['payer']['identification']['type'] ?? '',
            "number" => $data['payer']['identification']['number'] ?? ''
        ]
    ],
    "external_reference" => "BOT_VIP_CARD_" . $id_usuario . "_" . time()
];

$ch = curl_init('https://api.mercadopago.com/v1/payments');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $mp_access_token,
    'Content-Type: application/json',
    'X-Idempotency-Key: ' . uniqid('mp_brick_', true)
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$res = json_decode((string)$response, true);

if ($res && isset($res['status'])) {
    $status = $res['status'];
    $payment_id = (string)($res['id'] ?? '');

    if ($status === 'approved') {
        // Activar VIP en base de datos
        try {
            $stmt = $pdo->prepare("UPDATE usuarios SET es_premium = 1, rol = CASE WHEN rol = 'admin' THEN 'admin' ELSE 'vip' END, fecha_vencimiento = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id_usuario = ?");
            $stmt->execute([$id_usuario]);

            $stmt_pago = $pdo->prepare("INSERT INTO pagos (id_usuario, payment_id, external_reference, status_pago, monto_pagado, moneda, fecha_pago) VALUES (?, ?, ?, 'approved', ?, 'PEN', NOW())");
            $stmt_pago->execute([$id_usuario, $payment_id, $payment_data['external_reference'], $transaction_amount]);

            $_SESSION['es_premium'] = 1;
            if (isset($_SESSION['rol']) && $_SESSION['rol'] !== 'admin') {
                $_SESSION['rol'] = 'vip';
            }
        } catch (Throwable $e) {}

        echo json_encode(['success' => true, 'status' => 'approved', 'message' => '¡Pago con tarjeta aprobado! Tu membresía VIP está activa por 30 días.']);
        exit;
    } elseif ($status === 'in_process') {
        echo json_encode(['success' => true, 'status' => 'in_process', 'message' => 'Tu pago está en proceso de revisión por tu banco. Se activará en breve.']);
        exit;
    } else {
        $status_detail = $res['status_detail'] ?? 'Pago rechazado.';
        echo json_encode(['success' => false, 'error' => 'Pago rechazado por el banco: ' . $status_detail]);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'error' => $res['message'] ?? 'Error al procesar el cobro de la tarjeta.']);
    exit;
}
