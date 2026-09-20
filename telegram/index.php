<?php
/**
 * bot.mddorma.com/telegram/index.php — Panel Dedicado de Bot Personal de Telegram & Membresía VIP
 * 
 * Permite a los usuarios configurar su propio bot de Telegram, seleccionar qué criptomoneda monitorear
 * y recibir alertas en tiempo real conectadas a Binance Futuros desde nuestro servidor.
 */
declare(strict_types=1);

define('AUTH_LIB_ONLY', true);
require_once dirname(__DIR__) . '/api/auth.php';

$usuario_logueado = null;
$acceso_usuario = null;

if (!empty($_SESSION['id_usuario']) && !empty($pdo)) {
    try {
        $st = $pdo->prepare("SELECT id_usuario, nombre, correo, rol, es_premium, fecha_registro FROM usuarios WHERE id_usuario = ? LIMIT 1");
        $st->execute([(int)$_SESSION['id_usuario']]);
        $usuario_logueado = $st->fetch(PDO::FETCH_ASSOC);
        if ($usuario_logueado) {
            $acceso_usuario = calcular_acceso_usuario($usuario_logueado);
        }
    } catch (Throwable $e) {}
}

$es_vip = !empty($acceso_usuario['es_vip']);
$dias_restantes = $acceso_usuario['dias_restantes'] ?? 7;
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mi Bot de Telegram & Alertas VIP 24/7 | Quantum AI</title>
  <link rel="icon" type="image/png" href="/favicon.png">
  
  <!-- Tailwind CSS & Fuentes -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            obsidian: { 950: '#05070d', 900: '#0b0f19', 800: '#111726', 700: '#1b233a' },
            gold: { 400: '#fbbf24', 500: '#f59e0b', 600: '#d97706' },
            emerald: { 400: '#34d399', 500: '#10b981', 600: '#059669' },
            cyan: { 400: '#38bdf8', 500: '#0ea5e9', 600: '#0284c7' }
          },
          fontFamily: {
            mono: ['JetBrains Mono', 'Fira Code', 'Courier New', 'monospace'],
            sans: ['Inter', 'system-ui', 'sans-serif']
          }
        }
      }
    }
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

  <style>
    body {
      background-color: #05070d;
      color: #e2e8f0;
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    .glass-card {
      background: rgba(11, 15, 25, 0.75);
      backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.07);
    }
    .glow-gold {
      box-shadow: 0 0 25px -5px rgba(245, 158, 11, 0.2);
    }
    .glow-cyan {
      box-shadow: 0 0 25px -5px rgba(14, 165, 233, 0.2);
    }
    .grid-bg {
      background-size: 32px 32px;
      background-image: 
        linear-gradient(to right, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
    }
    .tg-bubble {
      background: #182533;
      border: 1px solid #243547;
      border-radius: 16px 16px 16px 4px;
    }
  </style>
</head>
<body class="grid-bg">

  <!-- ================= BARRA SUPERIOR ================= -->
  <header class="sticky top-0 z-40 bg-[#05070d]/90 backdrop-blur-md border-b border-slate-800/80 px-4 lg:px-8 py-3">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
      
      <!-- Brand Logo con Logo Oficial Transparente -->
      <a href="/" class="flex items-center gap-3 group">
        <img src="/assets/logo.png" alt="Quantum AI" class="w-9 h-9 object-contain drop-shadow-[0_0_12px_rgba(245,158,11,0.35)] group-hover:scale-105 transition duration-200">
        <div>
          <div class="flex items-center gap-1.5 leading-none">
            <span class="text-white font-black tracking-wider text-base">QUANTUM</span>
            <span class="text-amber-400 font-black text-base">AI</span>
            <span class="text-[9px] font-mono font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30 px-1.5 py-0.5 rounded">TELEGRAM VIP</span>
          </div>
          <p class="text-[10px] text-slate-400 font-mono tracking-wide mt-0.5">TERMINAL INSTITUCIONAL</p>
        </div>
      </a>

      <!-- Navegación Central Modular -->
      <nav class="hidden md:flex items-center gap-1 bg-[#0b0f19] border border-slate-800 p-1 rounded-xl text-xs font-semibold">
        <a href="/?tab=senales" class="px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/60 transition flex items-center gap-1.5">
          <span>⚡</span> Señales
        </a>
        <a href="/?tab=grafico" class="px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/60 transition flex items-center gap-1.5">
          <span>📈</span> Gráfico
        </a>
        <a href="/?tab=calculadora" class="px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/60 transition flex items-center gap-1.5">
          <span>🛡️</span> Calculadora
        </a>
        <a href="/telegram/" class="px-3 py-1.5 rounded-lg text-slate-950 bg-amber-400 font-black flex items-center gap-1.5 shadow-sm shadow-amber-400/20">
          <span>📱</span> Mi Bot Telegram
        </a>
        <a href="/vip/" class="px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/60 transition flex items-center gap-1.5">
          <span>👑</span> Membresía VIP
        </a>
        <a href="/perfil/" class="px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/60 transition flex items-center gap-1.5">
          <span>👤</span> Mi Perfil
        </a>
      </nav>

      <!-- Estado de Usuario -->
      <div class="flex items-center gap-3">
        <?php if ($usuario_logueado): ?>
          <a href="/perfil/" class="flex items-center gap-2 bg-[#0b0f19] hover:bg-[#121a2d] border border-slate-800 hover:border-amber-500/40 py-1.5 px-3 rounded-xl text-xs transition group" title="Ver Mi Perfil">
            <span class="w-2 h-2 rounded-full <?= $es_vip ? 'bg-amber-400 animate-pulse' : 'bg-emerald-400' ?>"></span>
            <div class="flex flex-col text-left">
              <span class="text-white font-bold leading-tight group-hover:text-amber-300 transition"><?= htmlspecialchars($usuario_logueado['nombre'] ?: 'Trader') ?></span>
              <span class="text-[10px] font-mono <?= $es_vip ? 'text-amber-400 font-bold' : 'text-slate-400' ?>">
                <?= $es_vip ? '👑 VIP Activo' : "Prueba: {$dias_restantes}d restantes" ?>
              </span>
            </div>
          </a>
          <a href="/api/auth.php?action=logout&redirect_login=1" title="Cerrar Sesión" class="text-slate-500 hover:text-rose-400 ml-1 transition">✕</a>
        <?php else: ?>
          <a href="/login.php" class="bg-[#121a2d] hover:bg-[#18233c] text-sky-300 border border-sky-500/40 text-xs font-bold px-3 py-1.5 rounded-xl transition flex items-center gap-1.5">
            <span>👤</span> Iniciar Sesión
          </a>
        <?php endif; ?>
      </div>

    </div>
  </header>

  <!-- ================= CONTENIDO PRINCIPAL ================= -->
  <main class="flex-1 max-w-7xl w-full mx-auto px-4 lg:px-8 py-6 space-y-6">

    <!-- Hero / Banner de Introducción -->
    <div class="glass-card rounded-2xl p-5 sm:p-6 relative overflow-hidden border border-slate-800">
      <div class="absolute -right-10 -top-10 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute -left-10 -bottom-10 w-64 h-64 bg-sky-500/10 rounded-full blur-3xl pointer-events-none"></div>
      
      <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="inline-flex items-center gap-2 bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-bold px-3 py-1 rounded-full mb-2">
            <span>✨</span> ALERTAS AUTOMÁTICAS DE ALTA CONFLUENCIA (≥70%)
          </div>
          <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">
            Centro de Conexión: <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-amber-200">Tu Bot de Telegram & Alertas VIP</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-400 mt-1 max-w-2xl">
            Conecta tu propio bot personal creado en Telegram para recibir al instante cada señal institucional analizada por nuestro servidor. Elige qué criptomoneda deseas monitorear o activa el radar total sin saturación.
          </p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
          <div class="p-3 bg-[#060912] border border-slate-800 rounded-xl text-right">
            <span class="text-[10px] text-slate-500 uppercase tracking-wider block font-mono">LATENCIA DESPACHO</span>
            <span class="text-emerald-400 font-mono font-black text-sm flex items-center justify-end gap-1">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
              &lt; 180 ms
            </span>
          </div>
          <div class="p-3 bg-[#060912] border border-slate-800 rounded-xl text-right">
            <span class="text-[10px] text-slate-500 uppercase tracking-wider block font-mono">ESTADO BOT</span>
            <span id="headerBotStatus" class="text-amber-400 font-mono font-bold text-sm">
              Verificando...
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Si no ha iniciado sesión, aviso informativo amigable -->
    <?php if (!$usuario_logueado): ?>
      <div class="bg-gradient-to-r from-sky-950/40 via-[#0b1426] to-sky-950/40 border border-sky-500/40 rounded-2xl p-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-sky-500/10 border border-sky-500/30 flex items-center justify-center text-sky-400 text-lg shrink-0">
            ℹ️
          </div>
          <div>
            <span class="text-white font-bold block">Inicia sesión para vincular tu propio Bot de Telegram</span>
            <span class="text-slate-400">Puedes probar el simulador ahora mismo, o ingresar a tu cuenta para que el servidor guarde tu Token y Chat ID permanentemente.</span>
          </div>
        </div>
        <a href="/login.php" class="bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-950 font-black px-4 py-2 rounded-xl transition shrink-0">
          Ingresar a Mi Cuenta
        </a>
      </div>
    <?php endif; ?>

    <!-- Grid Principal de Configuración y Preview -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

      <!-- ================= COLUMNA IZQUIERDA: CONFIGURADOR (7 cols) ================= -->
      <div class="lg:col-span-7 space-y-5">

        <!-- Tarjeta de Configuración -->
        <div class="glass-card rounded-2xl p-5 sm:p-6 border border-slate-800 space-y-5">
          
          <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div>
              <h2 class="text-white font-extrabold text-base flex items-center gap-2">
                <span>⚙️</span> Parámetros del Bot de Telegram
              </h2>
              <p class="text-xs text-slate-400 mt-0.5">Sigue los 3 pasos a continuación para conectar tu bot personal.</p>
            </div>
            <div class="flex items-center gap-2">
              <label class="text-xs font-semibold text-slate-300 cursor-pointer" for="chkBotActivo">Bot Activo</label>
              <input type="checkbox" id="chkBotActivo" checked class="w-4 h-4 accent-amber-500 rounded cursor-pointer">
            </div>
          </div>

          <!-- Mini Guía en 3 Pasos Visuales -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
            <div class="p-3 rounded-xl bg-[#060912] border border-slate-800 space-y-1.5 flex flex-col justify-between">
              <div>
                <span class="text-amber-400 font-bold block text-xs flex items-center gap-1">
                  <span>1.</span> Crear Bot
                </span>
                <p class="text-slate-400 text-[11px] leading-relaxed">
                  Abre <strong>@BotFather</strong>, escribe <code class="bg-black/60 px-1 py-0.5 rounded text-amber-300">/newbot</code>, nómbralo y copia el Token HTTP API.
                </p>
              </div>
              <a href="https://t.me/BotFather" target="_blank" class="mt-2 text-center bg-sky-500/10 hover:bg-sky-500/20 text-sky-400 border border-sky-500/30 font-bold py-1 px-2 rounded-lg text-[10px] transition">
                Abrir @BotFather ↗
              </a>
            </div>

            <div class="p-3 rounded-xl bg-[#060912] border border-slate-800 space-y-1.5 flex flex-col justify-between">
              <div>
                <span class="text-amber-400 font-bold block text-xs flex items-center gap-1">
                  <span>2.</span> Iniciar Bot
                </span>
                <p class="text-slate-400 text-[11px] leading-relaxed">
                  Entra a tu nuevo bot en Telegram y dale clic al botón grande de <strong>INICIAR</strong> o envíale un mensaje <code class="bg-black/60 px-1 py-0.5 rounded text-amber-300">/start</code>.
                </p>
              </div>
              <span class="text-center text-slate-500 text-[10px] py-1">
                (Obligatorio para recibir msgs)
              </span>
            </div>

            <div class="p-3 rounded-xl bg-[#060912] border border-slate-800 space-y-1.5 flex flex-col justify-between">
              <div>
                <span class="text-amber-400 font-bold block text-xs flex items-center gap-1">
                  <span>3.</span> Tu Chat ID
                </span>
                <p class="text-slate-400 text-[11px] leading-relaxed">
                  Abre <strong>@userinfobot</strong> en Telegram para ver tu ID numérico personal (ej. <code class="bg-black/60 px-1 py-0.5 rounded text-emerald-300">984729104</code>).
                </p>
              </div>
              <a href="https://t.me/userinfobot" target="_blank" class="mt-2 text-center bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 font-bold py-1 px-2 rounded-lg text-[10px] transition">
                Ver Mi ID ↗
              </a>
            </div>
          </div>

          <!-- Formulario de Configuración -->
          <div class="space-y-4 pt-1">
            
            <!-- Campo 1: Token del Bot -->
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label class="block text-xs font-bold text-slate-300 flex items-center gap-1.5">
                  <span>🔑</span> Token de Telegram (HTTP API):
                </label>
                <button type="button" onclick="toggleTokenVisibility()" class="text-[11px] text-sky-400 hover:text-sky-300 transition">
                  <span id="tokenToggleIcon">👁️</span> <span id="tokenToggleText">Mostrar Token</span>
                </button>
              </div>
              <input id="tgInputToken" type="password" placeholder="Ej: 7123456789:AAHq_xZb1y..." class="w-full bg-[#050811] border border-slate-700 focus:border-amber-400 rounded-xl px-3.5 py-2.5 text-white font-mono text-xs outline-none transition shadow-inner">
            </div>

            <!-- Campo 2: Chat ID -->
            <div>
              <label class="block text-xs font-bold text-slate-300 mb-1.5 flex items-center gap-1.5">
                <span>💬</span> Tu Chat ID de Telegram:
              </label>
              <input id="tgInputChatId" type="text" placeholder="Ej: 984729104 (ID numérico de tu cuenta)" class="w-full bg-[#050811] border border-slate-700 focus:border-amber-400 rounded-xl px-3.5 py-2.5 text-white font-mono text-xs outline-none transition shadow-inner">
            </div>

            <!-- Campo 3: Filtro de Criptomoneda (Moneda Específica o Todas) -->
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label class="block text-xs font-bold text-slate-300 flex items-center gap-1.5">
                  <span>🎯</span> ¿Qué criptomoneda deseas recibir en tu bot?
                </label>
                <span class="text-[11px] text-amber-400 font-mono" id="lblMonedaSeleccionada">Todas las Oportunidades</span>
              </div>
              <p class="text-[11px] text-slate-400 mb-2">
                Puedes filtrar para que tu bot te envíe únicamente las señales de la moneda que operas habitualmente, o todas las de alta probabilidad.
              </p>

              <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs font-semibold">
                <button type="button" onclick="setTgCoinFilter('ALL')" id="tgCoin-ALL" class="tg-coin-btn p-2.5 rounded-xl border border-amber-500 bg-amber-500 text-slate-950 font-bold transition flex items-center justify-center gap-1.5">
                  <span>⚡</span> Todas (Global)
                </button>
                <button type="button" onclick="setTgCoinFilter('BTCUSDT')" id="tgCoin-BTCUSDT" class="tg-coin-btn p-2.5 rounded-xl border border-slate-800 bg-[#060912] text-slate-300 hover:border-slate-700 transition flex items-center justify-center gap-1.5">
                  <span>🟡</span> Solo BTC
                </button>
                <button type="button" onclick="setTgCoinFilter('ETHUSDT')" id="tgCoin-ETHUSDT" class="tg-coin-btn p-2.5 rounded-xl border border-slate-800 bg-[#060912] text-slate-300 hover:border-slate-700 transition flex items-center justify-center gap-1.5">
                  <span>🔷</span> Solo ETH
                </button>
                <button type="button" onclick="setTgCoinFilter('SOLUSDT')" id="tgCoin-SOLUSDT" class="tg-coin-btn p-2.5 rounded-xl border border-slate-800 bg-[#060912] text-slate-300 hover:border-slate-700 transition flex items-center justify-center gap-1.5">
                  <span>🟣</span> Solo SOL
                </button>
                <button type="button" onclick="setTgCoinFilter('LINKUSDT')" id="tgCoin-LINKUSDT" class="tg-coin-btn p-2.5 rounded-xl border border-slate-800 bg-[#060912] text-slate-300 hover:border-slate-700 transition flex items-center justify-center gap-1.5">
                  <span>🔵</span> Solo LINK
                </button>
                <button type="button" onclick="setTgCoinFilter('BNBUSDT')" id="tgCoin-BNBUSDT" class="tg-coin-btn p-2.5 rounded-xl border border-slate-800 bg-[#060912] text-slate-300 hover:border-slate-700 transition flex items-center justify-center gap-1.5">
                  <span>🪙</span> Solo BNB
                </button>
                <button type="button" onclick="setTgCoinFilter('AVAXUSDT')" id="tgCoin-AVAXUSDT" class="tg-coin-btn p-2.5 rounded-xl border border-slate-800 bg-[#060912] text-slate-300 hover:border-slate-700 transition flex items-center justify-center gap-1.5">
                  <span>🔴</span> Solo AVAX
                </button>
                <button type="button" onclick="setTgCoinFilter('DOGEUSDT')" id="tgCoin-DOGEUSDT" class="tg-coin-btn p-2.5 rounded-xl border border-slate-800 bg-[#060912] text-slate-300 hover:border-slate-700 transition flex items-center justify-center gap-1.5">
                  <span>🐕</span> Solo DOGE
                </button>
              </div>
            </div>

            <!-- Campo 4: Confluencia Mínima -->
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label class="block text-xs font-bold text-slate-300 flex items-center gap-1.5">
                  <span>🛡️</span> Filtro de Confluencia Mínima:
                </label>
                <span class="text-amber-400 font-mono text-xs font-bold" id="lblScoreMinimo">≥ 70% (Recomendado)</span>
              </div>
              <div class="grid grid-cols-3 gap-2 text-xs">
                <button type="button" onclick="setScoreFilter(65)" id="btnScore-65" class="score-btn p-2 rounded-xl border border-slate-800 bg-[#060912] text-slate-300 transition hover:border-slate-700">
                  <span class="font-bold block">≥ 65%</span>
                  <span class="text-[10px] text-slate-500">Más Señales</span>
                </button>
                <button type="button" onclick="setScoreFilter(70)" id="btnScore-70" class="score-btn p-2 rounded-xl border border-amber-500 bg-amber-500/15 text-white font-bold transition">
                  <span class="font-bold block text-amber-400">≥ 70%</span>
                  <span class="text-[10px] text-slate-400">Equilibrado ⭐</span>
                </button>
                <button type="button" onclick="setScoreFilter(80)" id="btnScore-80" class="score-btn p-2 rounded-xl border border-slate-800 bg-[#060912] text-slate-300 transition hover:border-slate-700">
                  <span class="font-bold block">≥ 80%</span>
                  <span class="text-[10px] text-slate-500">Sniper A+</span>
                </button>
              </div>
            </div>

            <!-- Caja de Estado Dinámica -->
            <div id="tgAlertBox" class="hidden p-3.5 rounded-xl text-xs font-mono border"></div>

            <!-- Botones de Acción -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
              <button type="button" onclick="testTelegramAlert()" id="btnTgTest" class="bg-[#101b2e] hover:bg-[#162742] text-sky-300 border border-sky-500/40 font-bold py-3 rounded-xl text-xs transition cursor-pointer flex items-center justify-center gap-2 shadow-lg shadow-sky-950/40">
                <span>🧪</span> <span>Probar en mi Telegram</span>
              </button>
              <button type="button" onclick="saveTelegramConfig()" id="btnTgSave" class="bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-950 font-black py-3 rounded-xl text-xs transition shadow-lg shadow-amber-500/20 cursor-pointer flex items-center justify-center gap-2">
                <span>💾</span> <span>Guardar y Activar Bot</span>
              </button>
            </div>

          </div>

        </div>

      </div>

      <!-- ================= COLUMNA DERECHA: PREVIEW Y MEMBRESÍA VIP (5 cols) ================= -->
      <div class="lg:col-span-5 space-y-5">

        <!-- 1. Simulador Visual: Cómo llegará a tu Telegram -->
        <div class="glass-card rounded-2xl p-5 border border-slate-800 space-y-3">
          <div class="flex items-center justify-between border-b border-slate-800 pb-2.5">
            <span class="text-xs font-bold text-white flex items-center gap-1.5">
              <span>📱</span> Vista Previa en tu Celular
            </span>
            <span class="text-[10px] font-mono text-sky-400 bg-sky-500/10 border border-sky-500/20 px-2 py-0.5 rounded-full">
              Formato VIP en Vivo
            </span>
          </div>

          <!-- Burbuja de Telegram Estilo Dark Mode -->
          <div class="tg-bubble p-4 text-xs font-mono text-slate-100 shadow-xl space-y-2 leading-relaxed">
            <div class="flex items-center justify-between border-b border-white/10 pb-1.5 mb-1.5">
              <span class="font-bold text-amber-400 flex items-center gap-1.5">
                <span>🟡</span> SEÑAL BINANCE FUTUROS
              </span>
              <span class="text-[10px] text-slate-400 font-sans">Ahora</span>
            </div>

            <div class="space-y-1">
              <div>💎 <strong>Par:</strong> <span id="pvPar" class="text-sky-300 font-bold">LINK/USDT</span> <span class="text-slate-400 text-[11px]">(Perpetuo)</span></div>
              <div>📉 <strong>Tipo:</strong> <span id="pvTipo" class="text-rose-400 font-bold">SHORT (VENTA)</span></div>
              <div>🎯 <strong>Entrada:</strong> <span id="pvEntrada" class="text-white font-bold">$12.5400</span></div>
              <div>🟢 <strong>Take Profit:</strong> <span id="pvTP" class="text-emerald-400 font-bold">$12.3000 (+1.91%)</span></div>
              <div>🔴 <strong>Stop Loss:</strong> <span id="pvSL" class="text-rose-400 font-bold">$12.6700 (-1.04%)</span></div>
              <div>🛡️ <strong>R:R:</strong> <span id="pvRR" class="text-amber-300 font-bold">1:1.8R</span> · <strong>Confluencia:</strong> <span id="pvConf" class="text-emerald-400 font-bold">72%</span></div>
            </div>

            <div class="pt-2 border-t border-white/10 text-[10px] text-slate-400 flex items-center justify-between font-sans">
              <span>⚡ Despachado por Quantum AI Engine</span>
              <span class="text-slate-500">✓✓</span>
            </div>
          </div>
          <p class="text-[11px] text-slate-400 text-center italic">
            El bot te notificará al instante con un mensaje idéntico cuando se detecte la oportunidad.
          </p>
        </div>

        <!-- 2. Tarjeta VIP $5 USD / mes -->
        <div class="glass-card rounded-2xl p-5 border border-amber-500/30 glow-gold space-y-4 relative overflow-hidden">
          <div class="absolute top-0 right-0 w-32 h-32 bg-amber-500/10 rounded-full blur-2xl pointer-events-none"></div>

          <div class="flex items-start justify-between">
            <div>
              <div class="inline-flex items-center gap-1.5 text-amber-400 font-black text-xs uppercase tracking-wider mb-1">
                <span>👑</span> Membresía Completa
              </div>
              <h3 class="text-white font-black text-lg">Quantum VIP Trader</h3>
              <p class="text-xs text-slate-400 mt-0.5">Alertas automáticas 24/7 sin retraso en tu bot privado.</p>
            </div>
            <div class="text-right">
              <span class="text-amber-400 font-black text-2xl font-mono">$5</span>
              <span class="text-slate-400 text-xs block font-sans">USD / mes</span>
            </div>
          </div>

          <!-- Beneficios VIP -->
          <ul class="text-xs text-slate-300 space-y-1.5 pt-1">
            <li class="flex items-center gap-2">
              <span class="text-emerald-400 font-bold">✓</span> Alertas instantáneas en tu Telegram personal (&lt;180ms)
            </li>
            <li class="flex items-center gap-2">
              <span class="text-emerald-400 font-bold">✓</span> Filtro de moneda personalizada (BTC, ETH, SOL, LINK, etc.)
            </li>
            <li class="flex items-center gap-2">
              <span class="text-emerald-400 font-bold">✓</span> Acceso ilimitado al Radar Cuántico en vivo 24/7
            </li>
            <li class="flex items-center gap-2">
              <span class="text-emerald-400 font-bold">✓</span> Gestión de riesgo y confluencia institucional del bot
            </li>
          </ul>

          <!-- Enlace Directo a Carpeta Dedicada /vip/ -->
          <div class="pt-2">
            <a href="/vip/" class="w-full bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-950 font-black py-3 rounded-xl text-xs transition shadow-lg shadow-amber-400/20 flex items-center justify-center gap-2">
              <span>👑</span> <span>Abrir Pasarela VIP & Planes ($5 USD) ↗</span>
            </a>
          </div>

        </div>

      </div>

    </div>

  </main>

  <!-- ================= FOOTER ================= -->
  <footer class="bg-[#05070d] border-t border-slate-800/80 px-4 lg:px-8 py-4 text-xs text-slate-400 mt-auto">
    <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <img src="/assets/logo.png" alt="Quantum AI" class="w-5 h-5 object-contain">
        <span class="text-white font-bold">Quantum AI Terminal</span>
        <span>• Servidor de Alertas de Trading 24/7</span>
      </div>
      <div class="flex items-center gap-4 text-slate-500 text-[11px]">
        <a href="/" class="hover:text-slate-300 transition">Radar Cuántico</a>
        <span>•</span>
        <a href="/telegram/" class="text-amber-400 hover:underline">Mi Bot Telegram</a>
        <span>•</span>
        <a href="/login.php" class="hover:text-slate-300 transition">Mi Cuenta</a>
      </div>
    </div>
  </footer>

  <!-- ================= TOAST FLOTANTE ================= -->
  <div id="toast" class="fixed bottom-5 right-5 z-50 bg-[#0d1322] border-2 border-amber-500 text-white px-4 py-3 rounded-xl shadow-2xl text-xs font-sans font-bold hidden items-center gap-2 transition-all">
    <span class="text-amber-400 text-base">⚡</span>
    <span id="toastMsg">Mensaje</span>
  </div>

  <!-- ================= SCRIPTS JS ================= -->
  <script>
    // Variables de estado
    let selectedCoin = 'ALL';
    let selectedScore = 70;
    let currentPaymentNet = 'BEP20';
    let isTokenMasked = true;
    let rawToken = '';

    const WALLETS = {
      BEP20: '0x71C839a8204B6D84f04dD97e1c8d19f05Eb7F510',
      TRC20: 'TXy478A29dKms8910LmnoPqRsTuVwXyZ10'
    };

    // Datos simulados de vista previa según la moneda
    const COIN_PREVIEWS = {
      'ALL': { par: 'LINK/USDT', tipo: 'SHORT (VENTA)', entrada: '$12.5400', tp: '$12.3000 (+1.91%)', sl: '$12.6700 (-1.04%)', rr: '1:1.8R', conf: '72%', isLong: false },
      'BTCUSDT': { par: 'BTC/USDT', tipo: 'LONG (COMPRA)', entrada: '$64,280.00', tp: '$65,450.00 (+1.82%)', sl: '$63,750.00 (-0.82%)', rr: '1:2.2R', conf: '84%', isLong: true },
      'ETHUSDT': { par: 'ETH/USDT', tipo: 'SHORT (VENTA)', entrada: '$2,580.50', tp: '$2,520.00 (+2.34%)', sl: '$2,615.00 (-1.33%)', rr: '1:1.7R', conf: '76%', isLong: false },
      'SOLUSDT': { par: 'SOL/USDT', tipo: 'LONG (COMPRA)', entrada: '$148.20', tp: '$153.80 (+3.77%)', sl: '$145.50 (-1.82%)', rr: '1:2.1R', conf: '79%', isLong: true },
      'LINKUSDT': { par: 'LINK/USDT', tipo: 'SHORT (VENTA)', entrada: '$12.5400', tp: '$12.3000 (+1.91%)', sl: '$12.6700 (-1.04%)', rr: '1:1.8R', conf: '72%', isLong: false },
      'BNBUSDT': { par: 'BNB/USDT', tipo: 'LONG (COMPRA)', entrada: '$585.40', tp: '$598.00 (+2.15%)', sl: '$579.50 (-1.01%)', rr: '1:2.1R', conf: '75%', isLong: true },
      'AVAXUSDT': { par: 'AVAX/USDT', tipo: 'LONG (COMPRA)', entrada: '$27.85', tp: '$29.40 (+5.56%)', sl: '$27.10 (-2.69%)', rr: '1:2.0R', conf: '71%', isLong: true },
      'DOGEUSDT': { par: 'DOGE/USDT', tipo: 'LONG (COMPRA)', entrada: '$0.1245', tp: '$0.1320 (+6.02%)', sl: '$0.1210 (-2.81%)', rr: '1:2.1R', conf: '70%', isLong: true }
    };

    // Al cargar la página
    document.addEventListener('DOMContentLoaded', () => {
      cargarConfiguracionBot();
      actualizarPreviewTelegram();
    });

    // Cargar datos actuales desde el servidor
    async function cargarConfiguracionBot() {
      try {
        const res = await fetch('/api/telegram.php?action=get_config');
        const data = await res.json();

        const statusEl = document.getElementById('headerBotStatus');

        if (data.success && data.config) {
          const cfg = data.config;
          if (cfg.configurado) {
            document.getElementById('tgInputToken').value = cfg.bot_token || '';
            rawToken = cfg.token_raw || cfg.bot_token;
            document.getElementById('tgInputChatId').value = cfg.chat_id || '';
            document.getElementById('chkBotActivo').checked = !!cfg.activo;
            
            if (cfg.moneda_filtro) setTgCoinFilter(cfg.moneda_filtro);
            if (cfg.score_minimo) setScoreFilter(cfg.score_minimo);

            if (statusEl) {
              statusEl.className = cfg.activo ? 'text-emerald-400 font-mono font-bold text-sm' : 'text-slate-400 font-mono text-sm';
              statusEl.textContent = cfg.activo ? '🟢 Conectado' : '⚪ En Pausa';
            }
          } else {
            if (statusEl) {
              statusEl.className = 'text-amber-400 font-mono font-bold text-sm';
              statusEl.textContent = '🟡 Sin Configurar';
            }
          }
        } else {
          if (statusEl) {
            statusEl.className = 'text-slate-400 font-mono text-sm';
            statusEl.textContent = '⚪ Modo Prueba';
          }
        }
      } catch (err) {
        console.warn('Error al cargar config de telegram:', err);
      }
    }

    // Cambiar filtro de moneda
    function setTgCoinFilter(coin) {
      selectedCoin = coin;
      document.querySelectorAll('.tg-coin-btn').forEach(btn => {
        btn.classList.remove('border-amber-500', 'bg-amber-500', 'text-slate-950', 'font-bold');
        btn.classList.add('border-slate-800', 'bg-[#060912]', 'text-slate-300');
      });

      const activeBtn = document.getElementById(`tgCoin-${coin}`);
      if (activeBtn) {
        activeBtn.classList.remove('border-slate-800', 'bg-[#060912]', 'text-slate-300');
        activeBtn.classList.add('border-amber-500', 'bg-amber-500', 'text-slate-950', 'font-bold');
      }

      const lbl = document.getElementById('lblMonedaSeleccionada');
      if (lbl) {
        lbl.textContent = coin === 'ALL' ? 'Todas las Oportunidades' : `Solo ${coin}`;
      }

      actualizarPreviewTelegram();
    }

    // Cambiar filtro de confluencia
    function setScoreFilter(score) {
      selectedScore = score;
      document.querySelectorAll('.score-btn').forEach(btn => {
        btn.classList.remove('border-amber-500', 'bg-amber-500/15', 'text-white', 'font-bold');
        btn.classList.add('border-slate-800', 'bg-[#060912]', 'text-slate-300');
      });

      const activeBtn = document.getElementById(`btnScore-${score}`);
      if (activeBtn) {
        activeBtn.classList.remove('border-slate-800', 'bg-[#060912]', 'text-slate-300');
        activeBtn.classList.add('border-amber-500', 'bg-amber-500/15', 'text-white', 'font-bold');
      }

      const lbl = document.getElementById('lblScoreMinimo');
      if (lbl) {
        lbl.textContent = score === 80 ? '≥ 80% (Sniper A+)' : (score === 70 ? '≥ 70% (Equilibrado ⭐)' : '≥ 65% (Más Señales)');
      }
    }

    // Actualizar la vista previa del mensaje en vivo
    function actualizarPreviewTelegram() {
      const p = COIN_PREVIEWS[selectedCoin] || COIN_PREVIEWS['ALL'];
      document.getElementById('pvPar').textContent = p.par;
      
      const tipoEl = document.getElementById('pvTipo');
      tipoEl.textContent = p.tipo;
      tipoEl.className = p.isLong ? 'text-emerald-400 font-bold' : 'text-rose-400 font-bold';

      document.getElementById('pvEntrada').textContent = p.entrada;
      document.getElementById('pvTP').textContent = p.tp;
      document.getElementById('pvSL').textContent = p.sl;
      document.getElementById('pvRR').textContent = p.rr;
      document.getElementById('pvConf').textContent = `${Math.max(selectedScore, parseInt(p.conf))}%`;
    }

    // Alternar visibilidad del Token
    function toggleTokenVisibility() {
      const input = document.getElementById('tgInputToken');
      const icon = document.getElementById('tokenToggleIcon');
      const text = document.getElementById('tokenToggleText');

      if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = '🔒';
        text.textContent = 'Ocultar Token';
      } else {
        input.type = 'password';
        icon.textContent = '👁️';
        text.textContent = 'Mostrar Token';
      }
    }

    // Guardar configuración del bot
    async function saveTelegramConfig() {
      const token = document.getElementById('tgInputToken').value.trim();
      const chatId = document.getElementById('tgInputChatId').value.trim();
      const activo = document.getElementById('chkBotActivo').checked ? 1 : 0;
      const btn = document.getElementById('btnTgSave');

      if (!token || !chatId) {
        showTgAlert('error', '⚠️ Debes completar tanto el Token de tu Bot como tu Chat ID.');
        return;
      }

      btn.disabled = true;
      btn.innerHTML = '<span>⏳</span> <span>Guardando en Servidor...</span>';

      try {
        const res = await fetch('/api/telegram.php?action=save_config', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            bot_token: token,
            chat_id: chatId,
            moneda_filtro: selectedCoin,
            score_minimo: selectedScore,
            activo: activo
          })
        });
        const data = await res.json();

        if (data.success) {
          showTgAlert('success', '✅ ' + data.message);
          showToast('✅ Bot de Telegram guardado y activo');
          
          const statusEl = document.getElementById('headerBotStatus');
          if (statusEl) {
            statusEl.className = activo ? 'text-emerald-400 font-mono font-bold text-sm' : 'text-slate-400 font-mono text-sm';
            statusEl.textContent = activo ? '🟢 Conectado' : '⚪ En Pausa';
          }
        } else {
          if (data.requiere_vip) {
            showTgAlert('error', '👑 ' + data.message);
          } else {
            showTgAlert('error', '❌ ' + (data.message || 'Error al guardar'));
          }
        }
      } catch (err) {
        showTgAlert('error', '❌ Error de conexión al guardar el bot.');
      } finally {
        btn.disabled = false;
        btn.innerHTML = '<span>💾</span> <span>Guardar y Activar Bot</span>';
      }
    }

    // Probar envío de alerta real a Telegram
    async function testTelegramAlert() {
      const token = document.getElementById('tgInputToken').value.trim();
      const chatId = document.getElementById('tgInputChatId').value.trim();
      const btn = document.getElementById('btnTgTest');

      if (!token || !chatId) {
        showTgAlert('error', '⚠️ Pega tu Token y tu Chat ID para enviar la prueba a tu teléfono.');
        return;
      }

      btn.disabled = true;
      btn.innerHTML = '<span>⏳</span> <span>Enviando Alerta...</span>';

      try {
        const res = await fetch('/api/telegram.php?action=test_alert', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            bot_token: token,
            chat_id: chatId,
            moneda_filtro: selectedCoin
          })
        });
        const data = await res.json();

        if (data.success) {
          showTgAlert('success', '📲 ¡Mensaje de prueba enviado! Revisa tu Telegram ahora mismo.');
          showToast('📲 Revisa tu Telegram');
        } else {
          showTgAlert('error', '❌ ' + (data.message || 'Error al enviar alerta a Telegram'));
        }
      } catch (err) {
        showTgAlert('error', '❌ No se pudo conectar con el servidor de Telegram.');
      } finally {
        btn.disabled = false;
        btn.innerHTML = '<span>🧪</span> <span>Probar en mi Telegram</span>';
      }
    }

    // Caja de alertas visuales
    function showTgAlert(tipo, msg) {
      const box = document.getElementById('tgAlertBox');
      box.className = tipo === 'success' 
        ? 'p-3.5 rounded-xl text-xs font-mono border bg-emerald-500/10 text-emerald-300 border-emerald-500/30 block'
        : 'p-3.5 rounded-xl text-xs font-mono border bg-rose-500/10 text-rose-300 border-rose-500/30 block';
      box.textContent = msg;
    }

    // Copiar dirección de billetera
    function copyCryptoAddress() {
      const text = document.getElementById('cryptoWalletText').textContent.trim();
      navigator.clipboard.writeText(text).then(() => {
        showToast('📋 Billetera copiada al portapapeles');
      }).catch(() => {
        showToast('Billetera: ' + text);
      });
    }

    // Cambiar red de pago
    function setPaymentNetwork(net) {
      currentPaymentNet = net;
      document.querySelectorAll('.pay-net-btn').forEach(btn => {
        btn.classList.remove('border-amber-500', 'bg-amber-500/15', 'text-white');
        btn.classList.add('border-slate-800', 'bg-[#060912]', 'text-slate-400');
      });

      const active = document.getElementById(`payNet-${net}`);
      if (active) {
        active.classList.remove('border-slate-800', 'bg-[#060912]', 'text-slate-400');
        active.classList.add('border-amber-500', 'bg-amber-500/15', 'text-white');
      }

      document.getElementById('cryptoWalletText').textContent = WALLETS[net];
    }

    // Validar TxID
    async function validateCryptoTxId() {
      const tx = document.getElementById('inputTxId').value.trim();
      const btn = document.getElementById('btnValidateTx');
      const box = document.getElementById('txAlertBox');

      if (!tx || tx.length < 10) {
        box.className = 'text-xs p-2 rounded-lg mt-1 font-mono bg-rose-500/10 text-rose-300 border border-rose-500/30 block';
        box.textContent = '⚠️ Ingresa un Hash / TxID válido de la transacción.';
        return;
      }

      btn.disabled = true;
      btn.textContent = 'Verificando...';

      try {
        const res = await fetch('/api/auth.php?action=validate_tx', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ tx_hash: tx, network: currentPaymentNet })
        });
        const data = await res.json();

        if (data.success) {
          box.className = 'text-xs p-2 rounded-lg mt-1 font-mono bg-emerald-500/10 text-emerald-300 border border-emerald-500/30 block';
          box.textContent = '✅ ' + data.message;
          showToast('👑 Membresía VIP Activada');
          setTimeout(() => location.reload(), 1500);
        } else {
          box.className = 'text-xs p-2 rounded-lg mt-1 font-mono bg-rose-500/10 text-rose-300 border border-rose-500/30 block';
          box.textContent = '❌ ' + (data.message || 'Pago en verificación');
        }
      } catch (err) {
        box.className = 'text-xs p-2 rounded-lg mt-1 font-mono bg-amber-500/10 text-amber-300 border border-amber-500/30 block';
        box.textContent = '⏳ Comprobante registrado. Nuestro sistema lo validará en el siguiente bloque.';
      } finally {
        btn.disabled = false;
        btn.textContent = 'Validar';
      }
    }

    // Mensaje flotante Toast
    function showToast(msg) {
      const t = document.getElementById('toast');
      document.getElementById('toastMsg').textContent = msg;
      t.classList.remove('hidden');
      t.classList.add('flex');
      setTimeout(() => {
        t.classList.remove('flex');
        t.classList.add('hidden');
      }, 3500);
    }
  </script>

</body>
</html>
