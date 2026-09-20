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

  <!-- ================= CONTENEDOR PRINCIPAL INSTITUCIONAL ================= -->
  <main class="flex-1 max-w-7xl w-full mx-auto px-4 lg:px-8 py-6 space-y-6">

    <!-- ================= 1. BANNER HORIZONTAL DE IDENTIDAD & ESPECIFICACIONES ================= -->
    <section class="glass-panel rounded-3xl p-5 lg:p-6 border border-slate-800 glow-amber relative overflow-hidden">
      <div class="absolute -right-16 -top-16 w-60 h-60 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5 relative z-10">
        
        <!-- Identidad del Operador (Avatar + Nombre + Estado) -->
        <div class="flex items-center gap-4 min-w-0">
          <!-- Avatar Seguro con Monograma -->
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

          <!-- Nombre, Correo y Badge -->
          <div class="min-w-0 space-y-1">
            <div class="flex items-center gap-2 flex-wrap">
              <h1 class="text-xl font-black text-white truncate" id="displayProfileName">
                <?= $nombre_actual ?>
              </h1>
              <span class="text-amber-400 text-xs" title="Operador Registrado">✓</span>
              
              <!-- Badge VIP / Prueba -->
              <?php if ($es_vip): ?>
                <span class="inline-flex items-center gap-1.5 bg-amber-500/15 border border-amber-500/40 text-amber-300 text-[10px] font-mono font-bold px-2.5 py-0.5 rounded-full">
                  <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                  👑 VIP ACTIVO
                </span>
              <?php elseif ($tipo_acceso === 'prueba_gratis'): ?>
                <span class="inline-flex items-center gap-1.5 bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-[10px] font-mono font-bold px-2.5 py-0.5 rounded-full">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                  ⚡ PRUEBA: <?= $dias_restantes ?> DÍAS
                </span>
              <?php else: ?>
                <span class="inline-flex items-center gap-1.5 bg-rose-500/15 border border-rose-500/40 text-rose-300 text-[10px] font-mono font-bold px-2.5 py-0.5 rounded-full">
                  <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                  ⚠️ PRUEBA FINALIZADA
                </span>
              <?php endif; ?>
            </div>

            <p class="text-xs text-slate-400 font-mono truncate"><?= $correo_actual ?></p>
          </div>
        </div>

        <!-- Barra de Especificaciones de Enclave (Chips Horizontales) -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs font-mono">
          <!-- UID -->
          <div class="bg-[#070b14] border border-slate-800 rounded-xl px-3 py-2 flex flex-col justify-center">
            <span class="text-[9px] text-slate-500 font-bold uppercase tracking-wider">UID OPERADOR</span>
            <div class="flex items-center gap-1.5 mt-0.5">
              <strong class="text-white font-black text-xs"><?= $trader_id ?></strong>
              <button onclick="copyTraderUid('<?= $trader_id ?>')" class="text-slate-500 hover:text-amber-400 text-xs transition" title="Copiar UID">
                📋
              </button>
            </div>
          </div>

          <!-- Registro -->
          <div class="bg-[#070b14] border border-slate-800 rounded-xl px-3 py-2 flex flex-col justify-center">
            <span class="text-[9px] text-slate-500 font-bold uppercase tracking-wider">REGISTRO</span>
            <strong class="text-slate-300 text-xs mt-0.5 truncate" title="<?= $fecha_reg ?>"><?= $fecha_reg ?></strong>
          </div>

          <!-- Protocolo FIX -->
          <div class="bg-[#070b14] border border-slate-800 rounded-xl px-3 py-2 flex flex-col justify-center">
            <span class="text-[9px] text-slate-500 font-bold uppercase tracking-wider">ENCLAVE DMA</span>
            <div class="flex items-center gap-1.5 mt-0.5 text-sky-400 text-xs font-bold">
              <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>
              <span>FIX 4.4 LD4</span>
            </div>
          </div>

          <!-- Sesión -->
          <div class="bg-[#070b14] border border-slate-800 rounded-xl px-3 py-2 flex flex-col justify-center">
            <span class="text-[9px] text-slate-500 font-bold uppercase tracking-wider">SESIÓN</span>
            <div class="flex items-center gap-1.5 mt-0.5 text-emerald-400 text-xs font-bold">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
              <span>30 DÍAS ACTIVA</span>
            </div>
          </div>
        </div>

        <!-- Botón Rápido Terminal -->
        <div class="shrink-0 flex items-center gap-2">
          <a href="/" class="inline-flex items-center gap-2 bg-[#090f1e] hover:bg-[#121c35] text-amber-400 hover:text-amber-300 border border-amber-500/30 hover:border-amber-400 text-xs font-bold py-2.5 px-4 rounded-xl transition duration-150 shadow-sm">
            <span>⚡</span>
            <span>Abrir Terminal</span>
          </a>
        </div>

      </div>
    </section>

    <!-- ================= 2. PANELES SIMÉTRICOS: CONFIGURACIÓN & SEGURIDAD ================= -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">

      <!-- ================= PANEL IZQUIERDO: DATOS DE CUENTA & MEMBRESÍA VIP (6 cols) ================= -->
      <div class="lg:col-span-6 space-y-6 flex flex-col justify-between">
        
        <!-- Tarjeta: Datos del Operador -->
        <div class="glass-panel rounded-3xl p-6 border border-slate-800 space-y-5 flex-1">
          <div class="flex items-center justify-between border-b border-slate-800/80 pb-3.5">
            <div>
              <h2 class="text-white font-extrabold text-sm sm:text-base flex items-center gap-2">
                <span>👤</span> Identidad del Operador
              </h2>
              <p class="text-xs text-slate-400 mt-0.5">Modifica tu alias visible y consulta tus credenciales maestras.</p>
            </div>
            <span class="text-[10px] font-mono text-emerald-400 bg-emerald-500/10 border border-emerald-500/30 px-2.5 py-0.5 rounded-full">Sincronizado</span>
          </div>

          <form id="formUpdateProfile" onsubmit="handleUpdateProfile(event)" class="space-y-4">
            
            <!-- Input Nombre -->
            <div>
              <label for="inputNombre" class="block text-xs font-semibold text-slate-300 mb-1.5">
                Nombre o Alias de Trading
              </label>
              <div class="relative">
                <input 
                  type="text" 
                  id="inputNombre" 
                  name="nombre" 
                  value="<?= $nombre_actual ?>" 
                  required 
                  maxlength="80" 
                  class="w-full bg-[#080d19] border border-slate-700/80 rounded-xl px-3.5 py-2.5 text-white font-medium text-xs focus:outline-none focus:border-amber-400 transition pr-10"
                  placeholder="Tu Alias de Operador"
                >
                <span class="absolute right-3.5 top-2.5 text-slate-500 text-xs pointer-events-none">✏️</span>
              </div>
              <p class="text-[11px] text-slate-500 mt-1">Se muestra en el radar y en el registro de operaciones.</p>
            </div>

            <!-- Input Correo Maestro -->
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                Correo Electrónico (Cuenta Maestra)
              </label>
              <div class="relative">
                <input 
                  type="email" 
                  value="<?= $correo_actual ?>" 
                  readonly 
                  class="w-full bg-[#060a14] border border-slate-800 text-slate-400 rounded-xl px-3.5 py-2.5 font-mono text-xs cursor-not-allowed select-all pr-10"
                >
                <span class="absolute right-3.5 top-2.5 text-emerald-400 text-xs" title="Verificado">✓</span>
              </div>
              <p class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
                <span>🔒</span> Vinculado de forma permanente a tu enclave.
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
                <span>Guardar Cambios</span>
              </button>
            </div>

          </form>
        </div>

        <!-- Tarjeta: Estado de Membresía VIP -->
        <div class="glass-panel rounded-3xl p-6 border border-slate-800 relative overflow-hidden">
          <?php if ($es_vip): ?>
            <div class="flex items-center justify-between gap-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/15 border border-amber-500/40 flex items-center justify-center text-lg">
                  👑
                </div>
                <div>
                  <h3 class="text-white font-extrabold text-sm flex items-center gap-2">
                    Membresía VIP Institucional
                    <span class="text-[10px] font-mono text-emerald-400 bg-emerald-500/10 border border-emerald-500/30 px-2 py-0.5 rounded-full">Activo</span>
                  </h3>
                  <p class="text-xs text-slate-400 mt-0.5">Acceso total e ilimitado a todas las confluencias algorítmicas 24/7.</p>
                </div>
              </div>
              <a href="/vip/" class="text-xs font-bold text-amber-400 hover:text-amber-300 bg-[#070b14] border border-amber-500/30 px-4 py-2 rounded-xl transition shrink-0">
                Gestionar
              </a>
            </div>
          <?php else: ?>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div class="space-y-1">
                <div class="flex items-center gap-2">
                  <span class="text-amber-400 text-base">👑</span>
                  <h3 class="text-white font-extrabold text-sm">Desbloquea el Enclave VIP</h3>
                  <span class="text-[10px] font-mono font-black text-amber-300 bg-amber-500/20 px-2 py-0.5 rounded-lg">$19 USD/mes</span>
                </div>
                <p class="text-xs text-slate-400 leading-relaxed">
                  Señales confluentes 24/7 sin límite de días con conexión FIX 4.4 de baja latencia.
                </p>
              </div>
              <a href="/vip/" class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-amber-400 via-amber-500 to-amber-600 hover:from-amber-300 hover:to-amber-500 text-slate-950 font-black text-xs py-2.5 px-5 rounded-xl shadow-lg shadow-amber-500/20 transition shrink-0">
                <span>👑</span>
                <span>Activar VIP</span>
              </a>
            </div>
          <?php endif; ?>
        </div>

      </div>

      <!-- ================= PANEL DERECHO: SEGURIDAD & CONTRASEÑA KAIROS AI (6 cols) ================= -->
      <div class="lg:col-span-6">
        <div class="glass-panel rounded-3xl p-6 border border-slate-800 space-y-5 h-full flex flex-col justify-between">
          
          <div>
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-3.5">
              <div>
                <h2 class="text-white font-extrabold text-sm sm:text-base flex items-center gap-2">
                  <span>🔒</span> Seguridad & Contraseña
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">Autorizado por KAIROS AI Sentinel mediante código seguro al correo.</p>
              </div>
              <span class="text-[10px] font-mono font-bold text-amber-400 bg-amber-500/10 border border-amber-500/30 px-2.5 py-0.5 rounded-full">KAIROS AI OTP</span>
            </div>

            <form id="formChangePassword" onsubmit="handleChangePassword(event)" class="space-y-4 pt-4">
              
              <!-- Grid: Nueva y Confirmación lado a lado -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <!-- Nueva Clave -->
                <div>
                  <label for="inputNewPass" class="block text-xs font-semibold text-slate-300 mb-1.5">
                    Nueva Contraseña <span class="text-slate-500 font-normal">(≥ 8 car.)</span>
                  </label>
                  <div class="relative">
                    <input 
                      type="password" 
                      id="inputNewPass" 
                      name="new_password" 
                      required 
                      minlength="8" 
                      oninput="checkPasswordStrength()"
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
                      minlength="8" 
                      oninput="checkPasswordMatch()"
                      class="w-full bg-[#080d19] border border-slate-700/80 rounded-xl px-3.5 py-2.5 text-white font-mono text-xs focus:outline-none focus:border-amber-400 transition pr-10"
                      placeholder="••••••••••••"
                    >
                    <button type="button" onclick="togglePassVisibility('inputConfirmPass')" class="absolute right-3.5 top-2.5 text-slate-400 hover:text-white text-xs">
                      👁️
                    </button>
                  </div>
                </div>
              </div>

              <!-- Medidor de Robustez y Verificador de Requisitos en Vivo -->
              <div class="bg-[#070b14] border border-slate-800/80 rounded-2xl p-3.5 space-y-2.5">
                <div class="flex items-center justify-between text-xs">
                  <span class="text-slate-400 font-mono text-[11px]">Nivel de Seguridad:</span>
                  <span id="strengthLabel" class="font-mono text-[11px] font-bold text-slate-500">Sin evaluar</span>
                </div>
                
                <!-- Barra de Progreso -->
                <div class="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden">
                  <div id="strengthBar" class="h-full w-0 bg-slate-600 transition-all duration-300 rounded-full"></div>
                </div>

                <!-- Checklist de Requisitos -->
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-1.5 text-[10px] font-mono pt-1">
                  <div id="reqLength" class="flex items-center gap-1 text-slate-500 bg-slate-900/60 px-2 py-1 rounded-lg border border-slate-800">
                    <span class="icon">✕</span> <span>8+ car.</span>
                  </div>
                  <div id="reqUpper" class="flex items-center gap-1 text-slate-500 bg-slate-900/60 px-2 py-1 rounded-lg border border-slate-800">
                    <span class="icon">✕</span> <span>Mayúscula</span>
                  </div>
                  <div id="reqLower" class="flex items-center gap-1 text-slate-500 bg-slate-900/60 px-2 py-1 rounded-lg border border-slate-800">
                    <span class="icon">✕</span> <span>Minúscula</span>
                  </div>
                  <div id="reqNumber" class="flex items-center gap-1 text-slate-500 bg-slate-900/60 px-2 py-1 rounded-lg border border-slate-800">
                    <span class="icon">✕</span> <span>Número</span>
                  </div>
                  <div id="reqSymbol" class="col-span-2 sm:col-span-1 flex items-center gap-1 text-slate-500 bg-slate-900/60 px-2 py-1 rounded-lg border border-slate-800">
                    <span class="icon">✕</span> <span>Símbolo (#@!)</span>
                  </div>
                </div>
              </div>

              <!-- Paso de Verificación KAIROS AI -->
              <div class="bg-[#070b14] border border-slate-800/90 rounded-2xl p-3.5 space-y-2.5">
                <div class="flex items-center justify-between flex-wrap gap-2">
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
                    class="w-full bg-[#0b101e] border border-slate-700/80 rounded-xl px-4 py-2 text-center font-mono font-black text-amber-400 text-lg tracking-[8px] placeholder:tracking-normal placeholder:font-normal placeholder:text-slate-600 placeholder:text-xs focus:outline-none focus:border-amber-400 transition"
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
                  class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-950 font-black text-xs px-6 py-2.5 rounded-xl transition duration-150 shadow-md shadow-amber-500/20 active:scale-95"
                >
                  <span>🔑</span>
                  <span>Confirmar y Cambiar Contraseña</span>
                </button>
              </div>

            </form>
          </div>

        </div>
      </div>

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
    // Validador de robustez en tiempo real (mínimo 8 caracteres, mayúscula, minúscula, número y símbolo)
    function checkPasswordStrength() {
      const pass = document.getElementById('inputNewPass').value;
      
      const hasLength = pass.length >= 8;
      const hasUpper = /[A-Z]/.test(pass);
      const hasLower = /[a-z]/.test(pass);
      const hasNumber = /[0-9]/.test(pass);
      const hasSymbol = /[^a-zA-Z0-9]/.test(pass);

      const updatePill = (id, valid) => {
        const el = document.getElementById(id);
        if (!el) return;
        const icon = el.querySelector('.icon');
        if (valid) {
          el.className = 'flex items-center gap-1 text-emerald-400 bg-emerald-500/10 px-2 py-1 rounded-lg border border-emerald-500/30';
          if (icon) icon.textContent = '✓';
        } else {
          el.className = 'flex items-center gap-1 text-slate-500 bg-slate-900/60 px-2 py-1 rounded-lg border border-slate-800';
          if (icon) icon.textContent = '✕';
        }
      };

      updatePill('reqLength', hasLength);
      updatePill('reqUpper', hasUpper);
      updatePill('reqLower', hasLower);
      updatePill('reqNumber', hasNumber);
      updatePill('reqSymbol', hasSymbol);

      const score = (hasLength ? 1 : 0) + (hasUpper ? 1 : 0) + (hasLower ? 1 : 0) + (hasNumber ? 1 : 0) + (hasSymbol ? 1 : 0);
      const bar = document.getElementById('strengthBar');
      const label = document.getElementById('strengthLabel');

      if (!pass) {
        bar.style.width = '0%';
        bar.className = 'h-full bg-slate-600 transition-all duration-300 rounded-full';
        label.textContent = 'Sin evaluar';
        label.className = 'font-mono text-[11px] font-bold text-slate-500';
      } else if (score <= 2) {
        bar.style.width = '30%';
        bar.className = 'h-full bg-rose-500 transition-all duration-300 rounded-full shadow-[0_0_10px_rgba(244,63,94,0.5)]';
        label.textContent = 'Débil ⚠️';
        label.className = 'font-mono text-[11px] font-bold text-rose-400';
      } else if (score <= 4) {
        bar.style.width = '70%';
        bar.className = 'h-full bg-amber-400 transition-all duration-300 rounded-full shadow-[0_0_10px_rgba(251,191,36,0.5)]';
        label.textContent = 'Buena (completa requisitos)';
        label.className = 'font-mono text-[11px] font-bold text-amber-400';
      } else {
        bar.style.width = '100%';
        bar.className = 'h-full bg-emerald-400 transition-all duration-300 rounded-full shadow-[0_0_15px_rgba(52,211,153,0.6)]';
        label.textContent = 'Blindada / Excelente 🛡️';
        label.className = 'font-mono text-[11px] font-bold text-emerald-400';
      }

      return score === 5;
    }

    function checkPasswordMatch() {
      const pass = document.getElementById('inputNewPass').value;
      const confirm = document.getElementById('inputConfirmPass').value;
      const box = document.getElementById('passwordAlertBox');
      if (confirm && pass !== confirm) {
        box.className = 'text-xs p-2.5 rounded-xl font-mono bg-rose-500/10 text-rose-300 border border-rose-500/30 block';
        box.textContent = '⚠️ Las contraseñas no coinciden.';
      } else if (confirm && pass === confirm) {
        box.className = 'hidden';
      }
    }

    // Solicitar Código de Verificación OTP vía KAIROS AI
    async function handleSendVerificationCode() {
      const btn = document.getElementById('btnSendCode');
      const text = document.getElementById('btnSendCodeText');
      const box = document.getElementById('passwordAlertBox');
      const newPass = document.getElementById('inputNewPass').value;
      const confirmPass = document.getElementById('inputConfirmPass').value;

      if (!newPass || newPass.length < 8) {
        box.className = 'text-xs p-3 rounded-xl font-mono bg-rose-500/10 text-rose-300 border border-rose-500/30 block';
        box.textContent = '⚠️ La nueva contraseña debe tener al menos 8 caracteres.';
        document.getElementById('inputNewPass').focus();
        return;
      }

      if (!/[a-z]/.test(newPass)) {
        box.className = 'text-xs p-3 rounded-xl font-mono bg-rose-500/10 text-rose-300 border border-rose-500/30 block';
        box.textContent = '⚠️ La contraseña debe incluir al menos una letra minúscula (a-z).';
        document.getElementById('inputNewPass').focus();
        return;
      }

      if (!/[A-Z]/.test(newPass)) {
        box.className = 'text-xs p-3 rounded-xl font-mono bg-rose-500/10 text-rose-300 border border-rose-500/30 block';
        box.textContent = '⚠️ La contraseña debe incluir al menos una letra mayúscula (A-Z).';
        document.getElementById('inputNewPass').focus();
        return;
      }

      if (!/[0-9]/.test(newPass)) {
        box.className = 'text-xs p-3 rounded-xl font-mono bg-rose-500/10 text-rose-300 border border-rose-500/30 block';
        box.textContent = '⚠️ La contraseña debe incluir al menos un número (0-9).';
        document.getElementById('inputNewPass').focus();
        return;
      }

      if (!/[^a-zA-Z0-9]/.test(newPass)) {
        box.className = 'text-xs p-3 rounded-xl font-mono bg-rose-500/10 text-rose-300 border border-rose-500/30 block';
        box.textContent = '⚠️ La contraseña debe incluir al menos un símbolo especial (!@#$%^&*...).';
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

      if (!checkPasswordStrength()) {
        box.className = 'text-xs p-3 rounded-xl font-mono bg-rose-500/10 text-rose-300 border border-rose-500/30 block';
        box.textContent = '⚠️ Asegúrate de cumplir con los 5 requisitos de seguridad (8+ caracteres, mayúscula, minúscula, número y símbolo).';
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
          checkPasswordStrength();
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
