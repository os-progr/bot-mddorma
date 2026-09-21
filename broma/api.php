<?php
/**
 * bot.mddorma.com/broma/api.php — Motor Backend del Panel de Control (Enclave Broma)
 * 
 * Acceso estrictamente restringido a usuarios con rol = 'admin'.
 * Gestión institucional de usuarios, estado VIP, señales cuánticas y firewall WAF.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

define('AUTH_LIB_ONLY', true);
require_once dirname(__DIR__) . '/api/auth.php';

// 1. Verificación Estricta de Acceso de Administrador (Identidad Criptográfica)
if (empty($_SESSION['id_usuario']) || empty($pdo)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión requerida. Inicia sesión como administrador.']);
    exit;
}

// Hash SHA-256 de la única identidad autorizada para este enclave
const MASTER_IDENTITY_HASH = 'd0c972995fa361ce664cf1efc8fb447ca345f301e6f8372166f94d7e743ae1fc';

try {
    $stmt_auth = $pdo->prepare("SELECT id_usuario, nombre, correo, rol, es_premium FROM usuarios WHERE id_usuario = ? LIMIT 1");
    $stmt_auth->execute([(int)$_SESSION['id_usuario']]);
    $admin_user = $stmt_auth->fetch(PDO::FETCH_ASSOC);

    $user_email_hash = hash('sha256', mb_strtolower(trim((string)($admin_user['correo'] ?? '')), 'UTF-8'));
    if (!$admin_user || !hash_equals(MASTER_IDENTITY_HASH, $user_email_hash)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acceso denegado al enclave operativo.']);
        exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de autenticación con la base de datos.']);
    exit;
}

// 2. Rutas y Almacenamiento
$storage_dir = dirname(__DIR__) . '/storage';
if (!is_dir($storage_dir)) {
    @mkdir($storage_dir, 0755, true);
}
$activas_file   = $storage_dir . '/senales_activas.json';
$historial_file = $storage_dir . '/historial.json';
$config_file    = $storage_dir . '/bot_config.json';
$banned_cache   = dirname(dirname(__DIR__)) . '/cache/banned_ips.json';

$action = trim($_GET['action'] ?? ($_POST['action'] ?? 'stats'));

// Helper para leer JSON
function read_json_file(string $path, array $default = []): array {
    if (file_exists($path)) {
        $content = @file_get_contents($path);
        if ($content) {
            $data = json_decode($content, true);
            if (is_array($data)) return $data;
        }
    }
    return $default;
}

// Helper para escribir JSON
function write_json_file(string $path, array $data): bool {
    return (bool)@file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

// ==========================================
// ACCIONES DEL ENCLAVE BROMA
// ==========================================

try {
    switch ($action) {

        // ──────────────────────────────────────────
        // 1. ESTADÍSTICAS Y KPIS GENERALES
        // ──────────────────────────────────────────
        case 'stats':
            // Total usuarios
            $total_users = (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
            
            // Total VIP
            $total_vip = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE es_premium = 1 OR LOWER(rol) = 'admin'")->fetchColumn();
            
            // Registrados hoy
            $users_today = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE DATE(fecha_registro) = CURDATE()")->fetchColumn();
            
            // Nuevos en últimos 7 días
            $users_week = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE fecha_registro >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
            
            // Señales activas
            $senales_activas = read_json_file($activas_file, []);
            $count_senales = count($senales_activas);

            // IPs bloqueadas en firewall
            $total_banned_ips = 0;
            try {
                $total_banned_ips = (int)$pdo->query("SELECT COUNT(*) FROM firewall_ips")->fetchColumn();
            } catch (Throwable $e) {
                $banned_data = read_json_file($banned_cache, []);
                $total_banned_ips = count($banned_data);
            }

            // Configuración actual
            $config_data = read_json_file($config_file, [
                'modo_bot' => 'AUTONOMO_CUANTICO',
                'estado' => 'ONLINE',
                'anuncio_global' => '',
                'anuncio_activo' => false
            ]);

            echo json_encode([
                'success' => true,
                'stats' => [
                    'total_usuarios' => $total_users,
                    'total_vip' => $total_vip,
                    'usuarios_hoy' => $users_today,
                    'usuarios_semana' => $users_week,
                    'senales_activas' => $count_senales,
                    'ips_bloqueadas' => $total_banned_ips,
                    'config' => $config_data
                ]
            ]);
            break;

        // ──────────────────────────────────────────
        // 2. LISTAR USUARIOS (PAGINADO Y BUSCADOR)
        // ──────────────────────────────────────────
        case 'get_users':
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(50, max(5, (int)($_GET['limit'] ?? 15)));
            $offset = ($page - 1) * $limit;
            $search = trim((string)($_GET['search'] ?? ''));
            $filter = trim((string)($_GET['filter'] ?? 'all'));

            $where = [];
            $params = [];

            if ($search !== '') {
                $where[] = "(nombre LIKE :s OR correo LIKE :s OR id_usuario = :id_s)";
                $params[':s'] = "%$search%";
                $params[':id_s'] = is_numeric($search) ? (int)$search : 0;
            }

            if ($filter === 'vip') {
                $where[] = "(es_premium = 1 OR LOWER(rol) = 'admin')";
            } elseif ($filter === 'admin') {
                $where[] = "LOWER(rol) = 'admin'";
            } elseif ($filter === 'trial') {
                $where[] = "es_premium = 0 AND fecha_registro >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            } elseif ($filter === 'expired') {
                $where[] = "es_premium = 0 AND fecha_registro < DATE_SUB(NOW(), INTERVAL 7 DAY) AND LOWER(rol) != 'admin'";
            }

            $where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

            // Total con filtro
            $st_cnt = $pdo->prepare("SELECT COUNT(*) FROM usuarios $where_sql");
            $st_cnt->execute($params);
            $total_filtered = (int)$st_cnt->fetchColumn();

            // Lista paginada
            $sql = "SELECT id_usuario, nombre, correo, rol, es_premium, fecha_registro, ip_registro, foto_perfil 
                    FROM usuarios 
                    $where_sql 
                    ORDER BY id_usuario DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $pdo->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $raw_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $users_list = [];
            foreach ($raw_users as $u) {
                $acceso = calcular_acceso_usuario($u);
                $users_list[] = [
                    'id_usuario' => (int)$u['id_usuario'],
                    'nombre' => htmlspecialchars($u['nombre'] ?? 'Sin Nombre'),
                    'correo' => htmlspecialchars($u['correo'] ?? ''),
                    'rol' => htmlspecialchars($u['rol'] ?? 'usuario'),
                    'es_premium' => (int)($u['es_premium'] ?? 0),
                    'fecha_registro' => $u['fecha_registro'] ?? '',
                    'ip_registro' => htmlspecialchars($u['ip_registro'] ?? '-'),
                    'tipo_acceso' => $acceso['tipo_acceso'] ?? 'invitado',
                    'dias_restantes' => $acceso['dias_restantes'] ?? 0,
                    'es_vip' => !empty($acceso['es_vip'])
                ];
            }

            echo json_encode([
                'success' => true,
                'users' => $users_list,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => max(1, (int)ceil($total_filtered / $limit)),
                    'total_records' => $total_filtered,
                    'limit' => $limit
                ]
            ]);
            break;

        // ──────────────────────────────────────────
        // 3. ACTIVAR / DESACTIVAR VIP
        // ──────────────────────────────────────────
        case 'toggle_vip':
            $user_id = (int)($_POST['id_usuario'] ?? 0);
            $estado_nuevo = (int)($_POST['es_premium'] ?? 0);

            if ($user_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID de usuario inválido.']);
                exit;
            }

            $st = $pdo->prepare("UPDATE usuarios SET es_premium = ? WHERE id_usuario = ?");
            $st->execute([$estado_nuevo, $user_id]);

            echo json_encode([
                'success' => true,
                'message' => $estado_nuevo ? "Membresía VIP activada con éxito." : "Membresía VIP revocada.",
                'es_premium' => $estado_nuevo
            ]);
            break;

        // ──────────────────────────────────────────
        // 4. CAMBIAR ROL DE USUARIO
        // ──────────────────────────────────────────
        case 'change_role':
            $user_id = (int)($_POST['id_usuario'] ?? 0);
            $nuevo_rol = strtolower(trim((string)($_POST['rol'] ?? 'usuario')));

            if ($user_id <= 0 || !in_array($nuevo_rol, ['admin', 'usuario', 'moderador'], true)) {
                echo json_encode(['success' => false, 'message' => 'Rol o ID de usuario inválido.']);
                exit;
            }

            // Evitar auto-degradación accidental del superadmin actual
            if ($user_id === (int)$admin_user['id_usuario'] && $nuevo_rol !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'No puedes remover tu propio rol de administrador.']);
                exit;
            }

            $st = $pdo->prepare("UPDATE usuarios SET rol = ? WHERE id_usuario = ?");
            $st->execute([$nuevo_rol, $user_id]);

            echo json_encode([
                'success' => true,
                'message' => "Rol actualizado a '" . ucfirst($nuevo_rol) . "' con éxito.",
                'nuevo_rol' => $nuevo_rol
            ]);
            break;

        // ──────────────────────────────────────────
        // 5. RENOVAR / EXTENDER PRUEBA GRATIS DE 7 DÍAS
        // ──────────────────────────────────────────
        case 'extend_trial':
            $user_id = (int)($_POST['id_usuario'] ?? 0);
            if ($user_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID inválido.']);
                exit;
            }

            // Reinicia la fecha_registro al momento actual, otorgando 7 días completos
            $st = $pdo->prepare("UPDATE usuarios SET fecha_registro = NOW() WHERE id_usuario = ?");
            $st->execute([$user_id]);

            echo json_encode([
                'success' => true,
                'message' => "Prueba gratuita de 7 días renovada exitosamente."
            ]);
            break;

        // ──────────────────────────────────────────
        // 6. RESTABLECER CONTRASEÑA DE USUARIO
        // ──────────────────────────────────────────
        case 'reset_password':
            $user_id = (int)($_POST['id_usuario'] ?? 0);
            $nueva_pass = trim((string)($_POST['nueva_password'] ?? ''));

            if ($user_id <= 0 || strlen($nueva_pass) < 6) {
                echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres.']);
                exit;
            }

            $hash = password_hash($nueva_pass, PASSWORD_BCRYPT);
            $st = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id_usuario = ?");
            $st->execute([$hash, $user_id]);

            echo json_encode([
                'success' => true,
                'message' => "Contraseña actualizada exitosamente."
            ]);
            break;

        // ──────────────────────────────────────────
        // 7. SEÑALES: LISTAR ACTIVAS E HISTORIAL
        // ──────────────────────────────────────────
        case 'get_signals':
            $activas = read_json_file($activas_file, []);
            $historial = read_json_file($historial_file, []);

            echo json_encode([
                'success' => true,
                'activas' => $activas,
                'historial' => array_slice(array_reverse($historial), 0, 20)
            ]);
            break;

        // ──────────────────────────────────────────
        // 8. SEÑALES: EMITIR NUEVA SEÑAL MANUAL
        // ──────────────────────────────────────────
        case 'add_signal':
            $par = strtoupper(trim((string)($_POST['par'] ?? 'BTCUSDT')));
            $tipo = strtoupper(trim((string)($_POST['tipo'] ?? 'BUY')));
            $precio = (float)($_POST['precio_entrada'] ?? 0.0);
            $sl = (float)($_POST['stop_loss'] ?? 0.0);
            $tp = (float)($_POST['take_profit'] ?? 0.0);
            $apalancamiento = max(1, (int)($_POST['apalancamiento'] ?? 10));
            $razon = trim((string)($_POST['razon'] ?? 'Señal Cuántica Institucional'));

            if ($precio <= 0 || $sl <= 0 || $tp <= 0) {
                echo json_encode(['success' => false, 'message' => 'Precios de entrada, SL y TP deben ser mayores a 0.']);
                exit;
            }

            $activas = read_json_file($activas_file, []);
            $nueva_senal = [
                'id' => 'SIG_' . time() . '_' . rand(100, 999),
                'par' => $par,
                'tipo' => $tipo,
                'lado' => ($tipo === 'BUY' || $tipo === 'LONG') ? 'LONG' : 'SHORT',
                'precio_entrada' => $precio,
                'stop_loss' => $sl,
                'take_profit' => $tp,
                'apalancamiento' => $apalancamiento,
                'confianza' => 92,
                'estrategia' => 'Quantum SVP & Liquidity Flow',
                'razon' => $razon,
                'timestamp' => time(),
                'fecha_hora' => date('Y-m-d H:i:s'),
                'estado' => 'ACTIVA',
                'pnl_estimado' => '+0.00%'
            ];

            array_unshift($activas, $nueva_senal);
            write_json_file($activas_file, $activas);

            echo json_encode([
                'success' => true,
                'message' => "Señal para {$par} emitida exitosamente.",
                'senal' => $nueva_senal
            ]);
            break;

        // ──────────────────────────────────────────
        // 9. SEÑALES: CERRAR / FINALIZAR SEÑAL
        // ──────────────────────────────────────────
        case 'close_signal':
            $signal_id = trim((string)($_POST['id'] ?? ''));
            $resultado = trim((string)($_POST['resultado'] ?? 'WIN'));
            $pnl_final = trim((string)($_POST['pnl'] ?? '+2.50%'));

            if ($signal_id === '') {
                echo json_encode(['success' => false, 'message' => 'ID de señal requerido.']);
                exit;
            }

            $activas = read_json_file($activas_file, []);
            $historial = read_json_file($historial_file, []);

            $senal_cerrada = null;
            $nuevas_activas = [];
            foreach ($activas as $s) {
                if (($s['id'] ?? '') === $signal_id) {
                    $s['estado'] = ($resultado === 'WIN') ? 'TP_ALCANZADO' : 'SL_EJECUTADO';
                    $s['fecha_cierre'] = date('Y-m-d H:i:s');
                    $s['pnl_realizado'] = $pnl_final;
                    $senal_cerrada = $s;
                } else {
                    $nuevas_activas[] = $s;
                }
            }

            if ($senal_cerrada) {
                write_json_file($activas_file, $nuevas_activas);
                $historial[] = $senal_cerrada;
                write_json_file($historial_file, $historial);

                echo json_encode([
                    'success' => true,
                    'message' => "Señal cerrada y archivada en el historial."
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Señal no encontrada en las activas.']);
            }
            break;

        // ──────────────────────────────────────────
        // 10. FIREWALL WAF: LISTA DE IPS BLOQUEADAS
        // ──────────────────────────────────────────
        case 'get_firewall_ips':
            $ips = [];
            try {
                $st = $pdo->query("SELECT id, ip, motivo, fecha FROM firewall_ips ORDER BY id DESC LIMIT 50");
                $ips = $st->fetchAll(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {
                // Fallback a archivo json
                $cache_ips = read_json_file($banned_cache, []);
                foreach ($cache_ips as $ip => $val) {
                    $ips[] = ['id' => 0, 'ip' => $ip, 'motivo' => 'Baneado por WAF', 'fecha' => date('Y-m-d')];
                }
            }

            echo json_encode([
                'success' => true,
                'ips' => $ips
            ]);
            break;

        // ──────────────────────────────────────────
        // 11. FIREWALL WAF: DESBLOQUEAR IP (UNBAN)
        // ──────────────────────────────────────────
        case 'unban_ip':
            $ip_to_unban = trim((string)($_POST['ip'] ?? ''));

            if (!filter_var($ip_to_unban, FILTER_VALIDATE_IP)) {
                echo json_encode(['success' => false, 'message' => 'Dirección IP inválida.']);
                exit;
            }

            // 1. Quitar de base de datos
            try {
                $st = $pdo->prepare("DELETE FROM firewall_ips WHERE ip = ?");
                $st->execute([$ip_to_unban]);
            } catch (Throwable $e) {}

            // 2. Quitar del archivo de caché rápido
            $cache_ips = read_json_file($banned_cache, []);
            if (isset($cache_ips[$ip_to_unban])) {
                unset($cache_ips[$ip_to_unban]);
                write_json_file($banned_cache, $cache_ips);
            }

            echo json_encode([
                'success' => true,
                'message' => "IP {$ip_to_unban} desbloqueada del Firewall."
            ]);
            break;

        // ──────────────────────────────────────────
        // 12. FIREWALL WAF: BLOQUEAR IP MANUALMENTE
        // ──────────────────────────────────────────
        case 'ban_ip':
            $ip_to_ban = trim((string)($_POST['ip'] ?? ''));
            $motivo = trim((string)($_POST['motivo'] ?? 'Bloqueo manual por Administrador'));

            if (!filter_var($ip_to_ban, FILTER_VALIDATE_IP)) {
                echo json_encode(['success' => false, 'message' => 'Dirección IP inválida.']);
                exit;
            }

            try {
                $st = $pdo->prepare("INSERT INTO firewall_ips (ip, motivo) VALUES (?, ?)");
                $st->execute([$ip_to_ban, $motivo]);
            } catch (Throwable $e) {}

            $cache_ips = read_json_file($banned_cache, []);
            $cache_ips[$ip_to_ban] = true;
            write_json_file($banned_cache, $cache_ips);

            echo json_encode([
                'success' => true,
                'message' => "IP {$ip_to_ban} bloqueada permanentemente."
            ]);
            break;

        // ──────────────────────────────────────────
        // 13. AJUSTES GLOBALES DEL BOT
        // ──────────────────────────────────────────
        case 'save_settings':
            $modo = trim((string)($_POST['modo_bot'] ?? 'AUTONOMO_CUANTICO'));
            $estado = trim((string)($_POST['estado'] ?? 'ONLINE'));
            $anuncio = trim((string)($_POST['anuncio_global'] ?? ''));
            $anuncio_activo = !empty($_POST['anuncio_activo']);

            $new_config = [
                'modo_bot' => $modo,
                'estado' => $estado,
                'anuncio_global' => $anuncio,
                'anuncio_activo' => $anuncio_activo,
                'ultima_actualizacion' => date('Y-m-d H:i:s'),
                'admin_modificador' => $admin_user['nombre']
            ];

            write_json_file($config_file, $new_config);

            echo json_encode([
                'success' => true,
                'message' => "Configuraciones globales actualizadas.",
                'config' => $new_config
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Acción desconocida: '{$action}'"]);
            break;
    }

} catch (Throwable $err) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Excepción del servidor: ' . $err->getMessage()
    ]);
}
