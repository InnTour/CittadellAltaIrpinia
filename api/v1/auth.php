<?php
/**
 * MetaBorghi — Auth API
 * POST /api/v1/auth.php?action=register  — Registrazione utente
 * POST /api/v1/auth.php?action=login     — Login (ritorna JWT)
 * GET  /api/v1/auth.php?action=me        — Profilo utente corrente
 * POST /api/v1/auth.php?action=logout    — Logout (client-side, invalida info)
 */
require_once __DIR__ . '/../config/db.php';
jsonHeaders();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

ensureAdminUsersTable($db);

// ── REGISTER ──────────────────────────────────────────────────────
if ($action === 'register' && $method === 'POST') {
    $body = getJsonBody();
    $name     = trim($body['name'] ?? '');
    $email    = trim($body['email'] ?? '');
    $password = $body['password'] ?? '';
    $phone    = trim($body['phone'] ?? '');

    // Validazione
    if (!$name || strlen($name) < 2) {
        http_response_code(400);
        echo json_encode(['error' => 'Il nome deve avere almeno 2 caratteri']);
        exit;
    }
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email non valida']);
        exit;
    }
    if (strlen($password) < 8) {
        http_response_code(400);
        echo json_encode(['error' => 'La password deve avere almeno 8 caratteri']);
        exit;
    }

    // Verifica email duplicata
    $stmt = $db->prepare("SELECT id FROM admin_users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Email già registrata']);
        exit;
    }

    // Crea utente
    $userId = bin2hex(random_bytes(16));
    $hash   = password_hash($password, PASSWORD_BCRYPT);

    $db->prepare("INSERT INTO admin_users (id, name, email, password_hash, role, phone, is_active, email_verified)
                  VALUES (?, ?, ?, ?, 'registrato', ?, 1, 0)")
       ->execute([$userId, $name, $email, $hash, $phone ?: null]);

    // Genera JWT
    $token = jwtEncode([
        'sub'   => $userId,
        'email' => $email,
        'name'  => $name,
        'role'  => 'registrato',
    ]);

    http_response_code(201);
    echo json_encode([
        'ok'    => true,
        'token' => $token,
        'user'  => [
            'id'    => $userId,
            'name'  => $name,
            'email' => $email,
            'role'  => 'registrato',
            'phone' => $phone ?: null,
        ],
    ]);
    exit;
}

// ── LOGIN ─────────────────────────────────────────────────────────
if ($action === 'login' && $method === 'POST') {
    $body = getJsonBody();
    $email    = trim($body['email'] ?? '');
    $password = $body['password'] ?? '';

    if (!$email || !$password) {
        http_response_code(400);
        echo json_encode(['error' => 'Email e password sono obbligatori']);
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM admin_users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Credenziali non valide']);
        exit;
    }

    // Aggiorna last_login_at
    $db->prepare("UPDATE admin_users SET last_login_at = NOW() WHERE id = ?")->execute([$user['id']]);

    $token = jwtEncode([
        'sub'   => $user['id'],
        'email' => $user['email'],
        'name'  => $user['name'],
        'role'  => $user['role'],
    ]);

    echo json_encode([
        'ok'    => true,
        'token' => $token,
        'user'  => _buildUserProfile($user),
    ]);
    exit;
}

// ── ME (profilo corrente) ─────────────────────────────────────────
if ($action === 'me' && $method === 'GET') {
    $jwt = requireJwtAuth();
    $stmt = $db->prepare("SELECT * FROM admin_users WHERE id = ? AND is_active = 1");
    $stmt->execute([$jwt['sub']]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'Utente non trovato']);
        exit;
    }

    echo json_encode(['ok' => true, 'user' => _buildUserProfile($user)]);
    exit;
}

// ── REFRESH TOKEN ─────────────────────────────────────────────────
if ($action === 'refresh' && $method === 'POST') {
    $jwt = requireJwtAuth();
    // Verifica che l'utente esista ancora e sia attivo
    $stmt = $db->prepare("SELECT * FROM admin_users WHERE id = ? AND is_active = 1");
    $stmt->execute([$jwt['sub']]);
    $user = $stmt->fetch();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Utente non trovato o disattivato']);
        exit;
    }

    $token = jwtEncode([
        'sub'   => $user['id'],
        'email' => $user['email'],
        'name'  => $user['name'],
        'role'  => $user['role'],
    ]);

    echo json_encode(['ok' => true, 'token' => $token]);
    exit;
}

// ── LOGOUT (informativo, il client elimina il token) ──────────────
if ($action === 'logout') {
    echo json_encode(['ok' => true, 'message' => 'Token eliminato lato client']);
    exit;
}

// ── Fallback ──────────────────────────────────────────────────────
http_response_code(400);
echo json_encode(['error' => 'Azione non valida. Usa: register, login, me, refresh, logout']);

// ── Helper: costruisce profilo utente per la risposta ─────────────
function _buildUserProfile(array $u): array {
    return [
        'id'             => $u['id'],
        'name'           => $u['name'],
        'email'          => $u['email'],
        'role'           => $u['role'],
        'phone'          => $u['phone'] ?? null,
        'bio'            => $u['bio'] ?? null,
        'avatar_url'     => $u['avatar_url'] ?? null,
        'borough_id'     => $u['borough_id'] ?? null,
        'company_id'     => $u['company_id'] ?? null,
        'is_active'      => (bool)$u['is_active'],
        'email_verified' => (bool)($u['email_verified'] ?? false),
        'last_login_at'  => $u['last_login_at'] ?? null,
        'created_at'     => $u['created_at'] ?? null,
    ];
}
