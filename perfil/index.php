<?php
/**
 * bot.mddorma.com/perfil/index.php — Panel de Perfil del Operador Quantum AI
 * 
 * Gestiona información de cuenta, seguridad de acceso, estado de membresía VIP
 * y sincronización con el enclave institucional de trading.
 */
declare(strict_types=1);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');

define('AUTH_LIB_ONLY', true);
require_once dirname(__DIR__) . '/api/auth.php';

// Verificar sesión activa
if (empty($_SESSION['id_usuario']) || empty($pdo)) {
    header("Location: /login.php?redirect=" . urlencode('/perfil/'));
    exit;
}

$user = null;
try {
    $stmt = $pdo->prepare("SELECT id_usuario, nombre, correo, rol, es_premium, foto_perfil, fecha_registro, codigo_referido, ip_registro FROM usuarios WHERE id_usuario = ? LIMIT 1");
    $stmt->execute([(int)$_SESSION['id_usuario']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    // Si hay error en la base de datos
}

if (!$user) {
    session_destroy();
    header("Location: /login.php?mensaje=session_expired");
    exit;
}

$acceso_info = calcular_acceso_usuario($user);
$es_vip = !empty($acceso_info['es_vip']);
$dias_restantes = (int)($acceso_info['dias_restantes'] ?? 7);
$tipo_acceso = $acceso_info['tipo_acceso'] ?? 'invitado';
$trader_id = sprintf('#QAI-%05d', (int)$user['id_usuario']);
$fecha_reg_raw = $user['fecha_registro'] ?? date('Y-m-d H:i:s');
$fecha_reg = date('d/m/Y, H:i', strtotime($fecha_reg_raw));
$nombre_actual = htmlspecialchars($user['nombre'] ?: 'Operador Cuántico');
$correo_actual = htmlspecialchars($user['correo'] ?: '');

// Validar foto de perfil para evitar iconos rotos
$foto_valida = false;
$foto_perfil = null;
if (!empty($user['foto_perfil'])) {
    $fp = trim((string)$user['foto_perfil']);
    if (filter_var($fp, FILTER_VALIDATE_URL)) {
        $foto_valida = true;
        $foto_perfil = htmlspecialchars($fp);
    }
}
$inicial_nombre = mb_strtoupper(mb_substr($user['nombre'] ?: 'O', 0, 1));
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mi Perfil de Operador | QUANTUM.AI Institutional</title>
  <link rel="icon" type="image/png" href="/assets/logo.png">

  <!-- Fuentes & Tailwind CSS -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            obsidian: {
              950: '#05070d',
              900: '#0b0f19',
              850: '#0e1422',
              800: '#111726',
              700: '#1b233a'
            },
            gold: {
              400: '#fbbf24',
              500: '#f59e0b',
              600: '#d97706'
            },
            emerald: {
              400: '#34d399',
              500: '#10b981',
              600: '#059669'
            },
            sky: {
              400: '#38bdf8',
              500: '#0ea5e9',
              600: '#0284c7'
            }
          },
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
            mono: ['"JetBrains Mono"', 'monospace']
          }
        }
      }
    }
  </script>

  <style>
    body {
      background-color: #05070d;
      color: #cbd5e1;
      font-size: 13px;
      background-image: 
        radial-gradient(circle at 20% 15%, rgba(245, 158, 11, 0.04) 0%, transparent 40%),
        radial-gradient(circle at 80% 60%, rgba(14, 165, 233, 0.03) 0%, transparent 50%),
        linear-gradient(to right, rgba(255, 255, 255, 0.012) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(255, 255, 255, 0.012) 1px, transparent 1px);
      background-size: 100% 100%, 100% 100%, 32px 32px, 32px 32px;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .glass-panel {
      background: rgba(11, 15, 25, 0.85);
      backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.07);
    }

    .glow-amber {
      box-shadow: 0 0 35px -5px rgba(245, 158, 11, 0.18);
    }

    .badge-pulse {
      animation: pulseDot 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    @keyframes pulseDot {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.35; transform: scale(0.85); }
    }

    /* Autofill fix */
    input:-webkit-autofill,
    input:-webkit-autofill:hover, 
    input:-webkit-autofill:focus, 
    input:-webkit-autofill:active {
      -webkit-box-shadow: 0 0 0 1000px #080d19 inset !important;
      -webkit-text-fill-color: #f1f5f9 !important;
      transition: background-color 5000s ease-in-out 0s;
      caret-color: #f59e0b;
    }
  </style>
</head>
<body class="font-sans antialiased selection:bg-amber-500 selection:text-black">

  <!-- ================= BARRA SUPERIOR INSTITUCIONAL ================= -->
  <header class="sticky top-0 z-40 bg-[#05070d]/90 backdrop-blur-md border-b border-slate-800/80 px-4 lg:px-8 py-3">
    <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
      
      <!-- Brand Logo Oficial -->
      <a href="/" class="flex items-center gap-3 group">
        <img src="/assets/logo.png" alt="Quantum AI" class="w-9 h-9 object-contain drop-shadow-[0_0_12px_rgba(245,158,11,0.4)] group-hover:scale-105 transition duration-200">
        <div>
          <div class="flex items-center gap-1.5 leading-none">
            <span class="text-white font-black tracking-wider text-base">QUANTUM</span>
            <span class="text-amber-400 font-black text-base">AI</span>
            <span class="text-[9px] font-mono font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30 px-1.5 py-0.5 rounded">PERFIL DE TRADER</span>
          </div>
          <p class="text-[10px] text-slate-400 font-mono tracking-wide mt-0.5">ENCLAVE DE USUARIO</p>
        </div>
      </a>

      <!-- Navegación Modular Superior -->
      <nav class="hidden lg:flex items-center gap-1 bg-[#0b0f19] border border-slate-800 p-1 rounded-xl text-xs font-semibold">
        <a href="/?tab=senales" class="px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/60 transition flex items-center gap-1.5">
          <span>⚡</span> Señales
        </a>
        <a href="/?tab=grafico" class="px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/60 transition flex items-center gap-1.5">
          <span>📈</span> Gráfico
        </a>
        <a href="/?tab=calculadora" class="px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/60 transition flex items-center gap-1.5">
          <span>🛡️</span> Calculadora
        </a>
        <a href="/telegram/" class="px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/60 transition flex items-center gap-1.5">
          <span>📱</span> Mi Bot Telegram
        </a>
        <a href="/vip/" class="px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/60 transition flex items-center gap-1.5">
          <span>👑</span> Membresía VIP
        </a>
        <a href="/perfil/" class="px-3 py-1.5 rounded-lg bg-amber-400 text-slate-950 font-black flex items-center gap-1.5 shadow-sm shadow-amber-400/20">
          <span>👤</span> Mi Perfil
        </a>
      </nav>

      <!-- Estado en Vivo & Salir -->
      <div class="flex items-center gap-3">
        <div class="hidden sm:flex items-center gap-1.5 text-[11px] font-mono text-emerald-400 font-semibold bg-[#0b0f19] px-2.5 py-1.5 rounded-xl border border-slate-800">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 badge-pulse"></span>
          <span>FIX 4.4: <strong class="text-white">ACTIVO</strong></span>
        </div>

        <a href="/api/auth.php?action=logout&redirect_login=1" class="flex items-center gap-1.5 bg-[#141b2d] hover:bg-rose-950/40 text-slate-300 hover:text-rose-400 border border-slate-800 hover:border-rose-500/40 text-xs font-bold px-3 py-1.5 rounded-xl transition duration-150">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          <span class="hidden sm:inline">Cerrar Sesión</span>
        </a>
      </div>

    </div>
  </header>

  <!-- ================= SUB-NAVBAR MÓVIL ================= -->
  <div class="lg:hidden bg-[#080d1a] border-b border-slate-800 px-4 py-2 overflow-x-auto">
    <div class="flex items-center gap-1.5 text-xs font-bold shrink-0">
      <a href="/?tab=senales" class="px-3 py-1.5 rounded-lg text-slate-300 hover:text-white bg-slate-800/40">⚡ Señales</a>
      <a href="/?tab=grafico" class="px-3 py-1.5 rounded-lg text-slate-300 hover:text-white bg-slate-800/40">📈 Gráfico</a>
      <a href="/?tab=calculadora" class="px-3 py-1.5 rounded-lg text-slate-300 hover:text-white bg-slate-800/40">🛡️ Riesgo</a>
      <a href="/telegram/" class="px-3 py-1.5 rounded-lg text-slate-300 hover:text-white bg-slate-800/40">📱 Telegram</a>
      <a href="/vip/" class="px-3 py-1.5 rounded-lg text-amber-400 bg-amber-500/10 border border-amber-500/30">👑 VIP</a>
      <a href="/perfil/" class="px-3 py-1.5 rounded-lg bg-amber-400 text-slate-950 font-black">👤 Perfil</a>
    </div>
  </div>

  <!-- ================= CONTENEDOR PRINCIPAL: ARQUITECTURA DE 2 COLUMNAS ================= -->
  <main class="flex-1 max-w-7xl w-full mx-auto px-4 lg:px-8 py-7">

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-7 items-start">

      <!-- ================= COLUMNA IZQUIERDA: TARJETA DEL TRADER & INFRAESTRUCTURA (5 cols) ================= -->
      <aside class="lg:col-span-5 space-y-5">

        <!-- Tarjeta Principal del Operador -->
        <div class="glass-panel rounded-3xl p-6 relative overflow-hidden border border-slate-800 glow-amber space-y-5">
          <div class="absolute -right-12 -top-12 w-48 h-48 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

          <!-- Cabecera de Identidad: Avatar Limpio + Datos -->
          <div class="flex items-center gap-4">
            <!-- Avatar Seguro -->
            <div class="relative shrink-0">
              <?php if ($foto_valida && $foto_perfil): ?>
                <img 
                  src="<?= $foto_perfil ?>" 
                  alt="<?= $nombre_actual ?>" 
                  onerror="this.style.display='none'; document.getElementById('avatarFallback').style.display='flex';"
                  class="w-16 h-16 rounded-2xl object-cover border-2 border-amber-500/50 shadow-[0_0_20px_rgba(245,158,11,0.25)]"
                >
                <div id="avatarFallback" class="hidden w-16 h-16 rounded-2xl bg-gradient-to-tr from-amber-600 via-amber-500 to-amber-300 text-slate-950 font-black text-2xl items-center justify-center border-2 border-amber-400/60 shadow-[0_0_25px_rgba(245,158,11,0.3)]">
                  <?= $inicial_nombre ?>
                </div>
              <?php else: ?>
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-amber-600 via-amber-500 to-amber-300 text-slate-950 font-black text-2xl flex items-center justify-center border-2 border-amber-400/60 shadow-[0_0_25px_rgba(245,158,11,0.3)]">
                  <?= $inicial_nombre ?>
                </div>
              <?php endif; ?>
              
              <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full <?= $es_vip ? 'bg-amber-400' : 'bg-emerald-400' ?> border-2 border-[#0b0f19] flex items-center justify-center text-[9px]" title="Cuenta Activa">
                <?= $es_vip ? '👑' : '⚡' ?>
              </div>
            </div>

            <!-- Nombre y Badge -->
            <div class="min-w-0 flex-1 space-y-1">
              <div class="flex items-center gap-2">
                <h1 class="text-xl font-black text-white truncate" id="displayProfileName">
                  <?= $nombre_actual ?>
                </h1>
                <span class="text-amber-400 text-xs" title="Operador Registrado">✓</span>
              </div>
              <p class="text-xs text-slate-400 font-mono truncate"><?= $correo_actual ?></p>
              
              <div class="pt-0.5">
                <?php if ($es_vip): ?>
                  <span class="inline-flex items-center gap-1.5 bg-amber-500/15 border border-amber-500/40 text-amber-300 text-[10px] font-mono font-bold px-2.5 py-0.5 rounded-full">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                    👑 MEMBRESÍA VIP ACTIVA
                  </span>
                <?php elseif ($tipo_acceso === 'prueba_gratis'): ?>
                  <span class="inline-flex items-center gap-1.5 bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-[10px] font-mono font-bold px-2.5 py-0.5 rounded-full">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    ⚡ PRUEBA: <?= $dias_restantes ?> DÍAS RESTANTES
                  </span>
                <?php else: ?>
                  <span class="inline-flex items-center gap-1.5 bg-rose-500/15 border border-rose-500/40 text-rose-300 text-[10px] font-mono font-bold px-2.5 py-0.5 rounded-full">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                    ⚠️ PRUEBA FINALIZADA
                  </span>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Separador -->
          <div class="h-px bg-slate-800/80"></div>

          <!-- Cuadrícula Integrada de Especificaciones de Cuenta -->
          <div class="grid grid-cols-2 gap-2.5 text-xs font-mono">
            <!-- UID Operador -->
            <div class="bg-[#070b14] border border-slate-800/90 rounded-2xl p-3 flex flex-col justify-between">
              <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">UID OPERADOR</span>
              <div class="flex items-center justify-between mt-1">
                <strong class="text-white font-black text-sm tracking-wide"><?= $trader_id ?></strong>
                <button onclick="copyTraderUid('<?= $trader_id ?>')" class="text-slate-500 hover:text-amber-400 text-xs transition" title="Copiar UID">
                  📋
                </button>
              </div>
            </div>

            <!-- Fecha de Registro -->
            <div class="bg-[#070b14] border border-slate-800/90 rounded-2xl p-3 flex flex-col justify-between">
              <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">REGISTRO</span>
              <strong class="text-slate-300 text-xs mt-1 truncate" title="<?= $fecha_reg ?>"><?= $fecha_reg ?></strong>
            </div>

            <!-- Protocolo FIX DMA -->
            <div class="bg-[#070b14] border border-slate-800/90 rounded-2xl p-3 flex flex-col justify-between">
              <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">ENCLAVE DMA</span>
              <div class="flex items-center gap-1.5 mt-1 text-sky-400 text-xs font-bold">
                <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>
                <span>FIX 4.4 LD4</span>
              </div>
            </div>

            <!-- Sesión -->
            <div class="bg-[#070b14] border border-slate-800/90 rounded-2xl p-3 flex flex-col justify-between">
              <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">SESIÓN</span>
              <div class="flex items-center gap-1.5 mt-1 text-emerald-400 text-xs font-bold">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                <span>30 DÍAS ACTIVA</span>
              </div>
            </div>
          </div>

          <!-- Tarjeta de Estado VIP & Acceso Rápido -->
          <?php if (!$es_vip): ?>
            <div class="bg-gradient-to-br from-amber-500/10 via-[#0d1528] to-amber-500/5 border border-amber-500/30 rounded-2xl p-4 space-y-3">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <span class="text-amber-400 font-bold text-xs flex items-center gap-1.5">
                    <span>👑</span> Desbloquea Acceso Total
                  </span>
                  <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                    Accede a señales institucionales confluentes 24/7 sin límite de días.
                  </p>
                </div>
                <span class="text-xs font-mono font-black text-amber-300 bg-amber-500/20 px-2 py-0.5 rounded-lg shrink-0">
                  $5 USD/mes
                </span>
              </div>
              <a href="/vip/" class="w-full text-center inline-flex items-center justify-center gap-2 bg-gradient-to-r from-amber-400 via-amber-500 to-amber-600 hover:from-amber-300 hover:to-amber-500 text-slate-950 font-black text-xs py-2.5 px-4 rounded-xl shadow-lg shadow-amber-500/20 transition duration-150 transform hover:scale-[1.01] active:scale-[0.98]">
                <span>👑</span>
                <span>Activar Membresía VIP</span>
              </a>
            </div>
          <?php else: ?>
            <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-2xl p-3.5 flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <span class="text-lg">👑</span>
                <div>
                  <span class="text-emerald-300 font-bold text-xs block">Membresía VIP Activa</span>
                  <span class="text-[11px] text-slate-400">Acceso ilimitado a todas las herramientas</span>
                </div>
              </div>
              <a href="/vip/" class="text-xs font-bold text-amber-400 hover:text-amber-300 bg-[#070b14] border border-amber-500/30 px-3 py-1.5 rounded-lg transition">
                Gestionar
              </a>
            </div>
          <?php endif; ?>

          <!-- Botón Directo al Radar -->
          <div class="pt-1">
            <a href="/" class="w-full inline-flex items-center justify-center gap-2 bg-[#090f1e] hover:bg-[#121c35] text-amber-400 hover:text-amber-300 border border-slate-800 hover:border-amber-500/40 text-xs font-bold py-2.5 px-4 rounded-xl transition duration-150">
              <span>⚡</span>
              <span>Abrir Radar & Terminal Cuántico</span>
            </a>
          </div>

        </div>

      </aside>

      <!-- ================= COLUMNA DERECHA: FORMULARIOS DE CONFIGURACIÓN (7 cols) ================= -->
      <section class="lg:col-span-7 space-y-6">

        <!-- Tarjeta 1: Datos Personales -->
        <div class="glass-panel rounded-3xl p-6 border border-slate-800 space-y-5">
          <div class="flex items-center justify-between border-b border-slate-800/80 pb-3.5">
            <div>
              <h2 class="text-white font-extrabold text-sm sm:text-base flex items-center gap-2">
                <span>👤</span> Datos del Operador
              </h2>
              <p class="text-xs text-slate-400 mt-0.5">Configura tu nombre e identidad visible en el terminal.</p>
            </div>
            <span class="text-[10px] font-mono text-emerald-400 bg-emerald-500/10 border border-emerald-500/30 px-2.5 py-0.5 rounded-full">Sincronizado</span>
          </div>

          <form id="formUpdateProfile" onsubmit="handleUpdateProfile(event)" class="space-y-4">
            
            <!-- Input Nombre -->
            <div>
              <label for="inputNombre" class="block text-xs font-semibold text-slate-300 mb-1.5">
                Nombre de Operador
              </label>
              <div class="relative">
                <input 
                  type="text" 
                  id="inputNombre" 
                  name="nombre" 
                  value="<?= $nombre_actual ?>" 
                  required 
                  maxlength="80" 
                  class="w-full bg-[#080d19] border border-slate-700/80 rounded-xl px-3.5 py-2.5 text-white font-medium text-xs focus:outline-none focus:border-amber-400 transition"
                  placeholder="Tu Nombre o Alias de Trading"
                >
                <span class="absolute right-3.5 top-2.5 text-slate-500 text-xs pointer-events-none">✏️</span>
              </div>
              <p class="text-[11px] text-slate-500 mt-1">Visible en cabeceras y reportes de ejecución algorítmica.</p>
            </div>

            <!-- Input Correo -->
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                Correo Electrónico (Cuenta Maestra)
              </label>
              <div class="relative">
                <input 
                  type="email" 
                  value="<?= $correo_actual ?>" 
                  readonly 
                  class="w-full bg-[#060a14] border border-slate-800 text-slate-400 rounded-xl px-3.5 py-2.5 font-mono text-xs cursor-not-allowed select-all"
                >
                <span class="absolute right-3.5 top-2.5 text-emerald-400 text-xs" title="Verificado">✓</span>
              </div>
              <p class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
                <span>🔒</span> Vinculado a tu acceso institucional permanente.
              </p>
            </div>

            <!-- Alerta Formulario Perfil -->
            <div id="profileAlertBox" class="hidden text-xs p-3 rounded-xl font-mono"></div>

            <!-- Botón Guardar -->
            <div class="flex justify-end pt-1">
              <button 
                type="submit" 
                id="btnSaveProfile"
                class="inline-flex items-center gap-2 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-950 font-black text-xs px-5 py-2.5 rounded-xl transition duration-150 shadow-md shadow-amber-500/20 active:scale-95"
              >
                <span>💾</span>
                <span>Guardar Datos</span>
              </button>
            </div>

          </form>
        </div>

        <!-- Tarjeta 2: Seguridad & Contraseña (KAIROS AI Sentinel) -->
        <div class="glass-panel rounded-3xl p-6 border border-slate-800 space-y-5">
          <div class="flex items-center justify-between border-b border-slate-800/80 pb-3.5">
            <div>
              <h2 class="text-white font-extrabold text-sm sm:text-base flex items-center gap-2">
                <span>🔒</span> Seguridad & Contraseña
              </h2>
              <p class="text-xs text-slate-400 mt-0.5">Autorizado por KAIROS AI Sentinel mediante código seguro al correo.</p>
            </div>
            <span class="text-[10px] font-mono font-bold text-amber-400 bg-amber-500/10 border border-amber-500/30 px-2.5 py-0.5 rounded-full">KAIROS AI OTP</span>
          </div>

          <form id="formChangePassword" onsubmit="handleChangePassword(event)" class="space-y-4">
            
            <!-- Grid: Nueva y Confirmación lado a lado -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
              <!-- Nueva Clave -->
              <div>
                <label for="inputNewPass" class="block text-xs font-semibold text-slate-300 mb-1.5">
                  Nueva Contraseña <span class="text-slate-500 font-normal">(≥ 6 car.)</span>
                </label>
                <div class="relative">
                  <input 
                    type="password" 
                    id="inputNewPass" 
                    name="new_password" 
                    required 
                    minlength="6" 
                    class="w-full bg-[#080d19] border border-slate-700/80 rounded-xl px-3.5 py-2.5 text-white font-mono text-xs focus:outline-none focus:border-amber-400 transition pr-10"
                    placeholder="••••••••••••"
                  >
                  <button type="button" onclick="togglePassVisibility('inputNewPass')" class="absolute right-3.5 top-2.5 text-slate-400 hover:text-white text-xs">
                    👁️
                  </button>
                </div>
              </div>

              <!-- Confirmar Clave -->
              <div>
                <label for="inputConfirmPass" class="block text-xs font-semibold text-slate-300 mb-1.5">
                  Confirmar Contraseña
                </label>
                <div class="relative">
                  <input 
                    type="password" 
                    id="inputConfirmPass" 
                    name="confirm_password" 
                    required 
                    minlength="6" 
                    class="w-full bg-[#080d19] border border-slate-700/80 rounded-xl px-3.5 py-2.5 text-white font-mono text-xs focus:outline-none focus:border-amber-400 transition pr-10"
                    placeholder="••••••••••••"
                  >
                  <button type="button" onclick="togglePassVisibility('inputConfirmPass')" class="absolute right-3.5 top-2.5 text-slate-400 hover:text-white text-xs">
                    👁️
                  </button>
                </div>
              </div>
            </div>

            <!-- Paso de Verificación KAIROS AI -->
            <div class="bg-[#070b14] border border-slate-800/90 rounded-2xl p-4 space-y-3">
              <div class="flex items-center justify-between">
                <div>
                  <span class="text-xs font-bold text-white flex items-center gap-1.5">
                    <span>⚡</span> Código de Seguridad KAIROS
                  </span>
                  <p class="text-[11px] text-slate-400 mt-0.5">
                    Se enviará a: <strong class="text-amber-400 font-mono"><?= $correo_actual ?></strong>
                  </p>
                </div>
                <button 
                  type="button" 
                  id="btnSendCode" 
                  onclick="handleSendVerificationCode()"
                  class="inline-flex items-center gap-1.5 bg-amber-500/15 hover:bg-amber-500/25 text-amber-300 border border-amber-500/40 text-xs font-bold px-3 py-1.5 rounded-xl transition duration-150 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span>📨</span>
                  <span id="btnSendCodeText">Enviar Código</span>
                </button>
              </div>

              <div>
                <input 
                  type="text" 
                  id="inputOtpCode" 
                  name="verification_code" 
                  maxlength="6" 
                  inputmode="numeric" 
                  pattern="[0-9]{6}" 
                  class="w-full bg-[#0b101e] border border-slate-700/80 rounded-xl px-4 py-2.5 text-center font-mono font-black text-amber-400 text-lg tracking-[8px] placeholder:tracking-normal placeholder:font-normal placeholder:text-slate-600 placeholder:text-xs focus:outline-none focus:border-amber-400 transition"
                  placeholder="Introduce los 6 dígitos del correo"
                >
              </div>
            </div>

            <!-- Alerta Formulario Contraseña -->
            <div id="passwordAlertBox" class="hidden text-xs p-3 rounded-xl font-mono"></div>

            <!-- Botón Actualizar Clave -->
            <div class="flex justify-end pt-1">
              <button 
                type="submit" 
                id="btnChangePass"
                class="inline-flex items-center gap-2 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-950 font-black text-xs px-5 py-2.5 rounded-xl transition duration-150 shadow-md shadow-amber-500/20 active:scale-95"
              >
                <span>🔑</span>
                <span>Confirmar y Cambiar Contraseña</span>
              </button>
            </div>

          </form>
        </div>

      </section>

    </div>

  </main>

  <!-- ================= NOTIFICACIÓN TOAST FLOTANTE ================= -->
  <div id="toast" class="fixed bottom-6 right-6 hidden z-50 items-center gap-3 bg-[#0b0f19] border border-amber-500/40 text-white px-5 py-3 rounded-2xl shadow-2xl shadow-amber-500/20 backdrop-blur-xl transition-all duration-300">
    <div id="toastIcon" class="w-6 h-6 rounded-full bg-amber-400/20 text-amber-400 flex items-center justify-center text-xs font-black">
      ✓
    </div>
    <span id="toastMsg" class="text-xs font-medium">Operación realizada con éxito</span>
  </div>

  <!-- ================= CONTROLADOR JAVASCRIPT ================= -->
  <script>
    // Copiar UID al portapapeles
    function copyTraderUid(uid) {
      if (navigator.clipboard) {
        navigator.clipboard.writeText(uid).then(() => {
          showToast(`UID copiado: ${uid}`, true);
        });
      }
    }

    // Mostrar/ocultar contraseña
    function togglePassVisibility(inputId) {
      const input = document.getElementById(inputId);
      if (input.type === 'password') {
        input.type = 'text';
      } else {
        input.type = 'password';
      }
    }

    // Alerta flotante Toast
    function showToast(msg, isSuccess = true) {
      const toast = document.getElementById('toast');
      const msgEl = document.getElementById('toastMsg');
      const iconEl = document.getElementById('toastIcon');

      msgEl.textContent = msg;
      if (isSuccess) {
        toast.className = 'fixed bottom-6 right-6 flex z-50 items-center gap-3 bg-[#0b0f19] border border-emerald-500/40 text-white px-5 py-3 rounded-2xl shadow-2xl shadow-emerald-500/20 backdrop-blur-xl';
        iconEl.className = 'w-6 h-6 rounded-full bg-emerald-400/20 text-emerald-400 flex items-center justify-center text-xs font-black';
        iconEl.textContent = '✓';
      } else {
        toast.className = 'fixed bottom-6 right-6 flex z-50 items-center gap-3 bg-[#0b0f19] border border-rose-500/40 text-white px-5 py-3 rounded-2xl shadow-2xl shadow-rose-500/20 backdrop-blur-xl';
        iconEl.className = 'w-6 h-6 rounded-full bg-rose-400/20 text-rose-400 flex items-center justify-center text-xs font-black';
        iconEl.textContent = '✕';
      }

      setTimeout(() => {
        toast.classList.add('hidden');
        toast.classList.remove('flex');
      }, 3500);
    }

    // Actualizar Perfil (Nombre)
    async function handleUpdateProfile(e) {
      e.preventDefault();
      const btn = document.getElementById('btnSaveProfile');
      const box = document.getElementById('profileAlertBox');
      const nombre = document.getElementById('inputNombre').value.trim();

      if (!nombre || nombre.length < 2) {
        box.className = 'text-xs p-3 rounded-xl font-mono bg-rose-500/10 text-rose-300 border border-rose-500/30 block';
        box.textContent = '⚠️ El nombre debe tener al menos 2 caracteres.';
        return;
      }

      btn.disabled = true;
      btn.innerHTML = '<span class="animate-spin mr-1">⏳</span> Guardando...';

      try {
        const res = await fetch('/api/auth.php?action=update_profile', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ nombre })
        });
        const data = await res.json();

        if (data.success) {
          box.className = 'text-xs p-3 rounded-xl font-mono bg-emerald-500/10 text-emerald-300 border border-emerald-500/30 block';
          box.textContent = '✅ ' + data.message;
          document.getElementById('displayProfileName').textContent = nombre;
          showToast('Perfil actualizado con éxito', true);
        } else {
          box.className = 'text-xs p-3 rounded-xl font-mono bg-rose-500/10 text-rose-300 border border-rose-500/30 block';
          box.textContent = '❌ ' + (data.message || 'Error al actualizar');
          showToast(data.message || 'Error al actualizar', false);
        }
      } catch (err) {
        box.className = 'text-xs p-3 rounded-xl font-mono bg-rose-500/10 text-rose-300 border border-rose-500/30 block';
        box.textContent = '❌ Error de comunicación con el servidor.';
      } finally {
        btn.disabled = false;
        btn.innerHTML = '<span>💾</span> <span>Guardar Datos</span>';
      }
    }

    // Temporizador para reenvío de código KAIROS
    let codeCountdownTimer = null;
    function startCodeCountdown(seconds = 60) {
      const btn = document.getElementById('btnSendCode');
      const text = document.getElementById('btnSendCodeText');
      if (!btn || !text) return;
      btn.disabled = true;
      let remaining = seconds;
      text.textContent = `Reenviar (${remaining}s)`;

      if (codeCountdownTimer) clearInterval(codeCountdownTimer);
      codeCountdownTimer = setInterval(() => {
        remaining--;
        if (remaining <= 0) {
          clearInterval(codeCountdownTimer);
          btn.disabled = false;
          text.textContent = 'Reenviar Código';
        } else {
          text.textContent = `Reenviar (${remaining}s)`;
        }
      }, 1000);
    }

    // Solicitar Código de Verificación OTP vía KAIROS AI
    async function handleSendVerificationCode() {
      const btn = document.getElementById('btnSendCode');
      const text = document.getElementById('btnSendCodeText');
      const box = document.getElementById('passwordAlertBox');
      const newPass = document.getElementById('inputNewPass').value;
      const confirmPass = document.getElementById('inputConfirmPass').value;

      if (!newPass || newPass.length < 6) {
        box.className = 'text-xs p-3 rounded-xl font-mono bg-rose-500/10 text-rose-300 border border-rose-500/30 block';
        box.textContent = '⚠️ Primero escribe tu nueva contraseña (mínimo 6 caracteres).';
        document.getElementById('inputNewPass').focus();
        return;
      }

      if (newPass !== confirmPass) {
        box.className = 'text-xs p-3 rounded-xl font-mono bg-rose-500/10 text-rose-300 border border-rose-500/30 block';
        box.textContent = '⚠️ Las contraseñas no coinciden. Verifícalas antes de solicitar el código.';
        document.getElementById('inputConfirmPass').focus();
        return;
      }

      btn.disabled = true;
      text.textContent = 'Enviando...';
      box.className = 'text-xs p-3 rounded-xl font-mono bg-amber-500/10 text-amber-300 border border-amber-500/30 block';
      box.textContent = '⏳ KAIROS AI está despachando tu código de seguridad...';

      try {
        const res = await fetch('/api/auth.php?action=send_password_code', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();

        if (data.success) {
          box.className = 'text-xs p-3 rounded-xl font-mono bg-emerald-500/10 text-emerald-300 border border-emerald-500/30 block';
          box.textContent = '✅ ' + data.message;
          showToast('Código enviado a tu correo', true);
          startCodeCountdown(data.wait_seconds || 60);
          document.getElementById('inputOtpCode').focus();
        } else {
          box.className = 'text-xs p-3 rounded-xl font-mono bg-rose-500/10 text-rose-300 border border-rose-500/30 block';
          box.textContent = '❌ ' + (data.message || 'Error al enviar código');
          showToast(data.message || 'Error al enviar código', false);
          if (data.wait_seconds) {
            startCodeCountdown(data.wait_seconds);
          } else {
            btn.disabled = false;
            text.textContent = 'Enviar Código';
          }
        }
      } catch (err) {
        box.className = 'text-xs p-3 rounded-xl font-mono bg-rose-500/10 text-rose-300 border border-rose-500/30 block';
        box.textContent = '❌ Error de comunicación con el servidor al despachar el código.';
        btn.disabled = false;
        text.textContent = 'Enviar Código';
      }
    }

    // Actualizar Contraseña con Código OTP
    async function handleChangePassword(e) {
      e.preventDefault();
      const btn = document.getElementById('btnChangePass');
      const box = document.getElementById('passwordAlertBox');
      const newPass = document.getElementById('inputNewPass').value;
      const confirmPass = document.getElementById('inputConfirmPass').value;
      const otpCode = document.getElementById('inputOtpCode').value.trim();

      if (!newPass || newPass.length < 6) {
        box.className = 'text-xs p-3 rounded-xl font-mono bg-rose-500/10 text-rose-300 border border-rose-500/30 block';
        box.textContent = '⚠️ La nueva contraseña debe tener al menos 6 caracteres.';
        return;
      }

      if (newPass !== confirmPass) {
        box.className = 'text-xs p-3 rounded-xl font-mono bg-rose-500/10 text-rose-300 border border-rose-500/30 block';
        box.textContent = '⚠️ La confirmación de contraseña no coincide.';
        return;
      }

      if (!otpCode || !/^[0-9]{6}$/.test(otpCode)) {
        box.className = 'text-xs p-3 rounded-xl font-mono bg-rose-500/10 text-rose-300 border border-rose-500/30 block';
        box.textContent = '⚠️ Por favor pulsa "Enviar Código" e ingresa los 6 dígitos recibidos en tu correo.';
        document.getElementById('inputOtpCode').focus();
        return;
      }

      btn.disabled = true;
      btn.innerHTML = '<span class="animate-spin mr-1">⏳</span> Validando con KAIROS AI...';

      try {
        const res = await fetch('/api/auth.php?action=verify_and_change_password', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            new_password: newPass,
            confirm_password: confirmPass,
            verification_code: otpCode
          })
        });
        const data = await res.json();

        if (data.success) {
          box.className = 'text-xs p-3 rounded-xl font-mono bg-emerald-500/10 text-emerald-300 border border-emerald-500/30 block';
          box.textContent = '✅ ' + data.message;
          document.getElementById('inputNewPass').value = '';
          document.getElementById('inputConfirmPass').value = '';
          document.getElementById('inputOtpCode').value = '';
          if (codeCountdownTimer) clearInterval(codeCountdownTimer);
          document.getElementById('btnSendCode').disabled = false;
          document.getElementById('btnSendCodeText').textContent = 'Enviar Código';
          showToast('¡Contraseña actualizada con éxito!', true);
        } else {
          box.className = 'text-xs p-3 rounded-xl font-mono bg-rose-500/10 text-rose-300 border border-rose-500/30 block';
          box.textContent = '❌ ' + (data.message || 'Error al cambiar contraseña');
          showToast(data.message || 'Error al verificar código', false);
        }
      } catch (err) {
        box.className = 'text-xs p-3 rounded-xl font-mono bg-rose-500/10 text-rose-300 border border-rose-500/30 block';
        box.textContent = '❌ Error de comunicación con el servidor.';
      } finally {
        btn.disabled = false;
        btn.innerHTML = '<span>🔑</span> <span>Confirmar y Cambiar Contraseña</span>';
      }
    }
  </script>

</body>
</html>
