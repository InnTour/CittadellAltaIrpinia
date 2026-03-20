<?php
/**
 * MetaBorghi — Bookings API
 * GET    /api/v1/bookings.php              — Lista prenotazioni (utente: le sue, admin: tutte)
 * GET    /api/v1/bookings.php?id={id}      — Dettaglio prenotazione
 * POST   /api/v1/bookings.php              — Crea prenotazione
 * PUT    /api/v1/bookings.php?id={id}      — Aggiorna stato (admin/operatore) o note (utente)
 * DELETE /api/v1/bookings.php?id={id}      — Cancella prenotazione
 */
require_once __DIR__ . '/../config/db.php';
jsonHeaders();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;

// Assicura tabella bookings
$db->exec("CREATE TABLE IF NOT EXISTS `bookings` (
  `id`                       VARCHAR(100) NOT NULL,
  `user_id`                  VARCHAR(100) NOT NULL,
  `booking_type`             ENUM('experience','accommodation') NOT NULL DEFAULT 'experience',
  `experience_id`            VARCHAR(100) DEFAULT NULL,
  `accommodation_id`         VARCHAR(100) DEFAULT NULL,
  `status`                   ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
  `booking_date`             DATE         NOT NULL,
  `booking_time`             VARCHAR(10)  DEFAULT NULL,
  `guests_count`             INT          NOT NULL DEFAULT 1,
  `total_price_cents`        INT          DEFAULT NULL,
  `notes`                    TEXT         DEFAULT NULL,
  `contact_name`             VARCHAR(200) DEFAULT NULL,
  `contact_email`            VARCHAR(200) DEFAULT NULL,
  `contact_phone`            VARCHAR(50)  DEFAULT NULL,
  `stripe_payment_intent_id` VARCHAR(255) DEFAULT NULL,
  `created_at`               TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`               TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `experience_id` (`experience_id`),
  KEY `accommodation_id` (`accommodation_id`),
  KEY `status` (`status`),
  KEY `booking_date` (`booking_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$user   = requireRole('registrato', 'operatore', 'admin');
$userId = $user['sub'];
$isAdmin = in_array($user['role'], ['admin', 'operatore']);

// ── GET: lista o dettaglio ────────────────────────────────────────
if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare("SELECT * FROM bookings WHERE id = ?");
        $stmt->execute([$id]);
        $booking = $stmt->fetch();
        if (!$booking) {
            http_response_code(404);
            echo json_encode(['error' => 'Prenotazione non trovata']);
            exit;
        }
        // Solo il proprietario o admin può vedere
        if (!$isAdmin && $booking['user_id'] !== $userId) {
            http_response_code(403);
            echo json_encode(['error' => 'Accesso non consentito']);
            exit;
        }
        echo json_encode(_buildBooking($db, $booking));
    } else {
        $status = $_GET['status'] ?? null;
        $type   = $_GET['type'] ?? null;

        $sql = "SELECT * FROM bookings WHERE 1=1";
        $params = [];
        // Admin vede tutto, utente solo le sue
        if (!$isAdmin) { $sql .= " AND user_id = ?"; $params[] = $userId; }
        if ($status)    { $sql .= " AND status = ?";  $params[] = $status; }
        if ($type)      { $sql .= " AND booking_type = ?"; $params[] = $type; }
        $sql .= " ORDER BY booking_date DESC, created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        echo json_encode(array_map(fn($b) => _buildBooking($db, $b), $stmt->fetchAll()));
    }
    exit;
}

// ── POST: crea prenotazione ───────────────────────────────────────
if ($method === 'POST') {
    $body = getJsonBody();

    $bookingType    = $body['booking_type'] ?? 'experience';
    $experienceId   = $body['experience_id'] ?? null;
    $accommodationId = $body['accommodation_id'] ?? null;
    $bookingDate    = $body['booking_date'] ?? null;
    $bookingTime    = $body['booking_time'] ?? null;
    $guestsCount    = (int)($body['guests_count'] ?? 1);
    $notes          = $body['notes'] ?? null;
    $contactName    = $body['contact_name'] ?? ($user['name'] ?? null);
    $contactEmail   = $body['contact_email'] ?? ($user['email'] ?? null);
    $contactPhone   = $body['contact_phone'] ?? null;

    // Validazione
    if (!$bookingDate) {
        http_response_code(400);
        echo json_encode(['error' => 'La data di prenotazione è obbligatoria']);
        exit;
    }
    if ($bookingType === 'experience' && !$experienceId) {
        http_response_code(400);
        echo json_encode(['error' => 'experience_id è obbligatorio per prenotazioni esperienze']);
        exit;
    }
    if ($bookingType === 'accommodation' && !$accommodationId) {
        http_response_code(400);
        echo json_encode(['error' => 'accommodation_id è obbligatorio per prenotazioni alloggi']);
        exit;
    }
    if ($guestsCount < 1) $guestsCount = 1;

    // Calcola prezzo (se esperienza)
    $totalPriceCents = null;
    if ($experienceId) {
        $stmt = $db->prepare("SELECT price_per_person FROM experiences WHERE id = ?");
        $stmt->execute([$experienceId]);
        $exp = $stmt->fetch();
        if ($exp && $exp['price_per_person']) {
            $totalPriceCents = (int)($exp['price_per_person'] * 100 * $guestsCount);
        }
    }

    $bookingId = 'bk-' . bin2hex(random_bytes(12));
    $db->prepare("INSERT INTO bookings
        (id, user_id, booking_type, experience_id, accommodation_id, status,
         booking_date, booking_time, guests_count, total_price_cents, notes,
         contact_name, contact_email, contact_phone)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
    ->execute([
        $bookingId, $userId, $bookingType, $experienceId, $accommodationId,
        'pending', $bookingDate, $bookingTime, $guestsCount, $totalPriceCents,
        $notes, $contactName, $contactEmail, $contactPhone,
    ]);

    $stmt = $db->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();

    http_response_code(201);
    echo json_encode(['ok' => true, 'booking' => _buildBooking($db, $booking)]);
    exit;
}

// ── PUT: aggiorna prenotazione ────────────────────────────────────
if ($method === 'PUT' && $id) {
    $stmt = $db->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->execute([$id]);
    $booking = $stmt->fetch();
    if (!$booking) {
        http_response_code(404);
        echo json_encode(['error' => 'Prenotazione non trovata']);
        exit;
    }

    $body = getJsonBody();

    if ($isAdmin) {
        // Admin/operatore può cambiare stato e tutti i campi
        $allowed = ['status', 'booking_date', 'booking_time', 'guests_count',
                    'total_price_cents', 'notes', 'contact_name', 'contact_email', 'contact_phone'];
    } else {
        // Utente può solo aggiornare note e cancellare (se pending)
        if ($booking['user_id'] !== $userId) {
            http_response_code(403);
            echo json_encode(['error' => 'Accesso non consentito']);
            exit;
        }
        $allowed = ['notes', 'contact_phone'];
        // Utente può cancellare solo se pending
        if (isset($body['status']) && $body['status'] === 'cancelled' && $booking['status'] === 'pending') {
            $allowed[] = 'status';
        }
    }

    $sets = [];
    $params = [];
    foreach ($allowed as $field) {
        if (array_key_exists($field, $body)) {
            $sets[] = "`$field` = ?";
            $params[] = $body[$field];
        }
    }

    if (empty($sets)) {
        http_response_code(400);
        echo json_encode(['error' => 'Nessun campo da aggiornare']);
        exit;
    }

    $params[] = $id;
    $db->prepare("UPDATE bookings SET " . implode(', ', $sets) . " WHERE id = ?")
       ->execute($params);

    $stmt = $db->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['ok' => true, 'booking' => _buildBooking($db, $stmt->fetch())]);
    exit;
}

// ── DELETE: cancella prenotazione ─────────────────────────────────
if ($method === 'DELETE' && $id) {
    $stmt = $db->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->execute([$id]);
    $booking = $stmt->fetch();
    if (!$booking) {
        http_response_code(404);
        echo json_encode(['error' => 'Prenotazione non trovata']);
        exit;
    }

    if (!$isAdmin && $booking['user_id'] !== $userId) {
        http_response_code(403);
        echo json_encode(['error' => 'Accesso non consentito']);
        exit;
    }

    // Utente non admin può cancellare solo se pending
    if (!$isAdmin && $booking['status'] !== 'pending') {
        http_response_code(400);
        echo json_encode(['error' => 'Puoi cancellare solo prenotazioni in attesa']);
        exit;
    }

    if ($isAdmin) {
        $db->prepare("DELETE FROM bookings WHERE id = ?")->execute([$id]);
        echo json_encode(['ok' => true, 'message' => 'Prenotazione eliminata']);
    } else {
        $db->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?")->execute([$id]);
        echo json_encode(['ok' => true, 'message' => 'Prenotazione cancellata']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

// ── Helper: costruisce oggetto booking per la risposta ─────────────
function _buildBooking(PDO $db, array $b): array {
    $out = $b;
    $out['guests_count']      = (int)$b['guests_count'];
    $out['total_price_cents']  = $b['total_price_cents'] ? (int)$b['total_price_cents'] : null;
    $out['total_price_display'] = $out['total_price_cents']
        ? number_format($out['total_price_cents'] / 100, 2, ',', '.') . ' €'
        : null;

    // Arricchisci con info entità
    if ($b['experience_id']) {
        $stmt = $db->prepare("SELECT title, slug, cover_image FROM experiences WHERE id = ?");
        $stmt->execute([$b['experience_id']]);
        $out['experience'] = $stmt->fetch() ?: null;
    }
    if ($b['accommodation_id']) {
        $stmt = $db->prepare("SELECT name, slug, cover_image FROM accommodations WHERE id = ?");
        $stmt->execute([$b['accommodation_id']]);
        $out['accommodation'] = $stmt->fetch() ?: null;
    }

    unset($out['stripe_payment_intent_id']); // Non esporre dati Stripe
    return $out;
}
