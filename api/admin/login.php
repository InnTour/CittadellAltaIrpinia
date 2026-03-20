<?php
require_once __DIR__ . '/../config/db.php';
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['username'] === ADMIN_USER && $_POST['password'] === ADMIN_PASS) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: /api/admin/');
        exit;
    }
    $error = 'Credenziali non valide.';
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MetaBorghi — Admin Login</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>body{background:#0f172a}</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
  <div class="w-full max-w-sm">
    <div class="text-center mb-8">
      <h1 class="text-3xl font-bold text-white mb-1">MetaBorghi</h1>
      <p class="text-slate-400 text-sm">Pannello di amministrazione</p>
    </div>
    <form method="POST" class="bg-slate-800 rounded-2xl p-8 shadow-2xl space-y-5">
      <?php if ($error): ?>
        <div class="bg-red-500/20 border border-red-500 text-red-300 rounded-lg px-4 py-3 text-sm"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <div>
        <label class="block text-slate-300 text-sm font-medium mb-1">Username</label>
        <input type="text" name="username" required autofocus
          class="w-full bg-slate-700 text-white rounded-lg px-4 py-2.5 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
      </div>
      <div>
        <label class="block text-slate-300 text-sm font-medium mb-1">Password</label>
        <input type="password" name="password" required
          class="w-full bg-slate-700 text-white rounded-lg px-4 py-2.5 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
      </div>
      <button type="submit"
        class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg py-2.5 transition-colors">
        Accedi
      </button>
    </form>
  </div>
</body>
</html>
