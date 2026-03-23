<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MetaBorghi Admin — <?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  body { background: #0f172a; }
  .nav-link {
    display: flex; align-items: center; gap: 0.5rem;
    padding: 0.5rem 0.75rem; border-radius: 0.5rem;
    color: rgb(203 213 225); font-size: 0.875rem;
    text-decoration: none; transition: background-color 0.15s, color 0.15s;
  }
  .nav-link:hover { background-color: rgb(51 65 85); color: white; }
  .nav-link.active { background-color: rgb(5 150 105); color: white; }
</style>
</head>
<body class="min-h-screen text-white">
  <!-- Sidebar -->
  <div class="flex h-screen">
    <aside class="w-56 bg-slate-900 border-r border-slate-700 flex flex-col flex-shrink-0">
      <div class="px-5 py-5 border-b border-slate-700">
        <h1 class="font-bold text-lg text-white">MetaBorghi</h1>
        <p class="text-xs text-slate-400">Admin Panel</p>
      </div>
      <nav class="flex-1 px-3 py-4 space-y-1">
        <a href="/api/admin/" class="nav-link <?= ($pageTitle==='Dashboard'?'active':'') ?>">
          <span>🏠</span> Dashboard
        </a>
        <a href="/api/admin/borghi.php" class="nav-link <?= ($pageTitle==='Borghi'?'active':'') ?>">
          <span>🏔️</span> Borghi
        </a>
        <a href="/api/admin/aziende.php" class="nav-link <?= ($pageTitle==='Aziende'?'active':'') ?>">
          <span>🏢</span> Aziende
        </a>
        <a href="/api/admin/esperienze.php" class="nav-link <?= ($pageTitle==='Esperienze'?'active':'') ?>">
          <span>🎭</span> Esperienze
        </a>
        <a href="/api/admin/artigianato.php" class="nav-link <?= ($pageTitle==='Artigianato'?'active':'') ?>">
          <span>🏺</span> Artigianato
        </a>
        <a href="/api/admin/prodotti.php" class="nav-link <?= ($pageTitle==='Prodotti Food'?'active':'') ?>">
          <span>🧀</span> Prodotti Food
        </a>
        <a href="/api/admin/ospitalita.php" class="nav-link <?= ($pageTitle==='Ospitalità'?'active':'') ?>">
          <span>🏨</span> Ospitalità
        </a>
        <a href="/api/admin/ristorazione.php" class="nav-link <?= ($pageTitle==='Ristorazione'?'active':'') ?>">
          <span>🍽️</span> Ristorazione
        </a>
        <div class="border-t border-slate-700 mt-3 pt-3">
          <a href="/api/admin/utenti.php" class="nav-link <?= ($pageTitle==='Utenti'?'active':'') ?>">
            <span>👥</span> Utenti
          </a>
          <a href="/api/admin/comuni.php" class="nav-link <?= ($pageTitle==='Comuni B2G'?'active':'') ?>">
            <span>🏛️</span> Comuni B2G
          </a>
          <a href="/api/admin/statistiche.php" class="nav-link <?= ($pageTitle==='Statistiche'?'active':'') ?>">
            <span>📊</span> Statistiche
          </a>
        </div>
      </nav>
      <div class="px-3 py-4 border-t border-slate-700">
        <a href="/api/admin/logout.php" class="nav-link text-red-400 hover:text-red-300 hover:bg-red-900/20">
          <span>🚪</span> Esci
        </a>
      </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <header class="bg-slate-800 border-b border-slate-700 px-6 py-3 flex items-center justify-between">
        <h2 class="font-semibold text-white"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h2>
        <span class="text-xs text-slate-400">Benvenuto, <?= htmlspecialchars(ADMIN_USER) ?></span>
      </header>
      <main class="flex-1 overflow-y-auto p-6">
