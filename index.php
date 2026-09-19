<?php
/**
 * index.php — ENTRADA PRINCIPAL CON PRECARGA DE SESIÓN (PHP + MySQL + Realtime)
 * 
 * Verifica la sesión de usuario directamente en el servidor antes de entregar el HTML,
 * eliminando el parpadeo de carga y pre-inyectando el estado de prueba gratis o VIP.
 */
declare(strict_types=1);

$currentUser = null;
$serverUserAccess = [
    'acceso_total' => false,
    'tipo_acceso' => 'invitado',
    'dias_restantes' => 7,
    'es_vip' => false,
    'mensaje_acceso' => '🎁 7 Días de Prueba Gratis · Regístrate para activar'
];

try {
    // Reutilizar lógica de conexión y cálculo de acceso de auth.php
    define('AUTH_LIB_ONLY', true);
    require_once __DIR__ . '/api/auth.php';
    if (!empty($_SESSION['id_usuario']) && isset($pdo)) {
        $st = $pdo->prepare("SELECT id_usuario, nombre, correo, rol, es_premium, fecha_registro FROM usuarios WHERE id_usuario = ? LIMIT 1");
        $st->execute([(int)$_SESSION['id_usuario']]);
        $u = $st->fetch(PDO::FETCH_ASSOC);
        if ($u) {
            $currentUser = [
                'id_usuario' => (int)$u['id_usuario'],
                'nombre' => $u['nombre'],
                'correo' => $u['correo'],
                'rol' => $u['rol'],
                'es_premium' => (int)$u['es_premium'],
                'fecha_registro' => $u['fecha_registro']
            ];
            $serverUserAccess = calcular_acceso_usuario($u);
        }
    }
} catch (\Throwable $e) {
    // Si la BD no está disponible o el usuario no tiene sesión, degradar amistosamente a invitado
}

$htmlFile = __DIR__ . '/index.html';
if (file_exists($htmlFile)) {
    header('Content-Type: text/html; charset=utf-8');
    $html = file_get_contents($htmlFile);
    $hydrationScript = '<script>'
        . 'window.serverCurrentUser = ' . json_encode($currentUser, JSON_UNESCAPED_UNICODE) . ';'
        . 'window.serverUserAccess = ' . json_encode($serverUserAccess, JSON_UNESCAPED_UNICODE) . ';'
        . '</script>';

    echo str_replace('</head>', $hydrationScript . "\n</head>", $html);
} else {
    echo "Error: Interfaz no disponible.";
}
