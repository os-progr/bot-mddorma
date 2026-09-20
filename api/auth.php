<?php
/**
 * api/auth.php — Sistema de Autenticación y Sesión de Base de Datos para bot.mddorma.com
 * Conectado a la base de datos MySQL (tabla: usuarios)
 */
if (!defined('AUTH_LIB_ONLY')) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
}

// 1. Conexión Directa y Aislada a MySQL (Sin cargar db_connect.php ni session.php de la web de doramas)
$env_loader = '/home/qtenqbhl/public_html/api/env_loader.php';
if (!file_exists($env_loader)) {
    $env_loader = dirname(dirname(__DIR__)) . '/api/env_loader.php';
}
if (file_exists($env_loader)) {
    require_once $env_loader;
    $env_file = '/home/qtenqbhl/public_html/.env';
    if (!file_exists($env_file)) {
        $env_file = dirname(dirname(__DIR__)) . '/.env';
    }
    if (file_exists($env_file) && function_exists('load_env')) {
        @load_env($env_file);
    }
}

$db_host = $_ENV['DB_HOST'] ?? (getenv('DB_HOST') ?: 'localhost');
$db_name = $_ENV['DB_NAME'] ?? (getenv('DB_NAME') ?: 'qtenqbhl_Dorama2026');
$db_user = $_ENV['DB_USER'] ?? (getenv('DB_USER') ?: 'qtenqbhl_YJEZdEZVCxAR');
$db_pass = $_ENV['DB_PASS'] ?? (getenv('DB_PASS') ?: '');

if (empty($pdo)) {
    try {
        $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT => false
        ]);
        $pdo->exec("SET time_zone = '-05:00'");
    } catch (Throwable $e) {
        if (!defined('AUTH_LIB_ONLY')) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos.']);
            exit;
        }
    }
}

// 2. Sesión Exclusiva y Aislada para bot.mddorma.com (Zero colisiones, Zero saturación, 30 días persistente)
if (session_status() === PHP_SESSION_NONE) {
    session_name('QUANTUM_BOT_SESSID'); // Sesión 100% independiente de doramas
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    
    session_set_cookie_params([
        'lifetime' => 86400 * 30, // 30 días persistente para traders
        'path' => '/',
        'domain' => '', // Restringido exclusivamente al subdominio actual
        'secure' => $is_https,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

if (!function_exists('get_real_client_ip')) {
    function get_real_client_ip() {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}

// Función para calcular con precisión la prueba de 7 días y membresía VIP
function calcular_acceso_usuario(?array $u): array {
    if (!$u) {
        return [
            'acceso_total' => false,
            'tipo_acceso' => 'invitado',
            'dias_restantes' => 7,
            'es_vip' => false,
            'mensaje_acceso' => '⚡ 7 Días de Prueba Gratis · Regístrate para activar'
        ];
    }

    $es_vip = ((int)($u['es_premium'] ?? 0) === 1) || in_array(strtolower($u['rol'] ?? ''), ['admin', 'moderador'], true);

    if ($es_vip) {
        return [
            'acceso_total' => true,
            'tipo_acceso' => 'vip',
            'dias_restantes' => 9999,
            'es_vip' => true,
            'mensaje_acceso' => '👑 Membresía VIP Activa'
        ];
    }

    // Calcular días desde fecha de registro
    $fecha_reg = !empty($u['fecha_registro']) ? $u['fecha_registro'] : date('Y-m-d H:i:s');
    $segundos = time() - strtotime($fecha_reg);
    $dias_pasados = max(0, $segundos / 86400);

    if ($dias_pasados <= 7) {
        $dias_restantes = max(1, (int)ceil(7 - $dias_pasados));
        return [
            'acceso_total' => true,
            'tipo_acceso' => 'prueba_gratis',
            'dias_restantes' => $dias_restantes,
            'es_vip' => false,
            'mensaje_acceso' => "🎁 Prueba Gratuita: {$dias_restantes} día(s) restante(s)"
        ];
    }

    // Pasaron más de 7 días y no cuenta con membresía VIP
    return [
        'acceso_total' => false,
        'tipo_acceso' => 'prueba_expirada',
        'dias_restantes' => 0,
        'es_vip' => false,
        'mensaje_acceso' => '⚠️ Prueba de 7 días finalizada · Requiere VIP ($5/mes)'
    ];
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'me');

// Generar o recuperar CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Si se incluye como librería en index.php u otros scripts, retornar sin ejecutar router HTTP
if (defined('AUTH_LIB_ONLY') || (basename($_SERVER['SCRIPT_FILENAME'] ?? '') !== 'auth.php' && !isset($_GET['action']) && !isset($_POST['action']))) {
    return;
}

// ==========================================
// ACCIÓN: ME (Comprobar sesión activa)
// ==========================================
if ($action === 'me') {
    $logged_in = !empty($_SESSION['id_usuario']);
    $user_data = null;
    $acceso_info = calcular_acceso_usuario(null);

    if ($logged_in) {
        // Refrescar datos desde la base de datos
        try {
            $stmt = $pdo->prepare("SELECT id_usuario, nombre, correo, rol, es_premium, foto_perfil, fecha_registro FROM usuarios WHERE id_usuario = ? LIMIT 1");
            $stmt->execute([$_SESSION['id_usuario']]);
            $u = $stmt->fetch();
            if ($u) {
                $acceso_info = calcular_acceso_usuario($u);
                $user_data = array_merge([
                    'id' => (int)$u['id_usuario'],
                    'nombre' => $u['nombre'],
                    'correo' => $u['correo'],
                    'rol' => $u['rol'],
                    'es_premium' => (int)$u['es_premium'],
                    'fecha_registro' => $u['fecha_registro'] ?? date('Y-m-d H:i:s')
                ], $acceso_info);
            } else {
                // Usuario ya no existe
                session_destroy();
                $logged_in = false;
                $acceso_info = calcular_acceso_usuario(null);
            }
        } catch (Throwable $e) {
            $logged_in = false;
            $acceso_info = calcular_acceso_usuario(null);
        }
    }

    echo json_encode([
        'success' => true,
        'logged_in' => $logged_in,
        'user' => $user_data,
        'acceso' => $acceso_info,
        'csrf_token' => $_SESSION['csrf_token']
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ==========================================
// ACCIÓN: GOOGLE / GOOGLE_LOGIN (OAuth con Base de Datos)
// ==========================================
if ($action === 'google' || $action === 'google_login') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id_token = $input['credential'] ?? ($input['id_token'] ?? '');

    if (empty($id_token)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Token de credencial de Google no recibido.']);
        exit;
    }

    // 1. Validar el token con el endpoint oficial de Google Tokeninfo
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($id_token);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || empty($response)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token de Google inválido o expirado. Intenta de nuevo.']);
        exit;
    }

    $payload = json_decode($response, true);
    if (!is_array($payload)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Respuesta de Google inválida.']);
        exit;
    }

    // 2. Validar emisor (iss)
    $valid_issuers = ['accounts.google.com', 'https://accounts.google.com'];
    $issuer = $payload['iss'] ?? '';
    if (!in_array($issuer, $valid_issuers, true)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Emisor de token de Google no reconocido.']);
        exit;
    }

    $google_email = strtolower(trim($payload['email'] ?? ''));
    if (empty($google_email) || !filter_var($google_email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Correo de Google inválido.']);
        exit;
    }

    $google_name = trim($payload['name'] ?? '');
    if (empty($google_name) || strpos($google_name, '@') !== false) {
        $parts = explode('@', $google_email);
        $google_name = ucfirst($parts[0]);
    }
    $google_picture = $payload['picture'] ?? null;

    try {
        // 3. Buscar si el usuario ya existe en la base de datos MySQL (tabla: usuarios)
        $stmt = $pdo->prepare("SELECT id_usuario, nombre, correo, rol, es_premium, foto_perfil, fecha_registro FROM usuarios WHERE correo = ? LIMIT 1");
        $stmt->execute([$google_email]);
        $user = $stmt->fetch();

        if ($user) {
            // Usuario ya registrado -> Actualizar foto si no tiene
            if (!empty($google_picture) && empty($user['foto_perfil'])) {
                $pdo->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id_usuario = ?")->execute([$google_picture, $user['id_usuario']]);
                $user['foto_perfil'] = $google_picture;
            }

            session_regenerate_id(true);
            $_SESSION['id_usuario'] = (int)$user['id_usuario'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['correo'] = $user['correo'];
            $_SESSION['rol'] = $user['rol'];
            $_SESSION['es_premium'] = (int)$user['es_premium'];

            $acceso_info = calcular_acceso_usuario($user);
            $user_payload = array_merge([
                'id' => (int)$user['id_usuario'],
                'nombre' => $user['nombre'],
                'correo' => $user['correo'],
                'rol' => $user['rol'],
                'es_premium' => (int)$user['es_premium'],
                'fecha_registro' => $user['fecha_registro'] ?? date('Y-m-d H:i:s')
            ], $acceso_info);

            $return_to = trim((string)($input['return_to'] ?? ($_POST['return_to'] ?? '')));
            if (!empty($return_to)) {
                header("Location: " . $return_to);
                exit;
            }

            echo json_encode([
                'success' => true,
                'message' => 'Autenticación con Google exitosa. ¡Bienvenido!',
                'user' => $user_payload,
                'acceso' => $acceso_info
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            // Usuario nuevo -> Crear cuenta en MySQL usuarios
            $random_pass = bin2hex(random_bytes(16));
            $hash = password_hash($random_pass, PASSWORD_BCRYPT);
            $codigo_referido = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 8);
            $now_str = date('Y-m-d H:i:s');
            $user_ip = get_real_client_ip();

            try {
                $insert = $pdo->prepare("INSERT INTO usuarios (nombre, correo, password_hash, rol, codigo_referido, es_premium, foto_perfil, fecha_registro, ip_registro) VALUES (?, ?, ?, 'usuario', ?, 0, ?, ?, ?)");
                $insert->execute([$google_name, $google_email, $hash, $codigo_referido, $google_picture, $now_str, $user_ip]);
            } catch (Throwable $t) {
                $insert = $pdo->prepare("INSERT INTO usuarios (nombre, correo, password_hash, rol, es_premium, fecha_registro) VALUES (?, ?, ?, 'usuario', 0, ?)");
                $insert->execute([$google_name, $google_email, $hash, $now_str]);
            }

            $new_id = (int)$pdo->lastInsertId();

            session_regenerate_id(true);
            $_SESSION['id_usuario'] = $new_id;
            $_SESSION['nombre'] = $google_name;
            $_SESSION['correo'] = $google_email;
            $_SESSION['rol'] = 'usuario';
            $_SESSION['es_premium'] = 0;

            $new_user_data = [
                'id_usuario' => $new_id,
                'nombre' => $google_name,
                'correo' => $google_email,
                'rol' => 'usuario',
                'es_premium' => 0,
                'fecha_registro' => $now_str
            ];
            $acceso_info = calcular_acceso_usuario($new_user_data);
            $user_payload = array_merge([
                'id' => $new_id,
                'nombre' => $google_name,
                'correo' => $google_email,
                'rol' => 'usuario',
                'es_premium' => 0,
                'fecha_registro' => $now_str
            ], $acceso_info);

            $return_to = trim((string)($input['return_to'] ?? ($_POST['return_to'] ?? '')));
            if (!empty($return_to)) {
                header("Location: " . $return_to);
                exit;
            }

            echo json_encode([
                'success' => true,
                'message' => 'Cuenta creada y conectada con Google con éxito.',
                'user' => $user_payload,
                'acceso' => $acceso_info
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } catch (Throwable $e) {
        error_log("Error en Google Auth DB: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al conectar con la base de datos.']);
        exit;
    }
}

// ==========================================
// ACCIÓN: LOGIN (Iniciar sesión + Anti-Fuerza Bruta)
// ==========================================
if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $correo = trim(strtolower($input['correo'] ?? ''));
    $contrasena = (string)($input['contrasena'] ?? ($input['password'] ?? ''));

    if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Por favor, ingresa un correo electrónico válido.']);
        exit;
    }

    if (empty($contrasena)) {
        echo json_encode(['success' => false, 'message' => 'Por favor, ingresa tu contraseña.']);
        exit;
    }

    // Identificadores de protección Anti-Fuerza Bruta (IP y Cuenta)
    $user_ip = get_real_client_ip();
    $ip_key = 'ip:' . $user_ip;
    $email_key = 'email:' . md5($correo);

    try {
        // 🛡️ 1. Verificar bloqueo por Fuerza Bruta (Máx 5 intentos en 15 min)
        $stmt_bf = $pdo->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip IN (?, ?)");
        $stmt_bf->execute([$ip_key, $email_key]);
        $bfs = $stmt_bf->fetchAll(PDO::FETCH_ASSOC);

        foreach ($bfs as $bf) {
            if ($bf && (int)$bf['attempts'] >= 5) {
                $last = new DateTime($bf['last_attempt']);
                $now = new DateTime();
                $diff = $now->diff($last);
                if ($diff->i < 15 && $diff->h == 0 && $diff->days == 0) {
                    $minutos_restantes = 15 - $diff->i;
                    http_response_code(429);
                    echo json_encode([
                        'success' => false, 
                        'message' => "⚠️ Demasiados intentos fallidos. Esta cuenta o IP ha sido bloqueada temporalmente por seguridad. Reintenta en {$minutos_restantes} minuto(s)."
                    ]);
                    exit;
                } else {
                    $pdo->prepare("UPDATE login_attempts SET attempts = 0 WHERE ip IN (?, ?)")->execute([$ip_key, $email_key]);
                }
            }
        }

        // 2. Comprobar credenciales de usuario
        $stmt = $pdo->prepare("SELECT id_usuario, nombre, correo, password_hash, rol, es_premium, fecha_registro FROM usuarios WHERE correo = ? LIMIT 1");
        $stmt->execute([$correo]);
        $user = $stmt->fetch();

        if ($user && password_verify($contrasena, $user['password_hash'])) {
            // Login exitoso: reiniciar contador de fuerza bruta
            $pdo->prepare("UPDATE login_attempts SET attempts = 0 WHERE ip IN (?, ?)")->execute([$ip_key, $email_key]);

            session_regenerate_id(true);
            $_SESSION['id_usuario'] = (int)$user['id_usuario'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['correo'] = $user['correo'];
            $_SESSION['rol'] = $user['rol'];
            $_SESSION['es_premium'] = (int)$user['es_premium'];

            $acceso_info = calcular_acceso_usuario($user);
            $user_payload = array_merge([
                'id' => (int)$user['id_usuario'],
                'nombre' => $user['nombre'],
                'correo' => $user['correo'],
                'rol' => $user['rol'],
                'es_premium' => (int)$user['es_premium'],
                'fecha_registro' => $user['fecha_registro'] ?? date('Y-m-d H:i:s')
            ], $acceso_info);

            echo json_encode([
                'success' => true,
                'message' => 'Inicio de sesión exitoso. ¡Bienvenido!',
                'user' => $user_payload,
                'acceso' => $acceso_info
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            // Incrementar contador de intentos fallidos tanto para la IP como para la cuenta objetivo
            $pdo->prepare("INSERT INTO login_attempts (ip, attempts, last_attempt) VALUES (?, 1, NOW()) ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = NOW()")->execute([$ip_key]);
            $pdo->prepare("INSERT INTO login_attempts (ip, attempts, last_attempt) VALUES (?, 1, NOW()) ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = NOW()")->execute([$email_key]);

            echo json_encode(['success' => false, 'message' => 'Correo o contraseña incorrectos.']);
            exit;
        }
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Error interno al procesar el login.']);
        exit;
    }
}

// ==========================================
// ACCIÓN: REGISTER (Crear cuenta rápida)
// ==========================================
if ($action === 'register') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $nombre = trim($input['nombre'] ?? '');
    $correo = trim(strtolower($input['correo'] ?? ''));
    $contrasena = (string)($input['contrasena'] ?? ($input['password'] ?? ''));

    if (empty($nombre) || strlen($nombre) < 2) {
        echo json_encode(['success' => false, 'message' => 'Ingresa tu nombre o apodo (mínimo 2 caracteres).']);
        exit;
    }

    if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Ingresa un correo electrónico válido.']);
        exit;
    }

    if (strlen($contrasena) < 6) {
        echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres.']);
        exit;
    }

    try {
        // Verificar si el correo ya existe
        $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE correo = ? LIMIT 1");
        $stmt->execute([$correo]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Este correo ya está registrado. Inicia sesión directamente.']);
            exit;
        }

        // Crear usuario
        $now_str = date('Y-m-d H:i:s');
        $hash = password_hash($contrasena, PASSWORD_BCRYPT);
        $insert = $pdo->prepare("INSERT INTO usuarios (nombre, correo, password_hash, rol, es_premium, fecha_registro) VALUES (?, ?, ?, 'usuario', 0, ?)");
        $insert->execute([$nombre, $correo, $hash, $now_str]);
        $new_id = (int)$pdo->lastInsertId();

        // Autologin
        session_regenerate_id(true);
        $_SESSION['id_usuario'] = $new_id;
        $_SESSION['nombre'] = $nombre;
        $_SESSION['correo'] = $correo;
        $_SESSION['rol'] = 'usuario';
        $_SESSION['es_premium'] = 0;

        $new_user_data = [
            'id_usuario' => $new_id,
            'nombre' => $nombre,
            'correo' => $correo,
            'rol' => 'usuario',
            'es_premium' => 0,
            'fecha_registro' => $now_str
        ];
        $acceso_info = calcular_acceso_usuario($new_user_data);
        $user_payload = array_merge([
            'id' => $new_id,
            'nombre' => $nombre,
            'correo' => $correo,
            'rol' => 'usuario',
            'es_premium' => 0,
            'fecha_registro' => $now_str
        ], $acceso_info);

        echo json_encode([
            'success' => true,
            'message' => '¡Cuenta creada con éxito! Tus 7 días de prueba gratuita están activos.',
            'user' => $user_payload,
            'acceso' => $acceso_info
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Error al registrar la cuenta.']);
        exit;
    }
}

// ==========================================
// ACCIÓN: VALIDATE_TX (Validar pago Cripto / Activar VIP)
// ==========================================
if ($action === 'validate_tx') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
        exit;
    }

    if (empty($_SESSION['id_usuario'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para activar tu membresía VIP.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $tx_hash = trim((string)($input['tx_hash'] ?? ''));
    $network = trim((string)($input['network'] ?? 'BEP20'));
    $id_usuario = (int)$_SESSION['id_usuario'];

    if (strlen($tx_hash) < 10) {
        echo json_encode(['success' => false, 'message' => 'El Hash / TxID ingresado no es válido.']);
        exit;
    }

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS pagos_cripto_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT NOT NULL,
            tx_hash VARCHAR(120) NOT NULL UNIQUE,
            red VARCHAR(20) DEFAULT 'BEP20',
            monto DECIMAL(10,2) DEFAULT 5.00,
            estado VARCHAR(20) DEFAULT 'COMPLETADO',
            fecha_pago DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $stCheck = $pdo->prepare("SELECT id FROM pagos_cripto_log WHERE tx_hash = ? LIMIT 1");
        $stCheck->execute([$tx_hash]);
        if ($stCheck->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Este comprobante ya ha sido utilizado anteriormente.']);
            exit;
        }

        $stIns = $pdo->prepare("INSERT INTO pagos_cripto_log (id_usuario, tx_hash, red, monto, estado) VALUES (?, ?, ?, 5.00, 'COMPLETADO')");
        $stIns->execute([$id_usuario, $tx_hash, $network]);

        $stUp = $pdo->prepare("UPDATE usuarios SET es_premium = 1 WHERE id_usuario = ?");
        $stUp->execute([$id_usuario]);

        $_SESSION['es_premium'] = 1;

        echo json_encode([
            'success' => true,
            'message' => '¡Comprobante verificado con éxito! Tu Membresía VIP ($5/mes) ya está activa.',
            'es_vip' => true
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Error al procesar el comprobante: ' . $e->getMessage()]);
        exit;
    }
}

// ==========================================
// ACCIÓN: UPDATE_PROFILE (Actualizar datos básicos)
// ==========================================
if ($action === 'update_profile') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
        exit;
    }

    if (empty($_SESSION['id_usuario']) || empty($pdo)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para actualizar tu perfil.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $nombre = trim((string)($input['nombre'] ?? ''));

    if (mb_strlen($nombre) < 2) {
        echo json_encode(['success' => false, 'message' => 'El nombre debe tener al menos 2 caracteres.']);
        exit;
    }

    if (mb_strlen($nombre) > 80) {
        echo json_encode(['success' => false, 'message' => 'El nombre no puede exceder los 80 caracteres.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ? WHERE id_usuario = ?");
        $stmt->execute([$nombre, (int)$_SESSION['id_usuario']]);
        $_SESSION['nombre'] = $nombre;

        echo json_encode([
            'success' => true,
            'message' => 'Perfil actualizado exitosamente.',
            'nombre' => $nombre
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el perfil en la base de datos.']);
        exit;
    }
}

// ==========================================
// ACCIÓN: CHANGE_PASSWORD (Cambiar Contraseña)
// ==========================================
if ($action === 'change_password') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
        exit;
    }

    if (empty($_SESSION['id_usuario']) || empty($pdo)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para cambiar tu contraseña.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $current_pass = (string)($input['current_password'] ?? '');
    $new_pass = (string)($input['new_password'] ?? '');
    $confirm_pass = (string)($input['confirm_password'] ?? '');

    if (strlen($new_pass) < 6) {
        echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 6 caracteres.']);
        exit;
    }

    if ($new_pass !== $confirm_pass) {
        echo json_encode(['success' => false, 'message' => 'La confirmación de la nueva contraseña no coincide.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT password_hash FROM usuarios WHERE id_usuario = ? LIMIT 1");
        $stmt->execute([(int)$_SESSION['id_usuario']]);
        $row = $stmt->fetch();

        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
            exit;
        }

        $existing_hash = $row['password_hash'] ?? '';

        // Si ya tiene una contraseña configurada, verificar la actual
        if (!empty($existing_hash)) {
            if (empty($current_pass)) {
                echo json_encode(['success' => false, 'message' => 'Por favor ingresa tu contraseña actual.']);
                exit;
            }
            if (!password_verify($current_pass, $existing_hash)) {
                echo json_encode(['success' => false, 'message' => 'La contraseña actual no es correcta.']);
                exit;
            }
        }

        $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
        $update = $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE id_usuario = ?");
        $update->execute([$new_hash, (int)$_SESSION['id_usuario']]);

        echo json_encode([
            'success' => true,
            'message' => '¡Tu contraseña ha sido actualizada con éxito!'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la contraseña en la base de datos.']);
        exit;
    }
}

// ==========================================
// ACCIÓN: LOGOUT (Cerrar sesión)
// ==========================================
if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();

    if (!empty($_GET['redirect'])) {
        header('Location: ' . $_GET['redirect']);
        exit;
    }
    if ((isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'text/html') !== false) || !empty($_GET['redirect_login'])) {
        header('Location: /login.php');
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Sesión cerrada exitosamente.']);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Acción no reconocida.']);
