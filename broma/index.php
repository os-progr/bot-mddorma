<?php
/**
 * bot.mddorma.com/broma/index.php — Panel de Administración Institucional (Enclave Broma)
 * 
 * Acceso estrictamente reservado a administradores del sistema.
 */
declare(strict_types=1);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');

define('AUTH_LIB_ONLY', true);
require_once dirname(__DIR__) . '/api/auth.php';

// 1. Verificar si hay sesión
if (empty($_SESSION['id_usuario']) || empty($pdo)) {
    header("Location: /login.php?redirect=" . urlencode('/broma/'));
    exit;
}

// 2. Verificar identidad única autorizada (Cifrada criptográficamente)
$admin = null;
try {
    $stmt = $pdo->prepare("SELECT id_usuario, nombre, correo, rol, es_premium, foto_perfil FROM usuarios WHERE id_usuario = ? LIMIT 1");
    $stmt->execute([(int)$_SESSION['id_usuario']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

// Huella SHA-256 de la única identidad con acceso al enclave (sin exponer correos en texto plano)
const MASTER_IDENTITY_HASH = 'd0c972995fa361ce664cf1efc8fb447ca345f301e6f8372166f94d7e743ae1fc';
$user_email_hash = hash('sha256', mb_strtolower(trim((string)($admin['correo'] ?? '')), 'UTF-8'));
$is_master_admin = $admin && hash_equals(MASTER_IDENTITY_HASH, $user_email_hash);

// Si no coincide exactamente con la única identidad autorizada, denegar acceso inmediato
if (!$is_master_admin) {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="es" class="dark">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>403 Acceso Denegado | Enclave Privado</title>
      <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-[#05070d] text-slate-300 min-h-screen flex items-center justify-center p-4 font-sans">
      <div class="max-w-md w-full bg-[#0b0f19] border border-red-500/30 rounded-2xl p-8 text-center shadow-2xl shadow-red-950/20">
        <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-400 text-3xl">
          🔒
        </div>
        <h1 class="text-2xl font-bold text-white mb-2">Acceso Restringido</h1>
        <p class="text-sm text-slate-400 mb-6">Este enclave operativo está estrictamente restringido a la clave de identidad maestra autorizada.</p>
        <div class="bg-red-950/20 border border-red-900/30 rounded-xl p-3 text-xs text-red-300 mb-6 font-mono">
          ESTADO: NO AUTORIZADO (403)<br>
          IDENTIDAD: BLOQUEADA
        </div>
        <a href="/" class="inline-flex items-center justify-center px-6 py-2.5 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-medium text-sm hover:from-cyan-400 hover:to-blue-500 transition shadow-lg shadow-cyan-500/20">
          ← Volver al Terminal
        </a>
      </div>
    </body>
    </html>
    <?php
    exit;
}

$admin_nombre = htmlspecialchars($admin['nombre'] ?: 'Administrador');
$admin_correo = htmlspecialchars($admin['correo'] ?: '');
$inicial = mb_strtoupper(mb_substr($admin['nombre'] ?: 'A', 0, 1));
?>
<!DOCTYPE html>
<html lang="es" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Control | Enclave Broma • QUANTUM.AI</title>
  <link rel="icon" type="image/png" href="/favicon.png">

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
              850: '#0e1424',
              800: '#111726',
              750: '#161e33',
              700: '#1b233a',
              600: '#283454'
            },
            cyan: { 400: '#22d3ee', 500: '#06b6d4', 600: '#0891b2' },
            emerald: { 400: '#34d399', 500: '#10b981', 600: '#059669' },
            amber: { 400: '#fbbf24', 500: '#f59e0b', 600: '#d97706' }
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
    body { background-color: #05070d; font-family: 'Plus Jakarta Sans', sans-serif; }
    .mono { font-family: 'JetBrains Mono', monospace; }
    /* Scrollbar personalizada */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: #0b0f19; }
    ::-webkit-scrollbar-thumb { background: #1b233a; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #06b6d4; }
  </style>
</head>
<body class="text-slate-300 min-h-screen flex flex-col selection:bg-cyan-500/30 selection:text-cyan-200">

  <!-- ======================================================== -->
  <!-- NAVBAR SUPERIOR                                           -->
  <!-- ======================================================== -->
  <header class="sticky top-0 z-50 bg-[#0b0f19]/90 backdrop-blur-md border-b border-slate-800/80 px-4 lg:px-8 py-3.5 flex items-center justify-between">
    <div class="flex items-center gap-3.5">
      <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-cyan-500 via-blue-600 to-indigo-600 flex items-center justify-center text-white font-black text-lg shadow-lg shadow-cyan-500/20">
        Ω
      </div>
      <div>
        <div class="flex items-center gap-2">
          <span class="text-base font-extrabold text-white tracking-tight">ENCLAVE <span class="text-cyan-400">BROMA</span></span>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-purple-500/10 border border-purple-500/30 text-purple-300">ADMIN CONTROL</span>
        </div>
        <p class="text-[11px] text-slate-500 mono flex items-center gap-1.5">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
          SYS.ONLINE • v3.2.0-STABLE
        </p>
      </div>
    </div>

    <!-- Acciones del Header -->
    <div class="flex items-center gap-3">
      <a href="/" class="hidden sm:inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg bg-slate-800/60 hover:bg-slate-700/60 border border-slate-700/60 text-xs text-slate-300 font-medium transition">
        <span>Terminal Principal</span>
        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
      </a>

      <!-- Perfil Admin -->
      <div class="flex items-center gap-2.5 pl-3 border-l border-slate-800">
        <div class="w-8 h-8 rounded-full bg-cyan-500/20 border border-cyan-500/40 text-cyan-300 flex items-center justify-center font-bold text-xs">
          <?= $inicial ?>
        </div>
        <div class="hidden md:block text-left">
          <div class="text-xs font-semibold text-white leading-tight"><?= $admin_nombre ?></div>
          <div class="text-[10px] text-cyan-400 font-mono">SuperAdmin</div>
        </div>
        <a href="/login.php?action=logout" title="Cerrar Sesión" class="p-1.5 text-slate-400 hover:text-red-400 transition rounded-lg hover:bg-red-500/10">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
        </a>
      </div>
    </div>
  </header>

  <!-- ======================================================== -->
  <!-- CONTENIDO PRINCIPAL                                       -->
  <!-- ======================================================== -->
  <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8 space-y-6">

    <!-- BARRA DE KPIS SUPERIORES -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
      <!-- 1. Total Usuarios -->
      <div class="bg-[#0b0f19] border border-slate-800/80 rounded-2xl p-4 relative overflow-hidden">
        <div class="text-[11px] font-medium text-slate-400 uppercase tracking-wider mb-1">Total Usuarios</div>
        <div id="kpi-total-users" class="text-2xl font-black text-white mono">--</div>
        <div class="text-[10px] text-cyan-400 mt-1 flex items-center gap-1">👥 Registrados</div>
        <div class="absolute -right-2 -bottom-2 w-12 h-12 bg-cyan-500/5 rounded-full pointer-events-none"></div>
      </div>

      <!-- 2. VIP Activos -->
      <div class="bg-[#0b0f19] border border-slate-800/80 rounded-2xl p-4 relative overflow-hidden">
        <div class="text-[11px] font-medium text-slate-400 uppercase tracking-wider mb-1">Traders VIP</div>
        <div id="kpi-total-vip" class="text-2xl font-black text-amber-400 mono">--</div>
        <div class="text-[10px] text-amber-400/80 mt-1 flex items-center gap-1">👑 Membresía Activa</div>
        <div class="absolute -right-2 -bottom-2 w-12 h-12 bg-amber-500/5 rounded-full pointer-events-none"></div>
      </div>

      <!-- 3. Nuevos Hoy -->
      <div class="bg-[#0b0f19] border border-slate-800/80 rounded-2xl p-4 relative overflow-hidden">
        <div class="text-[11px] font-medium text-slate-400 uppercase tracking-wider mb-1">Nuevos Hoy</div>
        <div id="kpi-users-today" class="text-2xl font-black text-emerald-400 mono">--</div>
        <div class="text-[10px] text-emerald-400/80 mt-1 flex items-center gap-1">⚡ Últimas 24h</div>
        <div class="absolute -right-2 -bottom-2 w-12 h-12 bg-emerald-500/5 rounded-full pointer-events-none"></div>
      </div>

      <!-- 4. Nuevos 7 Días -->
      <div class="bg-[#0b0f19] border border-slate-800/80 rounded-2xl p-4 relative overflow-hidden">
        <div class="text-[11px] font-medium text-slate-400 uppercase tracking-wider mb-1">Últimos 7 Días</div>
        <div id="kpi-users-week" class="text-2xl font-black text-purple-400 mono">--</div>
        <div class="text-[10px] text-purple-400/80 mt-1 flex items-center gap-1">📅 En Prueba Activa</div>
        <div class="absolute -right-2 -bottom-2 w-12 h-12 bg-purple-500/5 rounded-full pointer-events-none"></div>
      </div>

      <!-- 5. Señales Activas -->
      <div class="bg-[#0b0f19] border border-slate-800/80 rounded-2xl p-4 relative overflow-hidden">
        <div class="text-[11px] font-medium text-slate-400 uppercase tracking-wider mb-1">Señales Vivas</div>
        <div id="kpi-signals-active" class="text-2xl font-black text-cyan-400 mono">--</div>
        <div class="text-[10px] text-cyan-400/80 mt-1 flex items-center gap-1">🎯 En Ejecución</div>
        <div class="absolute -right-2 -bottom-2 w-12 h-12 bg-cyan-500/5 rounded-full pointer-events-none"></div>
      </div>

      <!-- 6. Firewall WAF Bans -->
      <div class="bg-[#0b0f19] border border-slate-800/80 rounded-2xl p-4 relative overflow-hidden">
        <div class="text-[11px] font-medium text-slate-400 uppercase tracking-wider mb-1">IPs Bloqueadas</div>
        <div id="kpi-banned-ips" class="text-2xl font-black text-red-400 mono">--</div>
        <div class="text-[10px] text-red-400/80 mt-1 flex items-center gap-1">🛡️ Escudo WAF</div>
        <div class="absolute -right-2 -bottom-2 w-12 h-12 bg-red-500/5 rounded-full pointer-events-none"></div>
      </div>
    </div>

    <!-- NAVEGACIÓN POR PESTAÑAS -->
    <div class="border-b border-slate-800 flex items-center gap-2 overflow-x-auto pb-px">
      <button onclick="switchTab('tab-users')" id="btn-tab-users" class="tab-btn px-4 py-2.5 rounded-t-xl text-sm font-semibold border-b-2 border-cyan-400 text-white bg-slate-800/40 flex items-center gap-2 transition">
        <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
        <span>Operadores & Usuarios</span>
      </button>

      <button onclick="switchTab('tab-signals')" id="btn-tab-signals" class="tab-btn px-4 py-2.5 rounded-t-xl text-sm font-semibold border-b-2 border-transparent text-slate-400 hover:text-white flex items-center gap-2 transition">
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
        <span>Señales Cuánticas</span>
      </button>

      <button onclick="switchTab('tab-firewall')" id="btn-tab-firewall" class="tab-btn px-4 py-2.5 rounded-t-xl text-sm font-semibold border-b-2 border-transparent text-slate-400 hover:text-white flex items-center gap-2 transition">
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
        <span>Escudo WAF & Bans</span>
      </button>

      <button onclick="switchTab('tab-settings')" id="btn-tab-settings" class="tab-btn px-4 py-2.5 rounded-t-xl text-sm font-semibold border-b-2 border-transparent text-slate-400 hover:text-white flex items-center gap-2 transition">
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
        <span>Configuración del Bot</span>
      </button>
    </div>

    <!-- ======================================================== -->
    <!-- PESTAÑA 1: GESTIÓN DE USUARIOS                           -->
    <!-- ======================================================== -->
    <div id="tab-users" class="tab-content space-y-4">
      <!-- Barra de Filtros y Búsqueda -->
      <div class="bg-[#0b0f19] border border-slate-800/80 rounded-2xl p-4 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3.5">
        <div class="relative flex-1">
          <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
          <input type="text" id="user-search" oninput="debounceSearchUsers()" placeholder="Buscar por Nombre, Correo o ID..." class="w-full pl-10 pr-4 py-2 bg-slate-900/80 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500/60 focus:ring-1 focus:ring-cyan-500/60 transition">
        </div>

        <div class="flex items-center gap-2 overflow-x-auto">
          <button onclick="setFilter('all')" class="filter-btn active px-3 py-1.5 rounded-lg text-xs font-medium border border-slate-700 bg-slate-800 text-white" data-f="all">Todos</button>
          <button onclick="setFilter('vip')" class="filter-btn px-3 py-1.5 rounded-lg text-xs font-medium border border-slate-800 bg-slate-900 text-slate-400 hover:text-white" data-f="vip">VIPs 👑</button>
          <button onclick="setFilter('trial')" class="filter-btn px-3 py-1.5 rounded-lg text-xs font-medium border border-slate-800 bg-slate-900 text-slate-400 hover:text-white" data-f="trial">En Prueba 🎁</button>
          <button onclick="setFilter('expired')" class="filter-btn px-3 py-1.5 rounded-lg text-xs font-medium border border-slate-800 bg-slate-900 text-slate-400 hover:text-white" data-f="expired">Expirados ⚠️</button>
          <button onclick="setFilter('admin')" class="filter-btn px-3 py-1.5 rounded-lg text-xs font-medium border border-slate-800 bg-slate-900 text-slate-400 hover:text-white" data-f="admin">Admins 🛡️</button>
        </div>
      </div>

      <!-- Tabla de Usuarios -->
      <div class="bg-[#0b0f19] border border-slate-800/80 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-900/60 border-b border-slate-800 text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                <th class="p-3.5 pl-5">ID / Operador</th>
                <th class="p-3.5">Correo</th>
                <th class="p-3.5">Rol</th>
                <th class="p-3.5">Membresía / Acceso</th>
                <th class="p-3.5">Registro</th>
                <th class="p-3.5 pr-5 text-right">Acciones Rápidas</th>
              </tr>
            </thead>
            <tbody id="users-tbody" class="divide-y divide-slate-800/60">
              <tr>
                <td colspan="6" class="p-8 text-center text-slate-500">Cargando operadores del sistema...</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div class="bg-slate-900/40 border-t border-slate-800/80 px-5 py-3 flex items-center justify-between text-xs text-slate-400">
          <div id="users-pagination-info">Mostrando 0 de 0</div>
          <div class="flex items-center gap-1.5" id="users-pagination-controls">
            <!-- Botones generados dinámicamente -->
          </div>
        </div>
      </div>
    </div>

    <!-- ======================================================== -->
    <!-- PESTAÑA 2: SEÑALES CUÁNTICAS                             -->
    <!-- ======================================================== -->
    <div id="tab-signals" class="tab-content hidden space-y-4">
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div>
          <h2 class="text-lg font-bold text-white tracking-tight">Monitor de Señales Institucionales</h2>
          <p class="text-xs text-slate-400">Emisión manual y seguimiento del motor autónomo en tiempo real.</p>
        </div>
        <button onclick="openModalEmitirSenal()" class="px-4 py-2 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white text-xs font-semibold shadow-lg shadow-cyan-500/20 flex items-center gap-1.5 transition">
          <span>+ Emitir Nueva Señal</span>
        </button>
      </div>

      <!-- Grid Señales Activas -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="signals-active-grid">
        <div class="col-span-full p-8 text-center bg-[#0b0f19] border border-slate-800 rounded-2xl text-slate-500 text-xs">
          Cargando señales cuánticas...
        </div>
      </div>

      <!-- Historial de Señales -->
      <div class="bg-[#0b0f19] border border-slate-800/80 rounded-2xl overflow-hidden mt-6">
        <div class="px-5 py-3.5 border-b border-slate-800 flex items-center justify-between">
          <span class="text-xs font-bold text-white uppercase tracking-wider">Historial de Señales Cerradas</span>
          <span class="text-[11px] text-slate-500 mono" id="history-signals-count">0 cerradas</span>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-900/60 border-b border-slate-800 text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                <th class="p-3 pl-5">Par</th>
                <th class="p-3">Tipo</th>
                <th class="p-3">Entrada</th>
                <th class="p-3">SL / TP</th>
                <th class="p-3">Resultado</th>
                <th class="p-3 pr-5 text-right">Fecha Cierre</th>
              </tr>
            </thead>
            <tbody id="signals-history-tbody" class="divide-y divide-slate-800/60 font-mono text-[11px]">
              <tr>
                <td colspan="6" class="p-6 text-center text-slate-500">Sin historial reciente.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ======================================================== -->
    <!-- PESTAÑA 3: FIREWALL WAF & BANS                          -->
    <!-- ======================================================== -->
    <div id="tab-firewall" class="tab-content hidden space-y-4">
      <div class="bg-[#0b0f19] border border-slate-800/80 rounded-2xl p-5 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
          <h2 class="text-base font-bold text-white flex items-center gap-2">
            <span>🛡️ Escudo WAF Anti-Intrusión</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 border border-emerald-500/30 text-emerald-400">ACTIVO</span>
          </h2>
          <p class="text-xs text-slate-400 mt-1">Inspección de SQLi, XSS, XPath Injection y bloqueo automático de atacantes.</p>
        </div>

        <!-- Formulario Ban Manual -->
        <div class="flex items-center gap-2 w-full md:w-auto">
          <input type="text" id="manual-ban-ip" placeholder="Ej: 87.219.73.8" class="px-3 py-1.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-red-500/60 w-full sm:w-44 mono">
          <button onclick="handleManualBan()" class="px-3.5 py-1.5 bg-red-600/80 hover:bg-red-500 text-white rounded-xl text-xs font-semibold transition whitespace-nowrap">
            Bloquear IP
          </button>
        </div>
      </div>

      <!-- Tabla de IPs Bloqueadas -->
      <div class="bg-[#0b0f19] border border-slate-800/80 rounded-2xl overflow-hidden shadow-xl">
        <div class="px-5 py-3 border-b border-slate-800 text-xs font-bold text-white flex items-center justify-between">
          <span>Registro de IPs Baneadas</span>
          <button onclick="loadFirewallIps()" class="text-cyan-400 hover:text-cyan-300 text-xs flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            Refrescar
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-900/60 border-b border-slate-800 text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                <th class="p-3.5 pl-5">Dirección IP</th>
                <th class="p-3.5">Motivo / Regla Activada</th>
                <th class="p-3.5">Fecha y Hora</th>
                <th class="p-3.5 pr-5 text-right">Acción</th>
              </tr>
            </thead>
            <tbody id="firewall-tbody" class="divide-y divide-slate-800/60 font-mono text-[11px]">
              <tr>
                <td colspan="4" class="p-6 text-center text-slate-500">Cargando lista de amenazas bloqueadas...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ======================================================== -->
    <!-- PESTAÑA 4: CONFIGURACIÓN DEL BOT                        -->
    <!-- ======================================================== -->
    <div id="tab-settings" class="tab-content hidden space-y-4">
      <div class="max-w-2xl bg-[#0b0f19] border border-slate-800/80 rounded-2xl p-6 space-y-5">
        <div>
          <h2 class="text-base font-bold text-white tracking-tight">Parámetros Operativos del Bot</h2>
          <p class="text-xs text-slate-400 mt-0.5">Control global de comportamiento y notificaciones del terminal.</p>
        </div>

        <form id="form-settings" onsubmit="handleSaveSettings(event)" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Modo de Operación del Bot</label>
            <select id="cfg-modo" class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-cyan-500">
              <option value="AUTONOMO_CUANTICO">100% Autónomo (Análisis, Validación & Despacho Automático)</option>
              <option value="ASISTIDO_SEMI">Semi-Autónomo (Señales con Alertas y Aprobación)</option>
              <option value="SOLO_LECTURA">Modo Centinela (Solo lectura y recolección de datos)</option>
              <option value="MANTENIMIENTO">Mantenimiento Global (Pausa general)</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Estado del Servidor</label>
            <select id="cfg-estado" class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-cyan-500">
              <option value="ONLINE">ONLINE (Operaciones Normales)</option>
              <option value="PROTEGIDO">PROTEGIDO (Riesgo reducido a 0.01 lotes)</option>
              <option value="ROLLOVER_PAUSE">PAUSA POR ROLLOVER</option>
            </select>
          </div>

          <div class="border-t border-slate-800/80 pt-4">
            <div class="flex items-center justify-between mb-2">
              <label class="text-xs font-semibold text-slate-300">Banner de Anuncio en Terminal</label>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" id="cfg-anuncio-activo" class="sr-only peer">
                <div class="w-9 h-5 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-cyan-500"></div>
              </label>
            </div>
            <textarea id="cfg-anuncio-texto" rows="3" placeholder="Mensaje visible en el encabezado del terminal para todos los operadores..." class="w-full px-3.5 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500"></textarea>
          </div>

          <div class="pt-2">
            <button type="submit" class="w-full py-2.5 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white font-semibold text-xs rounded-xl shadow-lg shadow-cyan-500/20 transition">
              Guardar Configuraciones Globales
            </button>
          </div>
        </form>
      </div>
    </div>

  </main>

  <!-- ======================================================== -->
  <!-- MODAL: EMITIR SEÑAL                                      -->
  <!-- ======================================================== -->
  <div id="modal-emitir-senal" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-[#0b0f19] border border-slate-800 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl">
      <div class="flex items-center justify-between border-b border-slate-800 pb-3">
        <h3 class="text-sm font-bold text-white">Emitir Señal Institucional</h3>
        <button onclick="closeModalEmitirSenal()" class="text-slate-400 hover:text-white text-lg font-bold">&times;</button>
      </div>

      <form id="form-nueva-senal" onsubmit="handleCrearSenal(event)" class="space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-[11px] font-semibold text-slate-400 mb-1">Par / Símbolo</label>
            <input type="text" id="sig-par" required placeholder="Ej: BTCUSDT" class="w-full px-3 py-1.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white uppercase mono focus:border-cyan-500 focus:outline-none">
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-slate-400 mb-1">Dirección</label>
            <select id="sig-tipo" class="w-full px-3 py-1.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white font-bold focus:border-cyan-500 focus:outline-none">
              <option value="BUY" class="text-emerald-400">🟢 BUY / LONG</option>
              <option value="SELL" class="text-red-400">🔴 SELL / SHORT</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-3 gap-2">
          <div>
            <label class="block text-[11px] font-semibold text-slate-400 mb-1">Entrada</label>
            <input type="number" step="any" id="sig-entrada" required placeholder="85400" class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white mono focus:border-cyan-500 focus:outline-none">
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-slate-400 mb-1">Stop Loss</label>
            <input type="number" step="any" id="sig-sl" required placeholder="84900" class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white mono focus:border-cyan-500 focus:outline-none">
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-slate-400 mb-1">Take Profit</label>
            <input type="number" step="any" id="sig-tp" required placeholder="86800" class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white mono focus:border-cyan-500 focus:outline-none">
          </div>
        </div>

        <div>
          <label class="block text-[11px] font-semibold text-slate-400 mb-1">Apalancamiento Recomendado</label>
          <input type="number" id="sig-lev" value="10" min="1" max="100" class="w-full px-3 py-1.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white mono focus:border-cyan-500 focus:outline-none">
        </div>

        <div>
          <label class="block text-[11px] font-semibold text-slate-400 mb-1">Tesis / Justificación Cuántica</label>
          <textarea id="sig-razon" rows="2" placeholder="Ej: Ruptura de POC institucional + Absorción compradora" class="w-full px-3 py-1.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-cyan-500 focus:outline-none"></textarea>
        </div>

        <div class="pt-2 flex justify-end gap-2">
          <button type="button" onclick="closeModalEmitirSenal()" class="px-4 py-2 rounded-xl text-xs text-slate-400 hover:text-white">Cancelar</button>
          <button type="submit" class="px-5 py-2 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white rounded-xl text-xs font-semibold shadow-lg shadow-cyan-500/20">
            Publicar Señal
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- ======================================================== -->
  <!-- MODAL: CAMBIAR CONTRASEÑA DE USUARIO                     -->
  <!-- ======================================================== -->
  <div id="modal-reset-pass" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-[#0b0f19] border border-slate-800 rounded-2xl max-w-sm w-full p-6 space-y-4 shadow-2xl">
      <div class="flex items-center justify-between border-b border-slate-800 pb-3">
        <h3 class="text-sm font-bold text-white">Restablecer Contraseña</h3>
        <button onclick="closeModalResetPass()" class="text-slate-400 hover:text-white text-lg font-bold">&times;</button>
      </div>

      <form id="form-reset-pass" onsubmit="handleConfirmResetPass(event)" class="space-y-3">
        <input type="hidden" id="reset-user-id">
        <div>
          <div class="text-xs text-slate-400 mb-1" id="reset-user-desc">Usuario: --</div>
          <input type="password" id="reset-new-pass" required placeholder="Nueva contraseña (mínimo 6 caracteres)" class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white focus:border-cyan-500 focus:outline-none">
        </div>

        <div class="pt-2 flex justify-end gap-2">
          <button type="button" onclick="closeModalResetPass()" class="px-3.5 py-1.5 rounded-xl text-xs text-slate-400 hover:text-white">Cancelar</button>
          <button type="submit" class="px-4 py-1.5 bg-amber-600 hover:bg-amber-500 text-white rounded-xl text-xs font-semibold transition">
            Actualizar Clave
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- TOAST FLOTANTE -->
  <div id="toast" class="fixed bottom-5 right-5 z-50 transform translate-y-20 opacity-0 transition-all duration-300 pointer-events-none px-4 py-3 rounded-xl text-xs font-semibold text-white shadow-2xl flex items-center gap-2">
    <span id="toast-icon">✓</span>
    <span id="toast-msg">Operación completada</span>
  </div>

  <!-- ======================================================== -->
  <!-- JAVASCRIPT REACTIVO DEL PANEL                            -->
  <!-- ======================================================== -->
  <script>
    const API_URL = '/broma/api.php';
    let currentFilter = 'all';
    let currentPage = 1;
    let searchDebounce = null;

    // Inicialización al cargar la página
    document.addEventListener('DOMContentLoaded', () => {
      loadStats();
      loadUsers();
      loadSignals();
      loadFirewallIps();
    });

    // Cambiar de Pestaña
    function switchTab(tabId) {
      document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
      document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('border-cyan-400', 'text-white', 'bg-slate-800/40');
        b.classList.add('border-transparent', 'text-slate-400');
      });

      const target = document.getElementById(tabId);
      if (target) target.classList.remove('hidden');

      const btn = document.getElementById('btn-' + tabId);
      if (btn) {
        btn.classList.add('border-cyan-400', 'text-white', 'bg-slate-800/40');
        btn.classList.remove('border-transparent', 'text-slate-400');
      }
    }

    // Mostrar Toasts
    function showToast(msg, isSuccess = true) {
      const t = document.getElementById('toast');
      const ti = document.getElementById('toast-icon');
      const tm = document.getElementById('toast-msg');
      
      tm.innerText = msg;
      ti.innerText = isSuccess ? '✓' : '✕';
      t.className = `fixed bottom-5 right-5 z-50 transform translate-y-0 opacity-100 transition-all duration-300 px-4 py-3 rounded-xl text-xs font-semibold text-white shadow-2xl flex items-center gap-2 ${isSuccess ? 'bg-emerald-600' : 'bg-red-600'}`;
      
      setTimeout(() => {
        t.className = 'fixed bottom-5 right-5 z-50 transform translate-y-20 opacity-0 transition-all duration-300 pointer-events-none px-4 py-3 rounded-xl text-xs font-semibold text-white shadow-2xl flex items-center gap-2';
      }, 3500);
    }

    // ──────────────────────────────────────────
    // 1. CARGA DE ESTADÍSTICAS Y KPIS
    // ──────────────────────────────────────────
    async function loadStats() {
      try {
        const res = await fetch(`${API_URL}?action=stats`);
        const data = await res.json();
        if (data.success && data.stats) {
          const s = data.stats;
          document.getElementById('kpi-total-users').innerText = s.total_usuarios ?? 0;
          document.getElementById('kpi-total-vip').innerText = s.total_vip ?? 0;
          document.getElementById('kpi-users-today').innerText = s.usuarios_hoy ?? 0;
          document.getElementById('kpi-users-week').innerText = s.usuarios_semana ?? 0;
          document.getElementById('kpi-signals-active').innerText = s.senales_activas ?? 0;
          document.getElementById('kpi-banned-ips').innerText = s.ips_bloqueadas ?? 0;

          // Cargar config en formulario
          if (s.config) {
            document.getElementById('cfg-modo').value = s.config.modo_bot || 'AUTONOMO_CUANTICO';
            document.getElementById('cfg-estado').value = s.config.estado || 'ONLINE';
            document.getElementById('cfg-anuncio-texto').value = s.config.anuncio_global || '';
            document.getElementById('cfg-anuncio-activo').checked = !!s.config.anuncio_activo;
          }
        }
      } catch (err) {
        console.error('Error cargando stats:', err);
      }
    }

    // ──────────────────────────────────────────
    // 2. GESTIÓN DE USUARIOS
    // ──────────────────────────────────────────
    function setFilter(f) {
      currentFilter = f;
      currentPage = 1;
      document.querySelectorAll('.filter-btn').forEach(b => {
        if (b.dataset.f === f) {
          b.className = 'filter-btn active px-3 py-1.5 rounded-lg text-xs font-medium border border-slate-700 bg-slate-800 text-white';
        } else {
          b.className = 'filter-btn px-3 py-1.5 rounded-lg text-xs font-medium border border-slate-800 bg-slate-900 text-slate-400 hover:text-white';
        }
      });
      loadUsers();
    }

    function debounceSearchUsers() {
      clearTimeout(searchDebounce);
      searchDebounce = setTimeout(() => {
        currentPage = 1;
        loadUsers();
      }, 300);
    }

    async function loadUsers() {
      const q = document.getElementById('user-search').value.trim();
      const tbody = document.getElementById('users-tbody');
      
      try {
        const res = await fetch(`${API_URL}?action=get_users&page=${currentPage}&limit=15&search=${encodeURIComponent(q)}&filter=${currentFilter}`);
        const data = await res.json();
        
        if (!data.success || !data.users) {
          tbody.innerHTML = '<tr><td colspan="6" class="p-8 text-center text-red-400 text-xs">Error cargando operadores.</td></tr>';
          return;
        }

        if (data.users.length === 0) {
          tbody.innerHTML = '<tr><td colspan="6" class="p-8 text-center text-slate-500 text-xs">No se encontraron operadores con los criterios seleccionados.</td></tr>';
          renderPagination(data.pagination);
          return;
        }

        let html = '';
        data.users.forEach(u => {
          const isVip = u.es_vip;
          const isAdmin = u.rol.toLowerCase() === 'admin';
          
          let badgeAcceso = '';
          if (isVip) {
            badgeAcceso = '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 border border-amber-500/30 text-amber-300">👑 VIP ILIMITADO</span>';
          } else if (u.tipo_acceso === 'prueba_gratis') {
            badgeAcceso = `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 border border-emerald-500/30 text-emerald-300">🎁 Prueba (${u.dias_restantes}d)</span>`;
          } else {
            badgeAcceso = '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-red-500/10 border border-red-500/30 text-red-400">⚠️ Prueba Expirada</span>';
          }

          let badgeRol = '';
          if (isAdmin) {
            badgeRol = '<span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-purple-500/15 border border-purple-500/30 text-purple-300">ADMIN</span>';
          } else if (u.rol === 'moderador') {
            badgeRol = '<span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-500/15 border border-blue-500/30 text-blue-300">MOD</span>';
          } else {
            badgeRol = '<span class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-800 text-slate-400">TRADER</span>';
          }

          html += `
            <tr class="hover:bg-slate-800/25 transition">
              <td class="p-3.5 pl-5">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-slate-800 flex items-center justify-center font-bold text-xs text-slate-300 mono border border-slate-700">
                    #${u.id_usuario}
                  </div>
                  <div>
                    <div class="font-bold text-white leading-tight">${u.nombre}</div>
                    <div class="text-[10px] text-slate-500 mono">IP: ${u.ip_registro}</div>
                  </div>
                </div>
              </td>
              <td class="p-3.5 text-slate-300 mono text-[11px]">${u.correo}</td>
              <td class="p-3.5">${badgeRol}</td>
              <td class="p-3.5">${badgeAcceso}</td>
              <td class="p-3.5 text-slate-400 text-[11px]">${u.fecha_registro.split(' ')[0]}</td>
              <td class="p-3.5 pr-5 text-right">
                <div class="inline-flex items-center gap-1.5">
                  <!-- Toggle VIP -->
                  <button onclick="toggleVip(${u.id_usuario}, ${isVip ? 0 : 1})" title="${isVip ? 'Revocar Membresía VIP' : 'Activar Membresía VIP'}" class="p-1.5 rounded-lg border text-xs transition ${isVip ? 'bg-amber-500/10 border-amber-500/30 text-amber-400 hover:bg-amber-500/20' : 'bg-slate-800 border-slate-700 text-slate-400 hover:text-amber-400'}">
                    👑
                  </button>

                  <!-- Renovar Prueba 7 Días -->
                  <button onclick="extendTrial(${u.id_usuario})" title="Renovar Prueba de 7 Días" class="p-1.5 rounded-lg border border-slate-700 bg-slate-800 text-slate-400 hover:text-emerald-400 transition text-xs">
                    🎁
                  </button>

                  <!-- Cambiar Rol -->
                  <button onclick="promptChangeRole(${u.id_usuario}, '${u.rol}')" title="Cambiar Rol de Usuario" class="p-1.5 rounded-lg border border-slate-700 bg-slate-800 text-slate-400 hover:text-purple-400 transition text-xs">
                    🛡️
                  </button>

                  <!-- Resetear Clave -->
                  <button onclick="openModalResetPass(${u.id_usuario}, '${u.nombre}', '${u.correo}')" title="Restablecer Contraseña" class="p-1.5 rounded-lg border border-slate-700 bg-slate-800 text-slate-400 hover:text-cyan-400 transition text-xs">
                    🔑
                  </button>
                </div>
              </td>
            </tr>
          `;
        });

        tbody.innerHTML = html;
        renderPagination(data.pagination);

      } catch (err) {
        console.error('Error cargando usuarios:', err);
        tbody.innerHTML = '<tr><td colspan="6" class="p-8 text-center text-red-400 text-xs">Error de conexión al cargar operadores.</td></tr>';
      }
    }

    function renderPagination(pg) {
      if (!pg) return;
      document.getElementById('users-pagination-info').innerText = `Página ${pg.current_page} de ${pg.total_pages} (${pg.total_records} operadores totales)`;
      
      const ctrl = document.getElementById('users-pagination-controls');
      let btns = '';
      
      if (pg.current_page > 1) {
        btns += `<button onclick="changePage(${pg.current_page - 1})" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-white font-medium">← Anterior</button>`;
      }
      if (pg.current_page < pg.total_pages) {
        btns += `<button onclick="changePage(${pg.current_page + 1})" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-white font-medium">Siguiente →</button>`;
      }
      ctrl.innerHTML = btns;
    }

    function changePage(p) {
      currentPage = p;
      loadUsers();
    }

    // Toggle VIP
    async function toggleVip(userId, nuevoEstado) {
      const fd = new FormData();
      fd.append('action', 'toggle_vip');
      fd.append('id_usuario', userId);
      fd.append('es_premium', nuevoEstado);

      try {
        const res = await fetch(API_URL, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
          showToast(data.message);
          loadUsers();
          loadStats();
        } else {
          showToast(data.message, false);
        }
      } catch (e) {
        showToast('Error de red', false);
      }
    }

    // Extender Prueba
    async function extendTrial(userId) {
      if (!confirm('¿Deseas reiniciar y otorgar 7 días de prueba completa a este operador?')) return;
      const fd = new FormData();
      fd.append('action', 'extend_trial');
      fd.append('id_usuario', userId);

      try {
        const res = await fetch(API_URL, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
          showToast(data.message);
          loadUsers();
          loadStats();
        } else {
          showToast(data.message, false);
        }
      } catch (e) {
        showToast('Error de red', false);
      }
    }

    // Cambiar Rol
    async function promptChangeRole(userId, rolActual) {
      const nuevo = prompt(`Rol actual: ${rolActual}\nIngresa el nuevo rol (admin, moderador, usuario):`, rolActual);
      if (!nuevo || nuevo.trim().toLowerCase() === rolActual.toLowerCase()) return;

      const fd = new FormData();
      fd.append('action', 'change_role');
      fd.append('id_usuario', userId);
      fd.append('rol', nuevo.trim().toLowerCase());

      try {
        const res = await fetch(API_URL, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
          showToast(data.message);
          loadUsers();
        } else {
          showToast(data.message, false);
        }
      } catch (e) {
        showToast('Error de red', false);
      }
    }

    // Modal Reset Password
    function openModalResetPass(userId, nombre, correo) {
      document.getElementById('reset-user-id').value = userId;
      document.getElementById('reset-user-desc').innerText = `Operador #${userId}: ${nombre} (${correo})`;
      document.getElementById('reset-new-pass').value = '';
      document.getElementById('modal-reset-pass').classList.remove('hidden');
      document.getElementById('modal-reset-pass').classList.add('flex');
    }

    function closeModalResetPass() {
      document.getElementById('modal-reset-pass').classList.add('hidden');
      document.getElementById('modal-reset-pass').classList.remove('flex');
    }

    async function handleConfirmResetPass(e) {
      e.preventDefault();
      const userId = document.getElementById('reset-user-id').value;
      const pass = document.getElementById('reset-new-pass').value;

      const fd = new FormData();
      fd.append('action', 'reset_password');
      fd.append('id_usuario', userId);
      fd.append('nueva_password', pass);

      try {
        const res = await fetch(API_URL, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
          showToast(data.message);
          closeModalResetPass();
        } else {
          showToast(data.message, false);
        }
      } catch (err) {
        showToast('Error al resetear contraseña', false);
      }
    }

    // ──────────────────────────────────────────
    // 3. SEÑALES CUÁNTICAS
    // ──────────────────────────────────────────
    function openModalEmitirSenal() {
      document.getElementById('modal-emitir-senal').classList.remove('hidden');
      document.getElementById('modal-emitir-senal').classList.add('flex');
    }

    function closeModalEmitirSenal() {
      document.getElementById('modal-emitir-senal').classList.add('hidden');
      document.getElementById('modal-emitir-senal').classList.remove('flex');
    }

    async function loadSignals() {
      const grid = document.getElementById('signals-active-grid');
      const tbodyH = document.getElementById('signals-history-tbody');

      try {
        const res = await fetch(`${API_URL}?action=get_signals`);
        const data = await res.json();

        if (data.success) {
          // 1. Activas
          if (!data.activas || data.activas.length === 0) {
            grid.innerHTML = '<div class="col-span-full p-8 text-center bg-[#0b0f19] border border-slate-800 rounded-2xl text-slate-500 text-xs">No hay señales activas en este momento. Emite una nueva para activar el motor.</div>';
          } else {
            let cards = '';
            data.activas.forEach(s => {
              const isLong = (s.lado === 'LONG' || s.tipo === 'BUY');
              cards += `
                <div class="bg-[#0b0f19] border border-slate-800/80 rounded-2xl p-5 space-y-3 relative overflow-hidden">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                      <span class="text-sm font-black text-white mono">${s.par}</span>
                      <span class="px-2 py-0.5 rounded-md text-[10px] font-bold ${isLong ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-red-500/10 text-red-400 border border-red-500/30'}">${s.lado || s.tipo}</span>
                      <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-800 text-slate-400">${s.apalancamiento}x</span>
                    </div>
                    <span class="text-[10px] text-cyan-400 mono">● VIVA</span>
                  </div>

                  <div class="grid grid-cols-3 gap-2 bg-slate-900/60 p-2.5 rounded-xl border border-slate-800 text-center mono">
                    <div>
                      <div class="text-[9px] text-slate-500">ENTRADA</div>
                      <div class="text-xs font-bold text-white">${s.precio_entrada}</div>
                    </div>
                    <div>
                      <div class="text-[9px] text-red-400">STOP LOSS</div>
                      <div class="text-xs font-bold text-red-400">${s.stop_loss}</div>
                    </div>
                    <div>
                      <div class="text-[9px] text-emerald-400">TAKE PROFIT</div>
                      <div class="text-xs font-bold text-emerald-400">${s.take_profit}</div>
                    </div>
                  </div>

                  <p class="text-[11px] text-slate-400 line-clamp-1 italic">"${s.razon || 'Estrategia Cuántica'}"</p>

                  <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between">
                    <span class="text-[10px] text-slate-500 mono">${s.fecha_hora || ''}</span>
                    <div class="flex gap-1.5">
                      <button onclick="closeSignal('${s.id}', 'WIN', '+2.80%')" class="px-2.5 py-1 bg-emerald-500/15 hover:bg-emerald-500/30 border border-emerald-500/30 text-emerald-300 rounded-lg text-[10px] font-bold">
                        Cerrar WIN 🎯
                      </button>
                      <button onclick="closeSignal('${s.id}', 'LOSS', '-1.20%')" class="px-2.5 py-1 bg-red-500/15 hover:bg-red-500/30 border border-red-500/30 text-red-400 rounded-lg text-[10px] font-bold">
                        Cerrar LOSS
                      </button>
                    </div>
                  </div>
                </div>
              `;
            });
            grid.innerHTML = cards;
          }

          // 2. Historial
          if (data.historial && data.historial.length > 0) {
            document.getElementById('history-signals-count').innerText = `${data.historial.length} cerradas`;
            let hHtml = '';
            data.historial.forEach(h => {
              const isWin = h.estado === 'TP_ALCANZADO';
              hHtml += `
                <tr class="hover:bg-slate-800/20">
                  <td class="p-3 pl-5 font-bold text-white">${h.par}</td>
                  <td class="p-3">${h.lado || h.tipo}</td>
                  <td class="p-3 text-slate-300">${h.precio_entrada}</td>
                  <td class="p-3 text-slate-400">${h.stop_loss} / ${h.take_profit}</td>
                  <td class="p-3">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold ${isWin ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400'}">
                      ${isWin ? 'TP HIT (' + (h.pnl_realizado || '+2.5%') + ')' : 'SL HIT (' + (h.pnl_realizado || '-1.2%') + ')'}
                    </span>
                  </td>
                  <td class="p-3 pr-5 text-right text-slate-500">${h.fecha_cierre || '-'}</td>
                </tr>
              `;
            });
            tbodyH.innerHTML = hHtml;
          }
        }
      } catch (e) {
        console.error('Error cargando señales:', e);
      }
    }

    async function handleCrearSenal(e) {
      e.preventDefault();
      const fd = new FormData();
      fd.append('action', 'add_signal');
      fd.append('par', document.getElementById('sig-par').value.trim());
      fd.append('tipo', document.getElementById('sig-tipo').value);
      fd.append('precio_entrada', document.getElementById('sig-entrada').value);
      fd.append('stop_loss', document.getElementById('sig-sl').value);
      fd.append('take_profit', document.getElementById('sig-tp').value);
      fd.append('apalancamiento', document.getElementById('sig-lev').value);
      fd.append('razon', document.getElementById('sig-razon').value.trim());

      try {
        const res = await fetch(API_URL, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
          showToast(data.message);
          closeModalEmitirSenal();
          loadSignals();
          loadStats();
          document.getElementById('form-nueva-senal').reset();
        } else {
          showToast(data.message, false);
        }
      } catch (err) {
        showToast('Error de red al crear señal', false);
      }
    }

    async function closeSignal(id, resultado, pnl) {
      if (!confirm(`¿Confirmas el cierre de la señal como ${resultado} (${pnl})?`)) return;
      const fd = new FormData();
      fd.append('action', 'close_signal');
      fd.append('id', id);
      fd.append('resultado', resultado);
      fd.append('pnl', pnl);

      try {
        const res = await fetch(API_URL, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
          showToast(data.message);
          loadSignals();
          loadStats();
        } else {
          showToast(data.message, false);
        }
      } catch (e) {
        showToast('Error al cerrar señal', false);
      }
    }

    // ──────────────────────────────────────────
    // 4. FIREWALL WAF
    // ──────────────────────────────────────────
    async function loadFirewallIps() {
      const tbody = document.getElementById('firewall-tbody');
      try {
        const res = await fetch(`${API_URL}?action=get_firewall_ips`);
        const data = await res.json();
        if (data.success && data.ips) {
          if (data.ips.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="p-6 text-center text-slate-500 text-xs">No hay IPs bloqueadas actualmente. El perímetro está limpio.</td></tr>';
            return;
          }

          let html = '';
          data.ips.forEach(row => {
            html += `
              <tr class="hover:bg-slate-800/25 transition">
                <td class="p-3.5 pl-5 font-bold text-red-400">${row.ip}</td>
                <td class="p-3.5 text-slate-300">${row.motivo || 'Ataque interceptado'}</td>
                <td class="p-3.5 text-slate-400">${row.fecha || '-'}</td>
                <td class="p-3.5 pr-5 text-right">
                  <button onclick="handleUnban('${row.ip}')" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-emerald-600/30 text-slate-300 hover:text-emerald-300 border border-slate-700 transition text-[11px] font-medium">
                    Desbloquear
                  </button>
                </td>
              </tr>
            `;
          });
          tbody.innerHTML = html;
        }
      } catch (err) {
        console.error('Error cargando firewall:', err);
      }
    }

    async function handleUnban(ip) {
      if (!confirm(`¿Seguro que deseas desbloquear la IP ${ip}?`)) return;
      const fd = new FormData();
      fd.append('action', 'unban_ip');
      fd.append('ip', ip);

      try {
        const res = await fetch(API_URL, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
          showToast(data.message);
          loadFirewallIps();
          loadStats();
        } else {
          showToast(data.message, false);
        }
      } catch (e) {
        showToast('Error al desbloquear IP', false);
      }
    }

    async function handleManualBan() {
      const ip = document.getElementById('manual-ban-ip').value.trim();
      if (!ip) {
        alert('Por favor ingresa una dirección IP válida.');
        return;
      }

      const fd = new FormData();
      fd.append('action', 'ban_ip');
      fd.append('ip', ip);
      fd.append('motivo', 'Bloqueo manual administrativo');

      try {
        const res = await fetch(API_URL, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
          showToast(data.message);
          document.getElementById('manual-ban-ip').value = '';
          loadFirewallIps();
          loadStats();
        } else {
          showToast(data.message, false);
        }
      } catch (e) {
        showToast('Error al bloquear IP', false);
      }
    }

    // ──────────────────────────────────────────
    // 5. AJUSTES DEL BOT
    // ──────────────────────────────────────────
    async function handleSaveSettings(e) {
      e.preventDefault();
      const fd = new FormData();
      fd.append('action', 'save_settings');
      fd.append('modo_bot', document.getElementById('cfg-modo').value);
      fd.append('estado', document.getElementById('cfg-estado').value);
      fd.append('anuncio_global', document.getElementById('cfg-anuncio-texto').value);
      fd.append('anuncio_activo', document.getElementById('cfg-anuncio-activo').checked ? 1 : 0);

      try {
        const res = await fetch(API_URL, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
          showToast(data.message);
          loadStats();
        } else {
          showToast(data.message, false);
        }
      } catch (err) {
        showToast('Error guardando configuraciones', false);
      }
    }
  </script>
</body>
</html>
