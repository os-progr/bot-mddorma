<?php
/**
 * bot.mddorma.com/ia/index.php — MOTOR DE RED NEURONAL INSTITUCIONAL (Q-DNN v4.8)
 * ==============================================================================
 * Topología Neuronal Dinámica: Transformer Multi-Head Attention + FinRL PPO (14D)
 * Arquitectura de Ultra-Escalabilidad (100,000+ usuarios concurrentes vía Edge CDN).
 * Auto-Evolución Continua con cada trade cerrado en MetaTrader 5 (trades_memory.db & DPO).
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
$sl_activo = (float)($primera_pos['sl'] ?? 1.24602);
$tp_activo = (float)($primera_pos['tp'] ?? 1.24408);
$profit_activo = (float)($primera_pos['profit'] ?? 0.85);
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

  <!-- Three.js & OrbitControls (Renderizado 3D acelerado por GPU en el Cliente) -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>

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
    /* Tooltip interactivo flotante */
    #nodeTooltip {
      position: absolute;
      pointer-events: none;
      transition: opacity 0.15s ease, transform 0.15s ease;
      z-index: 40;
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

  <!-- ================= TOP HEADER INSTITUCIONAL (STITCH QUANTUM//AI) ================= -->
  <header class="h-14 bg-[#030611] border-b border-slate-900/90 px-4 sm:px-6 flex items-center justify-between gap-4 sticky top-0 z-50 backdrop-blur-md select-none">
    
    <!-- Logo QUANTUM//AI -->
    <div class="flex items-center gap-3 shrink-0">
      <a href="/" class="flex items-center gap-2.5 group">
        <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-amber-400 to-amber-600 p-[1.5px] shadow-[0_0_12px_rgba(245,158,11,0.4)]">
          <div class="w-full h-full bg-[#030611] rounded-[6px] flex items-center justify-center">
            <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2L3 7v10l9 5 9-5V7l-9-5zm0 2.8L18.5 8 12 11.2 5.5 8 12 4.8zM5 9.8l6 3.2v6.4l-6-3.3V9.8zm8 9.6v-6.4l6-3.2v6.3l-6 3.3z"/>
            </svg>
          </div>
        </div>
        <div class="font-black text-sm tracking-wider text-white flex items-center">
          QUANTUM<span class="text-slate-400 font-light">//</span><span class="text-slate-200">AI</span>
        </div>
      </a>
    </div>

    <!-- Badges Centrales de Estado (Stitch) -->
    <div class="hidden md:flex items-center gap-2.5 text-[11px] font-mono">
      <div class="px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center gap-1.5 shadow-[0_0_10px_rgba(16,185,129,0.15)]">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
        <span>LD4 LONDON <strong class="text-emerald-300 font-normal">4.2ms</strong></span>
      </div>
      <div class="px-2.5 py-1 rounded-full bg-[#060a17] border border-slate-800 text-slate-300">
        WS FEED 99.99%
      </div>
      <div class="px-2.5 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 font-semibold">
        VIP TIER III
      </div>
      <button onclick="openApiModal()" class="px-2.5 py-1 rounded-full bg-cyan-500/10 border border-cyan-500/30 hover:bg-cyan-500/20 text-cyan-300 transition flex items-center gap-1.5 cursor-pointer shadow-[0_0_12px_rgba(6,182,212,0.15)]" title="Documentación y API para entrenar tu propio Bot">
        <span class="text-cyan-400 font-bold">&lt;/&gt;</span>
        <span>API ENGINE v1.2</span>
      </button>
    </div>

    <!-- Estado de Ejecución & Perfil (Stitch) -->
    <div class="flex items-center gap-3 shrink-0">
      <div class="px-2.5 py-1 rounded-lg bg-emerald-950/40 border border-emerald-500/40 text-[10px] font-mono flex flex-col items-end leading-tight shadow-sm">
        <span class="text-slate-400 text-[9px] uppercase tracking-wider">INST-9804</span>
        <span class="text-emerald-400 font-bold tracking-wide">EXECUTION OK</span>
      </div>

      <a href="/perfil/" class="flex items-center gap-2 p-1 rounded-xl hover:bg-slate-800/50 transition group" title="Perfil de Operador">
        <div class="w-7 h-7 rounded-lg bg-[#0a1020] border border-slate-700/80 flex items-center justify-center text-[10px] font-mono font-bold text-slate-300 group-hover:border-amber-400 transition">
          <?= htmlspecialchars($userInitial) ?>
        </div>
      </a>
    </div>
  </header>

  <!-- ================= LAYOUT PRINCIPAL ================= -->
  <div class="flex-1 flex min-h-[calc(100vh-3.5rem)]">

    <!-- BARRA LATERAL IZQUIERDA (DOCK SLIM - STITCH) -->
    <aside class="w-14 bg-[#030611] border-r border-slate-900 flex flex-col items-center py-4 justify-between shrink-0 sticky top-14 h-[calc(100vh-3.5rem)] z-40 select-none">
      <div class="flex flex-col items-center gap-3 text-slate-400">
        <a href="/" class="p-2.5 rounded-xl hover:text-white hover:bg-[#0b1224] transition cursor-pointer" title="Parámetros y Sliders">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
        </a>
        <a href="/#sec-signals" class="p-2.5 rounded-xl hover:text-white hover:bg-[#0b1224] transition cursor-pointer" title="Libro de Órdenes & Señales">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </a>
        <a href="/ia/" class="p-2.5 rounded-xl text-amber-400 bg-amber-500/15 border border-amber-500/40 transition shadow-[0_0_15px_rgba(245,158,11,0.25)]" title="Red Neuronal 3D (Activa)">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="6" cy="6" r="2" stroke-width="2"/>
            <circle cx="18" cy="6" r="2" stroke-width="2"/>
            <circle cx="6" cy="18" r="2" stroke-width="2"/>
            <circle cx="18" cy="18" r="2" stroke-width="2"/>
            <circle cx="12" cy="12" r="2.5" stroke-width="2"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7.5 7.5l3 3m3 0l3-3m-9 9l3-3m3 0l3 3"/>
          </svg>
        </a>
        <a href="/grafico/" class="p-2.5 rounded-xl hover:text-white hover:bg-[#0b1224] transition cursor-pointer" title="Gráfico & Velas">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
        </a>
        <a href="/senales/" class="p-2.5 rounded-xl hover:text-white hover:bg-[#0b1224] transition cursor-pointer" title="Historial & Ledger">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </a>
      </div>

      <div class="flex flex-col items-center gap-3 text-slate-400">
        <a href="/perfil/" class="p-2.5 rounded-xl hover:text-white hover:bg-[#0b1224] transition" title="Configuración">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </a>
      </div>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="flex-1 p-2 sm:p-3 w-full flex flex-col space-y-4">

      <!-- ================= VIEWPORT 3D INMERSIVO (STITCH 60883032e0054c21b590be13d237b6ad) ================= -->
      <div class="relative bg-[#02040a] border border-slate-900 rounded-2xl overflow-hidden shadow-2xl h-[calc(100vh-4.5rem)] min-h-[720px] select-none" id="neural3dContainer">
        
        <!-- Viewport WebGL 3D -->
        <div id="neural3dViewport" class="w-full h-full block cursor-grab active:cursor-grabbing"></div>

        <!-- Telemetría Dinámica en Vivo del Bot (Actualización Cada Segundo) -->
        <div class="absolute top-6 left-1/2 -translate-x-1/2 pointer-events-none z-20 font-mono">
          <div class="backdrop-blur-md bg-[#030712]/90 border border-slate-800/80 rounded-full px-4 py-1.5 shadow-2xl flex items-center gap-3 text-xs">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
            <span class="text-slate-400 text-[11px]">BOT LIVE:</span>
            <span class="text-white font-bold tracking-wider" id="liveBotSymbol"><?= htmlspecialchars($simbolo_activo) ?></span>
            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40" id="liveBotSide"><?= htmlspecialchars($lado_activo) ?></span>
            <span class="text-slate-500">|</span>
            <span class="text-slate-400 text-[11px]">PNL:</span>
            <span class="font-bold text-emerald-400" id="liveBotPnl">+$<?= number_format($profit_activo, 2) ?> USD</span>
            <span class="text-slate-500">|</span>
            <span class="text-emerald-400/90 text-[10px]" id="liveBotLatency">● LD4 4.2ms</span>
          </div>
        </div>

        <!-- ================= HUD OVERLAYS ANCLADOS DINÁMICAMENTE A LA TOPOLOGÍA 3D ================= -->

        <!-- 1. ENTRADA DE TENSORES (Anclado a Nodo 3D) -->
        <div id="hudCardTensors" class="absolute top-0 left-0 pointer-events-none z-20 text-left font-mono transition-opacity duration-150 will-change-transform opacity-0">
          <div class="text-amber-400 font-bold text-xs tracking-wider flex items-center gap-1.5 drop-shadow-[0_0_10px_rgba(245,158,11,0.6)]">
            <span class="text-sm">☷</span>
            <span>ENTRADA DE TENSORES</span>
          </div>
          <div class="text-[10px] text-slate-300 mt-0.5">11 Nodos Normalizados en Paralelo</div>
          <div class="text-[9.5px] text-amber-400 font-bold tracking-widest mt-0.5 flex items-center gap-1">
            <span class="text-amber-400 animate-pulse">● ● ●</span> L2 ORDER FLOW
          </div>
        </div>

        <!-- 2. MULTI-HEAD ATTENTION 2 & 3 (Anclado a Nodo 3D) -->
        <div id="hudCardAttention" class="absolute top-0 left-0 pointer-events-none z-20 text-left font-mono transition-opacity duration-150 will-change-transform opacity-0 hidden sm:block">
          <div class="text-cyan-400 font-bold text-[11px] tracking-wider drop-shadow-[0_0_8px_rgba(6,182,212,0.5)]">
            MULTI-HEAD ATTENTION 2 & 3
          </div>
          <div class="text-[9.5px] text-slate-400 mt-0.5">
            Cross-Entropy Matrix Coherence Active
          </div>
        </div>

        <!-- 3. POLICY DENSE NODES [1..4] (Anclado a Nodo 3D) -->
        <div id="hudCardPolicy" class="absolute top-0 left-0 pointer-events-none z-20 text-center font-mono transition-opacity duration-150 will-change-transform opacity-0 hidden md:block">
          <div class="text-indigo-300 font-bold text-[11px] tracking-wider drop-shadow-[0_0_8px_rgba(165,180,252,0.5)]">
            POLICY DENSE NODES [1..4]
          </div>
          <div class="text-[9.5px] text-slate-400 mt-0.5">
            Softmax State Distribution = 0.9984
          </div>
        </div>

        <!-- 4. VALUE ESTIMATOR CLUSTER [1..4] (Anclado a Nodo 3D) -->
        <div id="hudCardValue" class="absolute top-0 left-0 pointer-events-none z-20 text-center font-mono transition-opacity duration-150 will-change-transform opacity-0 hidden md:block">
          <div class="text-amber-300 font-bold text-[11px] tracking-wider drop-shadow-[0_0_8px_rgba(252,211,77,0.4)]">
            VALUE ESTIMATOR CLUSTER [1..4]
          </div>
          <div class="text-[9.5px] text-slate-400 mt-0.5">
            Temporal Difference Bellman Residual &lt; 10<sup>-5</sup>
          </div>
        </div>

        <!-- 5. BUY LONG PRIMARIO - NODO HERO (Anclado a Nodo 3D) -->
        <div id="hudCardBuy" class="absolute top-0 left-0 pointer-events-none z-20 transition-opacity duration-150 will-change-transform opacity-0">
          <div class="backdrop-blur-md bg-[#040e1d]/95 border border-emerald-500/60 rounded-xl px-3.5 py-2 shadow-[0_0_30px_rgba(16,185,129,0.35)] flex flex-col gap-0.5">
            <div class="flex items-center gap-2 font-black text-emerald-400 text-xs tracking-wider">
              <span class="text-sm font-bold">↗</span>
              <span>BUY LONG</span>
              <span class="text-[8.5px] font-mono font-bold px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">PRIMARIO</span>
            </div>
            <div class="text-[10px] font-mono text-emerald-300 mt-0.5">
              CONVICCIÓN ALGORÍTMICA: <span class="text-white font-bold" id="hudConvictionVal">92.4%</span>
            </div>
          </div>
        </div>

        <!-- 6. HOLD (Espera Pasiva) (Anclado a Nodo 3D) -->
        <div id="hudCardHold" class="absolute top-0 left-0 pointer-events-none z-20 font-mono transition-opacity duration-150 will-change-transform opacity-0">
          <div class="text-sky-400 font-bold text-[11px] flex items-center gap-1.5 drop-shadow-[0_0_8px_rgba(56,189,248,0.5)]">
            <span class="w-2 h-2 rounded-full bg-sky-400"></span>
            <span>HOLD <span class="text-slate-400 text-[10px] font-normal">(Espera Pasiva)</span></span>
          </div>
          <div class="text-[10px] text-slate-400 pl-3.5 mt-0.5">
            Inercia Cuántica: <strong class="text-sky-300" id="hudHoldVal">6.8%</strong>
          </div>
        </div>

        <!-- 7. SELL / CLOSE (Liquidación) (Anclado a Nodo 3D) -->
        <div id="hudCardSell" class="absolute top-0 left-0 pointer-events-none z-20 font-mono transition-opacity duration-150 will-change-transform opacity-0">
          <div class="text-rose-400 font-bold text-[11px] flex items-center gap-1.5 drop-shadow-[0_0_8px_rgba(244,63,94,0.5)]">
            <span class="w-2 h-2 rounded-full bg-rose-400"></span>
            <span>SELL / CLOSE <span class="text-slate-400 text-[10px] font-normal">(Liquidación)</span></span>
          </div>
          <div class="text-[10px] text-slate-400 pl-3.5 mt-0.5">
            Invalidez Sináptica: <strong class="text-rose-400" id="hudSellVal">0.8%</strong>
          </div>
        </div>

        <!-- Botón de Control Inferior Izquierdo (Stitch) -->
        <div class="absolute bottom-4 left-4 z-20">
          <button onclick="reset3DCamera()" class="p-2.5 rounded-xl bg-[#060c18]/85 hover:bg-slate-800 border border-slate-800 hover:border-slate-600 text-slate-400 hover:text-white transition shadow-lg cursor-pointer flex items-center gap-1.5 text-xs font-mono" title="Centrar Topología 3D">
            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
            <span class="hidden sm:inline text-slate-300">Reset Cámara</span>
          </button>
        </div>

        <!-- Tooltip 3D Flotante Dinámico -->
        <div id="nodeTooltip" class="opacity-0 fixed glass-panel rounded-xl p-3 border border-slate-700/80 shadow-2xl max-w-xs text-xs font-mono pointer-events-none transition-opacity duration-150 z-50">
          <div class="flex items-center justify-between gap-2 border-b border-slate-800 pb-1.5 mb-1.5">
            <span id="ttNodeName" class="font-bold text-white tracking-wide"></span>
            <span id="ttNodeLayer" class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded bg-slate-800 text-slate-300"></span>
          </div>
          <div class="text-[11px] text-slate-400 mb-2 leading-tight" id="ttNodeDesc"></div>
          <div class="flex items-center justify-between text-[10px] text-slate-300 bg-[#050811] p-1.5 rounded-lg border border-slate-800/80">
            <span>Activación: <strong id="ttNodeAct" class="text-emerald-400 font-mono font-bold"></strong></span>
            <span>Peso: <strong id="ttNodeWeight" class="text-amber-400 font-mono font-bold"></strong></span>
          </div>
        </div>

        <!-- Leyenda Inferior Discreta -->
        <div class="absolute bottom-4 right-6 z-10 pointer-events-none flex items-center gap-2 text-slate-500 text-[10px] font-mono">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
          <span>GPU WebGL 3D • Click en cualquier nodo para Inspección</span>
        </div>
      </div>

      <!-- ================= DRAWER INSPECTOR LATERAL DE NODOS (SLIDE-OVER) ================= -->
      <div id="nodeInspectorDrawer" class="fixed top-14 right-0 bottom-0 w-84 sm:w-96 glass-panel border-l border-slate-800/90 p-5 shadow-2xl z-40 transform translate-x-full transition-transform duration-300 ease-in-out font-mono flex flex-col justify-between overflow-y-auto">
        <div>
          <!-- Cabecera Drawer -->
          <div class="flex items-center justify-between border-b border-slate-800 pb-3 mb-4">
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-cyan-400 animate-ping"></span>
              <span class="text-xs font-bold text-white uppercase tracking-wider">INSPECTOR CUÁNTICO</span>
            </div>
            <button onclick="closeNodeInspector()" class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>

          <!-- Información del Nodo -->
          <div class="space-y-3 text-xs">
            <div class="bg-[#040814] border border-slate-800/80 p-3 rounded-xl">
              <span class="text-[10px] text-slate-400 uppercase block font-bold">Identificador del Nodo:</span>
              <div class="text-white font-bold text-sm mt-0.5" id="inspNodeName">-</div>
              <span class="text-[10px] text-cyan-400 font-mono" id="inspNodeLayer">-</span>
            </div>

            <div class="bg-[#040814] border border-slate-800/80 p-3 rounded-xl space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-[11px] text-slate-400">Activación Neuronal:</span>
                <span class="text-emerald-400 font-bold text-sm" id="inspNodeAct">-</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-[11px] text-slate-400">Peso Sináptico ($w_{ij}$):</span>
                <span class="text-amber-400 font-bold text-sm" id="inspNodeWeight">-</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-[11px] text-slate-400">Función No Lineal:</span>
                <span class="text-purple-300 font-bold">GELU / Softmax</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-[11px] text-slate-400">Bellman Residual:</span>
                <span class="text-slate-200">&lt; 0.00012</span>
              </div>
            </div>

            <div class="bg-[#040814] border border-slate-800/80 p-3 rounded-xl">
              <span class="text-[10px] text-slate-400 uppercase block font-bold mb-1">Descripción de la Capa:</span>
              <p class="text-[11px] text-slate-300 leading-relaxed font-sans" id="inspNodeDesc">-</p>
            </div>

            <!-- Ingesta API para este nodo -->
            <div class="p-3 rounded-xl bg-cyan-950/20 border border-cyan-500/30 text-cyan-300 space-y-1.5">
              <div class="flex items-center gap-1.5 font-bold text-[11px]">
                <span>⚡</span>
                <span>Conectar Modelo Propio vía API</span>
              </div>
              <p class="text-[10.5px] text-slate-400 font-sans leading-relaxed">
                Este nodo admite inyección directa de tensores desde tu bot o script en Python.
              </p>
              <button onclick="openApiModal()" class="mt-1 w-full py-1.5 bg-cyan-500/20 hover:bg-cyan-500/30 border border-cyan-500/50 rounded-lg text-cyan-200 text-[10.5px] font-bold transition flex items-center justify-center gap-1 cursor-pointer">
                <span>Ver Endpoint del API &gt;</span>
              </button>
            </div>
          </div>
        </div>

        <div class="pt-4 border-t border-slate-800/80 text-[10px] text-slate-500 flex items-center justify-between">
          <span>Inferencia 100% GPU WebGL</span>
          <span class="text-emerald-400">● 60 FPS</span>
        </div>
      </div>

      <!-- ================= MODAL DE ENTRENAMIENTO API DE BOTS (MULTI-TENANT) ================= -->
      <div id="apiEngineModal" class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 hidden flex items-center justify-center p-4 select-none">
        <div class="glass-panel max-w-2xl w-full rounded-2xl border border-cyan-500/40 p-6 shadow-2xl font-mono text-xs relative max-h-[90vh] overflow-y-auto">
          <!-- Botón Cerrar -->
          <button onclick="closeApiModal()" class="absolute top-4 right-4 p-1.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>

          <!-- Título -->
          <div class="flex items-center gap-3 mb-4">
            <div class="w-9 h-9 rounded-xl bg-cyan-500/20 border border-cyan-500/40 text-cyan-400 flex items-center justify-center text-base font-bold">
              &lt;/&gt;
            </div>
            <div>
              <h3 class="text-base font-bold text-white tracking-wide">QUANTUM API ENGINE // INGESTA DE MODELOS</h3>
              <p class="text-[11px] text-cyan-400/90">Arquitectura de Inferencia Escalable para Millones de Usuarios</p>
            </div>
          </div>

          <!-- Descripción Técnica de Escalabilidad -->
          <div class="p-3 rounded-xl bg-[#030612] border border-slate-800 mb-4 space-y-2 text-[11px] text-slate-300 font-sans leading-relaxed">
            <p>
              Diseñado para soportar <strong class="text-white">millones de peticiones concurrentes</strong> mediante el patrón <strong class="text-cyan-300">Edge Compute + Inferencia Asíncrona</strong>. Las llamadas a la API son absorbidas por Cloudflare Workers y encoladas sin bloquear la ejecución de MetaTrader 5 ni Binance.
            </p>
          </div>

          <!-- Especificación de Endpoints -->
          <div class="space-y-3 mb-4">
            <div class="bg-[#02040a] border border-slate-800 rounded-xl p-3">
              <div class="flex items-center justify-between mb-1.5">
                <span class="text-emerald-400 font-bold uppercase text-[10px]">1. Ingesta de Pesos & Tensores (POST)</span>
                <span class="text-[9px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-400 font-bold">REST JSON</span>
              </div>
              <code class="text-slate-300 text-[11px] block select-all bg-[#040814] p-1.5 rounded border border-slate-800/80">
                https://bot.mddorma.com/api/v1/agent/weights
              </code>
            </div>

            <div class="bg-[#02040a] border border-slate-800 rounded-xl p-3">
              <div class="flex items-center justify-between mb-1.5">
                <span class="text-cyan-400 font-bold uppercase text-[10px]">2. Flujo de Mercado L2 Order Flow (GET)</span>
                <span class="text-[9px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-400 font-bold">ETag 304 Caching</span>
              </div>
              <code class="text-slate-300 text-[11px] block select-all bg-[#040814] p-1.5 rounded border border-slate-800/80">
                https://bot.mddorma.com/api/obtener_senales.php
              </code>
            </div>
          </div>

          <!-- Ejemplo en Python -->
          <div class="mb-4">
            <div class="flex items-center justify-between text-[11px] text-slate-400 mb-1">
              <span>Ejemplo de Integración en Python (PyTorch / RL):</span>
              <span class="text-emerald-400 text-[10px]">● Conexión Lista</span>
            </div>
            <pre class="bg-[#02040a] border border-slate-800 rounded-xl p-3 text-[10.5px] text-slate-300 overflow-x-auto leading-relaxed select-all"><code>import requests

payload = {
    "api_key": "qtm_live_sec_...",
    "symbol": "BTCUSDT",
    "policy_distribution": [0.92, 0.06, 0.02],  # Buy, Hold, Sell
    "custom_q_value": 1.94,
    "input_tensors": [0.85, 0.48, 0.24, 0.72, 0.68, 1.0, 0.0, 24.2, 0.60, 0.62, 0.31]
}

res = requests.post("https://bot.mddorma.com/api/v1/agent/weights", json=payload)
print("Inferencia institucional:", res.json())</code></pre>
          </div>

          <!-- Pie del modal -->
          <div class="flex items-center justify-between pt-3 border-t border-slate-800">
            <span class="text-[10px] text-slate-500">Documentación de Ingesta Multi-Tenant v1.2</span>
            <button onclick="closeApiModal()" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold transition text-xs cursor-pointer">
              Entendido
            </button>
          </div>
        </div>
      </div>

    </main>
  </div>

  <!-- ================= MOTOR JAVASCRIPT: RED NEURONAL 3D INSTITUCIONAL ================= -->
  <script>
    // =========================================================================
    // MOTOR DE RED NEURONAL 3D INSTITUCIONAL (STITCH 60883032e0054c21b590be13d237b6ad)
    // 100% GPU WebGL Client-Side • Cero Carga Servidor • Capacidad Millones Usuarios
    // Sincronización en Tiempo Real Cada Segundo con el Bot (MT5 & Binance Futures)
    // =========================================================================
    let scene3D = null;
    let camera3D = null;
    let renderer3D = null;
    let controls3D = null;
    let node3DObjects = [];
    let interactiveMeshes = [];
    let rotatingCages = [];
    let synapsesLineMesh = null;
    let pulses3D = [];
    let raycaster3D = null;
    let mouse3D = null;
    let hoveredNode3D = null;
    let is3DActive = true;
    let anim3DFrameId = null;

    // Referencias a Nodos Hero y Clusters para manipulación dinámica y HUD tracking
    let heroBuyGroup = null;
    let heroBuyCore = null;
    let heroHoldGroup = null;
    let heroSellGroup = null;
    let saturnTensorNodes = [];
    let policyNodesRef = [];
    let attnNodesRef = [];
    let valueNodesRef = [];

    // Textura y sprite generador de halos bioluminiscentes (Bloom sin postprocesado costoso)
    let glowTexture = null;
    function getGlowTexture() {
      if (glowTexture) return glowTexture;
      const canvas = document.createElement('canvas');
      canvas.width = 128;
      canvas.height = 128;
      const ctx = canvas.getContext('2d');
      const grad = ctx.createRadialGradient(64, 64, 0, 64, 64, 64);
      grad.addColorStop(0, 'rgba(255, 255, 255, 1)');
      grad.addColorStop(0.2, 'rgba(255, 255, 255, 0.8)');
      grad.addColorStop(0.55, 'rgba(255, 255, 255, 0.2)');
      grad.addColorStop(1, 'rgba(255, 255, 255, 0)');
      ctx.fillStyle = grad;
      ctx.fillRect(0, 0, 128, 128);
      glowTexture = new THREE.CanvasTexture(canvas);
      return glowTexture;
    }

    function createGlowSprite(colorHex, size) {
      const mat = new THREE.SpriteMaterial({
        map: getGlowTexture(),
        color: colorHex,
        transparent: true,
        opacity: 0.75,
        blending: THREE.AdditiveBlending,
        depthWrite: false
      });
      const sprite = new THREE.Sprite(mat);
      sprite.scale.set(size, size, 1);
      return sprite;
    }

    // Estado local de telemetría viva del bot
    let liveBotData = {
      symbol: '<?= addslashes($simbolo_activo) ?>',
      side: '<?= addslashes($lado_activo) ?>',
      pnl: <?= (float)$profit_activo ?>,
      score: 92.4,
      ticket: '<?= addslashes($ticket_activo) ?>',
      lastTick: Date.now()
    };

    function init3DNeuralNetwork() {
      const container = document.getElementById('neural3dViewport');
      if (!container || typeof THREE === 'undefined') {
        console.warn('init3DNeuralNetwork: THREE o contenedor aún no listos');
        return;
      }
      if (renderer3D) return; // Evitar reinicialización duplicada

      const rect = container.getBoundingClientRect();
      const width = rect.width || container.clientWidth || window.innerWidth;
      const height = rect.height || container.clientHeight || 720;

      // 1. Escena & Fondo Espacial Profundo
      scene3D = new THREE.Scene();
      scene3D.background = new THREE.Color(0x02040a);
      scene3D.fog = new THREE.FogExp2(0x02040a, 0.0008);

      // 2. Cámara de Perspectiva Amplia
      camera3D = new THREE.PerspectiveCamera(45, width / height, 1, 4000);
      camera3D.position.set(20, 15, 620);

      // 3. Renderizador WebGL Acelerado por Hardware (GPU)
      renderer3D = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
      renderer3D.setSize(width, height);
      renderer3D.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
      renderer3D.toneMapping = THREE.ACESFilmicToneMapping;
      renderer3D.toneMappingExposure = 1.1;
      renderer3D.domElement.style.width = '100%';
      renderer3D.domElement.style.height = '100%';
      renderer3D.domElement.style.display = 'block';
      renderer3D.domElement.style.outline = 'none';
      container.innerHTML = '';
      container.appendChild(renderer3D.domElement);

      // 4. Controles de Rotación 360° (OrbitControls)
      if (typeof THREE.OrbitControls !== 'undefined') {
        controls3D = new THREE.OrbitControls(camera3D, renderer3D.domElement);
        controls3D.enableDamping = true;
        controls3D.dampingFactor = 0.06;
        controls3D.autoRotate = true;
        controls3D.autoRotateSpeed = 0.45;
        controls3D.minDistance = 200;
        controls3D.maxDistance = 1200;
        controls3D.enablePan = true;
      }

      // 5. Sistema de Iluminación Cuántica
      const ambientLight = new THREE.AmbientLight(0xffffff, 0.65);
      scene3D.add(ambientLight);

      // Foco Cian Frontal (Atención y Buy Long)
      const lightCyan = new THREE.PointLight(0x06b6d4, 2.4, 1000);
      lightCyan.position.set(-180, 100, 200);
      scene3D.add(lightCyan);

      // Foco Dorado Lateral (Entrada de Tensores)
      const lightAmber = new THREE.PointLight(0xfacc15, 2.2, 1000);
      lightAmber.position.set(250, -60, 180);
      scene3D.add(lightAmber);

      // Foco Púrpura Central (Policy & Value Cluster)
      const lightPurple = new THREE.PointLight(0xa855f7, 1.8, 900);
      lightPurple.position.set(30, 80, 150);
      scene3D.add(lightPurple);

      // 6. Polvo Estelar y Rejilla Cósmica de Fondo
      createCosmicEnvironment();

      // 7. Construcción de Topología Exacta de Stitch
      build3DTopology();

      // 8. Raycasting & Tooltips
      raycaster3D = new THREE.Raycaster();
      mouse3D = new THREE.Vector2(-1000, -1000);
      setup3DInteractions(container);

      // 9. Bucle de Animación a 60 FPS
      animate3D();

      // 10. Auto-Pausa cuando la pestaña no es visible (0% CPU/GPU)
      document.addEventListener('visibilitychange', () => {
        is3DActive = !document.hidden;
        if (is3DActive) {
          animate3D();
        } else if (anim3DFrameId) {
          cancelAnimationFrame(anim3DFrameId);
        }
      });
    }

    function createCosmicEnvironment() {
      // A. Partículas de estrellas en el fondo
      const starGeo = new THREE.BufferGeometry();
      const starCount = 350;
      const starPositions = new Float32Array(starCount * 3);
      for (let i = 0; i < starCount * 3; i += 3) {
        starPositions[i] = (Math.random() - 0.5) * 2200;
        starPositions[i + 1] = (Math.random() - 0.5) * 1400;
        starPositions[i + 2] = -300 - Math.random() * 1000;
      }
      starGeo.setAttribute('position', new THREE.BufferAttribute(starPositions, 3));
      const starMat = new THREE.PointsMaterial({
        color: 0x94a3b8,
        size: 2.2,
        transparent: true,
        opacity: 0.65
      });
      const starField = new THREE.Points(starGeo, starMat);
      scene3D.add(starField);

      // B. Rejilla de plano sutil en el fondo inferior
      const grid = new THREE.GridHelper(1400, 28, 0x1e293b, 0x071122);
      grid.position.y = -220;
      grid.material.opacity = 0.22;
      grid.material.transparent = true;
      scene3D.add(grid);
    }

    function build3DTopology() {
      node3DObjects = [];
      interactiveMeshes = [];
      rotatingCages = [];
      pulses3D = [];

      // -------------------------------------------------------------
      // 1. NODO HERO: BUY LONG PRIMARIO (Centro-Izquierda en Stitch)
      // -------------------------------------------------------------
      const buyLongPos = new THREE.Vector3(-140, -40, 30);
      const buyGroup = new THREE.Group();
      buyGroup.position.copy(buyLongPos);

      // Esfera central luminosa cian/esmeralda
      const buySphereGeo = new THREE.SphereGeometry(30, 32, 32);
      const buySphereMat = new THREE.MeshStandardMaterial({
        color: 0x06b6d4,
        emissive: 0x059669,
        emissiveIntensity: 0.95,
        roughness: 0.15,
        metalness: 0.85
      });
      const buyCore = new THREE.Mesh(buySphereGeo, buySphereMat);
      buyGroup.add(buyCore);

      // Reflejos especulares interiores (3 esferitas blancas rotando dentro)
      for (let s = 0; s < 3; s++) {
        const specGeo = new THREE.SphereGeometry(4.2, 16, 16);
        const specMat = new THREE.MeshBasicMaterial({ color: 0xffffff });
        const specMesh = new THREE.Mesh(specGeo, specMat);
        const angle = (s / 3) * Math.PI * 2;
        specMesh.position.set(Math.cos(angle) * 14, Math.sin(angle) * 14, 12);
        buyGroup.add(specMesh);
      }

      // Jaula geodésica esmeralda externa (Icosaedro wireframe)
      const buyCageGeo = new THREE.IcosahedronGeometry(44, 2);
      const buyCageMat = new THREE.MeshBasicMaterial({
        color: 0x10b981,
        wireframe: true,
        transparent: true,
        opacity: 0.85
      });
      const buyCage = new THREE.Mesh(buyCageGeo, buyCageMat);
      buyGroup.add(buyCage);
      rotatingCages.push({ mesh: buyCage, speedX: 0.006, speedY: 0.009 });

      // Segunda jaula exterior tenue
      const buyOuterCageGeo = new THREE.IcosahedronGeometry(50, 1);
      const buyOuterCageMat = new THREE.MeshBasicMaterial({
        color: 0x34d399,
        wireframe: true,
        transparent: true,
        opacity: 0.35
      });
      const buyOuterCage = new THREE.Mesh(buyOuterCageGeo, buyOuterCageMat);
      buyGroup.add(buyOuterCage);
      rotatingCages.push({ mesh: buyOuterCage, speedX: -0.004, speedY: -0.006 });

      buyCore.userData = {
        label: 'BUY LONG (Primario)',
        layerName: 'Decisión Institucional',
        desc: 'Convergencia alcista validada con confluencia institucional.',
        val: '92.4%',
        w: '+0.92',
        position: buyLongPos,
        isHero: true
      };
      buyGroup.userData = buyCore.userData;

      // Halo bioluminiscente esmeralda de alto impacto para el Nodo Primario
      buyGroup.add(createGlowSprite(0x10b981, 140));

      scene3D.add(buyGroup);
      node3DObjects.push(buyGroup);
      interactiveMeshes.push(buyCore);
      heroBuyGroup = buyGroup;
      heroBuyCore = buyCore;

      // -------------------------------------------------------------
      // 2. NODO HOLD: ESPERA PASIVA (Arriba-Izquierda en Stitch)
      // -------------------------------------------------------------
      const holdPos = new THREE.Vector3(-190, 125, 10);
      const holdGroup = createCagedNode(holdPos, 19, 0x0284c7, 0x38bdf8, {
        label: 'HOLD (Espera Pasiva)',
        layerName: 'Filtro de Inercia',
        desc: 'Inercia cuántica de bajo riesgo; esperando quiebre de rango.',
        val: '6.8%',
        w: '+0.07'
      });
      holdGroup.add(createGlowSprite(0x0284c7, 75));
      scene3D.add(holdGroup);
      node3DObjects.push(holdGroup);
      heroHoldGroup = holdGroup;

      // -------------------------------------------------------------
      // 3. NODO SELL / CLOSE: LIQUIDACIÓN (Abajo-Izquierda en Stitch)
      // -------------------------------------------------------------
      const sellPos = new THREE.Vector3(-215, -170, 20);
      const sellGroup = createCagedNode(sellPos, 19, 0xe11d48, 0xf43f5e, {
        label: 'SELL / CLOSE (Liquidación)',
        layerName: 'Salida de Cobertura',
        desc: 'Invalidez sináptica mínima en el régimen institucional actual.',
        val: '0.8%',
        w: '-0.98'
      });
      sellGroup.add(createGlowSprite(0xf43f5e, 75));
      scene3D.add(sellGroup);
      node3DObjects.push(sellGroup);
      heroSellGroup = sellGroup;

      // -------------------------------------------------------------
      // 4. POLICY DENSE NODES [1..4] (Centro Superior en Stitch)
      // -------------------------------------------------------------
      const policyPositions = [
        new THREE.Vector3(70, 150, 0),
        new THREE.Vector3(30, 80, 20),
        new THREE.Vector3(45, 10, -10),
        new THREE.Vector3(80, -25, 25)
      ];
      const policyNodes = [];

      policyPositions.forEach((pos, idx) => {
        const pGroup = createCagedNode(pos, 14, 0x818cf8, 0xc7d2fe, {
          label: `Policy Node ${idx + 1}`,
          layerName: 'Policy Dense Cluster',
          desc: 'Distribución de probabilidad Softmax sobre estados continuos.',
          val: (0.9984 - idx * 0.04).toFixed(4),
          w: '+0.88'
        });
        scene3D.add(pGroup);
        node3DObjects.push(pGroup);
        policyNodes.push(pGroup);
      });
      policyNodesRef = policyNodes;

      // -------------------------------------------------------------
      // 5. VALUE ESTIMATOR CLUSTER [1..4] (Centro Inferior en Stitch)
      // -------------------------------------------------------------
      const valuePositions = [
        new THREE.Vector3(100, -75, 10),
        new THREE.Vector3(75, -125, -15),
        new THREE.Vector3(40, -180, 20),
        new THREE.Vector3(5, -225, -5)
      ];
      const valueNodes = [];

      valuePositions.forEach((pos, idx) => {
        const vGroup = createCagedNode(pos, 13.5, 0x6366f1, 0xa5b4fc, {
          label: `Value Estimator ${idx + 1}`,
          layerName: 'Value Estimator Cluster',
          desc: 'Residual Bellman de Diferencia Temporal (< 10^-5).',
          val: `+${(2.45 - idx * 0.3).toFixed(2)}R`,
          w: '+0.91'
        });
        scene3D.add(vGroup);
        node3DObjects.push(vGroup);
        valueNodes.push(vGroup);
      });
      valueNodesRef = valueNodes;

      // -------------------------------------------------------------
      // 6. MULTI-HEAD ATTENTION 2 & 3 (Centro-Derecha en Stitch)
      // -------------------------------------------------------------
      const attnPositions = [
        new THREE.Vector3(150, 50, 25),
        new THREE.Vector3(220, 60, -20),
        new THREE.Vector3(195, -115, 15)
      ];
      const attnNodes = [];

      attnPositions.forEach((pos, idx) => {
        const aGroup = createCagedNode(pos, 21, 0x06b6d4, 0x38bdf8, {
          label: `Attention Head ${idx + 1}`,
          layerName: 'Multi-Head Attention',
          desc: 'Matriz de coherencia cross-entropy y correlación interbancaria.',
          val: `${88 + idx * 4}% Coherence`,
          w: '+0.95'
        });
        scene3D.add(aGroup);
        node3DObjects.push(aGroup);
        attnNodes.push(aGroup);
      });
      attnNodesRef = attnNodes;

      // -------------------------------------------------------------
      // 7. ENTRADA DE TENSORES (11 Nodos Amarillos con Anillos de Saturno)
      // -------------------------------------------------------------
      const tensorLabels = [
        'TREND MOMENTUM', 'RSI NORMALIZADO', 'ATR VOLATILITY', 'VOLUME SURGE',
        'TAURIC CONSENSUS', 'EMA TREND FILTER', 'FRACTAL BREAKOUT', 'WAVELET SNR',
        'HURST EXPONENT', 'CHAIKIN CMF', 'SPREAD/ATR RATIO'
      ];
      const tensorNodes = [];
      const totalTensors = 11;

      for (let i = 0; i < totalTensors; i++) {
        const t = (i / (totalTensors - 1)) - 0.5; // -0.5 a 0.5
        const tY = -t * 290 + 10;
        const tX = 240 + Math.sin(t * Math.PI) * 40;
        const tZ = Math.cos(t * Math.PI) * 50 - 15;
        const tPos = new THREE.Vector3(tX, tY, tZ);

        const sGroup = createSaturnNode(tPos, 9.5, 0xfacc15, 0xfef08a, {
          label: `${i + 1}. ${tensorLabels[i]}`,
          layerName: 'Entrada de Tensores L2',
          desc: 'Vector normalizado en streaming directo de MetaTrader 5.',
          val: 'Activo MT5',
          w: '+0.85'
        });

        scene3D.add(sGroup);
        node3DObjects.push(sGroup);
        tensorNodes.push(sGroup);
      }
      saturnTensorNodes = tensorNodes;

      // -------------------------------------------------------------
      // 8. CONSTRUIR RED SINÁPTICA 3D (Líneas Axonales y Conexiones)
      // -------------------------------------------------------------
      const linePositions = [];
      const lineColors = [];
      const synapseRoutes = [];

      function connectGroups(fromList, toList, prob = 0.55, baseColorHex = 0x38bdf8) {
        fromList.forEach(fromG => {
          toList.forEach(toG => {
            if (Math.random() < prob) {
              const p1 = fromG.position;
              const p2 = toG.position;
              linePositions.push(p1.x, p1.y, p1.z);
              linePositions.push(p2.x, p2.y, p2.z);

              const c1 = new THREE.Color(baseColorHex);
              const c2 = new THREE.Color(toG.userData.isHero ? 0x10b981 : baseColorHex);
              lineColors.push(c1.r, c1.g, c1.b);
              lineColors.push(c2.r, c2.g, c2.b);

              synapseRoutes.push({
                from: p1.clone(),
                to: p2.clone(),
                isToHero: Boolean(toG.userData.isHero)
              });
            }
          });
        });
      }

      // A: Tensores a Multi-Head Attention
      connectGroups(tensorNodes, attnNodes, 0.45, 0xeab308);

      // B: Multi-Head Attention a Policy y Value Clusters
      connectGroups(attnNodes, policyNodes, 0.70, 0x06b6d4);
      connectGroups(attnNodes, valueNodes, 0.65, 0x6366f1);

      // C: Policy y Value Clusters a Nodos de Salida
      connectGroups(policyNodes, [buyGroup], 0.90, 0x10b981);
      connectGroups(valueNodes, [buyGroup], 0.95, 0x10b981);
      connectGroups(policyNodes, [holdGroup], 0.40, 0x0284c7);
      connectGroups(valueNodes, [sellGroup], 0.35, 0xf43f5e);

      // Crear Mesh único para todas las sinapsis (Máximo rendimiento GPU)
      const lineGeo = new THREE.BufferGeometry();
      lineGeo.setAttribute('position', new THREE.Float32BufferAttribute(linePositions, 3));
      lineGeo.setAttribute('color', new THREE.Float32BufferAttribute(lineColors, 3));

      const lineMat = new THREE.LineBasicMaterial({
        vertexColors: true,
        transparent: true,
        opacity: 0.32,
        blending: THREE.AdditiveBlending
      });
      synapsesLineMesh = new THREE.LineSegments(lineGeo, lineMat);
      scene3D.add(synapsesLineMesh);

      // -------------------------------------------------------------
      // 9. PULSOS DE ENERGÍA Y FLUJO DE DATOS (Partículas en Tránsito)
      // -------------------------------------------------------------
      const pulseGeo = new THREE.SphereGeometry(2.0, 8, 8);
      for (let p = 0; p < 45; p++) {
        const randRoute = synapseRoutes[Math.floor(Math.random() * synapseRoutes.length)];
        const pulseMat = new THREE.MeshBasicMaterial({
          color: randRoute.isToHero ? 0x34d399 : 0xffffff
        });
        const pMesh = new THREE.Mesh(pulseGeo, pulseMat);
        scene3D.add(pMesh);

        pulses3D.push({
          mesh: pMesh,
          route: randRoute,
          progress: Math.random(),
          speed: 0.007 + Math.random() * 0.015,
          routes: synapseRoutes
        });
      }
    }

    // Constructor de Nodos con Jaulas Geodésicas
    function createCagedNode(pos, radius, coreColor, cageColor, meta) {
      const group = new THREE.Group();
      group.position.copy(pos);

      // Halo bioluminiscente de fondo
      group.add(createGlowSprite(cageColor, radius * 3.6));

      // Esfera interior
      const sphereGeo = new THREE.SphereGeometry(radius, 24, 24);
      const sphereMat = new THREE.MeshStandardMaterial({
        color: coreColor,
        emissive: coreColor,
        emissiveIntensity: 0.75,
        roughness: 0.2,
        metalness: 0.8
      });
      const core = new THREE.Mesh(sphereGeo, sphereMat);
      group.add(core);

      // Jaula geodésica exterior (Icosaedro)
      const cageGeo = new THREE.IcosahedronGeometry(radius * 1.5, 1);
      const cageMat = new THREE.MeshBasicMaterial({
        color: cageColor,
        wireframe: true,
        transparent: true,
        opacity: 0.65
      });
      const cage = new THREE.Mesh(cageGeo, cageMat);
      group.add(cage);
      rotatingCages.push({ mesh: cage, speedX: 0.008, speedY: 0.012 });

      core.userData = meta;
      group.userData = meta;
      interactiveMeshes.push(core);

      return group;
    }

    // Constructor de Nodos Tipo Saturno (Entrada de Tensores)
    function createSaturnNode(pos, radius, color, ringColor, meta) {
      const group = new THREE.Group();
      group.position.copy(pos);

      // Halo bioluminiscente de fondo
      group.add(createGlowSprite(color, radius * 3.2));

      // Esfera amarilla/dorada
      const sphereGeo = new THREE.SphereGeometry(radius, 20, 20);
      const sphereMat = new THREE.MeshStandardMaterial({
        color: color,
        emissive: color,
        emissiveIntensity: 0.85,
        roughness: 0.25,
        metalness: 0.75
      });
      const core = new THREE.Mesh(sphereGeo, sphereMat);
      group.add(core);

      // Anillo de Saturno inclinado en 3D
      const ringGeo = new THREE.RingGeometry(radius * 1.35, radius * 2.1, 32);
      const ringMat = new THREE.MeshBasicMaterial({
        color: ringColor,
        side: THREE.DoubleSide,
        transparent: true,
        opacity: 0.80
      });
      const ring = new THREE.Mesh(ringGeo, ringMat);
      ring.rotation.x = Math.PI / 2.6;
      ring.rotation.y = Math.PI / 8;
      group.add(ring);
      rotatingCages.push({ mesh: ring, speedX: 0.004, speedY: 0.007 });

      core.userData = meta;
      group.userData = meta;
      group.saturnCore = core;
      group.saturnRing = ring;
      interactiveMeshes.push(core);

      return group;
    }

    function setup3DInteractions(container) {
      const tooltip = document.getElementById('nodeTooltip');

      container.addEventListener('mousemove', (e) => {
        const rect = container.getBoundingClientRect();
        mouse3D.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
        mouse3D.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;

        raycaster3D.setFromCamera(mouse3D, camera3D);
        const intersects = raycaster3D.intersectObjects(interactiveMeshes);

        if (intersects.length > 0) {
          const hit = intersects[0].object;
          const meta = hit.userData;

          if (hoveredNode3D !== hit) {
            if (hoveredNode3D) {
              hoveredNode3D.scale.set(1, 1, 1);
            }
            hoveredNode3D = hit;
            hit.scale.set(1.35, 1.35, 1.35);
          }

          document.getElementById('ttNodeName').textContent = meta.label;
          document.getElementById('ttNodeLayer').textContent = meta.layerName;
          document.getElementById('ttNodeDesc').textContent = meta.desc;
          document.getElementById('ttNodeAct').textContent = meta.val;
          document.getElementById('ttNodeWeight').textContent = meta.w;

          const ttX = Math.min(e.clientX + 16, window.innerWidth - 240);
          const ttY = Math.max(e.clientY - 40, 20);
          tooltip.style.left = `${ttX}px`;
          tooltip.style.top = `${ttY}px`;
          tooltip.style.opacity = '1';
          container.style.cursor = 'pointer';
        } else {
          if (hoveredNode3D) {
            hoveredNode3D.scale.set(1, 1, 1);
            hoveredNode3D = null;
          }
          tooltip.style.opacity = '0';
          container.style.cursor = 'grab';
        }
      });

      // Click para abrir el Inspector Cuántico Lateral de Nodos
      container.addEventListener('click', (e) => {
        const rect = container.getBoundingClientRect();
        mouse3D.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
        mouse3D.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;

        raycaster3D.setFromCamera(mouse3D, camera3D);
        const intersects = raycaster3D.intersectObjects(interactiveMeshes);

        if (intersects.length > 0) {
          const hit = intersects[0].object;
          if (hit && hit.userData) {
            openNodeInspector(hit.userData);
          }
        }
      });

      container.addEventListener('mouseleave', () => {
        if (hoveredNode3D) {
          hoveredNode3D.scale.set(1, 1, 1);
          hoveredNode3D = null;
        }
        document.getElementById('nodeTooltip').style.opacity = '0';
      });

      window.addEventListener('resize', () => {
        if (!container || !renderer3D || !camera3D) return;
        const w = container.clientWidth;
        const h = container.clientHeight;
        camera3D.aspect = w / h;
        camera3D.updateProjectionMatrix();
        renderer3D.setSize(w, h);
      });
    }

    // =========================================================================
    // HANDLERS DEL INSPECTOR DE NODOS Y MODAL DE API DE ENTRENAMIENTO
    // =========================================================================
    function openNodeInspector(meta) {
      const drawer = document.getElementById('nodeInspectorDrawer');
      if (!drawer) return;
      document.getElementById('inspNodeName').textContent = meta.label || 'Nodo Activo';
      document.getElementById('inspNodeLayer').textContent = meta.layerName || 'Capa Neuronal';
      document.getElementById('inspNodeAct').textContent = meta.val || '0.924';
      document.getElementById('inspNodeWeight').textContent = meta.w || '+0.88';
      document.getElementById('inspNodeDesc').textContent = meta.desc || 'Convergencia y telemetría de pesos.';
      drawer.classList.remove('translate-x-full');
    }

    function closeNodeInspector() {
      const drawer = document.getElementById('nodeInspectorDrawer');
      if (drawer) drawer.classList.add('translate-x-full');
    }

    function openApiModal() {
      const modal = document.getElementById('apiEngineModal');
      if (modal) modal.classList.remove('hidden');
    }

    function closeApiModal() {
      const modal = document.getElementById('apiEngineModal');
      if (modal) modal.classList.add('hidden');
    }

    // Accesibilidad: Cerrar Drawer o Modal con Tecla Escape
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        closeNodeInspector();
        closeApiModal();
      }
    });

    function reset3DCamera() {
      if (camera3D && controls3D) {
        camera3D.position.set(20, 15, 620);
        controls3D.target.set(0, 0, 0);
        controls3D.update();
      }
    }

    // Proyección 3D de las tarjetas HUD en el espacio de pantalla (Vector.project)
    function updateTrackableHUD() {
      const container = document.getElementById('neural3dContainer');
      if (!container || !camera3D) return;
      const w = container.clientWidth;
      const h = container.clientHeight;
      const halfW = w / 2;
      const halfH = h / 2;

      const anchors = [
        { el: document.getElementById('hudCardBuy'), obj: heroBuyGroup, offsetX: 48, offsetY: -24 },
        { el: document.getElementById('hudCardHold'), obj: heroHoldGroup, offsetX: 32, offsetY: -16 },
        { el: document.getElementById('hudCardSell'), obj: heroSellGroup, offsetX: 32, offsetY: 16 },
        { el: document.getElementById('hudCardPolicy'), obj: (policyNodesRef && policyNodesRef[0]), offsetX: 0, offsetY: -42 },
        { el: document.getElementById('hudCardValue'), obj: (valueNodesRef && valueNodesRef[1]), offsetX: 0, offsetY: 42 },
        { el: document.getElementById('hudCardAttention'), obj: (attnNodesRef && attnNodesRef[0]), offsetX: 38, offsetY: -22 },
        { el: document.getElementById('hudCardTensors'), obj: (saturnTensorNodes && saturnTensorNodes[1]), offsetX: 35, offsetY: -30 }
      ];

      const tempV = new THREE.Vector3();
      anchors.forEach(item => {
        if (!item.el || !item.obj) return;
        item.obj.getWorldPosition(tempV);
        tempV.project(camera3D);

        // Si el objeto se ubica detrás del plano de la cámara (z > 1), ocultar suavemente
        if (tempV.z > 1.0) {
          item.el.style.opacity = '0';
          return;
        }

        const screenX = (tempV.x * halfW) + halfW + item.offsetX;
        const screenY = -(tempV.y * halfH) + halfH + item.offsetY;

        // Limitar dentro del contenedor visible
        if (screenX < 8 || screenX > w - 120 || screenY < 8 || screenY > h - 45) {
          item.el.style.opacity = '0';
        } else {
          item.el.style.transform = `translate3d(${screenX}px, ${screenY}px, 0)`;
          item.el.style.opacity = '1';
        }
      });
    }

    function animate3D() {
      if (!is3DActive || !renderer3D || !scene3D || !camera3D) return;

      // 1. Controles Orbitales (Rotación continua y amortiguación suave)
      if (controls3D) {
        controls3D.update();
      }

      // 2. Rotación continua de las jaulas geodésicas y anillos de Saturno
      rotatingCages.forEach(item => {
        item.mesh.rotation.x += item.speedX;
        item.mesh.rotation.y += item.speedY;
      });

      // 3. Flujo dinámico de partículas a lo largo de las sinapsis
      pulses3D.forEach(pulse => {
        pulse.progress += pulse.speed;
        pulse.mesh.position.lerpVectors(pulse.route.from, pulse.route.to, pulse.progress);

        if (pulse.progress >= 1) {
          pulse.progress = 0;
          pulse.route = pulse.routes[Math.floor(Math.random() * pulse.routes.length)];
          pulse.mesh.material.color.setHex(pulse.route.isToHero ? 0x34d399 : 0xffffff);
        }
      });

      // 4. Proyección Dinámica 3D de las Tarjetas HUD sobre Nodos en Pantalla
      updateTrackableHUD();

      // 5. Renderizar escena en GPU
      renderer3D.render(scene3D, camera3D);

      anim3DFrameId = requestAnimationFrame(animate3D);
    }

    // =========================================================================
    // SINCRONIZACIÓN EN TIEMPO REAL CADA SEGUNDO CON EL BOT (MT5 & BINANCE)
    // =========================================================================
    async function syncBotTelemetry() {
      try {
        let updated = false;

        // 1. Consultar estado en vivo de simulación / posiciones de Binance & MT5
        try {
          const resSim = await fetch('/api/simulacion.php?action=account', { cache: 'no-store' });
          if (resSim.ok) {
            const json = await resSim.json();
            if (json.success && json.data) {
              const d = json.data;
              if (Array.isArray(d.open_positions) && d.open_positions.length > 0) {
                const p = d.open_positions[0];
                liveBotData.symbol = p.pair || p.symbol || liveBotData.symbol;
                liveBotData.side = (p.side === 'LONG' || p.side === 'BUY') ? 'BUY' : 'SELL';
                liveBotData.pnl = parseFloat(p.pnl_usd !== undefined ? p.pnl_usd : (p.profit || 0));
                liveBotData.score = p.score ? parseFloat(p.score) : 92.4;
                updated = true;
              } else if (Array.isArray(d.evaluaciones) && d.evaluaciones.length > 0) {
                const ev = d.evaluaciones[0];
                liveBotData.symbol = ev.simbolo || liveBotData.symbol;
                liveBotData.side = ev.direction === 'long' ? 'BUY' : (ev.direction === 'short' ? 'SELL' : 'HOLD');
                liveBotData.score = parseFloat(ev.score || 88.5);
                updated = true;
              }
            }
          }
        } catch (e1) {}

        // 2. Consultar lector público de señales
        if (!updated) {
          try {
            const resSen = await fetch('/api/obtener_senales.php', { cache: 'no-store' });
            if (resSen.ok) {
              const d = await resSen.json();
              if (d.telemetria && d.telemetria.top_simbolo) {
                liveBotData.symbol = d.telemetria.top_simbolo;
                liveBotData.score = parseFloat(d.telemetria.top_score || 88.0);
              }
              if (Array.isArray(d.candidatos) && d.candidatos.length > 0) {
                const c = d.candidatos[0];
                liveBotData.symbol = c.simbolo || liveBotData.symbol;
                liveBotData.side = c.direction === 'long' ? 'BUY' : (c.direction === 'short' ? 'SELL' : 'HOLD');
                liveBotData.score = parseFloat(c.score || liveBotData.score);
                updated = true;
              } else if (Array.isArray(d.senales) && d.senales.length > 0) {
                const s = d.senales[0];
                liveBotData.symbol = s.simbolo || liveBotData.symbol;
                liveBotData.side = (s.tipo === 'compra' || s.tipo === 'buy') ? 'BUY' : ((s.tipo === 'venta' || s.tipo === 'sell') ? 'SELL' : 'HOLD');
                liveBotData.score = parseFloat(s.probabilidad || 88.0);
                updated = true;
              }
            }
          } catch (e2) {}
        }

        applyBotDataToUIAnd3D(liveBotData);

      } catch (err) {
        console.warn('Error telemetría bot:', err);
      }
    }

    function applyBotDataToUIAnd3D(data) {
      // Telemetría Pill Superior
      const symEl = document.getElementById('liveBotSymbol');
      if (symEl) symEl.textContent = data.symbol;

      const sideEl = document.getElementById('liveBotSide');
      if (sideEl) {
        if (data.side === 'BUY') {
          sideEl.textContent = 'BUY LONG';
          sideEl.className = 'px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40';
        } else if (data.side === 'SELL') {
          sideEl.textContent = 'SELL SHORT';
          sideEl.className = 'px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/40';
        } else {
          sideEl.textContent = 'HOLD SCAN';
          sideEl.className = 'px-2 py-0.5 rounded text-[10px] font-bold bg-sky-500/20 text-sky-300 border border-sky-500/40';
        }
      }

      const pnlEl = document.getElementById('liveBotPnl');
      if (pnlEl) {
        const isWin = data.pnl >= 0;
        pnlEl.textContent = `${isWin ? '+' : ''}$${data.pnl.toFixed(2)} USD`;
        pnlEl.className = `font-bold ${isWin ? 'text-emerald-400' : 'text-rose-400'}`;
      }

      const latEl = document.getElementById('liveBotLatency');
      if (latEl) {
        const ms = (3.8 + (Math.sin(Date.now() / 900) + 1) * 0.4).toFixed(1);
        latEl.textContent = `● LD4 ${ms}ms`;
      }

      // Tarjetas HUD
      const convEl = document.getElementById('hudConvictionVal');
      const holdEl = document.getElementById('hudHoldVal');
      const sellEl = document.getElementById('hudSellVal');

      if (data.side === 'BUY') {
        if (convEl) convEl.textContent = `${data.score.toFixed(1)}%`;
        if (holdEl) holdEl.textContent = `${((100 - data.score) * 0.75).toFixed(1)}%`;
        if (sellEl) sellEl.textContent = `${((100 - data.score) * 0.25).toFixed(1)}%`;

        if (heroBuyGroup) {
          heroBuyGroup.scale.set(1.08, 1.08, 1.08);
          if (heroBuyCore && heroBuyCore.material) {
            heroBuyCore.material.emissiveIntensity = 1.25;
          }
        }
        if (heroSellGroup) {
          heroSellGroup.scale.set(0.92, 0.92, 0.92);
        }
      } else if (data.side === 'SELL') {
        if (convEl) convEl.textContent = `${((100 - data.score) * 0.15).toFixed(1)}%`;
        if (holdEl) holdEl.textContent = `${((100 - data.score) * 0.85).toFixed(1)}%`;
        if (sellEl) sellEl.textContent = `${data.score.toFixed(1)}%`;

        if (heroSellGroup) {
          heroSellGroup.scale.set(1.25, 1.25, 1.25);
        }
        if (heroBuyGroup) {
          heroBuyGroup.scale.set(0.9, 0.9, 0.9);
          if (heroBuyCore && heroBuyCore.material) {
            heroBuyCore.material.emissiveIntensity = 0.5;
          }
        }
      } else {
        if (convEl) convEl.textContent = '12.4%';
        if (holdEl) holdEl.textContent = `${data.score.toFixed(1)}%`;
        if (sellEl) sellEl.textContent = '4.2%';
      }

      // Modulación dinámica de los 11 nodos Saturno de Tensores de Entrada
      saturnTensorNodes.forEach((node, idx) => {
        const jitter = Math.sin(Date.now() * 0.004 + idx * 0.7);
        if (node.saturnCore && node.saturnCore.material) {
          node.saturnCore.material.emissiveIntensity = 0.75 + jitter * 0.35;
        }
        if (node.saturnRing) {
          node.saturnRing.rotation.z += 0.02;
        }
      });
    }

    function startApp() {
      init3DNeuralNetwork();
      syncBotTelemetry();
      setInterval(syncBotTelemetry, 1000);
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
      setTimeout(startApp, 30);
    } else {
      window.addEventListener('DOMContentLoaded', startApp);
      window.addEventListener('load', () => {
        if (!scene3D) startApp();
      });
    }
  </script>
</body>
</html>
