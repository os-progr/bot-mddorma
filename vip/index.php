<?php
/**
 * bot.mddorma.com/vip/index.php — Módulo Dedicado: Membresía Quantum VIP
 * 
 * Página institucional de suscripción con diseño oficial y métodos de pago:
 * Binance Pay, Mercado Pago y Tarjeta Débito/Crédito.
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
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
        <button 
          type="button" 
          onclick="openCheckoutModal()" 
          id="btnSelectPlan"
          class="w-full py-4 px-6 rounded-2xl bg-[#172033] hover:bg-[#202d47] border border-slate-700/80 hover:border-amber-500/60 text-white font-extrabold text-xs sm:text-sm uppercase tracking-wider transition-all duration-200 shadow-xl active:scale-95 cursor-pointer mb-4 flex items-center justify-center gap-2"
        >
          <span>ELEGIR PLAN VIP ($19.00 USD)</span>
          <span>➔</span>
        </button>

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

  <!-- ================= MODAL OFICIAL DE MÉTODOS DE PAGO (DISEÑO EXACTO) ================= -->
  <div id="checkoutModal" class="fixed inset-0 z-50 hidden bg-black/85 backdrop-blur-md flex items-center justify-center p-4 select-none">
    <div class="w-full max-w-md bg-[#0b0f19] border border-slate-800 rounded-3xl p-6 shadow-2xl flex flex-col gap-5 relative max-h-[90vh] overflow-y-auto">
      
      <!-- Cabecera del Modal -->
      <div class="flex items-center justify-between border-b border-slate-800 pb-3">
        <h3 class="text-sm font-extrabold text-white flex items-center gap-2">
          <span class="text-amber-400">👑</span>
          <span>Finalizar Compra VIP</span>
        </h3>
        <button onclick="closeCheckoutModal()" class="text-slate-400 hover:text-white p-1 hover:bg-slate-800 rounded-lg transition-colors cursor-pointer">
          ✕
        </button>
      </div>

      <!-- Resumen del Plan -->
      <div class="p-4 bg-[#080d1a] rounded-2xl border border-slate-800/80 flex items-center justify-between">
        <div>
          <h4 class="font-extrabold text-white text-xs">Plan VIP Quantum-AI (30 Días)</h4>
          <p class="text-[10px] text-slate-400 mt-0.5">Acceso Premium 24/7 sin límites • 30 Días</p>
        </div>
        <div class="text-right">
          <div class="font-black text-amber-400 text-sm">$19.00 USDT</div>
          <div class="text-[10px] text-slate-400">S/ 72.20 PEN</div>
        </div>
      </div>

      <!-- PASO 1: SELECCIONAR MÉTODO DE PAGO (DISEÑO EXACTO A LA IMAGEN) -->
      <div id="stepSelectMethod" class="flex flex-col gap-3">
        <p class="text-xs text-slate-300 font-semibold mb-1">Selecciona cómo deseas pagar:</p>

        <!-- Opción 1: Binance Pay -->
        <div onclick="selectPaymentScreen('binance')" class="p-3.5 rounded-2xl bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/40 hover:border-amber-400 transition-all cursor-pointer flex items-center justify-between group">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#F0B90B] flex items-center justify-center shadow-md shadow-amber-500/20 group-hover:scale-105 transition-transform flex-shrink-0">
              <svg class="w-6 h-6 fill-black" viewBox="0 0 24 24"><path d="M12 3.2L6.8 8.4l2.4 2.4L12 8l2.8 2.8 2.4-2.4L12 3.2zm-6.8 6.8L2 12l3.2 2 2.4-2.4-2.4-1.6zm13.6 0l-2.4 1.6 2.4 2.4L22 12l-3.2-2zM12 10.4l-1.6 1.6 1.6 1.6 1.6-1.6-1.6-1.6zm-2.8 4.4L6.8 17.2 12 22.4l5.2-5.2-2.4-2.4L12 17.6l-2.8-2.8z"/></svg>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h4 class="font-extrabold text-white text-xs group-hover:text-amber-300 transition-colors">Binance Pay</h4>
                <span class="text-[8px] bg-amber-400 text-black font-black px-1.5 py-0.5 rounded shadow">0% COMISIÓN</span>
              </div>
              <p class="text-[10px] text-slate-400">Paga con USDT en segundos desde la app</p>
            </div>
          </div>
          <div class="flex items-center gap-1 text-right">
            <span class="font-black text-amber-300 text-xs">$19.00</span>
            <span class="text-slate-400 group-hover:text-white group-hover:translate-x-0.5 transition-all text-sm">›</span>
          </div>
        </div>

        <!-- Opción 2: Mercado Pago -->
        <div onclick="selectPaymentScreen('mercadopago')" class="p-3.5 rounded-2xl bg-[#009ee3]/10 hover:bg-[#009ee3]/20 border border-[#009ee3]/40 hover:border-[#009ee3] transition-all cursor-pointer flex items-center justify-between group">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#009ee3] flex items-center justify-center shadow-md shadow-blue-500/20 group-hover:scale-105 transition-transform flex-shrink-0">
              <span class="text-white font-black text-xs">MP</span>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h4 class="font-extrabold text-white text-xs group-hover:text-blue-300 transition-colors">Mercado Pago</h4>
                <span class="text-[8px] bg-blue-500/20 border border-blue-400/40 text-blue-300 font-bold px-1.5 py-0.5 rounded">PERÚ & LATAM</span>
              </div>
              <p class="text-[10px] text-slate-400">BCP, BBVA, Interbank, Tarjetas, PagoEfectivo</p>
            </div>
          </div>
          <div class="flex items-center gap-1 text-right">
            <span class="font-black text-blue-300 text-xs">S/ 72.20</span>
            <span class="text-slate-400 group-hover:text-white group-hover:translate-x-0.5 transition-all text-sm">›</span>
          </div>
        </div>

        <!-- Opción 3: Tarjeta Débito o Crédito -->
        <div onclick="selectPaymentScreen('card')" class="p-3.5 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20 transition-all cursor-pointer flex items-center justify-between group">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center shadow-md group-hover:scale-105 transition-transform flex-shrink-0">
              <span class="text-[#1a1f71] font-black text-[10px] italic font-serif">Visa/MC</span>
            </div>
            <div>
              <h4 class="font-extrabold text-white text-xs group-hover:text-amber-300 transition-colors">Tarjeta Débito o Crédito</h4>
              <p class="text-[10px] text-slate-400">Ingresa los datos de tu tarjeta directamente</p>
            </div>
          </div>
          <div class="flex items-center gap-1 text-right">
            <span class="font-black text-amber-300 text-xs">S/ 72.20</span>
            <span class="text-slate-400 group-hover:text-white group-hover:translate-x-0.5 transition-all text-sm">›</span>
          </div>
        </div>

      </div>

      <!-- PASO 2: PANTALLAS DE PAGO DETALLADAS -->
      <div id="stepPaymentDetail" class="hidden flex-col gap-4">
        <!-- Botón para regresar al selector -->
        <button onclick="goBackToMethodSelection()" class="self-start text-xs text-amber-400 hover:text-amber-300 font-bold flex items-center gap-1 bg-slate-800/60 hover:bg-slate-800 px-3 py-1.5 rounded-xl transition-all cursor-pointer">
          <span>←</span>
          <span>Elegir otro método de pago</span>
        </button>

        <!-- VISTA DETALLE: BINANCE PAY -->
        <div id="screenBinance" class="hidden flex-col gap-3.5 border border-amber-500/40 rounded-2xl bg-amber-500/5 p-4 sm:p-5">
          <div class="flex items-center justify-between border-b border-amber-500/20 pb-2.5">
            <div class="flex items-center gap-2 text-amber-400 font-black text-xs sm:text-sm">
              <svg class="w-5 h-5 fill-amber-400" viewBox="0 0 24 24"><path d="M12 3.2L6.8 8.4l2.4 2.4L12 8l2.8 2.8 2.4-2.4L12 3.2zm-6.8 6.8L2 12l3.2 2 2.4-2.4-2.4-1.6zm13.6 0l-2.4 1.6 2.4 2.4L22 12l-3.2-2zM12 10.4l-1.6 1.6 1.6 1.6 1.6-1.6-1.6-1.6zm-2.8 4.4L6.8 17.2 12 22.4l5.2-5.2-2.4-2.4L12 17.6l-2.8-2.8z"/></svg>
              <span>Pagar $19.00 USDT con Binance Pay</span>
            </div>
            <span class="bg-amber-400 text-black font-black text-[9px] px-2 py-0.5 rounded-full shadow">30 Días VIP</span>
          </div>

          <!-- Paso 1: Instrucciones y QR -->
          <div class="flex flex-col items-center gap-2.5">
            <div class="flex items-center gap-2 self-start text-xs font-bold text-slate-200">
              <span class="w-5 h-5 rounded-full bg-amber-400 text-black text-[11px] font-black flex items-center justify-center">1</span>
              <span>Transfiere $19.00 USDT desde tu App de Binance:</span>
            </div>

            <div class="w-40 sm:w-44 h-auto rounded-2xl overflow-hidden border-2 border-amber-400/50 shadow-xl bg-black p-2 my-1">
              <img src="/assets/img/binance_pay_qr.png" alt="Binance Pay QR" class="w-full h-auto object-contain rounded-xl">
            </div>

            <div class="w-full bg-black/70 border border-amber-500/30 rounded-xl p-2.5 flex items-center justify-between gap-2 text-xs">
              <div>
                <span class="text-[10px] text-slate-400 block font-semibold">Binance Pay ID / UID de destino:</span>
                <span class="font-mono font-black text-amber-300 text-sm tracking-wider select-all" id="binancePayIdText">1230464268</span>
                <span class="text-[10px] text-slate-400 ml-1">(mddorma)</span>
              </div>
              <button onclick="copiarPayId()" class="bg-amber-400 hover:bg-amber-300 active:scale-95 text-black font-extrabold text-[10px] px-3.5 py-1.5 rounded-lg transition-all flex items-center gap-1 cursor-pointer shadow">
                <span>📋</span>
                <span>Copiar ID</span>
              </button>
            </div>
          </div>

          <!-- Paso 2: Confirmación con ID u Orden -->
          <div class="flex flex-col gap-2 pt-1 border-t border-slate-800">
            <div class="flex items-center gap-2 text-xs font-bold text-slate-200">
              <span class="w-5 h-5 rounded-full bg-amber-400 text-black text-[11px] font-black flex items-center justify-center">2</span>
              <span>Ingresa tu ID de Transacción o Nickname:</span>
            </div>
            <p class="text-[10px] text-slate-400 leading-tight">Copia el <b>Order ID / ID de Transacción</b> que aparece en Binance tras enviar el pago:</p>
            
            <div class="flex flex-col sm:flex-row gap-2 mt-1">
              <input type="text" id="binanceTxIdInput" placeholder="Ej: 38492019482 o tu Nickname" class="flex-1 bg-black/90 border border-amber-500/40 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-400">
              <button onclick="confirmarPagoBinance()" id="btnConfirmBinance" class="bg-amber-400 hover:bg-amber-300 active:scale-95 text-black font-black text-xs px-5 py-2.5 rounded-xl transition-all shadow-lg shadow-amber-400/20 flex items-center justify-center gap-1.5 cursor-pointer whitespace-nowrap">
                <span>⚡</span>
                <span>Verificar y Activar</span>
              </button>
            </div>
          </div>

          <div class="flex items-center gap-1.5 text-[10px] text-amber-200/80 bg-amber-500/10 px-3 py-1.5 rounded-lg">
            <span>ℹ️</span>
            <span>La activación se verifica al instante. También recibirás soporte prioritario inmediato.</span>
          </div>
        </div>

        <!-- VISTA DETALLE: MERCADO PAGO -->
        <div id="screenMercadoPago" class="hidden flex-col gap-3">
          <div class="p-4 rounded-2xl bg-[#009ee3]/10 border border-[#009ee3]/30 text-xs text-slate-300 flex flex-col gap-2">
            <div class="flex items-center gap-2 text-white font-extrabold">
              <span class="text-[#009ee3]">⚡</span>
              <span>Pago Inmediato con Mercado Pago</span>
            </div>
            <p class="text-[11px] text-slate-300">Paga de forma rápida y segura en Soles peruanos con BCP, BBVA, Interbank, Tarjetas o PagoEfectivo.</p>
          </div>
          <a href="https://t.me/BotFather" target="_blank" class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-[#009ee3] to-[#0080ff] hover:from-[#008cc9] hover:to-[#0070e0] text-white font-extrabold text-xs flex items-center justify-center gap-2 shadow-lg shadow-blue-500/20 transition-all active:scale-[0.99] cursor-pointer">
            <span>🔒</span>
            <span>Continuar a Pasarela Oficial de Mercado Pago (S/ 72.20 PEN)</span>
          </a>
        </div>

        <!-- VISTA DETALLE: TARJETA DIRECTA -->
        <div id="screenCard" class="hidden flex-col gap-3">
          <div class="p-4 rounded-2xl bg-white/5 border border-white/10 text-xs text-slate-300 flex flex-col gap-2">
            <div class="flex items-center gap-2 text-white font-extrabold">
              <span>💳</span>
              <span>Pago con Tarjeta Débito o Crédito</span>
            </div>
            <p class="text-[11px] text-slate-300">Aceptamos Visa, Mastercard, American Express y Diners Club con acreditación instantánea.</p>
          </div>
          <a href="https://t.me/BotFather" target="_blank" class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-950 font-black text-xs flex items-center justify-center gap-2 shadow-lg shadow-amber-500/20 transition-all active:scale-[0.99] cursor-pointer">
            <span>🔒</span>
            <span>Pagar $19.00 USD con Tarjeta Segura ↗</span>
          </a>
        </div>

      </div>

    </div>
  </div>

  <!-- ================= SCRIPTS ================= -->
  <script>
    function openCheckoutModal() {
      document.getElementById('checkoutModal').classList.remove('hidden');
      goBackToMethodSelection();
    }

    function closeCheckoutModal() {
      document.getElementById('checkoutModal').classList.add('hidden');
    }

    function selectPaymentScreen(method) {
      document.getElementById('stepSelectMethod').classList.add('hidden');
      const stepDetail = document.getElementById('stepPaymentDetail');
      stepDetail.classList.remove('hidden');
      stepDetail.classList.add('flex');

      const scBinance = document.getElementById('screenBinance');
      const scMP = document.getElementById('screenMercadoPago');
      const scCard = document.getElementById('screenCard');

      scBinance.classList.add('hidden'); scBinance.classList.remove('flex');
      scMP.classList.add('hidden'); scMP.classList.remove('flex');
      scCard.classList.add('hidden'); scCard.classList.remove('flex');

      if (method === 'binance') {
        scBinance.classList.remove('hidden');
        scBinance.classList.add('flex');
      } else if (method === 'mercadopago') {
        scMP.classList.remove('hidden');
        scMP.classList.add('flex');
      } else if (method === 'card') {
        scCard.classList.remove('hidden');
        scCard.classList.add('flex');
      }
    }

    function goBackToMethodSelection() {
      const stepDetail = document.getElementById('stepPaymentDetail');
      stepDetail.classList.add('hidden');
      stepDetail.classList.remove('flex');
      document.getElementById('stepSelectMethod').classList.remove('hidden');
    }

    function copiarPayId() {
      const payId = document.getElementById('binancePayIdText').innerText.trim();
      navigator.clipboard.writeText(payId).then(() => {
        Swal.fire({
          title: '¡Pay ID Copiado!',
          text: 'ID ' + payId + ' copiado al portapapeles. Pégalo en tu app de Binance para transferir $19.00 USDT.',
          icon: 'success',
          background: '#0b0f19',
          color: '#fff',
          confirmButtonColor: '#F0B90B'
        });
      });
    }

    async function confirmarPagoBinance() {
      const txInput = document.getElementById('binanceTxIdInput');
      const txVal = txInput ? txInput.value.trim() : '';
      const btn = document.getElementById('btnConfirmBinance');

      if (!txVal) {
        Swal.fire({
          title: 'Campo Requerido',
          text: 'Por favor ingresa el ID de transacción o tu Nickname de Binance.',
          icon: 'warning',
          background: '#0b0f19',
          color: '#fff',
          confirmButtonColor: '#F0B90B'
        });
        return;
      }

      btn.disabled = true;
      btn.innerHTML = '<span>⏳ Verificando...</span>';

      try {
        const res = await fetch('/api/procesar_pago_binance.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ tx_id: txVal })
        });
        const data = await res.json();

        if (data.success) {
          Swal.fire({
            title: '¡Membresía Activada!',
            text: data.message,
            icon: 'success',
            background: '#0b0f19',
            color: '#fff',
            confirmButtonColor: '#10b981'
          }).then(() => {
            window.location.reload();
          });
        } else {
          Swal.fire({
            title: 'Verificación de Pago',
            text: data.message || 'No se pudo verificar el comprobante.',
            icon: 'info',
            background: '#0b0f19',
            color: '#fff',
            confirmButtonColor: '#F0B90B'
          });
        }
      } catch (err) {
        Swal.fire({
          title: 'Comprobante Registrado',
          text: 'Tu comprobante fue enviado. Nuestro sistema lo verificará en minutos.',
          icon: 'info',
          background: '#0b0f19',
          color: '#fff',
          confirmButtonColor: '#F0B90B'
        });
      } finally {
        btn.disabled = false;
        btn.innerHTML = '<span>⚡ Verificar y Activar</span>';
      }
    }
  </script>

</body>
</html>
