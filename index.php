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
            $is_master_admin = function_exists('es_master_admin_autorizado') && es_master_admin_autorizado($u['correo'] ?? '');
        }
    }
} catch (\Throwable $e) {
    // Si la BD no está disponible o el usuario no tiene sesión, degradar amistosamente a invitado
}

$htmlFile = __DIR__ . '/index.html';
if (file_exists($htmlFile)) {
    header('Content-Type: text/html; charset=utf-8');
    $html = file_get_contents($htmlFile);

    // Inyección ultra-segura del icono de control Broma ÚNICAMENTE para la identidad maestra cifrada
    if (!empty($is_master_admin)) {
        $adminIconHtml = '
      <!-- Enclave Privado Broma (Exclusivo para la Identidad Maestra Autorizada) -->
      <a href="/broma/" class="p-2.5 rounded-xl text-cyan-400 bg-cyan-500/15 border border-cyan-500/40 hover:bg-cyan-500/25 hover:text-cyan-300 transition" title="Enclave de Control Broma">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
      </a>';
        $html = str_replace('<a href="/perfil/"', $adminIconHtml . "\n      " . '<a href="/perfil/"', $html);
    }

    $hydrationScript = '<script>'
        . 'window.serverCurrentUser = ' . json_encode($currentUser, JSON_UNESCAPED_UNICODE) . ';'
        . 'window.serverUserAccess = ' . json_encode($serverUserAccess, JSON_UNESCAPED_UNICODE) . ';'
        . '</script>';

    echo str_replace('</head>', $hydrationScript . "\n</head>", $html);
} else {
    echo "Error: Interfaz no disponible.";
}
