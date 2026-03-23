<?php
/**
 * MetaBorghi — Users API
 * GET    /api/v1/users.php              — Lista utenti (solo admin)
 * GET    /api/v1/users.php?id={id}      — Profilo utente
 * PUT    /api/v1/users.php?id={id}      — Aggiorna profilo
 * PUT    /api/v1/users.php?action=password — Cambio password
 * DELETE /api/v1/users.php?id={id}      — Disattiva utente (solo admin)
 *
 * Admin: accesso completo a tutti gli utenti
 * Utente autenticato: può vedere/modificare solo il proprio profilo
 */
require_once __DIR__ . '/../config/db.php';
jsonHeaders();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;
$action = $_GET['action'] ?? '';

ensureAdminUsersTable($db);

// ── CAMBIO PASSWORD ───────────────────────────────────────────────
if ($action === 'password' && $method === 'PUT') {
    $jwt  = requireJwtAuth();
    $body = getJsonBody();
    $currentPassword = $body['current_password'] ?? '';
    $newPassword     = $body['new_password'] ?? '';

    if (strlen($newPassword) < 8) {
        http_response_code(400);
        echo json_encode(['error' => 'La nuova password deve avere almeno 8 caratteri']);
        exit;
    }

    $stmt = $db->prepare("SELECT id, password_hash FROM admin_users WHERE id = ?");
    $stmt->execute([$jwt['sub']]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Password attuale non corretta']);
        exit;
    }

    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
    $db->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?")
       ->execute([$newHash, $jwt['sub']]);

    echo json_encode(['ok' => true, 'message' => 'Password aggiornata']);
    exit;
}

// ── GET: Lista utenti (admin) o profilo singolo ───────────────────
if ($method === 'GET') {
    $jwt = requireJwtAuth();

    if ($id) {
        // Admin può vedere chiunque, utente solo se stesso
        if ($jwt['role'] !== 'admin' && $jwt['sub'] !== $id) {
            http_response_code(403);
            echo json_encode(['error' => 'Accesso non consentito']);
            exit;
        }
        $stmt = $db->prepare("SELECT * FROM admin_users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'Utente non trovato']);
            exit;
        }
        echo json_encode(_userResponse($user, $jwt['role'] === 'admin'));
    } else {
        // Lista: solo admin
        requireRole('admin');
        $role = $_GET['role'] ?? null;
        $active = $_GET['active'] ?? null;

        $sql = "SELECT * FROM admin_users WHERE 1=1";
        $params = [];
        if ($role) { $sql .= " AND role = ?"; $params[] = $role; }
        if ($active !== null) { $sql .= " AND is_active = ?"; $params[] = (int)$active; }
        $sql .= " ORDER BY role ASC, name ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();
        echo json_encode(array_map(fn($u) => _userResponse($u, true), $users));
    }
    exit;
}

// ── PUT: Aggiorna profilo ─────────────────────────────────────────
if ($method === 'PUT' && $id) {
    $jwt  = requireJwtAuth();
    $body = getJsonBody();

    // Admin può modificare chiunque, utente solo se stesso
    $isAdmin = ($jwt['role'] === 'admin');
    if (!$isAdmin && $jwt['sub'] !== $id) {
        http_response_code(403);
        echo json_encode(['error' => 'Accesso non consentito']);
        exit;
    }

    // Campi che l'utente può modificare
    $allowed = ['name', 'phone', 'bio', 'avatar_url'];
    // Campi aggiuntivi solo per admin
    if ($isAdmin) {
        $allowed = array_merge($allowed, ['email', 'role', 'is_active', 'borough_id', 'company_id', 'email_verified']);
    }

    $sets = [];
    $params = [];
    foreach ($allowed as $field) {
        if (array_key_exists($field, $body)) {
            $val = $body[$field];
            if (in_array($field, ['is_active', 'email_verified'])) {
                $val = $val ? 1 : 0;
            }
            $sets[] = "`$field` = ?";
            $params[] = $val;
        }
    }

    // Admin può anche impostare la password
    if ($isAdmin && !empty($body['password'])) {
        $sets[] = "`password_hash` = ?";
        $params[] = password_hash($body['password'], PASSWORD_BCRYPT);
    }

    if (empty($sets)) {
        http_response_code(400);
        echo json_encode(['error' => 'Nessun campo da aggiornare']);
        exit;
    }

    $params[] = $id;
    $db->prepare("UPDATE admin_users SET " . implode(', ', $sets) . " WHERE id = ?")
       ->execute($params);

    // Ritorna il profilo aggiornato
    $stmt = $db->prepare("SELECT * FROM admin_users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    echo json_encode(['ok' => true, 'user' => _userResponse($user, $isAdmin)]);
    exit;
}

// ── DELETE: Disattiva utente (solo admin) ──────────────────────────
if ($method === 'DELETE' && $id) {
    requireRole('admin');

    // Non si può disattivare se stessi
    $jwt = getAuthUser();
    if ($jwt['sub'] === $id) {
        http_response_code(400);
        echo json_encode(['error' => 'Non puoi disattivare il tuo account']);
        exit;
    }

    $db->prepare("UPDATE admin_users SET is_active = 0 WHERE id = ?")->execute([$id]);
    echo json_encode(['ok' => true, 'message' => 'Utente disattivato']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

// ── Helper ────────────────────────────────────────────────────────
function _userResponse(array $u, bool $full = false): array {
    $out = [
        'id'         => $u['id'],
        'name'       => $u['name'],
        'role'       => $u['role'],
        'avatar_url' => $u['avatar_url'] ?? null,
        'bio'        => $u['bio'] ?? null,
    ];
    if ($full) {
        $out['email']          = $u['email'];
        $out['phone']          = $u['phone'] ?? null;
        $out['borough_id']     = $u['borough_id'] ?? null;
        $out['company_id']     = $u['company_id'] ?? null;
        $out['is_active']      = (bool)$u['is_active'];
        $out['email_verified'] = (bool)($u['email_verified'] ?? false);
        $out['last_login_at']  = $u['last_login_at'] ?? null;
        $out['created_at']     = $u['created_at'] ?? null;
    }
    return $out;
}
