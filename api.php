<?php
/**
 * O Rodrigo Foi Treinar? — API Router
 *
 * All endpoints: api.php?action=X
 */

declare(strict_types=1);

require_once __DIR__ . '/init.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// CSRF: require JSON content-type on POST
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'application/json') === false) {
        jsonError('Content-Type deve ser application/json.', 400);
    }
}

$db = initDatabase();
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'status':     requireMethod('GET');  handleStatus($db);     break;
        case 'history':    requireMethod('GET');  handleHistory($db);    break;
        case 'log_day':    requireMethod('POST'); handleLogDay($db);     break;
        case 'delete_day': requireMethod('POST'); handleDeleteDay($db);  break;
        case 'withdraw':   requireMethod('POST'); handleWithdraw($db);   break;
        case 'verify_pin': requireMethod('POST'); handleVerifyPin($db);  break;
        default:           jsonError('Ação desconhecida.', 404);         break;
    }
} catch (PDOException $e) {
    jsonError('Erro de base de dados.', 500);
} catch (Exception $e) {
    jsonError('Erro interno.', 500);
}

// ---------------------------------------------------------------------------
// Handlers
// ---------------------------------------------------------------------------

function handleStatus(PDO $db): void
{
    $result = calculateBalance($db);
    $currentWeek = getCurrentWeek($db);
    $challengeEnd     = getSetting($db, 'challenge_end_date') ?? '2025-05-30';
    $challengeName    = getSetting($db, 'challenge_name');
    $challengeArticle = getSetting($db, 'challenge_article');

    // For projection, pass completed weeks in chronological order
    $weeksChronological = array_reverse($result['weeks']);
    $projection = calculateProjection(
        $result['balance'],
        $result['streak'],
        $weeksChronological,
        $challengeEnd,
        $challengeName,
        $challengeArticle
    );

    $today = date('Y-m-d');

    jsonSuccess([
        'balance'          => $result['balance'],
        'current_week'     => $currentWeek,
        'streak'           => $result['streak'],
        'projection'       => $projection,
        'challenge_active' => $today <= $challengeEnd,
        'today'            => $today,
    ]);
}

function handleHistory(PDO $db): void
{
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(50, max(1, (int)($_GET['per_page'] ?? 20)));

    $result = calculateBalance($db);
    $allWeeks = $result['weeks']; // already newest-first

    $totalWeeks = count($allWeeks);
    $totalPages = max(1, (int)ceil($totalWeeks / $perPage));
    $offset = ($page - 1) * $perPage;
    $weeks = array_slice($allWeeks, $offset, $perPage);

    $withdrawals = getWithdrawals($db);

    jsonSuccess([
        'weeks'        => $weeks,
        'withdrawals'  => $withdrawals,
        'total_pages'  => $totalPages,
        'current_page' => $page,
        'balance'      => $result['balance'],
    ]);
}

function handleLogDay(PDO $db): void
{
    $body = getJsonBody();
    requirePin($db, $body);

    $date = trim($body['date'] ?? date('Y-m-d'));
    if (!validateDate($date)) {
        jsonError('Data inválida. Deve ser nos últimos 7 dias e no formato AAAA-MM-DD.', 400);
    }

    try {
        $stmt = $db->prepare("INSERT INTO gym_logs (log_date, logged_by) VALUES (?, '')");
        $stmt->execute([$date]);
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'UNIQUE constraint')) {
            // Check if it was soft-deleted — if so, restore it
            $stmt = $db->prepare("SELECT id, deleted_at FROM gym_logs WHERE log_date = ?");
            $stmt->execute([$date]);
            $existing = $stmt->fetch();
            if ($existing && $existing['deleted_at'] !== null) {
                $db->prepare("UPDATE gym_logs SET deleted_at = NULL WHERE id = ?")
                   ->execute([$existing['id']]);
                jsonMessage("Dia restaurado! Rodrigo foi ao ginásio a {$date}.");
            }
            jsonError('Já existe registo para este dia.', 409);
        }
        throw $e;
    }

    jsonMessage("Dia registado! Rodrigo foi ao ginásio a {$date}.");
}

function handleDeleteDay(PDO $db): void
{
    $body = getJsonBody();
    requirePin($db, $body);

    $date = trim($body['date'] ?? '');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        jsonError('Data inválida.', 400);
    }

    $stmt = $db->prepare("UPDATE gym_logs SET deleted_at = strftime('%Y-%m-%dT%H:%M:%S', 'now') WHERE log_date = ? AND deleted_at IS NULL");
    $stmt->execute([$date]);

    if ($stmt->rowCount() === 0) {
        jsonError('Não existe registo para este dia.', 404);
    }

    jsonMessage("Registo de {$date} removido.");
}

function handleWithdraw(PDO $db): void
{
    $body = getJsonBody();
    requirePin($db, $body);

    $amount = validateAmount($body['amount'] ?? null);
    $note = trim($body['note'] ?? '');
    if (mb_strlen($note) > 200) {
        $note = mb_substr($note, 0, 200);
    }

    $stmt = $db->prepare("INSERT INTO withdrawals (amount, note, logged_by) VALUES (?, ?, '')");
    $stmt->execute([$amount, $note]);

    $formatted = number_format($amount, 2, ',', '.');
    jsonMessage("Levantamento de {$formatted}\u{00a0}€ registado.");
}

function handleVerifyPin(PDO $db): void
{
    $body = getJsonBody();
    $pin = $body['pin'] ?? '';

    if (!checkRateLimit()) {
        jsonError('Demasiadas tentativas. Espera 15 minutos.', 429);
    }

    if (!verifyPin($db, $pin)) {
        jsonError('PIN incorreto.', 401);
    }

    jsonSuccess([]);
}
