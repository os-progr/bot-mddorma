<?php
/**
 * bot.mddorma.com/vip/index.php — Módulo Dedicado: Membresía Quantum VIP
 * 
 * Página institucional de suscripción y pasarela de pago para traders.
 * Desbloquea alertas 24/7 al bot de Telegram personal, confluencias del motor y canal VIP.
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
  <title>Plan VIP ($19.00 USD) | Quantum AI Terminal</title>
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
      background: rgba(11, 15, 25, 0.85);
      backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.08);
    }
    .gold-glow {
      box-shadow: 0 0 45px -5px rgba(245, 158, 11, 0.25);
    }
    .grid-bg {
      background-size: 32px 32px;
      background-image: 
        linear-gradient(to right, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
    }
    .badge-pulse {
      animation: pulseDot 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    @keyframes pulseDot {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.35; transform: scale(0.85); }
    }
  </style>
</head>
<body class="grid-bg selection:bg-amber-500 selection:text-black">

  <!-- ================= BARRA SUPERIOR INSTITUCIONAL ================= -->
  <header class="sticky top-0 z-40 bg-[#05070d]/90 backdrop-blur-md border-b border-slate-800/80 px-4 lg:px-8 py-3">
    <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
      
      <!-- Brand Logo con Logo Oficial Transparente -->
      <a href="/" class="flex items-center gap-3 group">
        <img src="/assets/logo.png" alt="Quantum AI" class="w-9 h-9 object-contain drop-shadow-[0_0_12px_rgba(245,158,11,0.35)] group-hover:scale-105 transition duration-200">
        <div>
          <div class="flex items-center gap-1.5 leading-none">
            <span class="text-white font-black tracking-wider text-base">QUANTUM</span>
            <span class="text-amber-400 font-black text-base">AI</span>
            <span class="text-[9px] font-mono font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30 px-1.5 py-0.5 rounded">PLANES VIP</span>
          </div>
          <p class="text-[10px] text-slate-400 font-mono tracking-wide mt-0.5">MEMBRESÍA & ACTIVACIÓN</p>
        </div>
      </a>

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

  <!-- ================= NAVEGACIÓN MODULAR ================= -->
  <nav class="bg-[#080d1a] border-b border-slate-800 px-4 lg:px-8 py-2 sticky top-[57px] z-30">
    <div class="max-w-7xl mx-auto flex items-center justify-between gap-3 overflow-x-auto">
      <div class="flex items-center gap-1.5 text-xs font-bold shrink-0">
        <a href="/?tab=senales" class="px-3.5 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition flex items-center gap-2">
          <span>⚡</span> <span>1. Señales en Vivo</span>
        </a>
        <a href="/?tab=grafico" class="px-3.5 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition flex items-center gap-2">
          <span>📈</span> <span>2. Gráfico & Mercado</span>
        </a>
        <a href="/?tab=calculadora" class="px-3.5 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition flex items-center gap-2">
          <span>🛡️</span> <span>3. Calculadora de Riesgo</span>
        </a>
        <a href="/telegram/" class="px-3.5 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition flex items-center gap-2">
          <span>📱</span> <span>4. Mi Bot de Telegram</span>
        </a>
        <a href="/vip/" class="px-3.5 py-2 rounded-xl bg-amber-400 text-slate-950 font-black flex items-center gap-2 shadow-sm shadow-amber-400/20">
          <span>👑</span> <span>5. Membresía VIP</span>
        </a>
        <a href="/perfil/" class="px-3.5 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition flex items-center gap-2">
          <span>👤</span> <span>6. Mi Perfil</span>
        </a>
      </div>

      <a href="https://www.binance.com/es/futures/SOLUSDT" target="_blank" rel="noopener" class="hidden md:flex items-center gap-1 text-xs font-bold text-amber-400 hover:text-amber-300 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 px-3 py-1.5 rounded-lg transition shrink-0">
        <span>🟡 Abrir Binance Futuros</span> <span>↗</span>
      </a>
    </div>
  </nav>

  <!-- ================= CONTENIDO PRINCIPAL: PLAN VIP EXACTO ================= -->
  <main class="flex-1 max-w-7xl w-full mx-auto px-4 lg:px-8 py-10 space-y-8">

    <!-- Estado y Notificaciones Superiores -->
    <?php if ($es_vip): ?>
      <div class="max-w-xl mx-auto p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-center font-mono text-xs text-emerald-300">
        <span class="w-2 h-2 inline-block rounded-full bg-emerald-400 animate-pulse mr-2"></span>
        ¡Tu Membresía VIP está actualmente <strong>ACTIVA</strong>! Tienes acceso 24/7 sin restricciones.
      </div>
    <?php elseif ($usuario_logueado): ?>
      <div class="max-w-xl mx-auto p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-center font-mono text-xs text-amber-300 flex items-center justify-center gap-2">
        <span>🎁</span>
        <span>Periodo de prueba gratuito: <strong><?= (int)$dias_restantes ?> días restantes</strong>. Elige tu plan para mantener tus alertas.</span>
      </div>
    <?php endif; ?>

    <!-- ================= TARJETA DE PLAN VIP EXACTA ================= -->
    <div class="max-w-md sm:max-w-lg mx-auto">
      <div class="bg-[#0b0f19] border border-slate-800/90 rounded-3xl p-6 sm:p-9 shadow-[0_0_50px_rgba(0,0,0,0.7)] relative overflow-hidden text-center gold-glow">
        
        <!-- Insignia Superior (Pill) -->
        <div class="inline-flex items-center gap-2 bg-[#121826] border border-amber-500/30 text-amber-400 text-[11px] font-mono font-bold px-4 py-1.5 rounded-full uppercase tracking-wider mb-5 shadow-sm">
          ACCESO VIP QUANTUM-AI (1 MES)
        </div>

        <!-- Título -->
        <h1 class="text-3xl sm:text-4xl font-black text-white tracking-tight mb-2">
          Plan VIP
        </h1>

        <!-- Precio Prominente -->
        <div class="text-4xl sm:text-5xl font-black text-amber-500 font-sans tracking-tight mb-2">
          $19.00 USD
        </div>

        <!-- Subtítulo -->
        <p class="text-xs sm:text-sm text-slate-400 mb-8 font-medium">
          Acceso completo durante 30 días (USDT, Cripto o Tarjeta)
        </p>

        <!-- Checklist de Beneficios (Exactos a la captura) -->
        <div class="space-y-4 mb-8 text-xs sm:text-sm text-slate-300">
          
          <div class="flex items-center gap-3 text-left">
            <span class="w-5 h-5 rounded-full bg-amber-500 flex items-center justify-center text-slate-950 font-black text-xs shrink-0 shadow-sm shadow-amber-500/30">✓</span>
            <span class="leading-snug">Señales y Radar Cuántico <strong class="text-white font-bold">24/7 sin límites</strong></span>
          </div>

          <div class="flex items-center gap-3 text-left">
            <span class="w-5 h-5 rounded-full bg-amber-500 flex items-center justify-center text-slate-950 font-black text-xs shrink-0 shadow-sm shadow-amber-500/30">✓</span>
            <span class="leading-snug">Calculadora Antiquemado integrada <strong class="text-white font-bold">(Protección estricta)</strong></span>
          </div>

          <div class="flex items-center gap-3 text-left">
            <span class="w-5 h-5 rounded-full bg-amber-500 flex items-center justify-center text-slate-950 font-black text-xs shrink-0 shadow-sm shadow-amber-500/30">✓</span>
            <span class="leading-snug">Terminal TradingView Pro con <strong class="text-white font-bold">gráficos en tiempo real</strong></span>
          </div>

          <div class="flex items-center gap-3 text-left">
            <span class="w-5 h-5 rounded-full bg-amber-500 flex items-center justify-center text-slate-950 font-black text-xs shrink-0 shadow-sm shadow-amber-500/30">✓</span>
            <span class="leading-snug">Alertas instantáneas al canal de Telegram VIP <strong class="text-white font-bold">(&lt;100ms)</strong></span>
          </div>

          <div class="flex items-center gap-3 text-left">
            <span class="w-5 h-5 rounded-full bg-amber-500 flex items-center justify-center text-slate-950 font-black text-xs shrink-0 shadow-sm shadow-amber-500/30">✓</span>
            <span class="leading-snug">Historial <strong class="text-white font-bold">100% auditado y verificable</strong></span>
          </div>

          <div class="flex items-center gap-3 text-left">
            <span class="w-5 h-5 rounded-full bg-amber-500 flex items-center justify-center text-slate-950 font-black text-xs shrink-0 shadow-sm shadow-amber-500/30">✓</span>
            <span class="leading-snug">Soporte técnico prioritario <strong class="text-white font-bold">1 a 1</strong></span>
          </div>

        </div>

        <!-- Botón de Selección -->
        <a 
          href="https://t.me/BotFather" 
          target="_blank" 
          rel="noopener noreferrer"
          id="btnSelectPlan"
          class="w-full py-4 px-6 rounded-2xl bg-[#172033] hover:bg-[#202d47] border border-slate-700/80 hover:border-amber-500/60 text-white font-extrabold text-xs sm:text-sm uppercase tracking-wider transition-all duration-200 shadow-xl active:scale-95 cursor-pointer mb-4 flex items-center justify-center"
        >
          ELEGIR PLAN VIP ($19.00 USD)
        </a>

        <!-- Garantías Inferiores -->
        <div class="flex items-center justify-center gap-1.5 text-xs text-emerald-400 font-semibold mb-1">
          <span class="w-4 h-4 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-[10px] font-black">✓</span>
          <span>Activación instantánea</span>
        </div>
        <p class="text-[11px] text-slate-500 font-medium">
          Pasarelas: USDT, BTC, Tarjeta • Cancela cuando quieras
        </p>

      </div>
    </div>

  </main>

  <!-- ================= TOAST FLOTANTE ================= -->
  <div id="toast" class="fixed bottom-5 right-5 z-50 bg-[#0d1322] border-2 border-amber-500 text-white px-4 py-3 rounded-xl shadow-2xl text-xs font-sans font-bold hidden items-center gap-2 transition-all">
    <span class="text-amber-400 text-base">⚡</span>
    <span id="toastMsg">Mensaje</span>
  </div>

  <script>
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
