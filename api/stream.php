<?php
/**
 * api/stream.php — TIEMPO REAL NATIVO EN PHP (Server-Sent Events / SSE)
 * 
 * Permite que el navegador reciba actualizaciones instantáneas enviadas por el servidor
 * sin depender de servicios externos de pago (Pusher, Firebase) ni requerir daemons
 * de WebSockets (Ratchet) que cPanel suele matar por tiempo de CPU.
 */
declare(strict_types=1);

// 1. Cabeceras obligatorias para SSE en tiempo real
header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Evita que Nginx/LiteSpeed almacene en búfer

// Permitir ejecución prolongada controlada (25 segundos por ciclo de conexión)
@set_time_limit(30);
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', 'off');

while (ob_get_level() > 0) {
    ob_end_flush();
}
flush();

const STORAGE_DIR     = __DIR__ . '/../storage';
const ACTIVAS_FILE    = STORAGE_DIR . '/senales_activas.json';
const HISTORIAL_FILE  = STORAGE_DIR . '/historial.json';
const CONTEXTO_FILE   = STORAGE_DIR . '/contexto_global.json';

// Función para emitir un evento SSE al navegador
function emitEvent(string $event, array $data): void
{
    echo "event: {$event}\n";
    echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}

// 2. Enviar señal inicial de bienvenida e intervalo de reconexión
echo "retry: 1500\n\n"; // Si se corta, el navegador reconecta en 1.5s automáticamente
emitEvent('connected', ['status' => 'online', 'time' => gmdate('Y-m-d H:i:s')]);

$ultimoMtime = 0;
$inicio = time();
$maxDuracion = 25; // Cerrar amistosamente a los 25s para que el navegador reconecte sin sobrecargar PHP-FPM

while (time() - $inicio < $maxDuracion) {
    if (connection_aborted()) {
        break;
    }

    // Calcular firma de cambio según timestamps de los archivos generados por el bot y guardados en MySQL/storage
    $m1 = is_file(ACTIVAS_FILE) ? (int)filemtime(ACTIVAS_FILE) : 0;
    $m2 = is_file(HISTORIAL_FILE) ? (int)filemtime(HISTORIAL_FILE) : 0;
    $m3 = is_file(CONTEXTO_FILE) ? (int)filemtime(CONTEXTO_FILE) : 0;
    $mtimeActual = max($m1, $m2, $m3);

    if ($mtimeActual > $ultimoMtime) {
        $ultimoMtime = $mtimeActual;

        // Leer datos actuales
        $activas = [];
        if (is_file(ACTIVAS_FILE)) {
            $tmp = json_decode((string)@file_get_contents(ACTIVAS_FILE), true);
            if (is_array($tmp)) { $activas = $tmp; }
        }

        $historial = [];
        if (is_file(HISTORIAL_FILE)) {
            $tmp = json_decode((string)@file_get_contents(HISTORIAL_FILE), true);
            if (is_array($tmp)) { $historial = $tmp; }
        }
        $historial = array_slice(array_reverse($historial), 0, 10);

        $contexto = null;
        if (is_file(CONTEXTO_FILE)) {
            $tmpC = json_decode((string)@file_get_contents(CONTEXTO_FILE), true);
            if (is_array($tmpC)) { $contexto = $tmpC; }
        }

        emitEvent('signals_update', [
            'success' => true,
            'activas' => $activas,
            'historial' => $historial,
            'contexto' => $contexto,
            'timestamp' => $mtimeActual,
            'servidor_tiempo' => gmdate('Y-m-d H:i:s')
        ]);
    } else {
        // Enviar un ping de latido para mantener viva la conexión
        echo ": keep-alive " . time() . "\n\n";
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    // Esperar 1 segundo antes de la siguiente verificación interna
    sleep(1);
}

// Cierre normal para permitir rotación de conexión limpia
echo "event: cycle_complete\ndata: {}\n\n";
flush();
