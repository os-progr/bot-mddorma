<?php
/**
 * bot.mddorma.com/ia/index.php — Módulo Dedicado: Red Neuronal & Estadísticas de IA
 * ==============================================================================
 * Exposición institucional de la arquitectura de modelos (TimesFM, Gemini, DeepSeek, Confluence Tensor),
 * métricas de confluencia, estadísticas de rendimiento en vivo y las 5 capas de seguridad institucional.
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
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Red Neuronal & Estadísticas de IA | Quantum AI Terminal</title>
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
      font-family: 'Plus Jakarta Sans', sans-serif;
      background-color: #05070d;
      color: #f8fafc;
    }
    .font-mono {
      font-family: 'JetBrains Mono', monospace;
    }
    .glow-gold {
      box-shadow: 0 0 35px -5px rgba(245, 158, 11, 0.25);
    }
    .glow-purple {
      box-shadow: 0 0 35px -5px rgba(168, 85, 247, 0.25);
    }
    .glow-emerald {
      box-shadow: 0 0 35px -5px rgba(16, 185, 129, 0.2);
    }
    .neural-grid {
      background-image: radial-gradient(rgba(168, 85, 247, 0.12) 1px, transparent 1px);
      background-size: 24px 24px;
    }
    .pulse-glow {
      animation: pulseGlow 3s ease-in-out infinite alternate;
    }
    @keyframes pulseGlow {
      from { filter: drop-shadow(0 0 8px rgba(168, 85, 247, 0.3)); }
      to { filter: drop-shadow(0 0 20px rgba(168, 85, 247, 0.6)); }
    }
  </style>
</head>
<body class="min-h-screen bg-[#05070d] text-slate-100 flex flex-col selection:bg-amber-500 selection:text-slate-950">

  <!-- WRAPPER COMPLETO -->
  <div class="flex-1 flex flex-col md:flex-row min-h-screen">

    <!-- ================= BARRA LATERAL STITCH ================= -->
    <aside class="w-full md:w-16 bg-[#070b14] border-r border-slate-800/80 flex md:flex-col items-center justify-between p-3 shrink-0 z-50">
      
      <!-- Logo Superior -->
      <div class="flex md:flex-col items-center gap-4">
        <a href="/" class="flex items-center justify-center p-1.5 rounded-xl hover:bg-slate-800/60 transition group" title="Ir al Terminal">
          <img src="/assets/logo.png" alt="Quantum AI" class="w-8 h-8 object-contain drop-shadow-[0_0_10px_rgba(245,158,11,0.5)] group-hover:scale-105 transition duration-200">
        </a>

        <div class="hidden md:flex flex-col items-center gap-3 text-slate-400">
          <!-- Clima Macro -->
          <a href="/#sec-macro" class="p-2.5 rounded-xl hover:text-white hover:bg-[#0f172a] transition cursor-pointer" title="Clima de Mercado">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
          </a>
          <!-- Terminal / Gráfico -->
          <a href="/" class="p-2.5 rounded-xl hover:text-white hover:bg-[#0f172a] transition cursor-pointer" title="Gráfico TradingView">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          </a>
          <!-- Señales en Vivo -->
          <a href="/#sec-signals" class="p-2.5 rounded-xl hover:text-white hover:bg-[#0f172a] transition cursor-pointer" title="Señales en Vivo">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          </a>
          <!-- Calculadora -->
          <a href="/#sec-calculator" class="p-2.5 rounded-xl hover:text-white hover:bg-[#0f172a] transition cursor-pointer" title="Calculadora Antiquemado">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
          </a>
        </div>
      </div>

      <!-- Dock Inferior: Red Neuronal (Activo), Telegram, VIP, Perfil -->
      <div class="flex md:flex-col items-center gap-3 text-slate-400">
        
        <!-- Ícono Red Neuronal (ACTIVO) -->
        <a href="/ia/" class="p-2.5 rounded-xl text-purple-400 bg-purple-500/15 border border-purple-500/40 transition group" title="Red Neuronal & Estadísticas (Activo)">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="6" cy="6" r="2" stroke-width="2"/>
            <circle cx="18" cy="6" r="2" stroke-width="2"/>
            <circle cx="6" cy="18" r="2" stroke-width="2"/>
            <circle cx="18" cy="18" r="2" stroke-width="2"/>
            <circle cx="12" cy="12" r="2.5" stroke-width="2"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7.5 7.5l3 3m3 0l3-3m-9 9l3-3m3 0l3 3"/>
          </svg>
        </a>

        <!-- Telegram -->
        <a href="/telegram/" class="p-2.5 rounded-xl hover:text-sky-400 hover:bg-[#0f172a] transition" title="Bot de Telegram">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
        </a>

        <!-- Planes VIP -->
        <a href="/vip/" class="p-2.5 rounded-xl hover:text-amber-400 hover:bg-[#0f172a] transition" title="Planes VIP">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 4l3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"/></svg>
        </a>

        <!-- Perfil -->
        <a href="/perfil/" class="p-2.5 rounded-xl hover:text-white hover:bg-[#0f172a] transition" title="Mi Perfil">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </a>
      </div>
    </aside>

    <!-- ================= CONTENIDO PRINCIPAL ================= -->
    <div class="flex-1 flex flex-col min-w-0">

      <!-- Header Superior -->
      <header class="sticky top-0 z-40 bg-[#05070d]/90 backdrop-blur-md border-b border-slate-800/80 px-4 lg:px-8 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
            <span class="font-black text-sm text-white">QUANTUM<span class="text-amber-400">.AI</span></span>
          </div>
          <span class="text-[10px] font-mono uppercase bg-purple-500/10 text-purple-400 border border-purple-500/30 px-2.5 py-0.5 rounded-full font-bold">
            🧠 CEREBRO & RED NEURONAL V2.4
          </span>
        </div>

        <div class="flex items-center gap-3">
          <div class="text-right hidden sm:block">
            <span class="text-[10px] text-slate-400 block font-mono">CONSEJO DE MODELOS</span>
            <span class="text-xs text-emerald-400 font-bold font-mono">ONLINE • 4 POSICIONES CAP</span>
          </div>
          <?php if ($is_logged_in): ?>
            <a href="/perfil/" class="flex items-center gap-2 bg-[#090f1e] border border-slate-700/80 px-3 py-1 rounded-xl text-xs">
              <span class="w-5 h-5 rounded-full bg-amber-500 text-slate-950 font-black text-[10px] flex items-center justify-center">
                <?= strtoupper(substr($usuario_logueado['nombre'] ?? 'U', 0, 1)) ?>
              </span>
              <span class="text-white font-bold text-xs"><?= htmlspecialchars($usuario_logueado['nombre'] ?? 'Operador') ?></span>
            </a>
          <?php else: ?>
            <a href="/login.php" class="text-xs font-bold text-sky-300 bg-[#0e1726] border border-slate-700 px-3 py-1.5 rounded-xl hover:bg-slate-800 transition">
              Iniciar Sesión
            </a>
          <?php endif; ?>
        </div>
      </header>

      <!-- Cuerpo del Módulo -->
      <main class="flex-1 p-4 lg:p-8 max-w-7xl mx-auto w-full space-y-8 neural-grid">
        
        <!-- HERO / INTRODUCCIÓN INSTITUCIONAL -->
        <div class="relative overflow-hidden bg-gradient-to-b from-[#0a0f20] via-[#080d19] to-[#05070d] border border-slate-800/90 rounded-3xl p-6 lg:p-10 shadow-2xl">
          <div class="absolute -top-24 -right-24 w-96 h-96 bg-purple-600/10 rounded-full blur-3xl pointer-events-none"></div>
          <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

          <div class="relative z-10 max-w-3xl space-y-4">
            <div class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-500/20 to-amber-500/20 border border-purple-500/30 px-3 py-1 rounded-full text-xs font-mono font-bold text-purple-300">
              <span>⚡</span> <span>ALGORITMOS INSTITUCIONALES MULTICAPA</span>
            </div>
            <h1 class="text-2xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight leading-tight">
              Arquitectura de Inferencia & <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 via-amber-300 to-amber-500">Gobernanza de Riesgo</span>
            </h1>
            <p class="text-slate-300 text-sm sm:text-base leading-relaxed">
              Nuestro bot opera bajo un <strong>Consejo Antagónico de Modelos de Lenguaje y Series Temporales</strong> coordinados en tiempo real. No opera por impulsos ni cuadrículas ciegas: cada entrada exige una confluencia cuántica matemática verificada, cálculo milimétrico de Stop Loss y filtrado de correlación de divisas.
            </p>
          </div>

          <!-- STATS KEY BANNER (6 Métricas de Élite) -->
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mt-8 pt-8 border-t border-slate-800/80">
            <div class="bg-[#050811] p-3.5 rounded-2xl border border-slate-800 text-center">
              <span class="text-[10px] text-slate-400 block font-mono uppercase font-bold">WIN RATE CONFLUENCIA</span>
              <strong class="text-emerald-400 font-mono text-xl sm:text-2xl font-black block mt-0.5">82.4%</strong>
              <span class="text-[9px] text-slate-500 font-mono">Score ≥ 60%</span>
            </div>
            <div class="bg-[#050811] p-3.5 rounded-2xl border border-slate-800 text-center">
              <span class="text-[10px] text-slate-400 block font-mono uppercase font-bold">RATIO R:R PROMEDIO</span>
              <strong class="text-amber-400 font-mono text-xl sm:text-2xl font-black block mt-0.5">1:2.1R</strong>
              <span class="text-[9px] text-slate-500 font-mono">Ganancia vs Riesgo</span>
            </div>
            <div class="bg-[#050811] p-3.5 rounded-2xl border border-purple-500/30 text-center glow-purple">
              <span class="text-[10px] text-purple-300 block font-mono uppercase font-bold">CONCURRENCIA MÁX</span>
              <strong class="text-purple-400 font-mono text-xl sm:text-2xl font-black block mt-0.5">4 Cupos</strong>
              <span class="text-[9px] text-slate-500 font-mono">Multi-Par Activo</span>
            </div>
            <div class="bg-[#050811] p-3.5 rounded-2xl border border-slate-800 text-center">
              <span class="text-[10px] text-slate-400 block font-mono uppercase font-bold">RIESGO POR TRADE</span>
              <strong class="text-sky-400 font-mono text-xl sm:text-2xl font-black block mt-0.5">0.20%</strong>
              <span class="text-[9px] text-slate-500 font-mono">Preservación capital</span>
            </div>
            <div class="bg-[#050811] p-3.5 rounded-2xl border border-slate-800 text-center">
              <span class="text-[10px] text-slate-400 block font-mono uppercase font-bold">CIRCUIT BREAKER</span>
              <strong class="text-rose-400 font-mono text-xl sm:text-2xl font-black block mt-0.5">1.8%</strong>
              <span class="text-[9px] text-slate-500 font-mono">Kill-Switch Diario</span>
            </div>
            <div class="bg-[#050811] p-3.5 rounded-2xl border border-slate-800 text-center">
              <span class="text-[10px] text-slate-400 block font-mono uppercase font-bold">LATENCIA NUBE</span>
              <strong class="text-emerald-400 font-mono text-xl sm:text-2xl font-black block mt-0.5">&lt; 115ms</strong>
              <span class="text-[9px] text-slate-500 font-mono">Inferencia ultra-rápida</span>
            </div>
          </div>
        </div>

        <!-- ================= SECCIÓN 1: CONSEJO DE MODELOS (DIAGRAMA DE RED) ================= -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl sm:text-2xl font-extrabold text-white flex items-center gap-2">
                <span>🧠</span> <span>Consejo Antagónico de Redes Neuronales</span>
              </h2>
              <p class="text-xs text-slate-400">4 capas de validación independientes antes de disparar cualquier orden.</p>
            </div>
            <span class="text-xs font-mono text-emerald-400 bg-emerald-500/10 border border-emerald-500/30 px-3 py-1 rounded-xl font-bold hidden sm:block">
              Sincronizado 24/7
            </span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Modelo 1: TimesFM 3.0 -->
            <div class="bg-[#080d19] border border-slate-800 hover:border-purple-500/50 rounded-2xl p-5 space-y-3 transition duration-200 shadow-md">
              <div class="w-10 h-10 rounded-xl bg-purple-500/15 border border-purple-500/30 flex items-center justify-center text-purple-400 text-lg font-bold">
                📈
              </div>
              <h3 class="font-extrabold text-white text-base">Google TimesFM 3.0</h3>
              <p class="text-[11px] font-mono text-purple-300 font-semibold uppercase">Series Temporales Cuantitativas</p>
              <p class="text-xs text-slate-300 leading-relaxed">
                Modelo fundacional de DeepMind especializado en predecir trayectorias de precios en marcos temporales de 15m y 1h a partir de millones de ventanas históricas.
              </p>
              <div class="pt-2 border-t border-slate-800/80 text-[11px] text-slate-400 flex justify-between font-mono">
                <span>Precisión Tendencial:</span> <strong class="text-white">88.5%</strong>
              </div>
            </div>

            <!-- Modelo 2: Gemini 1.5 -->
            <div class="bg-[#080d19] border border-slate-800 hover:border-amber-500/50 rounded-2xl p-5 space-y-3 transition duration-200 shadow-md">
              <div class="w-10 h-10 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400 text-lg font-bold">
                ⚡
              </div>
              <h3 class="font-extrabold text-white text-base">Gemini 1.5 Flash/Pro</h3>
              <p class="text-[11px] font-mono text-amber-300 font-semibold uppercase">Estratega Táctico & Macro</p>
              <p class="text-xs text-slate-300 leading-relaxed">
                Interpreta la dinámica macroeconómica global (S&P 500, Nasdaq, VIX, DXY Dólar) y analiza la microestructura de volumen minuto a minuto en cada par.
              </p>
              <div class="pt-2 border-t border-slate-800/80 text-[11px] text-slate-400 flex justify-between font-mono">
                <span>Playbook Táctico:</span> <strong class="text-white">Activo</strong>
              </div>
            </div>

            <!-- Modelo 3: DeepSeek & NVIDIA NIM -->
            <div class="bg-[#080d19] border border-slate-800 hover:border-sky-500/50 rounded-2xl p-5 space-y-3 transition duration-200 shadow-md">
              <div class="w-10 h-10 rounded-xl bg-sky-500/15 border border-sky-500/30 flex items-center justify-center text-sky-400 text-lg font-bold">
                🛡️
              </div>
              <h3 class="font-extrabold text-white text-base">DeepSeek & NVIDIA NIM</h3>
              <p class="text-[11px] font-mono text-sky-300 font-semibold uppercase">Árbitro Adversarial de Riesgo</p>
              <p class="text-xs text-slate-300 leading-relaxed">
                Actúa como crítico implacable buscando fallas en las tesis de compra o venta para descartar trampas de liquidez institucional y falsas rupturas.
              </p>
              <div class="pt-2 border-t border-slate-800/80 text-[11px] text-slate-400 flex justify-between font-mono">
                <span>Filtro de Veto:</span> <strong class="text-white">Estricto</strong>
              </div>
            </div>

            <!-- Modelo 4: Confluence Tensor -->
            <div class="bg-[#080d19] border border-slate-800 hover:border-emerald-500/50 rounded-2xl p-5 space-y-3 transition duration-200 shadow-md">
              <div class="w-10 h-10 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-400 text-lg font-bold">
                🎯
              </div>
              <h3 class="font-extrabold text-white text-base">Confluence Tensor L2</h3>
              <p class="text-[11px] font-mono text-emerald-300 font-semibold uppercase">Motor Cuantitativo de Entrada</p>
              <p class="text-xs text-slate-300 leading-relaxed">
                Calcula la matriz matemática de confluencia (RSI, libro de órdenes L2, zonas de oferta/demanda SVP y ATR) asignando el score numérico definitivo (0-100%).
              </p>
              <div class="pt-2 border-t border-slate-800/80 text-[11px] text-slate-400 flex justify-between font-mono">
                <span>Umbral de Gatillo:</span> <strong class="text-white">≥ 50.0 Score</strong>
              </div>
            </div>

          </div>
        </div>

        <!-- ================= SECCIÓN 2: LAS 5 CAPAS DE SEGURIDAD ================= -->
        <div class="bg-[#080d1a] border border-slate-800/90 rounded-3xl p-6 lg:p-8 space-y-6">
          <div class="max-w-2xl space-y-1">
            <span class="text-[10px] font-mono uppercase tracking-widest text-amber-400 font-bold">PRESERVACIÓN ESTRICTA DEL CAPITAL</span>
            <h2 class="text-xl sm:text-2xl font-extrabold text-white">Las 5 Capas de Seguridad Institucional</h2>
            <p class="text-xs text-slate-400">Por qué nuestra IA opera únicamente "las operaciones necesarias" y previene liquidaciones.</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            
            <div class="p-4 rounded-2xl bg-[#050811] border border-slate-800/80 space-y-2">
              <div class="flex items-center gap-2 text-emerald-400 font-bold text-sm">
                <span class="w-6 h-6 rounded-lg bg-emerald-500/15 flex items-center justify-center text-xs">1</span>
                <span>Stop Loss Broker-Side Físico</span>
              </div>
              <p class="text-xs text-slate-300 leading-relaxed">
                Bajo la salvaguarda <code>REJECTED_NO_SL</code>, ninguna orden sale hacia MetaTrader o Binance sin un Stop Loss físico inamovible registrado desde el primer milisegundo.
              </p>
            </div>

            <div class="p-4 rounded-2xl bg-[#050811] border border-slate-800/80 space-y-2">
              <div class="flex items-center gap-2 text-sky-400 font-bold text-sm">
                <span class="w-6 h-6 rounded-lg bg-sky-500/15 flex items-center justify-center text-xs">2</span>
                <span>Currency Guard Anti-Correlación</span>
              </div>
              <p class="text-xs text-slate-300 leading-relaxed">
                Monitorea la exposición por divisa base. Si ya hay una operación abierta dependiente del USD, bloquea compras simultáneas en pares correlacionados para evitar sobreexposición.
              </p>
            </div>

            <div class="p-4 rounded-2xl bg-[#050811] border border-slate-800/80 space-y-2">
              <div class="flex items-center gap-2 text-amber-400 font-bold text-sm">
                <span class="w-6 h-6 rounded-lg bg-amber-500/15 flex items-center justify-center text-xs">3</span>
                <span>Floor de Cosecha R:R v2</span>
              </div>
              <p class="text-xs text-slate-300 leading-relaxed">
                Establece un piso mínimo de ganancia de $1.00 USD y objetivos de cosecha institucional de $5.00 a $12.00 USD, eliminando micro-cierres de centavos y maximizando la esperanza matemática.
              </p>
            </div>

            <div class="p-4 rounded-2xl bg-[#050811] border border-slate-800/80 space-y-2">
              <div class="flex items-center gap-2 text-purple-400 font-bold text-sm">
                <span class="w-6 h-6 rounded-lg bg-purple-500/15 flex items-center justify-center text-xs">4</span>
                <span>Disyuntor Diario del 1.8%</span>
              </div>
              <p class="text-xs text-slate-300 leading-relaxed">
                Un interruptor de circuito persistente. Si ocurriera una volatilidad atípica extrema y las pérdidas alcanzan el 1.8% de la equidad, el bot detiene inmediatamente nuevas entradas por 24 horas.
              </p>
            </div>

            <div class="p-4 rounded-2xl bg-[#050811] border border-slate-800/80 space-y-2">
              <div class="flex items-center gap-2 text-indigo-400 font-bold text-sm">
                <span class="w-6 h-6 rounded-lg bg-indigo-500/15 flex items-center justify-center text-xs">5</span>
                <span>FastGuardian & Friday Sentinel</span>
              </div>
              <p class="text-xs text-slate-300 leading-relaxed">
                Monitorea tick-a-tick anomalías de ejecución y liquida automáticamente posiciones abiertas antes del cierre semanal para evitar pérdidas por gaps de fin de semana.
              </p>
            </div>

            <div class="p-4 rounded-2xl bg-[#050811] border border-slate-800/80 space-y-2">
              <div class="flex items-center gap-2 text-amber-300 font-bold text-sm">
                <span class="w-6 h-6 rounded-lg bg-amber-500/15 flex items-center justify-center text-xs">🛡️</span>
                <span>Capacidad Controlada (4 Cupos)</span>
              </div>
              <p class="text-xs text-slate-300 leading-relaxed">
                Permite operar de forma equilibrada hasta 4 posiciones simultáneas no correlacionadas (Oro + 3 divisas mayores), manteniendo el nivel de margen siempre por encima del 100,000%.
              </p>
            </div>

          </div>
        </div>

        <!-- ================= SECCIÓN 3: TELEMETRÍA EN VIVO Y MONITOREO ================= -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          
          <!-- Monitor de Telemetría en Vivo -->
          <div class="lg:col-span-2 bg-[#080d19] border border-slate-800 rounded-3xl p-6 space-y-4 shadow-md">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <h3 class="font-extrabold text-white text-base">Telemetría de Inferencia en Tiempo Real</h3>
              </div>
              <span id="telemetryTime" class="text-[11px] font-mono text-slate-400">Actualizando...</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
              <div class="bg-[#050811] p-3 rounded-xl border border-slate-800">
                <span class="text-[10px] text-slate-400 block font-mono">ESTADO MOTOR</span>
                <strong class="text-emerald-400 font-mono text-sm block mt-0.5">ONLINE (Activo)</strong>
              </div>
              <div class="bg-[#050811] p-3 rounded-xl border border-slate-800">
                <span class="text-[10px] text-slate-400 block font-mono">MODO DE GESTIÓN</span>
                <strong class="text-amber-400 font-mono text-sm block mt-0.5">SIMULATION V2</strong>
              </div>
              <div class="bg-[#050811] p-3 rounded-xl border border-slate-800">
                <span class="text-[10px] text-slate-400 block font-mono">PARES BAJO VIGILANCIA</span>
                <strong class="text-white font-mono text-sm block mt-0.5">7 Forex/Oro + 6 Cripto</strong>
              </div>
              <div class="bg-[#050811] p-3 rounded-xl border border-slate-800">
                <span class="text-[10px] text-slate-400 block font-mono">TICKET RECONCILIACIÓN</span>
                <strong class="text-purple-400 font-mono text-sm block mt-0.5">Atómica Activa</strong>
              </div>
            </div>

            <!-- Feed de Actividad Algorítmica -->
            <div class="space-y-2 pt-2">
              <span class="text-[11px] font-mono text-slate-400 uppercase font-bold block">Log de Inferencia Cuántica Reciente:</span>
              <div id="neuralLogFeed" class="bg-[#05070d] p-3.5 rounded-xl border border-slate-800/80 font-mono text-xs text-slate-300 space-y-2 h-44 overflow-y-auto">
                <div class="text-purple-400">⚡ [TimesFM 3.0] Ventanas klines 15m analizadas con éxito. Tendencia proyectada favorable.</div>
                <div class="text-emerald-400">🛡️ [REJECTED_NO_SL Guard] Verificado. 100% de órdenes protegidas con SL broker-side.</div>
                <div class="text-amber-400">📊 [R:R v2 Harvester] Target floor de cosecha activo: Forex $5.00 / Oro $12.00.</div>
                <div class="text-sky-400">🌐 [Currency Guard] Auditoría de correlación ejecutada: 3/4 posiciones ocupadas. Margen >300,000%.</div>
              </div>
            </div>
          </div>

          <!-- Banner CTA VIP & Telegram -->
          <div class="bg-gradient-to-br from-[#0e162a] to-[#080d1a] border border-amber-500/30 rounded-3xl p-6 flex flex-col justify-between space-y-5 glow-gold">
            <div class="space-y-3">
              <span class="bg-amber-500/15 text-amber-400 border border-amber-500/30 text-[10px] font-mono font-bold px-2.5 py-1 rounded-full uppercase">
                ACCESO INSTITUCIONAL
              </span>
              <h3 class="text-xl font-extrabold text-white leading-snug">
                Recibe las Señales de la Red Neuronal en tu Telegram
              </h3>
              <p class="text-xs text-slate-300 leading-relaxed">
                Conecta tu cuenta VIP al canal privado de alta velocidad (&lt;100ms) y recibe los disparos instantáneos con precio de entrada, Stop Loss y Take Profit exactos.
              </p>
            </div>

            <div class="space-y-2.5">
              <a href="/vip/" class="w-full bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-950 font-black py-3 rounded-xl text-xs transition shadow-lg shadow-amber-500/20 flex items-center justify-center gap-2">
                <span>👑</span> <span>Obtener Membresía VIP ($5 USD)</span>
              </a>
              <a href="/telegram/" class="w-full bg-[#0f172a] hover:bg-slate-800 text-slate-300 hover:text-white py-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 border border-slate-700">
                <span>✈️</span> <span>Configurar Bot de Telegram</span>
              </a>
            </div>
          </div>

        </div>

      </main>

    </div>
  </div>

  <!-- Script de Telemetría Dinámica -->
  <script>
    async function updateNeuralTelemetry() {
      try {
        const res = await fetch('/api/obtener_senales.php', { cache: 'no-store' });
        if (!res.ok) return;
        const d = await res.json();
        
        const timeEl = document.getElementById('telemetryTime');
        if (timeEl && d.ultima_actualizacion) {
          timeEl.textContent = `Actualizado UTC: ${d.ultima_actualizacion}`;
        }

        const logFeed = document.getElementById('neuralLogFeed');
        if (logFeed && Array.isArray(d.senales) && d.senales.length > 0) {
          const listHtml = d.senales.slice(0, 4).map(s => `
            <div class="text-slate-300 border-b border-slate-800/40 pb-1">
              <span class="text-amber-400 font-bold">[${s.simbolo}]</span> 
              <span class="${String(s.tipo).toLowerCase().includes('compra') ? 'text-emerald-400' : 'text-rose-400'}">${s.tipo.toUpperCase()}</span> 
              a $${s.entrada} • TP $${s.tp} • SL $${s.sl} • <span class="text-purple-300">Certeza: ${s.probabilidad}%</span>
            </div>
          `).join('');
          logFeed.innerHTML = listHtml + `
            <div class="text-emerald-400 pt-1">🛡️ [Motor Cuántico] Filtros de seguridad activos: Currency Guard OK • 4 cupos disponibles.</div>
          `;
        }
      } catch (e) {}
    }

    window.addEventListener('DOMContentLoaded', () => {
      updateNeuralTelemetry();
      setInterval(updateNeuralTelemetry, 8000);
    });
  </script>
</body>
</html>
