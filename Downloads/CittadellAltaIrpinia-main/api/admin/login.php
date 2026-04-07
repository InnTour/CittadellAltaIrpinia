<?php
require_once __DIR__ . '/../config/db.php';
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputUser = trim($_POST['username'] ?? '');
    $inputPass = $_POST['password'] ?? '';
    $authenticated = false;
    $sessionRole = 'admin';
    $sessionUserId = null;
    $sessionUserName = null;

    $sessionOperatorType = null;
    $sessionBoroughId    = null;
    $sessionCompanyId    = null;

    // 1. Prova autenticazione via admin_users table (bcrypt)
    try {
        $db = getDB();
        ensureAdminUsersTable($db);
        $stmt = $db->prepare("SELECT * FROM admin_users WHERE (email = ? OR id = ?) AND is_active = 1 AND role IN ('admin','operatore')");
        $stmt->execute([$inputUser, $inputUser]);
        $dbUser = $stmt->fetch();
        if ($dbUser && password_verify($inputPass, $dbUser['password_hash'])) {
            $authenticated       = true;
            $sessionRole         = $dbUser['role'];
            $sessionUserId       = $dbUser['id'];
            $sessionUserName     = $dbUser['name'];
            $sessionOperatorType = $dbUser['operator_type'] ?? null;
            $sessionBoroughId    = $dbUser['borough_id']    ?? null;
            $sessionCompanyId    = $dbUser['company_id']    ?? null;
            $db->prepare("UPDATE admin_users SET last_login_at = NOW() WHERE id = ?")->execute([$dbUser['id']]);
        }
    } catch (Throwable $e) {
        // DB non raggiungibile o tabella non creabile — prosegui con fallback statico
    }

    // 2. Fallback: credenziali statiche legacy (solo admin)
    if (!$authenticated && $inputUser === ADMIN_USER && $inputPass === ADMIN_PASS) {
        $authenticated = true;
        $sessionRole   = 'admin';
    }

    if ($authenticated) {
        $_SESSION['admin_logged_in']      = true;
        $_SESSION['admin_role']           = $sessionRole;
        $_SESSION['admin_user_id']        = $sessionUserId;
        $_SESSION['admin_user_name']      = $sessionUserName;
        $_SESSION['admin_operator_type']  = $sessionOperatorType;
        $_SESSION['admin_borough_id']     = $sessionBoroughId;
        $_SESSION['admin_company_id']     = $sessionCompanyId;
        // Redirige operatori direttamente alla loro sezione
        if ($sessionRole === 'operatore' && $sessionOperatorType) {
            $opPages = [
                'borough'       => '/api/admin/borghi.php',
                'company'       => '/api/admin/aziende.php',
                'experience'    => '/api/admin/esperienze.php',
                'craft'         => '/api/admin/artigianato.php',
                'food'          => '/api/admin/prodotti.php',
                'accommodation' => '/api/admin/ospitalita.php',
                'restaurant'    => '/api/admin/ristorazione.php',
            ];
            header('Location: ' . ($opPages[$sessionOperatorType] ?? '/api/admin/'));
        } else {
            header('Location: /api/admin/');
        }
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
        <label class="block text-slate-300 text-sm font-medium mb-1">Email o Username</label>
        <input type="text" name="username" required autofocus placeholder="admin@metaborghi.org"
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
