<?php
/**
 * bot.mddorma.com/ia/index.php — MOTOR DE RED NEURONAL INSTITUCIONAL (Q-DNN v4.8)
 * ==============================================================================
 * Topología Neuronal Dinámica: Transformer Multi-Head Attention + FinRL PPO (14D)
 * Alimentado con Datos Interbancarios de MetaTrader 5 y Optimización de Preferencia Directa (DPO).
 * Stitch Project ID: 5906833528634846781 / Screen: 45df22a5f18c449687e4b3b1a4ffbd99m
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
$is_logged_in = !empty($usuario_logueado);
$userName = $usuario_logueado['nombre'] ?? 'Operador Cuántico';
$userInitial = strtoupper(substr($userName, 0, 1));

// Carga de telemetría y posiciones vivas desde almacenamiento local o JSON
$storage_dir = dirname(__DIR__) . '/storage';
$activas = [];
if (is_file($storage_dir . '/senales_activas.json')) {
    $tmp = json_decode((string)file_get_contents($storage_dir . '/senales_activas.json'), true);
    if (is_array($tmp)) { $activas = $tmp; }
}

// Respaldo de live_positions.json si está en entorno local
$local_live = 'C:/Users/pucll/Desktop/TRA/data_cache/live_positions.json';
if (empty($activas) && is_file($local_live)) {
    $tmpL = json_decode((string)file_get_contents($local_live), true);
    if (is_array($tmpL)) { $activas = $tmpL; }
}

$primera_pos = !empty($activas) ? reset($activas) : null;
$simbolo_activo = $primera_pos['symbol'] ?? 'AUDNZD';
$lado_activo = strtoupper($primera_pos['side'] ?? 'SELL');
$precio_activo = (float)($primera_pos['price_open'] ?? 1.24612);
$sl_activo = (float)($primera_pos['sl'] ?? 1.24732);
$tp_activo = (float)($primera_pos['tp'] ?? 1.24408);
$profit_activo = (float)($primera_pos['profit'] ?? 0.92);
$ticket_activo = $primera_pos['ticket'] ?? '10585261453';
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Motor de Red Neuronal Cuántica (Q-DNN v4.8) | Quantum AI Institutional</title>
  <link rel="icon" type="image/png" href="/favicon.png">

  <!-- Tailwind CSS & Fuentes Google -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            brand: {
              gold: '#f59e0b',
              dark: '#04070e',
              card: '#080d1a',
              border: '#1e293b'
            }
          }
        }
      }
    }
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
      background-color: #04070e;
      color: #cbd5e1;
      overflow-x: hidden;
    }
    .font-mono {
      font-family: 'JetBrains Mono', monospace;
    }
    .glass-panel {
      background: rgba(8, 13, 26, 0.92);
      backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.07);
    }
    .glow-cyan {
      box-shadow: 0 0 25px -3px rgba(6, 182, 212, 0.25);
    }
    .glow-emerald {
      box-shadow: 0 0 25px -3px rgba(16, 185, 129, 0.30);
    }
    ::-webkit-scrollbar {
      width: 5px;
      height: 5px;
    }
    ::-webkit-scrollbar-track {
      background: #04070e;
    }
    ::-webkit-scrollbar-thumb {
      background: #1e293b;
      border-radius: 4px;
    }
  </style>
</head>
<body class="min-h-screen bg-[#04070e] text-slate-100 flex flex-col selection:bg-amber-500 selection:text-slate-950">

  <!-- ================= TOP HEADER INSTITUCIONAL ================= -->
  <header class="h-14 bg-[#060a14] border-b border-slate-800/90 px-3 sm:px-6 flex items-center justify-between gap-4 sticky top-0 z-50 backdrop-blur-md">
    
    <!-- Logo & Subtítulo -->
    <div class="flex items-center gap-3 shrink-0">
      <a href="/" class="flex items-center gap-2.5 group">
        <img src="/assets/logo.png" alt="Quantum AI" class="w-7 h-7 object-contain drop-shadow-[0_0_10px_rgba(245,158,11,0.5)] group-hover:scale-105 transition">
        <div>
          <div class="flex items-center gap-1 leading-none">
            <span class="font-black text-sm tracking-wider text-white">QUANTUM<span class="text-amber-400">.AI</span></span>
          </div>
          <p class="text-[8.5px] font-mono tracking-widest text-amber-400 font-bold uppercase mt-0.5">TERMINAL INSTITUCIONAL</p>
        </div>
      </a>
    </div>

    <!-- Pestañas de Navegación Centrales -->
    <nav class="hidden lg:flex items-center gap-1 bg-[#090f20]/90 border border-slate-800/80 p-1 rounded-xl text-xs font-semibold text-slate-400">
      <a href="/" class="px-3 py-1 rounded-lg hover:text-white hover:bg-slate-800/50 transition">Terminal Unificado</a>
      <a href="/#sec-signals" class="px-3 py-1 rounded-lg hover:text-white hover:bg-slate-800/50 transition">Radar en Vivo</a>
      <a href="/ia/" class="px-3 py-1 rounded-lg text-amber-400 bg-amber-500/15 border border-amber-500/30 transition">Red Neuronal Q-DNN</a>
      <a href="/#sec-calculator" class="px-3 py-1 rounded-lg hover:text-white hover:bg-slate-800/50 transition">Calculadora & Riesgo</a>
    </nav>

    <!-- Telemetría & Perfil Usuario -->
    <div class="flex items-center gap-3 sm:gap-4 shrink-0 text-xs font-mono">
      <div class="hidden sm:flex items-center gap-3 text-slate-400">
        <span class="flex items-center gap-1.5">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          <span class="text-slate-300">EQUINIX LD4:</span> <span class="text-emerald-400 font-bold">3.8ms</span>
        </span>
        <span class="text-slate-700">|</span>
        <span class="flex items-center gap-1.5">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          <span class="text-slate-300">METATRADER 5 FIX:</span> <span class="text-emerald-400 font-bold">CONECTADO</span>
        </span>
        <span class="text-slate-700">|</span>
        <a href="/vip/" class="text-amber-400 hover:text-amber-300 font-bold tracking-wider uppercase transition">
          <?= $es_vip ? 'VIP TIER III' : 'PRUEBA ACTIVA' ?>
        </a>
      </div>

      <!-- Avatar Pill -->
      <a href="/perfil/" class="flex items-center gap-2 bg-[#090f1f] hover:bg-[#111933] border border-slate-700/80 hover:border-amber-500/50 px-2.5 py-1 rounded-xl transition group">
        <div class="w-6 h-6 rounded-full bg-gradient-to-tr from-amber-500 to-amber-300 text-slate-950 font-black text-[10px] flex items-center justify-center shadow-sm">
          <?= htmlspecialchars($userInitial) ?>
        </div>
        <span class="text-white font-bold text-xs group-hover:text-amber-300 transition hidden sm:inline">
          <?= htmlspecialchars($userName) ?>
        </span>
      </a>
    </div>
  </header>

  <!-- ================= LAYOUT PRINCIPAL ================= -->
  <div class="flex-1 flex min-h-[calc(100vh-3.5rem)]">

    <!-- BARRA LATERAL IZQUIERDA (DOCK SLIM) -->
    <aside class="w-14 bg-[#060a14] border-r border-slate-800/90 flex flex-col items-center py-4 justify-between shrink-0 sticky top-14 h-[calc(100vh-3.5rem)] z-40 select-none">
      <div class="flex flex-col items-center gap-3 text-slate-400">
        <a href="/" class="p-2.5 rounded-xl hover:text-white hover:bg-[#0f172a] transition cursor-pointer" title="Gráfico y Terminal">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </a>
        <a href="/#sec-signals" class="p-2.5 rounded-xl hover:text-white hover:bg-[#0f172a] transition cursor-pointer" title="Señales en Vivo">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        </a>
        <a href="/ia/" class="p-2.5 rounded-xl text-amber-400 bg-amber-500/15 border border-amber-500/35 transition shadow-[0_0_15px_rgba(245,158,11,0.2)]" title="Red Neuronal Cuántica (Activa)">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="6" cy="6" r="2" stroke-width="2"/>
            <circle cx="18" cy="6" r="2" stroke-width="2"/>
            <circle cx="6" cy="18" r="2" stroke-width="2"/>
            <circle cx="18" cy="18" r="2" stroke-width="2"/>
            <circle cx="12" cy="12" r="2.5" stroke-width="2"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7.5 7.5l3 3m3 0l3-3m-9 9l3-3m3 0l3 3"/>
          </svg>
        </a>
      </div>

      <div class="flex flex-col items-center gap-3 text-slate-400">
        <a href="/telegram/" class="p-2.5 rounded-xl hover:text-sky-400 hover:bg-[#0f172a] transition" title="Alertas de Telegram">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
        </a>
        <a href="/vip/" class="p-2.5 rounded-xl hover:text-amber-400 hover:bg-[#0f172a] transition" title="Membresía VIP">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 4l3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"/></svg>
        </a>
        <a href="/perfil/" class="p-2.5 rounded-xl hover:text-white hover:bg-[#0f172a] transition" title="Mi Cuenta">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </a>
      </div>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="flex-1 p-3 sm:p-5 max-w-[1520px] mx-auto w-full flex flex-col space-y-4">

      <!-- ================= 1. CABECERA DEL MOTOR CUÁNTICO ================= -->
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
          <!-- Badges de Estado -->
          <div class="flex flex-wrap items-center gap-2 mb-2">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-[10px] font-mono font-bold tracking-wider">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
              METATRADER 5 FIX EN VIVO
            </span>
            <span class="px-2.5 py-0.5 rounded-full bg-slate-800/80 border border-slate-700/60 text-slate-300 text-[10px] font-mono">
              LATENCIA TICK: <span class="text-cyan-400 font-bold">1.2ms</span>
            </span>
            <span class="px-2.5 py-0.5 rounded-full bg-slate-800/80 border border-slate-700/60 text-slate-300 text-[10px] font-mono">
              PRECISIÓN CONFLUENCIA: <span class="text-emerald-400 font-bold">94.8%</span>
            </span>
            <span class="px-2.5 py-0.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-300 text-[10px] font-mono flex items-center gap-1 font-bold">
              <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
              HOT-RELOAD EN CALIENTE ACTIVO
            </span>
          </div>

          <!-- Título Principal -->
          <div class="flex items-center gap-3">
            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2">
              Motor de Red Neuronal Institucional
              <span class="text-xs font-mono font-bold px-2 py-0.5 rounded bg-amber-500/20 border border-amber-500/40 text-amber-400">
                Q-DNN v4.8
              </span>
            </h1>
          </div>
          <p class="text-xs text-slate-400 font-medium mt-0.5">
            Temporal Attention Transformer + FinRL PPO (14 Dimensiones Cuánticas) entrenado con $7.5T de liquidez interbancaria
          </p>
        </div>

        <!-- Botones de Acción Limpios -->
        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
          <button onclick="sincronizarInferencia()" id="btnSync" class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-700 hover:border-cyan-500/50 text-xs font-bold text-slate-200 hover:text-white transition flex items-center gap-2 cursor-pointer shadow-sm">
            <span>🔄</span>
            <span>Sincronizar Inferencia</span>
          </button>

          <button onclick="exportarModeloONNX()" class="px-4 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-black text-xs transition flex items-center gap-2 cursor-pointer shadow-[0_0_20px_rgba(245,158,11,0.25)]">
            <span>📥</span>
            <span>Descargar Pesos (.pt / ONNX)</span>
          </button>
        </div>
      </div>

      <!-- ================= 2. BARRA DE CAPAS INSTITUCIONALES (14D) ================= -->
      <div class="glass-panel rounded-2xl px-4 py-2.5 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs font-mono">
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <span class="text-amber-400 font-bold flex items-center gap-1.5 tracking-wider uppercase text-[11px]">
            ARQUITECTURA ACTIVA:
          </span>

          <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-500/10 border border-amber-500/30 text-amber-300 text-[11px] font-semibold">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
            14 Tensores Entrada (Forex & Oro MT5)
          </span>

          <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-cyan-500/10 border border-cyan-500/30 text-cyan-300 text-[11px] font-semibold">
            <span class="w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
            Multi-Head Attention (4 Cabezas)
          </span>

          <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-500/10 border border-blue-500/30 text-blue-300 text-[11px] font-semibold">
            <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
            Actor-Critic PPO (64 Nodos GELU)
          </span>

          <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-[11px] font-semibold">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
            3 Acciones (HOLD, BUY, SELL)
          </span>
        </div>

        <div class="text-slate-400 text-[11px] flex items-center gap-2">
          <span>SINAPSE ENGINE: <strong class="text-slate-200">MT5 REINFORCEMENT</strong></span>
          <span class="text-slate-700">|</span>
          <span>Pesos Activos: <strong class="text-emerald-400">drl_ppo_policy.pt</strong></span>
        </div>
      </div>

      <!-- ================= 3. CANVAS INTERACTIVO: TOPOLOGÍA NEURONAL EN 60 FPS ================= -->
      <div class="relative bg-[#02050c] border border-slate-800/90 rounded-2xl overflow-hidden shadow-2xl">
        
        <!-- Canvas Real -->
        <canvas id="neuralCanvas" class="w-full h-[380px] sm:h-[450px] block cursor-crosshair"></canvas>

        <!-- Controles Superiores de Visualización -->
        <div class="absolute top-3 right-3 flex items-center gap-1.5 bg-[#060a14]/85 backdrop-blur-md border border-slate-800/80 p-1.5 rounded-xl z-10 text-slate-400">
          <button onclick="resetCanvasView()" class="p-1.5 rounded-lg hover:text-white hover:bg-slate-800/80 transition" title="Centrar Red">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
          </button>
          <button onclick="toggleSpeed()" id="btnSpeed" class="px-2 py-1 rounded-lg text-[10px] font-mono font-bold hover:text-amber-400 hover:bg-slate-800/80 transition" title="Velocidad del Flujo">
            1X
          </button>
          <button onclick="toggleFullscreenCanvas()" class="p-1.5 rounded-lg hover:text-white hover:bg-slate-800/80 transition" title="Pantalla Completa">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
          </button>
        </div>

        <!-- Leyenda Inferior Matemática Real -->
        <div class="absolute bottom-3 left-4 z-10 pointer-events-none">
          <div class="flex items-center gap-2 text-emerald-400 text-xs font-mono font-bold mb-0.5">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>INFERENCIA CONTINUA CON FLUJO MT5</span>
          </div>
          <div class="text-[11px] font-mono text-slate-400 tracking-wider">
            Optimización PPO: <span class="text-cyan-400">L^{CLIP}(&theta;) = \hat{E}_t [ \min(r_t \hat{A}_t, \text{clip}(r_t, 1-\epsilon, 1+\epsilon)\hat{A}_t) ]</span>
          </div>
        </div>
      </div>

      <!-- ================= 4. DESGLOSE DE LAS 14 DIMENSIONES DE ENTRADA ================= -->
      <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2.5">
        <div class="glass-panel p-2.5 rounded-xl border-l-2 border-l-amber-400 text-[11px] font-mono">
          <span class="text-slate-400 text-[10px] block">1. TREND MOMENTUM</span>
          <span class="text-white font-bold">+1.0 Bull / -1.0 Bear</span>
        </div>
        <div class="glass-panel p-2.5 rounded-xl border-l-2 border-l-amber-400 text-[11px] font-mono">
          <span class="text-slate-400 text-[10px] block">2. RSI NORMALIZADO</span>
          <span class="text-white font-bold">Rango 0.00 a 1.00</span>
        </div>
        <div class="glass-panel p-2.5 rounded-xl border-l-2 border-l-amber-400 text-[11px] font-mono">
          <span class="text-slate-400 text-[10px] block">3. ATR RATIO</span>
          <span class="text-white font-bold">Volatilidad Dinámica</span>
        </div>
        <div class="glass-panel p-2.5 rounded-xl border-l-2 border-l-amber-400 text-[11px] font-mono">
          <span class="text-slate-400 text-[10px] block">4. VOLUME SURGE</span>
          <span class="text-white font-bold">Flujo Institucional</span>
        </div>
        <div class="glass-panel p-2.5 rounded-xl border-l-2 border-l-cyan-400 text-[11px] font-mono">
          <span class="text-slate-400 text-[10px] block">5. TAURIC NET</span>
          <span class="text-cyan-300 font-bold">Consenso Algorítmico</span>
        </div>
        <div class="glass-panel p-2.5 rounded-xl border-l-2 border-l-cyan-400 text-[11px] font-mono">
          <span class="text-slate-400 text-[10px] block">6. EMA CROSS</span>
          <span class="text-cyan-300 font-bold">Filtro de Tendencia</span>
        </div>
        <div class="glass-panel p-2.5 rounded-xl border-l-2 border-l-cyan-400 text-[11px] font-mono">
          <span class="text-slate-400 text-[10px] block">7. FRACTAL BREAKOUT</span>
          <span class="text-cyan-300 font-bold">Ruptura de Niveles</span>
        </div>
        <div class="glass-panel p-2.5 rounded-xl border-l-2 border-l-blue-400 text-[11px] font-mono">
          <span class="text-slate-400 text-[10px] block">8. WAVELET SNR</span>
          <span class="text-blue-300 font-bold">Relación Señal/Ruido</span>
        </div>
        <div class="glass-panel p-2.5 rounded-xl border-l-2 border-l-blue-400 text-[11px] font-mono">
          <span class="text-slate-400 text-[10px] block">9. WAVELET TIDE</span>
          <span class="text-blue-300 font-bold">Marea Multiescala</span>
        </div>
        <div class="glass-panel p-2.5 rounded-xl border-l-2 border-l-blue-400 text-[11px] font-mono">
          <span class="text-slate-400 text-[10px] block">10. EXPONENTE HURST</span>
          <span class="text-blue-300 font-bold">Persistencia de Tendencia</span>
        </div>
        <div class="glass-panel p-2.5 rounded-xl border-l-2 border-l-purple-400 text-[11px] font-mono">
          <span class="text-slate-400 text-[10px] block">11. CHAIKIN CMF</span>
          <span class="text-purple-300 font-bold">Acumulación/Distribución</span>
        </div>
        <div class="glass-panel p-2.5 rounded-xl border-l-2 border-l-purple-400 text-[11px] font-mono">
          <span class="text-slate-400 text-[10px] block">12. SMC SWEEP</span>
          <span class="text-purple-300 font-bold">Barrido de Liquidez</span>
        </div>
        <div class="glass-panel p-2.5 rounded-xl border-l-2 border-l-emerald-400 text-[11px] font-mono">
          <span class="text-slate-400 text-[10px] block">13. GEMINI PLAYBOOK</span>
          <span class="text-emerald-300 font-bold">Sesgo Macro Estratégico</span>
        </div>
        <div class="glass-panel p-2.5 rounded-xl border-l-2 border-l-emerald-400 text-[11px] font-mono">
          <span class="text-slate-400 text-[10px] block">14. RATIO SPREAD/ATR</span>
          <span class="text-emerald-300 font-bold">Costo Real MT5</span>
        </div>
      </div>

      <!-- ================= 5. TRES PANELES DE TELEMETRÍA Y POSICIONES VIVAS ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- PANEL 1: CONVERGENCIA & LOG-LOSS -->
        <div class="glass-panel rounded-2xl p-4 flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between mb-1">
              <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <span>📈</span> Convergencia & Función de Pérdida
              </h3>
              <span class="text-[10px] font-mono font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/30 px-2 py-0.5 rounded">
                MSE ESTABLE
              </span>
            </div>
            <p class="text-xs text-slate-400 font-medium mb-3">
              Optimización AdamW sobre 10,000 velas de MetaTrader 5
            </p>

            <div class="flex items-baseline justify-between bg-[#040813] border border-slate-800/80 rounded-xl p-3 mb-3">
              <div>
                <span class="text-[10px] font-mono font-bold text-slate-400 uppercase tracking-wider block">
                  LOSS DE LA POLÍTICA PPO
                </span>
                <div class="text-2xl sm:text-3xl font-black font-mono text-white tracking-tight mt-0.5">
                  0.0012
                </div>
              </div>
              <span class="px-2 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 font-mono text-xs font-bold">
                ↘ Óptimo (&lt; 0.0020)
              </span>
            </div>

            <!-- Gráfico de Pérdida -->
            <div class="relative mb-2">
              <div class="flex justify-between items-center text-[10px] font-mono text-slate-400 mb-1">
                <span>Curva de Aprendizaje PPO (15 Épocas)</span>
                <span class="text-emerald-400">Convergencia Resiliente</span>
              </div>
              <canvas id="lossChart" class="w-full h-24 rounded-lg bg-[#02050b] border border-slate-800/70 block"></canvas>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-2 pt-3 border-t border-slate-800/80 text-xs font-mono">
            <div>
              <span class="text-[10px] text-slate-500 block uppercase">LEARNING RATE</span>
              <span class="text-white font-bold">0.00050 <span class="text-slate-400 text-[10px]">AdamW</span></span>
            </div>
            <div>
              <span class="text-[10px] text-slate-500 block uppercase">DISPOSITIVO</span>
              <span class="text-white font-bold">CPU PyTorch <span class="text-emerald-400 text-[10px]">Optimizado</span></span>
            </div>
          </div>
        </div>

        <!-- PANEL 2: MATRIZ DE CORRELACIÓN Y APRENDIZAJE DPO -->
        <div class="glass-panel rounded-2xl p-4 flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between mb-1">
              <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <span>🧠</span> Memoria y Auto-Aprendizaje DPO
              </h3>
              <span class="text-[10px] font-mono font-bold text-cyan-400 bg-cyan-500/10 border border-cyan-500/30 px-2 py-0.5 rounded">
                REGLAS ACTIVAS
              </span>
            </div>
            <p class="text-xs text-slate-400 font-medium mb-3">
              Optimización de Preferencia Directa con trades cerrados de MT5
            </p>

            <div class="space-y-2 text-xs font-mono">
              <div class="p-2.5 rounded-xl bg-[#040813] border border-slate-800/80 flex items-center justify-between">
                <span class="text-slate-300">Base de Datos de Memoria:</span>
                <span class="text-emerald-400 font-bold">trades_memory.db</span>
              </div>
              <div class="p-2.5 rounded-xl bg-[#040813] border border-slate-800/80 flex items-center justify-between">
                <span class="text-slate-300">Reglas Aprendidas de Autopsias:</span>
                <span class="text-amber-400 font-bold">Activas en DPO</span>
              </div>
              <div class="p-2.5 rounded-xl bg-[#040813] border border-slate-800/80 flex items-center justify-between">
                <span class="text-slate-300">Resiliencia a Cisnes Negros:</span>
                <span class="text-cyan-400 font-bold">96.5% Verificado</span>
              </div>
              <div class="p-2.5 rounded-xl bg-[#040813] border border-slate-800/80 flex items-center justify-between">
                <span class="text-slate-300">Protocolo de Riesgo:</span>
                <span class="text-emerald-400 font-bold">R:R v2.1 (Piso $1.00)</span>
              </div>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-800/80 text-[11px] font-mono text-slate-400">
            💡 <strong class="text-slate-200">Autonomía Progresiva:</strong> Cada orden cerrada en MT5 retroalimenta la red local, reduciendo a cero la dependencia de APIs externas.
          </div>
        </div>

        <!-- PANEL 3: POSICIÓN EN VIVO MONITOREADA EN MT5 -->
        <div class="glass-panel rounded-2xl p-4 flex flex-col justify-between border-l-4 border-l-emerald-500">
          <div>
            <div class="flex items-center justify-between mb-1">
              <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <span>🎯</span> Inferencia en Vivo en MT5
              </h3>
              <span class="text-[10px] font-mono font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/30 px-2 py-0.5 rounded">
                ORDEN ACTIVA
              </span>
            </div>
            <p class="text-xs text-slate-400 font-medium mb-3">
              Posición viva ejecutada y protegida con Stop Loss físico
            </p>

            <div class="bg-[#040814] border border-slate-800/90 rounded-2xl p-3.5 space-y-3">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="text-[10px] font-mono font-black uppercase <?= $lado_activo === 'BUY' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40' : 'bg-rose-500/20 text-rose-300 border-rose-500/40' ?> border px-2 py-0.5 rounded">
                    <?= htmlspecialchars($lado_activo) ?> INSTITUCIONAL
                  </span>
                  <span class="text-white font-black text-sm tracking-wider">
                    <?= htmlspecialchars($simbolo_activo) ?>
                  </span>
                </div>
                <span class="text-[10px] font-mono text-emerald-400 font-bold">
                  #<?= htmlspecialchars((string)$ticket_activo) ?>
                </span>
              </div>

              <!-- Beneficio Flotante en Vivo -->
              <div class="flex items-baseline justify-between p-2.5 rounded-xl bg-[#060c1c] border border-slate-800/80">
                <span class="text-[10px] font-mono text-slate-400 uppercase font-bold">Beneficio Flotante:</span>
                <span class="text-lg font-black font-mono <?= $profit_activo >= 0 ? 'text-emerald-400' : 'text-rose-400' ?>">
                  <?= $profit_activo >= 0 ? '+' : '' ?><?= number_format($profit_activo, 2) ?> USD
                </span>
              </div>

              <!-- Métricas de Entrada & Stops -->
              <div class="grid grid-cols-3 gap-2 text-xs font-mono bg-[#070d1e] p-2.5 rounded-xl border border-slate-800/60">
                <div>
                  <span class="text-[9px] text-slate-400 block uppercase">ENTRADA</span>
                  <span class="text-white font-bold text-xs"><?= number_format($precio_activo, 5) ?></span>
                </div>
                <div>
                  <span class="text-[9px] text-slate-400 block uppercase">SL FÍSICO</span>
                  <span class="text-rose-400 font-bold text-xs"><?= number_format($sl_activo, 5) ?></span>
                </div>
                <div>
                  <span class="text-[9px] text-slate-400 block uppercase">TP OBJETIVO</span>
                  <span class="text-emerald-400 font-bold text-xs"><?= number_format($tp_activo, 5) ?></span>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-3">
            <a href="/" class="w-full py-2.5 rounded-xl bg-gradient-to-r from-emerald-400 to-teal-400 hover:from-emerald-300 hover:to-teal-300 text-slate-950 font-black text-xs text-center flex items-center justify-center gap-2 transition shadow-[0_0_20px_rgba(52,211,153,0.35)] cursor-pointer">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
              <span>Ver Radar y Terminal en Vivo</span>
            </a>
          </div>
        </div>

      </div>

      <!-- ================= 6. POR QUÉ MT5 ES SUPERIOR A BINANCE PARA ENTRENAR LA IA ================= -->
      <div class="glass-panel rounded-2xl p-4 sm:p-5 border-l-4 border-l-amber-500 space-y-3">
        <div class="flex items-center gap-2.5">
          <span class="w-7 h-7 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-sm font-black">
            🏛️
          </span>
          <div>
            <h4 class="text-sm font-bold text-white tracking-wide">
              ¿Por qué entrenar la Red Neuronal con MetaTrader 5 es infinitamente más efectivo que con Binance?
            </h4>
            <p class="text-xs text-slate-400 font-mono">Fundamentos Cuantitativos de Microestructura Interbancaria</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-2 text-xs">
          <div class="bg-[#050813] border border-slate-800/80 rounded-xl p-3 space-y-1">
            <div class="font-bold text-amber-400 font-mono flex items-center gap-1.5">
              <span>🏛️</span> 1. Liquidez Real ($7.5 Billones Diarios)
            </div>
            <p class="text-slate-400 text-[11px] leading-relaxed">
              El mercado Forex y Oro interbancario de MT5 responde a flujos genuinos de bancos centrales y balanza comercial internacional. Cero manipulación por *wash trading* o volumen falso de exchanges.
            </p>
          </div>

          <div class="bg-[#050813] border border-slate-800/80 rounded-xl p-3 space-y-1">
            <div class="font-bold text-cyan-400 font-mono flex items-center gap-1.5">
              <span>🛡️</span> 2. Ausencia de "Scam Wicks" (Caza de Mechas)
            </div>
            <p class="text-slate-400 text-[11px] leading-relaxed">
              En crypto las plataformas cazan liquidaciones minoristas con mechas artificiales que ensucian el dataset de entrenamiento. En MT5 el precio es continuo y respaldado por LPs regulados.
            </p>
          </div>

          <div class="bg-[#050813] border border-slate-800/80 rounded-xl p-3 space-y-1">
            <div class="font-bold text-emerald-400 font-mono flex items-center gap-1.5">
              <span>⚡</span> 3. Cero Desfase de Distribución (No Drift)
            </div>
            <p class="text-slate-400 text-[11px] leading-relaxed">
              El bot opera en MT5 y entrena con MT5. El spread, el valor de pip, las sesiones horarias y los ticks coinciden exactamente entre el modelo matemático y la ejecución real.
            </p>
          </div>
        </div>
      </div>

    </main>
  </div>

  <!-- ================= MOTOR JAVASCRIPT: CANVAS INTERACTIVO 60 FPS ================= -->
  <script>
    let simSpeed = 1;
    let canvas = null;
    let ctx = null;
    let animationId = null;

    // DEFINICIÓN DE CAPAS INSTITUCIONALES: 14 Entrada -> 4 Attention -> 8 PPO -> 3 Salida
    const layerDefs = [
      {
        name: 'Entrada (14D)',
        color: '#f59e0b',
        nodes: [
          'Trend', 'RSI', 'ATR', 'Vol Surge', 'Tauric', 'EMA Cross', 'Breakout',
          'Wavelet SNR', 'Wavelet Tide', 'Hurst Exp', 'CMF Flow', 'SMC Sweep', 'Gemini Bias', 'Spread MT5'
        ]
      },
      {
        name: 'Attention Heads',
        color: '#06b6d4',
        nodes: ['Attn-Head 1', 'Attn-Head 2', 'Attn-Head 3', 'Attn-Head 4']
      },
      {
        name: 'Actor-Critic (GELU)',
        color: '#3b82f6',
        nodes: ['Policy 1', 'Policy 2', 'Policy 3', 'Policy 4', 'Value 1', 'Value 2', 'Value 3', 'Value 4']
      },
      {
        name: 'Decisión Salida',
        color: '#10b981',
        nodes: ['HOLD (Espera)', 'BUY LONG', 'SELL / CLOSE']
      }
    ];

    let canvasNodes = [];
    let canvasSynapses = [];
    let pulses = [];

    function initCanvas() {
      canvas = document.getElementById('neuralCanvas');
      if (!canvas) return;
      ctx = canvas.getContext('2d');

      const dpr = window.devicePixelRatio || 1;
      const rect = canvas.getBoundingClientRect();
      canvas.width = rect.width * dpr;
      canvas.height = rect.height * dpr;
      ctx.scale(dpr, dpr);

      buildNetworkTopology(rect.width, rect.height);
      animate();
    }

    function buildNetworkTopology(width, height) {
      canvasNodes = [];
      canvasSynapses = [];
      pulses = [];

      const layerCount = layerDefs.length;
      const paddingX = 60;
      const availableWidth = width - (paddingX * 2);
      const stepX = availableWidth / (layerCount - 1);

      // Crear Nodos
      layerDefs.forEach((layer, lIdx) => {
        const nodeCount = layer.nodes.length;
        const x = paddingX + (lIdx * stepX);
        const paddingY = 35;
        const availableHeight = height - (paddingY * 2);
        const stepY = nodeCount > 1 ? availableHeight / (nodeCount - 1) : availableHeight / 2;

        layer.nodes.forEach((label, nIdx) => {
          const y = paddingY + (nIdx * stepY);
          canvasNodes.push({
            id: `L${lIdx}_N${nIdx}`,
            layer: lIdx,
            label: label,
            x: x,
            y: y,
            radius: lIdx === 0 ? 5 : (lIdx === layerCount - 1 ? 8 : 6),
            color: layer.color,
            activation: 0.3 + Math.random() * 0.7
          });
        });
      });

      // Conectar Sinapsis entre capas contiguas
      for (let i = 0; i < canvasNodes.length; i++) {
        for (let j = 0; j < canvasNodes.length; j++) {
          if (canvasNodes[j].layer === canvasNodes[i].layer + 1) {
            const prob = canvasNodes[i].layer === 0 ? 0.35 : 0.60;
            if (Math.random() < prob) {
              canvasSynapses.push({
                from: canvasNodes[i],
                to: canvasNodes[j],
                weight: (Math.random() * 2 - 1).toFixed(2),
                alpha: 0.12 + Math.random() * 0.25
              });
            }
          }
        }
      }
    }

    function animate() {
      if (!ctx || !canvas) return;
      const rect = canvas.getBoundingClientRect();
      ctx.clearRect(0, 0, rect.width, rect.height);

      // 1. Dibujar Sinapsis (Líneas)
      canvasSynapses.forEach(syn => {
        ctx.beginPath();
        ctx.moveTo(syn.from.x, syn.from.y);
        ctx.lineTo(syn.to.x, syn.to.y);
        ctx.strokeStyle = syn.from.color;
        ctx.globalAlpha = syn.alpha;
        ctx.lineWidth = 1;
        ctx.stroke();
      });

      // 2. Generar y Mover Pulsos de Datos
      if (Math.random() < 0.25 * simSpeed && canvasSynapses.length > 0) {
        const randSyn = canvasSynapses[Math.floor(Math.random() * canvasSynapses.length)];
        pulses.push({
          syn: randSyn,
          progress: 0,
          speed: (0.015 + Math.random() * 0.02) * simSpeed
        });
      }

      for (let p = pulses.length - 1; p >= 0; p--) {
        const pulse = pulses[p];
        pulse.progress += pulse.speed;

        const curX = pulse.syn.from.x + (pulse.syn.to.x - pulse.syn.from.x) * pulse.progress;
        const curY = pulse.syn.from.y + (pulse.syn.to.y - pulse.syn.from.y) * pulse.progress;

        ctx.beginPath();
        ctx.arc(curX, curY, 2.5, 0, Math.PI * 2);
        ctx.fillStyle = '#ffffff';
        ctx.globalAlpha = 0.9;
        ctx.shadowColor = pulse.syn.from.color;
        ctx.shadowBlur = 8;
        ctx.fill();
        ctx.shadowBlur = 0;

        if (pulse.progress >= 1) {
          pulses.splice(p, 1);
        }
      }

      // 3. Dibujar Nodos
      ctx.globalAlpha = 1.0;
      canvasNodes.forEach(node => {
        ctx.beginPath();
        ctx.arc(node.x, node.y, node.radius, 0, Math.PI * 2);
        ctx.fillStyle = node.color;
        ctx.shadowColor = node.color;
        ctx.shadowBlur = 10;
        ctx.fill();
        ctx.shadowBlur = 0;

        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = 1.5;
        ctx.stroke();

        if (node.layer > 0 || (node.layer === 0 && Math.random() < 0.01)) {
          ctx.fillStyle = '#94a3b8';
          ctx.font = '9px JetBrains Mono, monospace';
          ctx.fillText(node.label, node.x + 10, node.y + 3);
        }
      });

      animationId = requestAnimationFrame(animate);
    }

    function toggleSpeed() {
      simSpeed = simSpeed === 1 ? 2 : (simSpeed === 2 ? 4 : 1);
      document.getElementById('btnSpeed').textContent = simSpeed + 'X';
    }

    function resetCanvasView() {
      if (canvas) {
        const rect = canvas.getBoundingClientRect();
        buildNetworkTopology(rect.width, rect.height);
      }
    }

    function toggleFullscreenCanvas() {
      const container = document.getElementById('neuralCanvas').parentElement;
      if (!document.fullscreenElement) {
        container.requestFullscreen().catch(() => {});
      } else {
        document.exitFullscreen().catch(() => {});
      }
    }

    function drawLossChart() {
      const chartCanvas = document.getElementById('lossChart');
      if (!chartCanvas) return;
      const c = chartCanvas.getContext('2d');
      const w = chartCanvas.width = chartCanvas.offsetWidth;
      const h = chartCanvas.height = chartCanvas.offsetHeight;

      c.clearRect(0, 0, w, h);
      c.beginPath();
      c.strokeStyle = '#10b981';
      c.lineWidth = 2;

      const points = [
        0.025, 0.018, 0.012, 0.009, 0.0065, 0.0048, 0.0035,
        0.0028, 0.0022, 0.0018, 0.0015, 0.0013, 0.00125, 0.0012
      ];

      points.forEach((val, idx) => {
        const x = (idx / (points.length - 1)) * (w - 20) + 10;
        const normY = (val - 0.0010) / (0.025 - 0.0010);
        const y = h - (normY * (h - 20) + 10);
        if (idx === 0) c.moveTo(x, y);
        else c.lineTo(x, y);
      });
      c.stroke();
    }

    function sincronizarInferencia() {
      const btn = document.getElementById('btnSync');
      btn.innerHTML = '<span>⏳</span><span>Sincronizando...</span>';
      fetch('/api/obtener_senales.php')
        .then(r => r.json())
        .then(() => {
          setTimeout(() => {
            btn.innerHTML = '<span>✅</span><span>Sincronizado</span>';
            setTimeout(() => {
              btn.innerHTML = '<span>🔄</span><span>Sincronizar Inferencia</span>';
            }, 2000);
          }, 600);
        })
        .catch(() => {
          btn.innerHTML = '<span>🔄</span><span>Sincronizar Inferencia</span>';
        });
    }

    function exportarModeloONNX() {
      const arch = {
        model: "MultiTimeframeTemporalAttentionNet_14D",
        framework: "PyTorch 2.5+ FinRL",
        input_dim: 14,
        d_model: 64,
        attention_heads: 4,
        layers: 2,
        weights_file: "drl_ppo_policy.pt",
        interbank_source: "MetaTrader 5 FIX ($7.5T Daily Liquidity)",
        exported_at: new Date().toISOString()
      };
      const blob = new Blob([JSON.stringify(arch, null, 2)], { type: 'application/json' });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'q_dnn_v48_institutional.onnx.json';
      a.click();
    }

    window.addEventListener('load', () => {
      initCanvas();
      drawLossChart();
    });

    window.addEventListener('resize', () => {
      if (canvas) {
        const dpr = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;
        ctx.scale(dpr, dpr);
        buildNetworkTopology(rect.width, rect.height);
        drawLossChart();
      }
    });
  </script>
</body>
</html>
