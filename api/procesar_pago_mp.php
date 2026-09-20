<?php
/**
 * bot.mddorma.com/api/procesar_pago_mp.php
 * Genera preferencia de pago en Mercado Pago para la Membresía Quantum VIP ($5 USD / S/ 19.00 PEN).
 */
declare(strict_types=1);

define('AUTH_LIB_ONLY', true);
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión primero.']);
    exit;
}

$id_usuario = (int)$_SESSION['id_usuario'];
$correo_usuario = $_SESSION['correo'] ?? 'cliente@mddorma.com';

// Cargar credenciales de Mercado Pago desde .env
$mp_access_token = $_ENV['MP_ACCESS_TOKEN_LIVE'] ?? getenv('MP_ACCESS_TOKEN_LIVE') ?: 'APP_USR-5802049572993180-081111-1e9bb0cbbea0412f11a4325ef2e26e1b-3496449440';
$mp_sub_link = $_ENV['MP_SUBSCRIPTION_LINK'] ?? getenv('MP_SUBSCRIPTION_LINK') ?: '';

if (!empty($mp_sub_link)) {
    echo json_encode(['success' => true, 'init_point' => $mp_sub_link]);
    exit;
}

$precio_pen = 19.00; // S/ 19.00 PEN ≈ $5.00 USD
$titulo = "Membresía Quantum VIP Trader (30 Días)";

$data = [
    "items" => [
        [
            "title" => $titulo,
            "quantity" => 1,
            "unit_price" => (float)$precio_pen,
            "currency_id" => "PEN"
        ]
    ],
    "payer" => [
        "email" => $correo_usuario
    ],
    "back_urls" => [
        "success" => "https://bot.mddorma.com/vip/?status=success",
        "failure" => "https://bot.mddorma.com/vip/?status=failure",
        "pending" => "https://bot.mddorma.com/vip/?status=pending"
    ],
    "auto_return" => "approved",
    "external_reference" => "BOT_VIP_" . $id_usuario . "_" . time()
];

$ch = curl_init('https://api.mercadopago.com/checkout/preferences');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $mp_access_token,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$res_data = json_decode((string)$response, true);

if ($http_code === 201 && !empty($res_data['init_point'])) {
    echo json_encode([
        'success' => true,
        'init_point' => $res_data['init_point']
    ]);
    exit;
} else {
    // Si la API falla, responder con mensaje amigable
    echo json_encode([
        'success' => false,
        'message' => $res_data['message'] ?? 'No se pudo generar la preferencia de Mercado Pago.'
    ]);
    exit;
}
