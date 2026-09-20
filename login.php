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
        radial-gradient(circle at 50% 20%, rgba(245, 158, 11, 0.06) 0%, transparent 50%),
        radial-gradient(circle at 50% 80%, rgba(16, 185, 129, 0.03) 0%, transparent 60%);
    }

    /* Fix Chrome/Edge/Safari autofill yellow/cream background on dark inputs */
    input:-webkit-autofill,
    input:-webkit-autofill:hover, 
    input:-webkit-autofill:focus, 
    input:-webkit-autofill:active {
      -webkit-box-shadow: 0 0 0 1000px #080d19 inset !important;
      -webkit-text-fill-color: #f1f5f9 !important;
      transition: background-color 5000s ease-in-out 0s;
      caret-color: #f59e0b;
    }

    /* Institutional button polish */
    .btn-terminal {
      background: linear-gradient(180deg, #fbbf24 0%, #f59e0b 100%);
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.4), 0 0 20px rgba(245, 158, 11, 0.2);
      transition: transform 120ms ease, box-shadow 150ms ease, background 150ms ease;
    }
    .btn-terminal:hover {
      background: linear-gradient(180deg, #fcd34d 0%, #fbbf24 100%);
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.4), 0 0 28px rgba(245, 158, 11, 0.35);
    }
    .btn-terminal:active {
      transform: scale(0.985);
    }
  </style>
  <link rel="icon" type="image/png" href="/assets/logo.png">
</head>
<body class="font-sans antialiased min-h-screen flex flex-col justify-center selection:bg-amber-500 selection:text-black">

  <!-- ================= CUERPO CENTRAL DE LOGIN ================= -->
  <main class="flex-1 flex flex-col items-center justify-center p-4 my-8">
    
    <!-- Header de Marca: Logo + Título -->
    <div class="text-center mb-7 flex flex-col items-center">
      <div class="w-16 h-16 relative mb-3 flex items-center justify-center">
        <div class="absolute inset-0 bg-amber-500/15 rounded-2xl blur-xl"></div>
        <img src="/assets/logo.png" alt="Quantum.AI Logo" class="w-14 h-14 object-contain relative z-10 drop-shadow-[0_0_20px_rgba(245,158,11,0.5)]">
      </div>

      <h1 class="text-2xl font-black tracking-tight text-white flex items-center justify-center">
        QUANTUM<span class="text-amber-400">.AI</span>
      </h1>
      <p class="text-[11px] font-mono tracking-widest text-slate-400 mt-1 uppercase">
        Terminal Cuántico • Trading Algorítmico
      </p>
    </div>

    <!-- Tarjeta Principal de Acceso (Obsidian Glass) -->
    <div class="w-full max-w-md bg-[#0a0f1d]/95 border border-slate-800/80 rounded-2xl p-6 sm:p-8 shadow-[0_25px_60px_rgba(0,0,0,0.65)] backdrop-blur-xl relative">
      
      <div class="text-center mb-6">
        <h2 id="formTitle" class="text-xl font-bold text-white tracking-tight">Bienvenido al Terminal</h2>
        <p id="formSubtitle" class="text-xs text-slate-400 mt-1">Introduce tus credenciales para acceder al radar institucional</p>
      </div>

      <!-- Banner de Alerta de Sesión Expirada o Errores -->
      <div id="alertBox" class="<?php echo $mostrar_alerta_expirado ? 'flex' : 'hidden'; ?> items-start gap-2.5 p-3 rounded-xl bg-rose-950/40 border border-rose-500/40 text-rose-300 text-xs mb-4">
        <span class="text-rose-400 text-sm shrink-0">🚫</span>
        <div id="alertText" class="leading-relaxed">
          Tu sesión ha expirado por inactividad o seguridad. Por favor, valida tus credenciales de acceso institucional.
        </div>
      </div>

      <!-- Formulario de Acceso Directo -->
      <form id="loginForm" onsubmit="submitAuthForm(event)" class="space-y-4">
        
        <!-- Campo Nombre (Solo visible en Registro) -->
        <div id="fieldNombreGroup" class="hidden">
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nombre Completo</label>
          <div class="relative flex items-center">
            <span class="absolute left-3.5 text-slate-500 flex items-center pointer-events-none">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </span>
            <input id="authNombre" type="text" placeholder="Ej: Alex Quant" class="w-full bg-[#080d19] border border-slate-800 hover:border-slate-700 focus:border-amber-500/80 focus:ring-2 focus:ring-amber-500/15 rounded-xl pl-10 pr-4 py-2.5 text-slate-100 text-xs outline-none transition duration-150 placeholder:text-slate-600">
          </div>
        </div>

        <!-- Campo Correo Electrónico -->
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Correo Electrónico</label>
          <div class="relative flex items-center">
            <span class="absolute left-3.5 text-slate-500 flex items-center pointer-events-none">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </span>
            <input id="authEmail" type="email" required placeholder="tu@correo.com" class="w-full bg-[#080d19] border border-slate-800 hover:border-slate-700 focus:border-amber-500/80 focus:ring-2 focus:ring-amber-500/15 rounded-xl pl-10 pr-4 py-2.5 text-slate-100 text-xs font-mono outline-none transition duration-150 placeholder:text-slate-600">
          </div>
        </div>

        <!-- Campo Contraseña -->
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Contraseña</label>
          <div class="relative flex items-center">
            <span class="absolute left-3.5 text-slate-500 flex items-center pointer-events-none">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </span>
            <input id="authPassword" type="password" required placeholder="••••••••••••" class="w-full bg-[#080d19] border border-slate-800 hover:border-slate-700 focus:border-amber-500/80 focus:ring-2 focus:ring-amber-500/15 rounded-xl pl-10 pr-10 py-2.5 text-slate-100 text-xs font-mono outline-none transition duration-150 placeholder:text-slate-600">
            <button type="button" onclick="togglePassVisibility()" class="absolute right-3.5 text-slate-500 hover:text-slate-300 transition cursor-pointer p-0.5" title="Mostrar/Ocultar contraseña">
              <svg id="eyeOpen" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              <svg id="eyeClosed" class="w-4 h-4 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
            </button>
          </div>
        </div>

        <!-- Opciones: Recordar 30 días y Recuperar -->
        <div class="flex items-center justify-between text-xs pt-1">
          <label class="flex items-center gap-2 cursor-pointer select-none text-slate-400 hover:text-slate-300 transition">
            <input id="chkRemember" type="checkbox" checked class="w-4 h-4 rounded border-slate-700 bg-slate-900 text-amber-500 focus:ring-0 accent-amber-500 cursor-pointer">
            <span>Recordar sesión (30 días)</span>
          </label>
          <a href="#" onclick="alert('Contacta al administrador para restablecer tu contraseña institucional.'); return false;" class="text-amber-400/90 hover:text-amber-300 transition">
            ¿Olvidaste tu contraseña?
          </a>
        </div>

        <!-- Botón de Acción Principal -->
        <button type="submit" id="btnSubmitAuth" class="btn-terminal w-full text-slate-950 font-bold py-3 px-4 rounded-xl text-xs sm:text-sm tracking-wide flex items-center justify-center gap-2 cursor-pointer mt-3">
          <span id="btnSubmitText">Ingresar al Terminal Cuántico</span>
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
        </button>

      </form>

    </div>

    <!-- Alternar Login / Registro y Retorno -->
    <div class="text-center mt-6 space-y-3">
      <div class="text-xs text-slate-400">
        <span id="toggleQuestion">¿No tienes una cuenta institucional?</span>
        <button onclick="toggleAuthMode()" id="toggleBtn" class="text-amber-400 hover:text-amber-300 font-semibold ml-1 cursor-pointer transition">
          Regístrate aquí (7 Días Gratis VIP)
        </button>
      </div>
      <div>
        <a href="/" class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-slate-300 transition py-1.5 px-3 rounded-lg hover:bg-slate-900/60">
          <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
          <span>Volver al Radar Cuántico</span>
        </a>
      </div>
    </div>

  </main>

  <!-- ================= LÓGICA DE CONTROL JS ================= -->
  <script>
    let isRegisterMode = false;

    function togglePassVisibility() {
      const p = document.getElementById('authPassword');
      const eyeOpen = document.getElementById('eyeOpen');
      const eyeClosed = document.getElementById('eyeClosed');
      if (p.type === 'password') {
        p.type = 'text';
        if (eyeOpen && eyeClosed) {
          eyeOpen.classList.add('hidden');
          eyeClosed.classList.remove('hidden');
        }
      } else {
        p.type = 'password';
        if (eyeOpen && eyeClosed) {
          eyeOpen.classList.remove('hidden');
          eyeClosed.classList.add('hidden');
        }
      }
    }

    function toggleAuthMode() {
      isRegisterMode = !isRegisterMode;
      const title = document.getElementById('formTitle');
      const subtitle = document.getElementById('formSubtitle');
      const fName = document.getElementById('fieldNombreGroup');
      const btnText = document.getElementById('btnSubmitText');
      const q = document.getElementById('toggleQuestion');
      const btn = document.getElementById('toggleBtn');
      const alert = document.getElementById('alertBox');

      if (alert) alert.classList.add('hidden');

      if (isRegisterMode) {
        title.textContent = "Crear Cuenta Institucional";
        if (subtitle) subtitle.textContent = "Acceso instantáneo con 7 días de prueba VIP incluida";
        fName.classList.remove('hidden');
        btnText.textContent = "Crear Cuenta (7 Días Gratis)";
        q.textContent = "¿Ya tienes una cuenta?";
        btn.textContent = "Inicia Sesión aquí";
      } else {
        title.textContent = "Bienvenido al Terminal";
        if (subtitle) subtitle.textContent = "Introduce tus credenciales para acceder al radar institucional";
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
      btn.innerHTML = '<svg class="animate-spin w-4 h-4 text-slate-950 inline-block mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> <span>Conectando con Enclave Seguro...</span>';

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
        btn.innerHTML = `<span id="btnSubmitText">${isRegisterMode ? 'Crear Cuenta (7 Días Gratis)' : 'Ingresar al Terminal Cuántico'}</span> <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>`;

        if (d && d.success) {
          window.location.href = '/';
        } else {
          if (alertBox && alertText) {
            alertBox.className = "flex items-start gap-2.5 p-3 rounded-xl bg-rose-950/40 border border-rose-500/40 text-rose-300 text-xs mb-4";
            alertText.textContent = d.message || 'Error en las credenciales institucionales.';
            alertBox.classList.remove('hidden');
          }
        }
      })
      .catch(() => {
        btn.disabled = false;
        btn.innerHTML = `<span id="btnSubmitText">${isRegisterMode ? 'Crear Cuenta (7 Días Gratis)' : 'Ingresar al Terminal Cuántico'}</span> <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>`;
        if (alertBox && alertText) {
          alertBox.className = "flex items-start gap-2.5 p-3 rounded-xl bg-rose-950/40 border border-rose-500/40 text-rose-300 text-xs mb-4";
          alertText.textContent = 'Error de conexión con el servidor.';
          alertBox.classList.remove('hidden');
        }
      });
    }
  </script>
</body>
</html>
