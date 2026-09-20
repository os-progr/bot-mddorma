<?php
/**
 * login.php — PÁGINA DE ACCESO INSTITUCIONAL DEDICADA
 * 
 * Separada al 100% de la web de doramas.
 * Utiliza QUANTUM_BOT_SESSID (30 días de persistencia) y conecta directamente a MySQL.
 */
declare(strict_types=1);

define('AUTH_LIB_ONLY', true);
require_once __DIR__ . '/api/auth.php';

// Si ya tiene sesión activa válida, redirigir al terminal
if (!empty($_SESSION['id_usuario']) && !empty($pdo)) {
    header("Location: /");
    exit;
}

$mensaje = $_GET['mensaje'] ?? '';
$mostrar_alerta_expirado = ($mensaje === 'session_expired');
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceso al Terminal Cuántico | QUANTUM.AI</title>
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
            gold: {
              400: '#fbbf24',
              500: '#f59e0b',
              600: '#d97706',
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
        radial-gradient(circle at 50% 25%, rgba(245, 158, 11, 0.05) 0%, transparent 55%),
        radial-gradient(circle at 50% 70%, rgba(16, 185, 129, 0.03) 0%, transparent 60%);
    }
    .badge-pulse {
      animation: pulseDot 2s infinite;
    }
    @keyframes pulseDot {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.35; transform: scale(0.85); }
    }
    .cube-glow {
      box-shadow: 0 0 35px rgba(245, 158, 11, 0.25);
    }
    .btn-gold-glow {
      box-shadow: 0 4px 20px rgba(245, 158, 11, 0.35);
    }
    .btn-gold-glow:hover {
      box-shadow: 0 6px 25px rgba(245, 158, 11, 0.45);
    }
  </style>
  <link rel="icon" type="image/png" href="/assets/logo.png">
</head>
<body class="font-sans antialiased min-h-screen flex flex-col justify-center selection:bg-amber-500 selection:text-black">

  <!-- ================= CUERPO CENTRAL DE LOGIN ================= -->
  <main class="flex-1 flex flex-col items-center justify-center p-4 my-6">
    
    <!-- Logo Central Quantum.AI con Cubo Isométrico -->
    <div class="text-center mb-6 flex flex-col items-center">
      <div class="w-20 h-20 relative mb-2 flex items-center justify-center">
        <img src="/assets/logo.png" alt="Quantum.AI Logo" class="w-full h-full object-contain drop-shadow-[0_0_30px_rgba(245,158,11,0.55)]">
      </div>

      <h1 class="text-2xl font-black tracking-tight text-white flex items-center gap-1">
        QUANTUM <span class="text-amber-400">.AI</span>
      </h1>
      <p class="text-[10px] font-mono uppercase tracking-widest text-slate-400 mt-1">
        TERMINAL INSTITUCIONAL DE PRECISIÓN CUÁNTICA & TRADING ALGORÍTMICO
      </p>
    </div>

    <!-- Tarjeta Principal de Acceso (Obsidian Glass) -->
    <div class="w-full max-w-md bg-[#0a0f1d]/90 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl backdrop-blur-md relative">
      
      <div class="text-center mb-5">
        <h2 id="formTitle" class="text-lg font-bold text-white tracking-tight">Bienvenido al Terminal</h2>
      </div>

      <!-- Banner de Alerta de Sesión Expirada o Errores -->
      <div id="alertBox" class="<?php echo $mostrar_alerta_expirado ? 'flex' : 'hidden'; ?> items-start gap-2.5 p-3 rounded-xl bg-[#1c1216] border border-rose-500/40 text-rose-300 text-xs mb-4">
        <span class="text-rose-400 text-sm shrink-0 mt-0.5">🚫</span>
        <div id="alertText" class="leading-relaxed">
          Tu sesión ha expirado por inactividad o seguridad. Por favor, valida tus credenciales de acceso institucional.
        </div>
      </div>

      <!-- Formulario de Acceso Directo -->
      <form id="loginForm" onsubmit="submitAuthForm(event)" class="space-y-4">
        
        <!-- Campo Nombre (Solo visible en Registro) -->
        <div id="fieldNombreGroup" class="hidden">
          <label class="block text-xs font-bold text-slate-300 mb-1">Nombre Completo:</label>
          <div class="relative flex items-center">
            <span class="absolute left-3 text-slate-500">👤</span>
            <input id="authNombre" type="text" placeholder="Ej: Alex Quant" class="w-full bg-[#050811] border border-slate-700 focus:border-amber-400 rounded-xl pl-9 pr-3 py-2.5 text-white text-xs outline-none transition">
          </div>
        </div>

        <!-- Campo Correo Electrónico -->
        <div>
          <label class="block text-xs font-bold text-slate-300 mb-1">Correo Electrónico:</label>
          <div class="relative flex items-center">
            <span class="absolute left-3 text-slate-500">✉️</span>
            <input id="authEmail" type="email" required placeholder="tu@correo.com" class="w-full bg-[#050811] border border-slate-700 focus:border-amber-400 rounded-xl pl-9 pr-16 py-2.5 text-white text-xs font-mono outline-none transition">
            <span class="absolute right-2.5 inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-emerald-500/15 border border-emerald-500/40 text-[9px] font-mono text-emerald-300 font-bold">
              <span>🔒</span> DMA
            </span>
          </div>
        </div>

        <!-- Campo Contraseña -->
        <div>
          <label class="block text-xs font-bold text-slate-300 mb-1">Contraseña:</label>
          <div class="relative flex items-center">
            <span class="absolute left-3 text-slate-500">🔒</span>
            <input id="authPassword" type="password" required placeholder="••••••••••••" class="w-full bg-[#050811] border border-slate-700 focus:border-amber-400 rounded-xl pl-9 pr-10 py-2.5 text-white text-xs font-mono outline-none transition">
            <button type="button" onclick="togglePassVisibility()" class="absolute right-3 text-slate-400 hover:text-white transition cursor-pointer" title="Mostrar/Ocultar contraseña">
              <span id="passEyeIcon">👁️</span>
            </button>
          </div>
        </div>

        <!-- Opciones: Recordar 30 días y Recuperar -->
        <div class="flex items-center justify-between text-xs pt-1">
          <label class="flex items-center gap-2 cursor-pointer select-none text-slate-300">
            <input id="chkRemember" type="checkbox" checked class="w-4 h-4 rounded accent-amber-500 cursor-pointer">
            <span>Recordar sesión (30 días)</span>
          </label>
          <a href="#" onclick="alert('Contacta al administrador para restablecer tu contraseña institucional.'); return false;" class="text-amber-400 hover:text-amber-300 text-xs transition">
            ¿Olvidaste tu contraseña?
          </a>
        </div>

        <!-- Gran Botón Dorado de Acción -->
        <button type="submit" id="btnSubmitAuth" class="w-full bg-gradient-to-r from-amber-400 via-amber-500 to-amber-600 hover:from-amber-300 hover:to-amber-500 text-slate-950 font-black py-3 rounded-xl text-sm transition btn-gold-glow flex items-center justify-center gap-2 cursor-pointer mt-2">
          <span id="btnSubmitText">Ingresar al Terminal Cuántico</span>
          <span>→</span>
        </button>

      </form>

      <!-- Telegram OTP Fast Enclave -->
      <div class="mt-4 pt-3 border-t border-slate-800/80 text-center text-[11px] font-mono text-slate-400 flex items-center justify-center gap-1.5">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
        <span>Fast Enclave:</span>
        <a href="https://t.me/BotFather" target="_blank" class="text-amber-400 hover:text-amber-300 flex items-center gap-1 font-bold">
          <span>▷</span> Telegram VIP OTP
        </a>
      </div>

    </div>

    <!-- Enlace Alternar Login / Registro -->
    <div class="text-center mt-5 text-xs text-slate-400">
      <span id="toggleQuestion">¿No tienes una cuenta institucional?</span>
      <button onclick="toggleAuthMode()" id="toggleBtn" class="text-amber-400 hover:text-amber-300 font-bold ml-1 transition cursor-pointer">
        Regístrate aquí (7 Días Gratis VIP)
      </button>
      <div class="text-[10px] font-mono text-slate-600 mt-1.5">
        Cifrado SHA-256 DMA Enclave • Protocolo FIX 4.4 • LD4 Equinix
      </div>
      <div class="mt-4 flex items-center justify-center gap-4 text-xs font-semibold">
        <a href="/" class="text-slate-400 hover:text-white transition flex items-center gap-1">
          <span>⚡</span> Volver al Radar Cuántico
        </a>
        <span class="text-slate-700">|</span>
        <a href="/telegram/" class="text-amber-400 hover:text-amber-300 transition flex items-center gap-1">
          <span>📱</span> Mi Bot de Telegram & VIP
        </a>
      </div>
    </div>

  </main>



  <!-- ================= LÓGICA DE CONTROL JS ================= -->
  <script>
    let isRegisterMode = false;

    function togglePassVisibility() {
      const p = document.getElementById('authPassword');
      const icon = document.getElementById('passEyeIcon');
      if (p.type === 'password') {
        p.type = 'text';
        icon.textContent = '🔒';
      } else {
        p.type = 'password';
        icon.textContent = '👁️';
      }
    }

    function toggleAuthMode() {
      isRegisterMode = !isRegisterMode;
      const title = document.getElementById('formTitle');
      const fName = document.getElementById('fieldNombreGroup');
      const btnText = document.getElementById('btnSubmitText');
      const q = document.getElementById('toggleQuestion');
      const btn = document.getElementById('toggleBtn');
      const alert = document.getElementById('alertBox');

      if (alert) alert.classList.add('hidden');

      if (isRegisterMode) {
        title.textContent = "Crear Cuenta Institucional";
        fName.classList.remove('hidden');
        btnText.textContent = "Crear Cuenta (7 Días Gratis)";
        q.textContent = "¿Ya tienes una cuenta?";
        btn.textContent = "Inicia Sesión aquí";
      } else {
        title.textContent = "Bienvenido al Terminal";
        fName.classList.add('hidden');
        btnText.textContent = "Ingresar al Terminal Cuántico";
        q.textContent = "¿No tienes una cuenta institucional?";
        btn.textContent = "Regístrate aquí (7 Días Gratis VIP)";
      }
    }

    function submitAuthForm(e) {
      e.preventDefault();
      const email = document.getElementById('authEmail').value.trim();
      const pass = document.getElementById('authPassword').value;
      const nombre = document.getElementById('authNombre').value.trim();
      const btn = document.getElementById('btnSubmitAuth');
      const alertBox = document.getElementById('alertBox');
      const alertText = document.getElementById('alertText');

      if (!email || !pass) return;

      btn.disabled = true;
      btn.innerHTML = '<span>⏳ Conectando con Enclave Seguro...</span>';

      const action = isRegisterMode ? 'register' : 'login';
      const payload = isRegisterMode ? { correo: email, password: pass, nombre } : { correo: email, password: pass };

      fetch(`/api/auth.php?action=${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(r => r.json())
      .then(d => {
        btn.disabled = false;
        btn.innerHTML = `<span id="btnSubmitText">${isRegisterMode ? 'Crear Cuenta' : 'Ingresar al Terminal Cuántico'}</span> <span>→</span>`;

        if (d && d.success) {
          // Redirigir al terminal principal
          window.location.href = '/';
        } else {
          if (alertBox && alertText) {
            alertBox.className = "flex items-start gap-2.5 p-3 rounded-xl bg-[#1c1216] border border-rose-500/40 text-rose-300 text-xs mb-4";
            alertText.textContent = d.message || 'Error en las credenciales institucionales.';
            alertBox.classList.remove('hidden');
          }
        }
      })
      .catch(() => {
        btn.disabled = false;
        btn.innerHTML = `<span id="btnSubmitText">${isRegisterMode ? 'Crear Cuenta' : 'Ingresar al Terminal Cuántico'}</span> <span>→</span>`;
        if (alertBox && alertText) {
          alertBox.className = "flex items-start gap-2.5 p-3 rounded-xl bg-[#1c1216] border border-rose-500/40 text-rose-300 text-xs mb-4";
          alertText.textContent = 'Error de conexión con el servidor.';
          alertBox.classList.remove('hidden');
        }
      });
    }
  </script>
</body>
</html>
