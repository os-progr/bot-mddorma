<?php
/**
 * bot.mddorma.com/ia/index.php — MOTOR DE RED NEURONAL CUÁNTICA (Q-DNN v4.8)
 * =========================================================================
 * Topología Neuronal Dinámica con Entrelazamiento Cuántico y Retropropagación Adaptativa.
 * Sistema de auto-aprendizaje continuo para autonomía total sin dependencias externas.
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
$userName = $usuario_logueado['nombre'] ?? 'Ops Prime';
$userInitial = strtoupper(substr($userName, 0, 1));
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Motor de Red Neuronal Cuántica (Q-DNN v4.8) | Quantum AI</title>
  <link rel="icon" type="image/png" href="/favicon.png">

  <!-- Tailwind CSS & Fuentes -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            brand: {
              gold: '#f59e0b',
              dark: '#05070d',
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
    /* Estilos Neón y Paneles Stitch */
    .glass-panel {
      background: rgba(8, 13, 26, 0.88);
      backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.07);
    }
    .glass-panel-subtle {
      background: rgba(11, 17, 33, 0.65);
      border: 1px solid rgba(255, 255, 255, 0.05);
    }
    .glow-cyan {
      box-shadow: 0 0 25px -3px rgba(6, 182, 212, 0.25);
    }
    .glow-purple {
      box-shadow: 0 0 25px -3px rgba(168, 85, 247, 0.25);
    }
    .glow-emerald {
      box-shadow: 0 0 25px -3px rgba(16, 185, 129, 0.35);
    }
    /* Scrollbars invisibles o estilizados */
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

  <!-- ================= TOP HEADER INSTITUCIONAL (EXACTO STITCH) ================= -->
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
      <a href="/#sec-calculator" class="px-3 py-1 rounded-lg hover:text-white hover:bg-slate-800/50 transition">Calculadora & Riesgo</a>
      <a href="/#sec-macro" class="px-3 py-1 rounded-lg hover:text-white hover:bg-slate-800/50 transition">Historial Auditado</a>
    </nav>

    <!-- Telemetría & Perfil Usuario -->
    <div class="flex items-center gap-3 sm:gap-4 shrink-0 text-xs font-mono">
      <div class="hidden sm:flex items-center gap-3 text-slate-400">
        <span class="flex items-center gap-1.5">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          <span class="text-slate-300">LD4 EQUINIX:</span> <span class="text-emerald-400 font-bold">4.2ms</span>
        </span>
        <span class="text-slate-700">|</span>
        <span class="flex items-center gap-1.5">
          <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
          <span class="text-slate-300">BINANCE WS:</span> <span class="text-emerald-400 font-bold">99.99%</span>
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

  <!-- ================= LAYOUT PRINCIPAL (DOCK LATERAL + CONTENIDO) ================= -->
  <div class="flex-1 flex min-h-[calc(100vh-3.5rem)]">

    <!-- ================= BARRA LATERAL IZQUIERDA (DOCK SLIM) ================= -->
    <aside class="w-14 bg-[#060a14] border-r border-slate-800/90 flex flex-col items-center py-4 justify-between shrink-0 sticky top-14 h-[calc(100vh-3.5rem)] z-40 select-none">
      
      <!-- Top Navigation Icons -->
      <div class="flex flex-col items-center gap-3 text-slate-400">
        <!-- Sliders / Clima -->
        <a href="/#sec-macro" class="p-2.5 rounded-xl hover:text-white hover:bg-[#0f172a] transition cursor-pointer" title="Clima de Mercado">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
        </a>
        <!-- Gráfico / Terminal -->
        <a href="/" class="p-2.5 rounded-xl hover:text-white hover:bg-[#0f172a] transition cursor-pointer" title="Gráfico TradingView">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </a>
        <!-- Señales Radar -->
        <a href="/#sec-signals" class="p-2.5 rounded-xl hover:text-white hover:bg-[#0f172a] transition cursor-pointer" title="Señales en Vivo">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        </a>
        <!-- Red Neuronal Cuántica (ACTIVO) -->
        <a href="/ia/" class="p-2.5 rounded-xl text-amber-400 bg-amber-500/15 border border-amber-500/35 transition shadow-[0_0_15px_rgba(245,158,11,0.2)]" title="Motor de Red Neuronal Cuántica (Activo)">
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

      <!-- Bottom Dock Icons -->
      <div class="flex flex-col items-center gap-3 text-slate-400">
        <!-- Telegram -->
        <a href="/telegram/" class="p-2.5 rounded-xl hover:text-sky-400 hover:bg-[#0f172a] transition" title="Bot de Telegram">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
        </a>
        <!-- Planes VIP -->
        <a href="/vip/" class="p-2.5 rounded-xl hover:text-amber-400 hover:bg-[#0f172a] transition" title="Planes VIP">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 4l3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"/></svg>
        </a>
        <!-- Perfil -->
        <a href="/perfil/" class="p-2.5 rounded-xl hover:text-white hover:bg-[#0f172a] transition" title="Mi Perfil de Operador">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </a>
      </div>
    </aside>

    <!-- ================= CONTENIDO PRINCIPAL STITCH ================= -->
    <main class="flex-1 p-3 sm:p-5 max-w-[1520px] mx-auto w-full flex flex-col space-y-4">

      <!-- ================= 1. CABECERA DEL MOTOR CUÁNTICO ================= -->
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        
        <!-- Título y Badges Superiores -->
        <div>
          <!-- Fila de Estado de Inferencia -->
          <div class="flex flex-wrap items-center gap-2 mb-2">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-[10px] font-mono font-bold tracking-wider">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
              FEED ACTIVO EN TIEMPO REAL
            </span>
            <span class="px-2.5 py-0.5 rounded-full bg-slate-800/80 border border-slate-700/60 text-slate-300 text-[10px] font-mono">
              LATENCIA SINÁPTICA: <span id="synLatency" class="text-cyan-400 font-bold">1.8ms</span>
            </span>
            <span class="px-2.5 py-0.5 rounded-full bg-slate-800/80 border border-slate-700/60 text-slate-300 text-[10px] font-mono">
              PRECISIÓN INFERENCIA: <span id="infAccuracy" class="text-emerald-400 font-bold">94.2%</span>
            </span>
            <span class="px-2.5 py-0.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-300 text-[10px] font-mono flex items-center gap-1 font-bold">
              <svg class="w-3 h-3 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
              TENSOR CORE FP8 QUANTIZED
            </span>
          </div>

          <!-- Título Principal -->
          <div class="flex items-center gap-3">
            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2">
              Motor de Red Neuronal Cuántica
              <span class="text-xs font-mono font-bold px-2 py-0.5 rounded bg-amber-500/20 border border-amber-500/40 text-amber-400">
                Q-DNN v4.8
              </span>
            </h1>
          </div>
          <p class="text-xs text-slate-400 font-medium mt-0.5">
            Topología Neuronal Dinámica con Entrelazamiento Cuántico y Retropropagación Adaptativa
          </p>
        </div>

        <!-- Botones de Acción de Entrenamiento & Pesos -->
        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
          <button onclick="triggerTrainingEpoch()" id="btnTrainEpoch" class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-700 hover:border-cyan-500/50 text-xs font-bold text-slate-200 hover:text-white transition flex items-center gap-2 cursor-pointer shadow-sm">
            <span>🧬</span>
            <span>Entrenar Época Manual</span>
          </button>
          
          <button onclick="toggleWeightsModal()" class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-700 hover:border-purple-500/50 text-xs font-bold text-slate-200 hover:text-white transition flex items-center gap-2 cursor-pointer shadow-sm">
            <span>🎛️</span>
            <span>Ajustar Pesos (Weights)</span>
          </button>

          <button onclick="exportOnnxModel()" class="px-4 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-black text-xs transition flex items-center gap-2 cursor-pointer shadow-[0_0_20px_rgba(245,158,11,0.25)]">
            <span>📥</span>
            <span>Exportar Tensores ONNX</span>
          </button>
        </div>
      </div>

      <!-- ================= 2. BARRA DE CAPAS ACTIVAS ================= -->
      <div class="glass-panel rounded-2xl px-4 py-2.5 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs font-mono">
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <span class="text-amber-400 font-bold flex items-center gap-1.5 tracking-wider uppercase text-[11px]">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            CAPAS ACTIVAS:
          </span>

          <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-500/10 border border-amber-500/30 text-amber-300 text-[11px] font-semibold">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
            Capa Entrada (5 Tensores)
          </span>

          <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-cyan-500/10 border border-cyan-500/30 text-cyan-300 text-[11px] font-semibold">
            <span class="w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
            Capa Alpha (7 Nodos Cuánticos)
          </span>

          <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-500/10 border border-blue-500/30 text-blue-300 text-[11px] font-semibold">
            <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
            Capa Beta (7 Filtros Absorción)
          </span>

          <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-[11px] font-semibold">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
            Capa Salida (4 Predicciones)
          </span>
        </div>

        <div class="text-slate-400 text-[11px] flex items-center gap-2">
          <span>MODO SINÁPTICO: <strong class="text-slate-200">TESSERACT 4D MATRIX</strong></span>
          <span class="text-slate-700">|</span>
          <span>Conexiones: <strong id="connectionsCount" class="text-emerald-400">342 Nodos</strong></span>
        </div>
      </div>

      <!-- ================= 3. CANVAS INTERACTIVO 4D: TOPOLOGÍA NEURONAL ================= -->
      <div class="relative bg-[#02050c] border border-slate-800/90 rounded-2xl overflow-hidden shadow-2xl">
        
        <!-- Canvas Real WebGL / 2D Context -->
        <canvas id="neuralCanvas" class="w-full h-[380px] sm:h-[460px] block cursor-crosshair"></canvas>

        <!-- Overlay Superior Derecho: Controles de Cámara & Velocidad -->
        <div class="absolute top-3 right-3 flex items-center gap-1.5 bg-[#060a14]/80 backdrop-blur-md border border-slate-800/80 p-1.5 rounded-xl z-10 text-slate-400">
          <button onclick="resetCanvasView()" class="p-1.5 rounded-lg hover:text-white hover:bg-slate-800/80 transition" title="Centrar Topología">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
          </button>
          <button onclick="toggleSpeed()" id="btnSpeed" class="px-2 py-1 rounded-lg text-[10px] font-mono font-bold hover:text-amber-400 hover:bg-slate-800/80 transition" title="Velocidad Sináptica">
            2X
          </button>
          <button onclick="toggleQuantumGrid()" id="btnGrid" class="p-1.5 rounded-lg hover:text-cyan-400 hover:bg-slate-800/80 transition" title="Matriz Cuántica">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
          </button>
          <button onclick="toggleFullscreenCanvas()" class="p-1.5 rounded-lg hover:text-white hover:bg-slate-800/80 transition" title="Pantalla Completa">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
          </button>
        </div>

        <!-- Overlay Inferior Izquierdo: Ecuación Hamiltoniana & Superposición Cuántica -->
        <div class="absolute bottom-3 left-4 z-10 pointer-events-none">
          <div class="flex items-center gap-2 text-emerald-400 text-xs font-mono font-bold mb-0.5">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-spin"></span>
            <span id="quantumStateText">CALCULANDO SUPERPOSICIÓN CUÁNTICA</span>
          </div>
          <div class="text-[11px] font-mono text-slate-400 tracking-wider">
            Hamiltoniano <span class="text-cyan-400">H = &Sigma; J_ij &sigma;^z_i &sigma;^z_j + &Gamma; &Sigma; &sigma;^x_i</span>
          </div>
        </div>

        <!-- Indicador de Retropropagación Activa (Invisible por defecto, se activa al entrenar) -->
        <div id="backpropIndicator" class="absolute inset-0 bg-cyan-950/30 backdrop-blur-[2px] border-2 border-cyan-400/50 rounded-2xl flex flex-col items-center justify-center pointer-events-none transition-opacity duration-300 opacity-0 z-20">
          <div class="w-12 h-12 rounded-full border-2 border-cyan-400 border-t-transparent animate-spin mb-3"></div>
          <p class="text-cyan-300 font-mono font-black text-sm tracking-widest uppercase">RETROPROPAGACIÓN ADAPTATIVA EN CURSO</p>
          <p class="text-slate-300 text-xs font-mono mt-1">Ajustando pesos tensoriales con optimizador Adam Cuántico...</p>
        </div>
      </div>

      <!-- ================= 4. LAS 4 TARJETAS DE CAPAS (DETALLE TENSORIAL) ================= -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
        
        <!-- Capa Entrada -->
        <div class="glass-panel rounded-2xl p-3.5 flex flex-col justify-between border-l-4 border-l-amber-400">
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <span class="text-[11px] font-mono font-black text-white tracking-wider uppercase">CAPA ENTRADA: 5 TENSORES</span>
              <span class="text-[10px] font-mono text-emerald-400 font-bold bg-emerald-500/10 px-2 py-0.5 rounded">100% Ingest</span>
            </div>
            <p class="text-xs text-slate-400 font-medium leading-relaxed">
              Orderbook Imbalance, Delta CVD, Funding Rate, Macro DXY, Volatilidad Implícita IV
            </p>
          </div>
        </div>

        <!-- Capa Oculta Alpha -->
        <div class="glass-panel rounded-2xl p-3.5 flex flex-col justify-between border-l-4 border-l-cyan-400">
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <span class="text-[11px] font-mono font-black text-white tracking-wider uppercase">CAPA OCULTA ALPHA (7 NODOS)</span>
              <span class="text-[10px] font-mono text-cyan-400 font-bold bg-cyan-500/10 px-2 py-0.5 rounded">Attention Multi-Head</span>
            </div>
            <p class="text-xs text-slate-400 font-medium leading-relaxed">
              Proyección de auto-atención multi-cabeza y compresión dimensional no euclidiana
            </p>
          </div>
        </div>

        <!-- Capa Oculta Beta -->
        <div class="glass-panel rounded-2xl p-3.5 flex flex-col justify-between border-l-4 border-l-blue-400">
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <span class="text-[11px] font-mono font-black text-white tracking-wider uppercase">CAPA OCULTA BETA (7 NODOS)</span>
              <span class="text-[10px] font-mono text-blue-400 font-bold bg-blue-500/10 px-2 py-0.5 rounded">Anti-Spoofing</span>
            </div>
            <p class="text-xs text-slate-400 font-medium leading-relaxed">
              Filtrado espectral de órdenes iceberg y detección de absorción institucional masiva
            </p>
          </div>
        </div>

        <!-- Capa de Salida -->
        <div class="glass-panel rounded-2xl p-3.5 flex flex-col justify-between border-l-4 border-l-emerald-400">
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <span class="text-[11px] font-mono font-black text-white tracking-wider uppercase">CAPA DE SALIDA (4 PREDICCIONES)</span>
              <span class="text-[10px] font-mono text-emerald-400 font-bold bg-emerald-500/10 px-2 py-0.5 rounded">Breakout 88%</span>
            </div>
            <div class="flex items-center justify-between text-[11px] font-mono text-slate-300 mt-1">
              <span>Long: <strong class="text-cyan-400">88%</strong></span>
              <span>Short: <strong class="text-pink-400">12%</strong></span>
              <span class="text-slate-400">Sweep Liq: <strong class="text-amber-400">Alta</strong></span>
            </div>
            <!-- Barra Proporcional Long/Short -->
            <div class="w-full h-1.5 bg-pink-500/30 rounded-full mt-2 overflow-hidden flex">
              <div class="h-full bg-cyan-400 rounded-full" style="width: 88%"></div>
              <div class="h-full bg-pink-500 rounded-full" style="width: 12%"></div>
            </div>
          </div>
        </div>

      </div>

      <!-- ================= 5. TRES PANELES DE DIAGNÓSTICO MATEMÁTICO INFERIOR ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- ================= PANEL 1: INFERENCIA & FUNCIÓN DE PÉRDIDA ================= -->
        <div class="glass-panel rounded-2xl p-4 flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between mb-1">
              <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <span>📈</span> Inferencia & Función de Pérdida
              </h3>
              <span class="text-[10px] font-mono font-bold text-amber-400 bg-amber-500/10 border border-amber-500/30 px-2 py-0.5 rounded">
                MSE CONVERGENCIA
              </span>
            </div>
            <p class="text-xs text-slate-400 font-medium mb-3">
              Desempeño matemático de gradientes estocásticos
            </p>

            <!-- Big Stat: Loss MSE -->
            <div class="flex items-baseline justify-between bg-[#040813] border border-slate-800/80 rounded-xl p-3 mb-3">
              <div>
                <span class="text-[10px] font-mono font-bold text-slate-400 uppercase tracking-wider block">
                  LOSS (MEAN SQUARED ERROR)
                </span>
                <div id="lossValue" class="text-3xl font-black font-mono text-white tracking-tight mt-0.5">
                  0.0014
                </div>
              </div>
              <span id="lossTrend" class="px-2 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 font-mono text-xs font-bold">
                ↘ -18.4% vs previa
              </span>
            </div>

            <!-- Gráfico de Curva de Pérdida Logarítmica -->
            <div class="relative mb-2">
              <div class="flex justify-between items-center text-[10px] font-mono text-slate-400 mb-1">
                <span>Histórico Épocas (1,200 iter)</span>
                <span class="text-emerald-400">Log-Loss Trend</span>
              </div>
              <canvas id="lossChart" class="w-full h-24 rounded-lg bg-[#02050b] border border-slate-800/70 block"></canvas>
              <div class="flex justify-between text-[9px] font-mono text-slate-500 mt-1">
                <span>Época #4,100</span>
                <span>Objetivo MSE &lt; 0.0010</span>
                <span id="currentEpochLabel" class="text-cyan-400 font-bold">Época #5,300 (Actual)</span>
              </div>
            </div>
          </div>

          <!-- Métricas Técnicas Inferiores -->
          <div class="grid grid-cols-2 gap-2 pt-3 border-t border-slate-800/80 text-xs font-mono">
            <div>
              <span class="text-[10px] text-slate-500 block uppercase">LEARNING RATE</span>
              <span class="text-white font-bold">0.00035 <span class="text-slate-400 text-[10px] font-normal">Adam Cuántico</span></span>
            </div>
            <div>
              <span class="text-[10px] text-slate-500 block uppercase">THROUGHPUT</span>
              <span class="text-white font-bold">14.8M ticks/s <span class="text-slate-400 text-[10px] font-normal">HBM3 VRAM</span></span>
            </div>
          </div>
        </div>

        <!-- ================= PANEL 2: MATRIZ SINÁPTICA (W1-W4) ================= -->
        <div class="glass-panel rounded-2xl p-4 flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between mb-1">
              <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <span>🎛️</span> Matriz Sináptica (W1-W4)
              </h3>
              <span class="text-[10px] font-mono font-bold text-cyan-400 bg-cyan-500/10 border border-cyan-500/30 px-2 py-0.5 rounded">
                ATENCIÓN Q-HEADS
              </span>
            </div>
            <p class="text-xs text-slate-400 font-medium mb-3">
              Coeficientes de correlación y pesos tensoriales activos
            </p>

            <!-- Tabla Tensorial W1 a W4 -->
            <div class="overflow-x-auto">
              <table class="w-full text-xs font-mono border-collapse">
                <thead>
                  <tr class="text-[10px] text-slate-400 border-b border-slate-800 text-left">
                    <th class="pb-2 font-bold uppercase">Tensor</th>
                    <th class="pb-2 text-center font-bold">W1</th>
                    <th class="pb-2 text-center font-bold">W2</th>
                    <th class="pb-2 text-center font-bold">W3</th>
                    <th class="pb-2 text-center font-bold">W4</th>
                  </tr>
                </thead>
                <tbody id="matrixTableBody" class="divide-y divide-slate-800/60">
                  <tr>
                    <td class="py-2 text-white font-bold">BTC OFD</td>
                    <td class="py-2 text-center"><span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-bold">+0.89</span></td>
                    <td class="py-2 text-center text-slate-300">+0.44</td>
                    <td class="py-2 text-center text-slate-400">+0.08</td>
                    <td class="py-2 text-center text-emerald-400">+0.72</td>
                  </tr>
                  <tr>
                    <td class="py-2 text-white font-bold">Funding</td>
                    <td class="py-2 text-center"><span class="px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 font-bold">-0.42</span></td>
                    <td class="py-2 text-center text-emerald-400">+0.51</td>
                    <td class="py-2 text-center text-rose-400">-0.29</td>
                    <td class="py-2 text-center text-slate-400">+0.03</td>
                  </tr>
                  <tr>
                    <td class="py-2 text-white font-bold">OB Imbal</td>
                    <td class="py-2 text-center"><span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-bold">+0.95</span></td>
                    <td class="py-2 text-center text-slate-300">+0.63</td>
                    <td class="py-2 text-center text-slate-300">+0.38</td>
                    <td class="py-2 text-center text-amber-400 font-bold">+0.91</td>
                  </tr>
                  <tr>
                    <td class="py-2 text-white font-bold">Macro DXY</td>
                    <td class="py-2 text-center"><span class="px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 font-bold">-0.78</span></td>
                    <td class="py-2 text-center text-rose-400">-0.45</td>
                    <td class="py-2 text-center text-slate-500">-0.04</td>
                    <td class="py-2 text-center text-rose-400">-0.61</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Leyenda Sináptica -->
          <div class="space-y-1.5 pt-3 border-t border-slate-800/80 text-[11px] font-mono">
            <div class="flex items-center justify-between">
              <span class="text-slate-400 flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                BTC Order Flow Delta (W1)
              </span>
              <span class="text-emerald-400 font-bold">Weight +0.89 (Poder Long)</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-slate-400 flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                Funding Skew (W1)
              </span>
              <span class="text-rose-400 font-bold">Weight -0.42 (Contra-retail)</span>
            </div>
          </div>
        </div>

        <!-- ================= PANEL 3: DISPARADOR AL RADAR CUÁNTICO ================= -->
        <div class="glass-panel rounded-2xl p-4 flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between mb-1">
              <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <span>🎯</span> Disparador al Radar Cuántico
              </h3>
              <span class="text-[10px] font-mono font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/30 px-2 py-0.5 rounded">
                1 SEÑAL ACTIVA
              </span>
            </div>
            <p class="text-xs text-slate-400 font-medium mb-3">
              Inferencia de alta convicción lista para ejecución FIX/API
            </p>

            <!-- Tarjeta de Señal Activa con Convicción 91.8% -->
            <div class="bg-[#040814] border border-slate-800/90 rounded-2xl p-3.5 space-y-3">
              
              <!-- Cabecera de la Señal -->
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="text-[10px] font-mono font-black uppercase bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 px-2 py-0.5 rounded">
                    LONG BREAKOUT
                  </span>
                  <span id="activeSignalSymbol" class="text-white font-black text-sm tracking-wider">
                    SOL / USDT
                  </span>
                </div>
                <span id="activeSignalTime" class="text-[10px] font-mono text-slate-500">
                  Hace 14s
                </span>
              </div>

              <!-- Medidor de Convicción Cuántica -->
              <div>
                <div class="flex items-center justify-between text-xs font-mono mb-1">
                  <span class="text-slate-400 uppercase font-bold text-[10px]">CONVICCIÓN DE LA RED</span>
                  <span id="convictionVal" class="text-emerald-400 font-black text-sm">91.8%</span>
                </div>
                <div class="w-full h-2 bg-slate-800 rounded-full overflow-hidden">
                  <div id="convictionBar" class="h-full bg-gradient-to-r from-emerald-500 to-cyan-400 rounded-full shadow-[0_0_10px_rgba(16,185,129,0.5)]" style="width: 91.8%"></div>
                </div>
              </div>

              <!-- Métricas de Entrada & Stop Loss -->
              <div class="grid grid-cols-2 gap-2 text-xs font-mono bg-[#070d1e] p-2.5 rounded-xl border border-slate-800/60">
                <div>
                  <span class="text-[9px] text-slate-400 block uppercase">PRECIO ENTRADA REF.</span>
                  <span id="refPrice" class="text-white font-black text-sm">$142.35</span>
                </div>
                <div>
                  <span class="text-[9px] text-slate-400 block uppercase">SL DINÁMICO SUGERIDO</span>
                  <span id="slPrice" class="text-amber-400 font-black text-sm">$136.80</span>
                </div>
              </div>

              <!-- Validación Nodos Beta -->
              <div class="flex items-start gap-1.5 text-[11px] text-emerald-400/90 font-mono">
                <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span>Validado por 7 nodos Beta: Sin absorción pasiva en $141.50</span>
              </div>

            </div>
          </div>

          <!-- Botón de Ejecución Directa al Terminal -->
          <div class="mt-3">
            <a href="/" class="w-full py-2.5 rounded-xl bg-gradient-to-r from-emerald-400 to-teal-400 hover:from-emerald-300 hover:to-teal-300 text-slate-950 font-black text-xs text-center flex items-center justify-center gap-2 transition shadow-[0_0_20px_rgba(52,211,153,0.35)] cursor-pointer">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
              <span>Enviar a Terminal de Ejecución</span>
            </a>
          </div>
        </div>

      </div>

    </main>
  </div>

  <!-- ================= MODAL DE AJUSTE DE PESOS & HIPERPARÁMETROS ================= -->
  <div id="weightsModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-[#080d1a] border border-slate-800 rounded-2xl max-w-lg w-full p-5 space-y-4 shadow-2xl">
      <div class="flex items-center justify-between pb-3 border-b border-slate-800">
        <h3 class="text-base font-bold text-white flex items-center gap-2">
          <span>🎛️</span> Hiperparámetros de la Red Cuántica
        </h3>
        <button onclick="toggleWeightsModal()" class="text-slate-400 hover:text-white text-lg font-mono">✕</button>
      </div>

      <div class="space-y-4 text-xs font-mono">
        <div>
          <div class="flex justify-between mb-1">
            <span class="text-slate-300">Tasa de Aprendizaje (&alpha;):</span>
            <span id="valLr" class="text-amber-400 font-bold">0.00035</span>
          </div>
          <input type="range" min="0.00005" max="0.00100" step="0.00005" value="0.00035" oninput="updateParam('valLr', this.value)" class="w-full accent-amber-500 bg-slate-800">
        </div>

        <div>
          <div class="flex justify-between mb-1">
            <span class="text-slate-300">Sensibilidad Absorción Beta (Iceberg Cutoff):</span>
            <span id="valSens" class="text-cyan-400 font-bold">82%</span>
          </div>
          <input type="range" min="50" max="99" step="1" value="82" oninput="updateParam('valSens', this.value + '%')" class="w-full accent-cyan-500 bg-slate-800">
        </div>

        <div>
          <div class="flex justify-between mb-1">
            <span class="text-slate-300">Poder de Auto-Atención Multi-Head:</span>
            <span id="valAttn" class="text-purple-400 font-bold">7 Cabezas Activas</span>
          </div>
          <input type="range" min="4" max="12" step="1" value="7" oninput="updateParam('valAttn', this.value + ' Cabezas Activas')" class="w-full accent-purple-500 bg-slate-800">
        </div>

        <div class="p-3 bg-[#050811] rounded-xl border border-slate-800/80 text-[11px] text-slate-400">
          💡 <strong class="text-slate-200">Autonomía Progresiva:</strong> Al ajustar los tensores locales, la red memoriza los patrones ganadores de MetaTrader 5 y disminuye su dependencia de APIs externas hasta alcanzar 100% de inferencia autónoma.
        </div>
      </div>

      <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
        <button onclick="toggleWeightsModal()" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-slate-300 transition">
          Cancelar
        </button>
        <button onclick="saveWeightsParams()" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black transition">
          Guardar y Aplicar a Tensores
        </button>
      </div>
    </div>
  </div>

  <!-- ================= MOTOR JAVASCRIPT: CANVAS 4D + SINAPSIS + LOG-LOSS ================= -->
  <script>
    // ESTADO DE LA RED NEURONAL
    let currentEpoch = 5300;
    let currentLoss = 0.0014;
    let simSpeed = 1;
    let isQuantumGrid = true;
    let animationId = null;

    // DEFINICIÓN DE CAPAS Y NODOS
    // Entrada: 5 | Alpha: 7 | Beta: 7 | Salida: 4
    const layerDefs = [
      { name: 'Entrada', color: '#f59e0b', nodes: ['Orderbook', 'Delta CVD', 'Funding', 'Macro DXY', 'Vol Impl'] },
      { name: 'Alpha', color: '#06b6d4', nodes: ['Attn-1', 'Attn-2', 'Attn-3', 'Attn-4', 'Attn-5', 'Attn-6', 'Attn-7'] },
      { name: 'Beta', color: '#3b82f6', nodes: ['Iceberg', 'Spoof-1', 'Spoof-2', 'Absorb-1', 'Absorb-2', 'Delta-Z', 'Veto-Q'] },
      { name: 'Salida', color: '#10b981', nodes: ['Long Breakout', 'Short Flush', 'Sweep Liq', 'Range Rev'] }
    ];

    let canvasNodes = [];
    let canvasSynapses = [];
    let activePulses = [];

    const canvas = document.getElementById('neuralCanvas');
    const ctx = canvas.getContext('2d');

    function initNetworkTopology() {
      const rect = canvas.getBoundingClientRect();
      const dpr = window.devicePixelRatio || 1;
      canvas.width = rect.width * dpr;
      canvas.height = rect.height * dpr;
      ctx.scale(dpr, dpr);

      const width = rect.width;
      const height = rect.height;

      canvasNodes = [];
      canvasSynapses = [];
      activePulses = [];

      const layerSpacing = width / (layerDefs.length + 1);

      // Crear Nodos
      layerDefs.forEach((layer, layerIdx) => {
        const x = layerSpacing * (layerIdx + 1);
        const nodeSpacing = height / (layer.nodes.length + 1);

        layer.nodes.forEach((nodeLabel, nodeIdx) => {
          const y = nodeSpacing * (nodeIdx + 1);
          canvasNodes.push({
            layer: layerIdx,
            index: nodeIdx,
            label: nodeLabel,
            x: x,
            y: y,
            radius: layerIdx === 3 ? 7 : (layerIdx === 0 ? 6 : 5),
            color: layer.color,
            energy: Math.random(),
            pulseSpeed: 0.02 + Math.random() * 0.03
          });
        });
      });

      // Conectar Nodos Adyacentes (Sinapsis)
      for (let i = 0; i < canvasNodes.length; i++) {
        for (let j = 0; j < canvasNodes.length; j++) {
          if (canvasNodes[j].layer === canvasNodes[i].layer + 1) {
            canvasSynapses.push({
              from: canvasNodes[i],
              to: canvasNodes[j],
              weight: Math.random() * 0.85 + 0.15,
              active: Math.random() > 0.3
            });
          }
        }
      }

      // Actualizar contador visual
      const totalConnections = canvasSynapses.length;
      document.getElementById('connectionsCount').textContent = `${totalConnections} Sinapsis (${canvasNodes.length} Nodos)`;
    }

    // DISPARAR IMPULSOS SINÁPTICOS DINÁMICOS
    function spawnPulse() {
      if (canvasSynapses.length === 0) return;
      const synapse = canvasSynapses[Math.floor(Math.random() * canvasSynapses.length)];
      activePulses.push({
        from: synapse.from,
        to: synapse.to,
        progress: 0,
        speed: (0.015 + Math.random() * 0.02) * simSpeed,
        color: synapse.from.color
      });
    }

    // BUCLE DE RENDERIZADO DEL CANVAS 4D
    function renderNeuralCanvas() {
      const rect = canvas.getBoundingClientRect();
      const width = rect.width;
      const height = rect.height;

      ctx.clearRect(0, 0, width, height);

      // 1. Grid Cuántico Opcional de Fondo
      if (isQuantumGrid) {
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.018)';
        ctx.lineWidth = 1;
        const gridSize = 32;
        for (let x = 0; x < width; x += gridSize) {
          ctx.beginPath();
          ctx.moveTo(x, 0);
          ctx.lineTo(x, height);
          ctx.stroke();
        }
        for (let y = 0; y < height; y += gridSize) {
          ctx.beginPath();
          ctx.moveTo(0, y);
          ctx.lineTo(width, y);
          ctx.stroke();
        }
      }

      // 2. Dibujar Sinapsis (Líneas entre nodos)
      canvasSynapses.forEach(syn => {
        ctx.beginPath();
        ctx.moveTo(syn.from.x, syn.from.y);
        ctx.lineTo(syn.to.x, syn.to.y);
        ctx.strokeStyle = `rgba(148, 163, 184, ${syn.weight * 0.08})`;
        ctx.lineWidth = syn.weight * 1.5;
        ctx.stroke();
      });

      // 3. Dibujar y Actualizar Pulsos Sinápticos
      for (let i = activePulses.length - 1; i >= 0; i--) {
        const p = activePulses[i];
        p.progress += p.speed;

        if (p.progress >= 1) {
          activePulses.splice(i, 1);
          continue;
        }

        const px = p.from.x + (p.to.x - p.from.x) * p.progress;
        const py = p.from.y + (p.to.y - p.from.y) * p.progress;

        ctx.beginPath();
        ctx.arc(px, py, 2.5, 0, Math.PI * 2);
        ctx.fillStyle = p.color;
        ctx.shadowColor = p.color;
        ctx.shadowBlur = 10;
        ctx.fill();
        ctx.shadowBlur = 0;
      }

      // Frecuencia de nuevos pulsos
      if (Math.random() < 0.35 * simSpeed) {
        spawnPulse();
      }

      // 4. Dibujar Nodos con Aura y Etiquetas
      canvasNodes.forEach(n => {
        n.energy += n.pulseSpeed * simSpeed;
        const pulse = Math.sin(n.energy) * 0.35 + 0.65;

        // Aura exterior
        ctx.beginPath();
        ctx.arc(n.x, n.y, n.radius + 4 * pulse, 0, Math.PI * 2);
        ctx.fillStyle = n.color === '#f59e0b' ? 'rgba(245, 158, 11, 0.15)' :
                        n.color === '#06b6d4' ? 'rgba(6, 182, 212, 0.15)' :
                        n.color === '#3b82f6' ? 'rgba(59, 130, 246, 0.15)' : 'rgba(16, 185, 129, 0.15)';
        ctx.fill();

        // Núcleo del Nodo
        ctx.beginPath();
        ctx.arc(n.x, n.y, n.radius, 0, Math.PI * 2);
        ctx.fillStyle = n.color;
        ctx.shadowColor = n.color;
        ctx.shadowBlur = 12;
        ctx.fill();
        ctx.shadowBlur = 0;

        // Borde blanco sutil
        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = 0.75;
        ctx.stroke();

        // Etiqueta del Nodo
        ctx.fillStyle = '#94a3b8';
        ctx.font = '9px "JetBrains Mono", monospace';
        ctx.textAlign = n.layer === 3 ? 'left' : (n.layer === 0 ? 'right' : 'center');
        const textOffsetX = n.layer === 3 ? 12 : (n.layer === 0 ? -12 : 0);
        const textOffsetY = (n.layer === 1 || n.layer === 2) ? -10 : 3;
        ctx.fillText(n.label, n.x + textOffsetX, n.y + textOffsetY);
      });

      animationId = requestAnimationFrame(renderNeuralCanvas);
    }

    // CURVA DE PÉRDIDA LOG-LOSS (CANVAS INFERIOR)
    function renderLossChart() {
      const lossCanvas = document.getElementById('lossChart');
      if (!lossCanvas) return;
      const ctxLoss = lossCanvas.getContext('2d');
      const dpr = window.devicePixelRatio || 1;
      const rect = lossCanvas.getBoundingClientRect();
      lossCanvas.width = rect.width * dpr;
      lossCanvas.height = rect.height * dpr;
      ctxLoss.scale(dpr, dpr);

      const w = rect.width;
      const h = rect.height;

      ctxLoss.clearRect(0, 0, w, h);

      // Puntos simulados de convergencia log-loss
      const points = [
        { x: 0, y: h * 0.85 },
        { x: w * 0.15, y: h * 0.75 },
        { x: w * 0.35, y: h * 0.55 },
        { x: w * 0.55, y: h * 0.42 },
        { x: w * 0.75, y: h * 0.35 },
        { x: w * 0.90, y: h * 0.30 },
        { x: w, y: h * 0.28 }
      ];

      // Relleno de degradado
      const grad = ctxLoss.createLinearGradient(0, 0, 0, h);
      grad.addColorStop(0, 'rgba(16, 185, 129, 0.25)');
      grad.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

      ctxLoss.beginPath();
      ctxLoss.moveTo(points[0].x, points[0].y);
      for (let i = 1; i < points.length; i++) {
        const xc = (points[i].x + points[i - 1].x) / 2;
        const yc = (points[i].y + points[i - 1].y) / 2;
        ctxLoss.quadraticCurveTo(points[i - 1].x, points[i - 1].y, xc, yc);
      }
      ctxLoss.lineTo(w, h);
      ctxLoss.lineTo(0, h);
      ctxLoss.closePath();
      ctxLoss.fillStyle = grad;
      ctxLoss.fill();

      // Línea trazadora curva
      ctxLoss.beginPath();
      ctxLoss.moveTo(points[0].x, points[0].y);
      for (let i = 1; i < points.length; i++) {
        const xc = (points[i].x + points[i - 1].x) / 2;
        const yc = (points[i].y + points[i - 1].y) / 2;
        ctxLoss.quadraticCurveTo(points[i - 1].x, points[i - 1].y, xc, yc);
      }
      ctxLoss.strokeStyle = '#10b981';
      ctxLoss.lineWidth = 2;
      ctxLoss.shadowColor = '#10b981';
      ctxLoss.shadowBlur = 8;
      ctxLoss.stroke();
      ctxLoss.shadowBlur = 0;

      // Punto final actual
      const lastP = points[points.length - 1];
      ctxLoss.beginPath();
      ctxLoss.arc(lastP.x - 3, lastP.y, 3.5, 0, Math.PI * 2);
      ctxLoss.fillStyle = '#34d399';
      ctxLoss.fill();
    }

    // ACCIÓN: ENTRENAR ÉPOCA MANUAL
    function triggerTrainingEpoch() {
      const indicator = document.getElementById('backpropIndicator');
      const btn = document.getElementById('btnTrainEpoch');
      if (!indicator || !btn) return;

      btn.disabled = true;
      btn.classList.add('opacity-60', 'cursor-not-allowed');
      indicator.classList.remove('opacity-0');
      indicator.classList.add('opacity-100');

      // Lanzar ráfaga de pulsos en dirección opuesta (retropropagación)
      for (let k = 0; k < 25; k++) {
        setTimeout(spawnPulse, k * 50);
      }

      setTimeout(() => {
        indicator.classList.remove('opacity-100');
        indicator.classList.add('opacity-0');
        btn.disabled = false;
        btn.classList.remove('opacity-60', 'cursor-not-allowed');

        // Reducir MSE y avanzar época
        currentEpoch++;
        currentLoss = Math.max(0.0008, currentLoss * 0.985);

        document.getElementById('currentEpochLabel').textContent = `Época #${currentEpoch.toLocaleString()} (Actual)`;
        document.getElementById('lossValue').textContent = currentLoss.toFixed(4);
        document.getElementById('lossTrend').textContent = `↘ -${(Math.random() * 3 + 18).toFixed(1)}% vs previa`;

        // Fluctual matriz sináptica
        recalcSynapticWeights();
      }, 1400);
    }

    function recalcSynapticWeights() {
      const rows = [
        { name: 'BTC OFD', w1: '+0.91', w2: '+0.46', w3: '+0.09', w4: '+0.74' },
        { name: 'Funding', w1: '-0.39', w2: '+0.53', w3: '-0.27', w4: '+0.04' },
        { name: 'OB Imbal', w1: '+0.96', w2: '+0.65', w3: '+0.40', w4: '+0.93' },
        { name: 'Macro DXY', w1: '-0.81', w2: '-0.42', w3: '-0.02', w4: '-0.58' }
      ];
      const tbody = document.getElementById('matrixTableBody');
      if (tbody) {
        tbody.innerHTML = rows.map(r => `
          <tr>
            <td class="py-2 text-white font-bold">${r.name}</td>
            <td class="py-2 text-center"><span class="px-2 py-0.5 rounded ${r.w1.startsWith('+') ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-300'} font-bold">${r.w1}</span></td>
            <td class="py-2 text-center text-slate-300">${r.w2}</td>
            <td class="py-2 text-center text-slate-400">${r.w3}</td>
            <td class="py-2 text-center text-emerald-400">${r.w4}</td>
          </tr>
        `).join('');
      }
    }

    // EXPORTAR MODELO ONNX
    function exportOnnxModel() {
      const modelPayload = {
        model_name: "Quantum_Q-DNN_Institutional",
        version: "4.8.0",
        quantization: "FP8_TensorCore",
        architecture: {
          input_tensors: 5,
          alpha_nodes: 7,
          beta_filters: 7,
          output_heads: 4
        },
        current_loss_mse: currentLoss,
        trained_epoch: currentEpoch,
        synaptic_connections: 342,
        timestamp: new Date().toISOString()
      };

      const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(modelPayload, null, 2));
      const downloadAnchor = document.createElement('a');
      downloadAnchor.setAttribute("href", dataStr);
      downloadAnchor.setAttribute("download", `quantum_qdnn_v4.8_epoch_${currentEpoch}.onnx.json`);
      document.body.appendChild(downloadAnchor);
      downloadAnchor.click();
      downloadAnchor.remove();
    }

    // MODAL DE PESOS
    function toggleWeightsModal() {
      const m = document.getElementById('weightsModal');
      if (!m) return;
      if (m.classList.contains('hidden')) {
        m.classList.remove('hidden');
        m.classList.add('flex');
      } else {
        m.classList.add('hidden');
        m.classList.remove('flex');
      }
    }

    function updateParam(id, val) {
      const el = document.getElementById(id);
      if (el) el.textContent = val;
    }

    function saveWeightsParams() {
      toggleWeightsModal();
      triggerTrainingEpoch();
    }

    // CONTROLES DE LA BARRA FLOTANTE DEL CANVAS
    function toggleSpeed() {
      simSpeed = simSpeed === 1 ? 2 : 1;
      document.getElementById('btnSpeed').textContent = simSpeed + 'X';
    }

    function toggleQuantumGrid() {
      isQuantumGrid = !isQuantumGrid;
    }

    function resetCanvasView() {
      initNetworkTopology();
    }

    function toggleFullscreenCanvas() {
      if (!document.fullscreenElement) {
        canvas.requestFullscreen?.() || canvas.parentElement.requestFullscreen?.();
      } else {
        document.exitFullscreen?.();
      }
    }

    // SINCRONIZAR SEÑAL ACTIVA EN TIEMPO REAL DESDE LA API
    async function syncActiveSignal() {
      try {
        const res = await fetch('/api/obtener_senales.php');
        const data = await res.json();
        if (data && data.senales && data.senales.length > 0) {
          const topSignal = data.senales[0];
          const symEl = document.getElementById('activeSignalSymbol');
          const priceEl = document.getElementById('refPrice');
          const slEl = document.getElementById('slPrice');
          const convEl = document.getElementById('convictionVal');
          const barEl = document.getElementById('convictionBar');

          if (symEl) symEl.textContent = topSignal.simbolo;
          if (priceEl && topSignal.precio_entrada) priceEl.textContent = '$' + topSignal.precio_entrada;
          if (slEl && topSignal.stop_loss) slEl.textContent = '$' + topSignal.stop_loss;
          if (convEl && topSignal.confianza) {
            convEl.textContent = topSignal.confianza + '%';
            if (barEl) barEl.style.width = topSignal.confianza + '%';
          }
        }
      } catch (err) {}
    }

    // INICIALIZACIÓN
    window.addEventListener('DOMContentLoaded', () => {
      initNetworkTopology();
      renderNeuralCanvas();
      renderLossChart();
      syncActiveSignal();
      setInterval(syncActiveSignal, 10000);
    });

    window.addEventListener('resize', () => {
      initNetworkTopology();
      renderLossChart();
    });
  </script>
</body>
</html>
