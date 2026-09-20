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
    // Sesión huérfana
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
$foto_perfil = !empty($user['foto_perfil']) ? htmlspecialchars($user['foto_perfil']) : null;
$inicial_nombre = mb_strtoupper(mb_substr($nombre_actual, 0, 1));
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
        radial-gradient(circle at 50% 10%, rgba(245, 158, 11, 0.04) 0%, transparent 40%),
        radial-gradient(circle at 80% 60%, rgba(14, 165, 233, 0.03) 0%, transparent 50%),
        linear-gradient(to right, rgba(255, 255, 255, 0.015) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(255, 255, 255, 0.015) 1px, transparent 1px);
      background-size: 100% 100%, 100% 100%, 32px 32px, 32px 32px;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .glass-panel {
      background: rgba(11, 15, 25, 0.82);
      backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.07);
    }

    .glow-amber {
      box-shadow: 0 0 25px -4px rgba(245, 158, 11, 0.25);
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
      
      <!-- Brand Logo con Logo Oficial -->
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

      <!-- Navegación Central Modular -->
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
        <!-- Latencia Enclave -->
        <div class="hidden sm:flex items-center gap-1.5 text-[11px] font-mono text-emerald-400 font-semibold bg-[#0b0f19] px-2.5 py-1.5 rounded-xl border border-slate-800">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 badge-pulse"></span>
          <span>FIX 4.4: <strong class="text-white">ACTIVO</strong></span>
        </div>

        <!-- Botón Salir -->
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

  <!-- ================= CONTENIDO PRINCIPAL ================= -->
  <main class="flex-1 max-w-7xl w-full mx-auto px-4 lg:px-8 py-6 space-y-6">

    <!-- ================= 1. BANNER DE PERFIL DEL TRADER ================= -->
    <div class="glass-panel rounded-2xl p-5 sm:p-7 relative overflow-hidden border border-slate-800 glow-amber">
      <div class="absolute -right-12 -top-12 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute -left-12 -bottom-12 w-64 h-64 bg-sky-500/10 rounded-full blur-3xl pointer-events-none"></div>

      <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
        
        <!-- Info Izquierda: Avatar + Datos -->
        <div class="flex items-center gap-4 sm:gap-5">
          <!-- Avatar -->
          <div class="relative shrink-0">
            <?php if ($foto_perfil): ?>
              <img src="<?= $foto_perfil ?>" alt="<?= $nombre_actual ?>" class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl object-cover border-2 border-amber-500/50 shadow-[0_0_20px_rgba(245,158,11,0.25)]">
            <?php else: ?>
              <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-tr from-amber-600 via-amber-500 to-amber-300 text-slate-950 font-black text-2xl sm:text-3xl flex items-center justify-center border-2 border-amber-400/60 shadow-[0_0_25px_rgba(245,158,11,0.3)]">
                <?= $inicial_nombre ?>
              </div>
            <?php endif; ?>
            <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full <?= $es_vip ? 'bg-amber-400' : 'bg-emerald-400' ?> border-2 border-[#0b0f19] flex items-center justify-center text-[9px]" title="Cuenta Activa">
              <?= $es_vip ? '👑' : '⚡' ?>
            </div>
          </div>

          <!-- Textos Principales -->
          <div class="space-y-1">
            <div class="flex flex-wrap items-center gap-2">
              <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight" id="displayProfileName">
                <?= $nombre_actual ?>
              </h1>
              
              <!-- Badge de Membresía -->
              <?php if ($es_vip): ?>
                <span class="inline-flex items-center gap-1.5 bg-amber-500/15 border border-amber-500/40 text-amber-300 text-[11px] font-mono font-bold px-2.5 py-0.5 rounded-full">
                  <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                  👑 VIP ACTIVO
                </span>
              <?php elseif ($tipo_acceso === 'prueba_gratis'): ?>
                <span class="inline-flex items-center gap-1.5 bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-[11px] font-mono font-bold px-2.5 py-0.5 rounded-full">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                  ⚡ PRUEBA: <?= $dias_restantes ?>d RESTANTES
                </span>
              <?php else: ?>
                <span class="inline-flex items-center gap-1.5 bg-rose-500/15 border border-rose-500/40 text-rose-300 text-[11px] font-mono font-bold px-2.5 py-0.5 rounded-full">
                  <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                  ⚠️ PRUEBA FINALIZADA
                </span>
              <?php endif; ?>
            </div>

            <p class="text-xs text-slate-400 font-mono flex items-center gap-2">
              <span><?= $correo_actual ?></span>
              <span class="text-slate-600">•</span>
              <span class="text-amber-400/90 font-bold"><?= $trader_id ?></span>
            </p>
            
            <p class="text-[11px] text-slate-500">
              Registrado el: <strong class="text-slate-300 font-mono"><?= $fecha_reg ?></strong>
            </p>
          </div>
        </div>

        <!-- Acciones Rápidas Derecha -->
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <?php if (!$es_vip): ?>
            <a href="/vip/" class="inline-flex items-center gap-2 bg-gradient-to-r from-amber-400 via-amber-500 to-amber-600 hover:from-amber-300 hover:to-amber-500 text-slate-950 font-black text-xs px-4 py-2.5 rounded-xl shadow-lg shadow-amber-500/20 transition duration-150 transform hover:scale-[1.02] active:scale-[0.98]">
              <span>👑</span>
              <span>Activar Membresía VIP ($5/mes)</span>
            </a>
          <?php else: ?>
            <a href="/vip/" class="inline-flex items-center gap-2 bg-[#0d1528] hover:bg-[#121c35] text-amber-400 border border-amber-500/40 font-bold text-xs px-4 py-2.5 rounded-xl transition">
              <span>👑</span>
              <span>Gestionar Plan VIP</span>
            </a>
          <?php endif; ?>

          <a href="/" class="inline-flex items-center gap-2 bg-[#0e1726] hover:bg-[#142138] text-amber-400 hover:text-amber-300 border border-amber-500/30 font-bold text-xs px-4 py-2.5 rounded-xl transition">
            <span>⚡</span>
            <span>Terminal Cuántico</span>
          </a>
        </div>

      </div>
    </div>

    <!-- ================= 2. GRID DE MÉTRICAS DE LA CUENTA ================= -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
      
      <!-- Card: ID de Trader -->
      <div class="glass-panel p-4 rounded-2xl border border-slate-800/90 flex flex-col justify-between">
        <span class="text-[10px] text-slate-500 uppercase font-mono font-bold tracking-wider">ID DE OPERADOR</span>
        <div class="mt-2">
          <span class="text-white font-mono font-black text-lg tracking-wider"><?= $trader_id ?></span>
          <p class="text-[10px] text-slate-400 mt-0.5">Identificador de cuenta seguro</p>
        </div>
      </div>

      <!-- Card: Estado de Acceso -->
      <div class="glass-panel p-4 rounded-2xl border border-slate-800/90 flex flex-col justify-between">
        <span class="text-[10px] text-slate-500 uppercase font-mono font-bold tracking-wider">NIVEL DE ACCESO</span>
        <div class="mt-2">
          <?php if ($es_vip): ?>
            <span class="text-amber-400 font-mono font-black text-lg">VIP TIER III</span>
            <p class="text-[10px] text-emerald-400 mt-0.5 flex items-center gap-1">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Acceso Total Ilimitado
            </p>
          <?php elseif ($tipo_acceso === 'prueba_gratis'): ?>
            <span class="text-emerald-400 font-mono font-black text-lg"><?= $dias_restantes ?> Días</span>
            <p class="text-[10px] text-slate-400 mt-0.5">Prueba institucional activa</p>
          <?php else: ?>
            <span class="text-rose-400 font-mono font-black text-lg">Expirado</span>
            <p class="text-[10px] text-amber-400 mt-0.5">Requiere pase VIP para operar</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Card: Protocolo & Enclave -->
      <div class="glass-panel p-4 rounded-2xl border border-slate-800/90 flex flex-col justify-between">
        <span class="text-[10px] text-slate-500 uppercase font-mono font-bold tracking-wider">PROTOCOLO FIX DMA</span>
        <div class="mt-2">
          <span class="text-sky-400 font-mono font-black text-lg">FIX 4.4</span>
          <p class="text-[10px] text-slate-400 mt-0.5">LD4 Equinix Prime (&lt; 4.2ms)</p>
        </div>
      </div>

      <!-- Card: Sesión Persistente -->
      <div class="glass-panel p-4 rounded-2xl border border-slate-800/90 flex flex-col justify-between">
        <span class="text-[10px] text-slate-500 uppercase font-mono font-bold tracking-wider">SESIÓN AISLADA</span>
        <div class="mt-2">
          <span class="text-emerald-400 font-mono font-black text-lg">30 DÍAS</span>
          <p class="text-[10px] text-slate-400 mt-0.5">QUANTUM_BOT_SESSID Activa</p>
        </div>
      </div>

    </div>

    <!-- ================= 3. SECCIÓN DE CONFIGURACIÓN Y FORMULARIOS ================= -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

      <!-- ================= COLUMNA IZQUIERDA: DATOS PERSONALES (6 COLS) ================= -->
      <div class="lg:col-span-6 space-y-6">

        <!-- Tarjeta: Información de Cuenta -->
        <div class="glass-panel rounded-2xl p-5 sm:p-6 border border-slate-800 space-y-5">
          <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
            <div>
              <h2 class="text-white font-extrabold text-sm sm:text-base flex items-center gap-2">
                <span>👤</span> Datos Personales
              </h2>
              <p class="text-xs text-slate-400 mt-0.5">Actualiza tu nombre visible en el terminal.</p>
            </div>
            <span class="text-[10px] font-mono text-emerald-400 bg-emerald-500/10 border border-emerald-500/30 px-2 py-0.5 rounded">Sincronizado</span>
          </div>

          <form id="formUpdateProfile" onsubmit="handleUpdateProfile(event)" class="space-y-4">
            <!-- Nombre Completo -->
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
                <span class="absolute right-3 top-2.5 text-slate-500 text-xs">✏️</span>
              </div>
              <p class="text-[11px] text-slate-500 mt-1">Este nombre se mostrará en las alertas y en la cabecera del terminal.</p>
            </div>

            <!-- Correo Electrónico (Solo Lectura) -->
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                Correo Electrónico (Cuenta de Acceso)
              </label>
              <div class="relative">
                <input 
                  type="email" 
                  value="<?= $correo_actual ?>" 
                  readonly 
                  class="w-full bg-[#060a14] border border-slate-800 text-slate-400 rounded-xl px-3.5 py-2.5 font-mono text-xs cursor-not-allowed select-all"
                >
                <span class="absolute right-3 top-2.5 text-emerald-400 text-xs" title="Verificado">✓</span>
              </div>
              <p class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
                <span>🔒</span> Vinculado a tu cuenta de Google o credenciales maestras.
              </p>
            </div>

            <!-- Alerta de Respuesta Formulario -->
            <div id="profileAlertBox" class="hidden text-xs p-3 rounded-xl font-mono"></div>

            <!-- Botón Guardar Nombre -->
            <div class="flex justify-end pt-2">
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

      </div>

      <!-- ================= COLUMNA DERECHA: SEGURIDAD & CONTRASEÑA (6 COLS) ================= -->
      <div class="lg:col-span-6 space-y-6">

        <!-- Tarjeta: Cambiar Contraseña -->
        <div class="glass-panel rounded-2xl p-5 sm:p-6 border border-slate-800 space-y-5">
          <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
            <div>
              <h2 class="text-white font-extrabold text-sm sm:text-base flex items-center gap-2">
                <span>🔒</span> Seguridad & Contraseña
              </h2>
              <p class="text-xs text-slate-400 mt-0.5">Protege tu acceso al enclave con una clave robusta.</p>
            </div>
            <span class="text-[10px] font-mono text-amber-400 bg-amber-500/10 border border-amber-500/30 px-2 py-0.5 rounded">BCRYPT 12</span>
          </div>

          <form id="formChangePassword" onsubmit="handleChangePassword(event)" class="space-y-4">
            
            <!-- Contraseña Actual -->
            <div>
              <label for="inputCurrentPass" class="block text-xs font-semibold text-slate-300 mb-1.5">
                Contraseña Actual
              </label>
              <div class="relative">
                <input 
                  type="password" 
                  id="inputCurrentPass" 
                  name="current_password" 
                  class="w-full bg-[#080d19] border border-slate-700/80 rounded-xl px-3.5 py-2.5 text-white font-mono text-xs focus:outline-none focus:border-amber-400 transition pr-10"
                  placeholder="Tu contraseña actual (o déjalo si ingresaste con Google)"
                >
                <button type="button" onclick="togglePassVisibility('inputCurrentPass')" class="absolute right-3 top-2.5 text-slate-400 hover:text-white text-xs">
                  👁️
                </button>
              </div>
            </div>

            <!-- Nueva Contraseña -->
            <div>
              <label for="inputNewPass" class="block text-xs font-semibold text-slate-300 mb-1.5">
                Nueva Contraseña <span class="text-slate-500 font-normal">(mínimo 6 caracteres)</span>
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
                <button type="button" onclick="togglePassVisibility('inputNewPass')" class="absolute right-3 top-2.5 text-slate-400 hover:text-white text-xs">
                  👁️
                </button>
              </div>
            </div>

            <!-- Confirmar Contraseña -->
            <div>
              <label for="inputConfirmPass" class="block text-xs font-semibold text-slate-300 mb-1.5">
                Confirmar Nueva Contraseña
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
                <button type="button" onclick="togglePassVisibility('inputConfirmPass')" class="absolute right-3 top-2.5 text-slate-400 hover:text-white text-xs">
                  👁️
                </button>
              </div>
            </div>

            <!-- Alerta de Respuesta Contraseña -->
            <div id="passwordAlertBox" class="hidden text-xs p-3 rounded-xl font-mono"></div>

            <!-- Botón Actualizar Clave -->
            <div class="flex justify-end pt-2">
              <button 
                type="submit" 
                id="btnChangePass"
                class="inline-flex items-center gap-2 bg-[#18233a] hover:bg-[#202e4d] text-white hover:text-amber-300 border border-slate-700 font-bold text-xs px-5 py-2.5 rounded-xl transition duration-150 active:scale-95"
              >
                <span>🔑</span>
                <span>Actualizar Contraseña</span>
              </button>
            </div>
          </form>
        </div>

      </div>

    </div>

  </main>

  <!-- ================= PIE DE PÁGINA INSTITUCIONAL ================= -->
  <footer class="mt-auto border-t border-slate-800/80 bg-[#04060b] py-6 px-4 lg:px-8 text-center text-xs text-slate-500 space-y-2">
    <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <span class="text-white font-black tracking-wider">QUANTUM.AI</span>
        <span class="text-slate-600">•</span>
        <span class="font-mono text-[11px]">Enclave de Trading Institucional & Algorítmico</span>
      </div>
      <div class="flex items-center gap-4 text-[11px] font-mono">
        <a href="/" class="hover:text-amber-400 transition">Terminal Principal</a>
        <a href="/vip/" class="hover:text-amber-400 transition">Membresía VIP</a>
        <a href="/telegram/" class="hover:text-amber-400 transition">Telegram</a>
        <a href="/calculadora.php" class="hover:text-amber-400 transition">Calculadora</a>
      </div>
    </div>
  </footer>

  <!-- ================= NOTIFICACIÓN TOAST FLOTANTE ================= -->
  <div id="toast" class="fixed bottom-6 right-6 hidden z-50 items-center gap-3 bg-[#0b0f19] border border-amber-500/40 text-white px-5 py-3 rounded-2xl shadow-2xl shadow-amber-500/20 backdrop-blur-xl transition-all duration-300">
    <div id="toastIcon" class="w-6 h-6 rounded-full bg-amber-400/20 text-amber-400 flex items-center justify-center text-xs font-black">
      ✓
    </div>
    <span id="toastMsg" class="text-xs font-medium">Operación realizada con éxito</span>
  </div>

  <!-- ================= CONTROLADOR JAVASCRIPT ================= -->
  <script>
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

    // Actualizar Contraseña
    async function handleChangePassword(e) {
      e.preventDefault();
      const btn = document.getElementById('btnChangePass');
      const box = document.getElementById('passwordAlertBox');
      const currentPass = document.getElementById('inputCurrentPass').value;
      const newPass = document.getElementById('inputNewPass').value;
      const confirmPass = document.getElementById('inputConfirmPass').value;

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

      btn.disabled = true;
      btn.innerHTML = '<span class="animate-spin mr-1">⏳</span> Procesando...';

      try {
        const res = await fetch('/api/auth.php?action=change_password', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            current_password: currentPass,
            new_password: newPass,
            confirm_password: confirmPass
          })
        });
        const data = await res.json();

        if (data.success) {
          box.className = 'text-xs p-3 rounded-xl font-mono bg-emerald-500/10 text-emerald-300 border border-emerald-500/30 block';
          box.textContent = '✅ ' + data.message;
          document.getElementById('inputCurrentPass').value = '';
          document.getElementById('inputNewPass').value = '';
          document.getElementById('inputConfirmPass').value = '';
          showToast('Contraseña actualizada con éxito', true);
        } else {
          box.className = 'text-xs p-3 rounded-xl font-mono bg-rose-500/10 text-rose-300 border border-rose-500/30 block';
          box.textContent = '❌ ' + (data.message || 'Error al cambiar contraseña');
          showToast(data.message || 'Error al cambiar contraseña', false);
        }
      } catch (err) {
        box.className = 'text-xs p-3 rounded-xl font-mono bg-rose-500/10 text-rose-300 border border-rose-500/30 block';
        box.textContent = '❌ Error de comunicación con el servidor.';
      } finally {
        btn.disabled = false;
        btn.innerHTML = '<span>🔑</span> <span>Actualizar Contraseña</span>';
      }
    }
  </script>

</body>
</html>
