<?php
/**
 * O Rodrigo Foi Treinar? — Database initialization, helpers, balance engine
 */

declare(strict_types=1);

date_default_timezone_set('Europe/Lisbon');

// ---------------------------------------------------------------------------
// Database
// ---------------------------------------------------------------------------

function initDatabase(): PDO
{
    $dbPath = __DIR__ . '/data/rodrigo.db';
    $isNew = !file_exists($dbPath);

    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('PRAGMA foreign_keys=ON');

    // Create tables
    $db->exec("CREATE TABLE IF NOT EXISTS gym_logs (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        log_date   TEXT    NOT NULL UNIQUE,
        logged_by  TEXT    NOT NULL,
        created_at TEXT    NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%S', 'now')),
        deleted_at TEXT    DEFAULT NULL
    )");

    $db->exec("CREATE INDEX IF NOT EXISTS idx_gym_logs_log_date ON gym_logs(log_date)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_gym_logs_deleted ON gym_logs(deleted_at)");

    $db->exec("CREATE TABLE IF NOT EXISTS withdrawals (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        amount     REAL    NOT NULL CHECK(amount > 0),
        note       TEXT    DEFAULT '',
        logged_by  TEXT    NOT NULL,
        created_at TEXT    NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%S', 'now'))
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS settings (
        key   TEXT PRIMARY KEY,
        value TEXT NOT NULL
    )");

    // Seed defaults on first run
    if ($isNew) {
        $hash = password_hash('1234', PASSWORD_BCRYPT, ['cost' => 12]);
        $db->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)")
           ->execute(['pin_hash', $hash]);
        $db->exec("INSERT OR IGNORE INTO settings (key, value) VALUES ('challenge_end_date', '2025-05-30')");
    }

    return $db;
}

// ---------------------------------------------------------------------------
// Settings helpers
// ---------------------------------------------------------------------------

function getSetting(PDO $db, string $key): ?string
{
    $stmt = $db->prepare("SELECT value FROM settings WHERE key = ?");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    return $val === false ? null : $val;
}

// ---------------------------------------------------------------------------
// PIN & Auth
// ---------------------------------------------------------------------------

function verifyPin(PDO $db, string $pin): bool
{
    $hash = getSetting($db, 'pin_hash');
    if ($hash === null) return false;
    return password_verify($pin, $hash);
}

function requirePin(PDO $db, array $body): void
{
    $pin = $body['pin'] ?? '';
    if (!checkRateLimit()) {
        jsonError('Demasiadas tentativas. Espera 15 minutos.', 429);
    }
    if (!verifyPin($db, $pin)) {
        jsonError('PIN incorreto.', 401);
    }
}

// ---------------------------------------------------------------------------
// Rate limiting (file-based)
// ---------------------------------------------------------------------------

function checkRateLimit(string $action = 'pin', int $maxAttempts = 10, int $windowSeconds = 900): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = md5($ip . $action);
    $file = __DIR__ . '/data/rate_' . $key . '.json';

    if (!file_exists($file)) {
        file_put_contents($file, json_encode(['attempts' => 1, 'first_at' => time()]));
        return true;
    }

    $data = json_decode(file_get_contents($file), true);
    if (!$data) {
        @unlink($file);
        return true;
    }

    if (time() - $data['first_at'] > $windowSeconds) {
        file_put_contents($file, json_encode(['attempts' => 1, 'first_at' => time()]));
        return true;
    }

    if ($data['attempts'] >= $maxAttempts) {
        return false;
    }

    $data['attempts']++;
    file_put_contents($file, json_encode($data));
    return true;
}

// ---------------------------------------------------------------------------
// Validation
// ---------------------------------------------------------------------------

function validateDate(string $date): bool
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return false;
    $parts = explode('-', $date);
    if (!checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])) return false;
    if ($date > date('Y-m-d')) return false;
    $currentMonday = getWeekMonday(date('Y-m-d'));
    if ($date < $currentMonday) return false;
    return true;
}

function validateAmount($amount): float
{
    $amount = filter_var($amount, FILTER_VALIDATE_FLOAT);
    if ($amount === false || $amount <= 0 || $amount > 100) {
        jsonError('Valor inválido (deve ser entre €0.01 e €100.00).', 400);
    }
    return $amount;
}

// ---------------------------------------------------------------------------
// JSON response helpers
// ---------------------------------------------------------------------------

function jsonSuccess(array $data): void
{
    echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonMessage(string $message): void
{
    echo json_encode(['ok' => true, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError(string $error, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $error], JSON_UNESCAPED_UNICODE);
    exit;
}

function requireMethod(string $expected): void
{
    if ($_SERVER['REQUEST_METHOD'] !== $expected) {
        jsonError('Método não permitido.', 405);
    }
}

function getJsonBody(): array
{
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);
    if (!is_array($body)) {
        jsonError('Corpo do pedido inválido (JSON esperado).', 400);
    }
    return $body;
}

// ---------------------------------------------------------------------------
// Week helpers
// ---------------------------------------------------------------------------

function getWeekMonday(string $dateStr): string
{
    $date = new DateTime($dateStr);
    $dayOfWeek = (int)$date->format('N'); // 1=Mon, 7=Sun
    $date->modify('-' . ($dayOfWeek - 1) . ' days');
    return $date->format('Y-m-d');
}

function getWeekSunday(string $mondayStr): string
{
    $date = new DateTime($mondayStr);
    $date->modify('+6 days');
    return $date->format('Y-m-d');
}

// ---------------------------------------------------------------------------
// Balance Calculation Engine
// ---------------------------------------------------------------------------

function evaluateWeek(int $dayCount): float
{
    if ($dayCount <= 3) return -1.00;
    if ($dayCount === 4) return 0.00;
    if ($dayCount === 5) return 0.75;
    return 1.00; // 6 or 7
}

function calculateBalance(PDO $db): array
{
    // Fetch all active gym logs
    $stmt = $db->query("SELECT log_date, logged_by FROM gym_logs WHERE deleted_at IS NULL ORDER BY log_date ASC");
    $logs = $stmt->fetchAll();

    // Fetch total withdrawals
    $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) FROM withdrawals");
    $totalWithdrawn = (float)$stmt->fetchColumn();

    // Group logs by week Monday
    $weekBuckets = [];
    foreach ($logs as $log) {
        $monday = getWeekMonday($log['log_date']);
        $weekBuckets[$monday][] = $log;
    }

    // Determine range
    $today = new DateTime('today');
    $currentMonday = getWeekMonday($today->format('Y-m-d'));

    if (empty($weekBuckets)) {
        return [
            'balance'         => round(0 - $totalWithdrawn, 2),
            'streak'          => 0,
            'weeks'           => [],
            'total_withdrawn' => $totalWithdrawn,
        ];
    }

    $firstMonday = min(array_keys($weekBuckets));

    // Iterate all weeks from first to last completed (before current week)
    $balance = 0.0;
    $streak = 0;
    $weeksDetail = [];

    $weekStart = new DateTime($firstMonday);
    $currentMondayDt = new DateTime($currentMonday);

    while ($weekStart < $currentMondayDt) {
        $weekKey = $weekStart->format('Y-m-d');
        $weekEnd = getWeekSunday($weekKey);
        $days = $weekBuckets[$weekKey] ?? [];
        $dayCount = count($days);

        $contribution = evaluateWeek($dayCount);
        $balance += $contribution;

        // Streak
        if ($dayCount >= 5) {
            $streak++;
        } else {
            $streak = 0;
        }

        // Bonus every 4th consecutive good week
        $bonus = 0.0;
        if ($streak > 0 && $streak % 4 === 0) {
            $bonus = 0.50;
            $balance += $bonus;
        }

        $weeksDetail[] = [
            'week_start'   => $weekKey,
            'week_end'     => $weekEnd,
            'days'         => $days,
            'day_count'    => $dayCount,
            'contribution' => $contribution,
            'bonus'        => $bonus,
        ];

        $weekStart->modify('+7 days');
    }

    $balance -= $totalWithdrawn;

    return [
        'balance'         => round($balance, 2),
        'streak'          => $streak,
        'weeks'           => array_reverse($weeksDetail), // newest first
        'total_withdrawn' => $totalWithdrawn,
    ];
}

// ---------------------------------------------------------------------------
// Current week
// ---------------------------------------------------------------------------

function getCurrentWeek(PDO $db): array
{
    $today = new DateTime('today');
    $monday = getWeekMonday($today->format('Y-m-d'));
    $sunday = getWeekSunday($monday);

    $stmt = $db->prepare(
        "SELECT log_date, logged_by FROM gym_logs
         WHERE log_date BETWEEN ? AND ? AND deleted_at IS NULL
         ORDER BY log_date ASC"
    );
    $stmt->execute([$monday, $sunday]);
    $days = $stmt->fetchAll();

    return [
        'start'       => $monday,
        'end'         => $sunday,
        'days_logged' => count($days),
        'days'        => $days,
    ];
}

// ---------------------------------------------------------------------------
// Projection
// ---------------------------------------------------------------------------

function calculateProjection(float $currentBalance, int $currentStreak, array $completedWeeks, string $challengeEndDate, ?string $challengeName = null, ?string $challengeArticle = null): array
{
    $today = new DateTime('today');
    $endDate = new DateTime($challengeEndDate);

    if ($today > $endDate) {
        return ['visible' => false];
    }

    // Average days per week from completed weeks
    if (!empty($completedWeeks)) {
        $totalDays = array_sum(array_column($completedWeeks, 'day_count'));
        $avgDaysPerWeek = $totalDays / count($completedWeeks);
    } else {
        $avgDaysPerWeek = 0;
    }

    $projectedDays = (int)round($avgDaysPerWeek);
    $weeklyContribution = evaluateWeek($projectedDays);
    $isGoodWeekPace = $projectedDays >= 5;

    // Count remaining weeks
    $todayMonday = new DateTime(getWeekMonday($today->format('Y-m-d')));
    $nextMonday = clone $todayMonday;
    $nextMonday->modify('+7 days');

    $remainingWeeks = 0;
    $cursor = clone $nextMonday;
    while ($cursor <= $endDate) {
        $remainingWeeks++;
        $cursor->modify('+7 days');
    }

    // Project
    $projectedBalance = $currentBalance;
    $projectedStreak = $currentStreak;

    for ($i = 0; $i < $remainingWeeks; $i++) {
        $projectedBalance += $weeklyContribution;
        if ($isGoodWeekPace) {
            $projectedStreak++;
            if ($projectedStreak % 4 === 0) {
                $projectedBalance += 0.50;
            }
        } else {
            $projectedStreak = 0;
        }
    }

    $projectedBalance = round($projectedBalance, 2);
    $formattedAmount = number_format(abs($projectedBalance), 2, ',', '.');

    // Build the target phrase — prefer challenge name over date
    if ($challengeName !== null && $challengeName !== '') {
        $article = ($challengeArticle !== null && $challengeArticle !== '') ? $challengeArticle : 'no';
        $target = "{$article} {$challengeName}";
    } else {
        $ptMonths = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho',
                     'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
        $endFormatted = $endDate->format('j') . ' de ' . $ptMonths[(int)$endDate->format('n') - 1];
        $target = "em {$endFormatted}";
    }

    if ($projectedBalance > 0) {
        $message = $avgDaysPerWeek >= 5
            ? "Bom ritmo! O frasco terá {$formattedAmount}\u{00a0}€ {$target}."
            : "A este ritmo, o frasco terá {$formattedAmount}\u{00a0}€ {$target}.";
    } elseif ($projectedBalance == 0.0) {
        $message = "A este ritmo, o Rodrigo fica a zeros {$target}. Tem de fazer mais!";
    } else {
        $message = $avgDaysPerWeek >= 4
            ? "A este ritmo, o Rodrigo ainda vai a dever {$formattedAmount}\u{00a0}€ {$target}."
            : "A este ritmo, o Rodrigo vai a dever {$formattedAmount}\u{00a0}€ {$target}. Vergonha!";
    }

    return [
        'visible'           => true,
        'target_date'       => $challengeEndDate,
        'projected_balance' => $projectedBalance,
        'avg_days_per_week' => round($avgDaysPerWeek, 1),
        'message'           => $message,
    ];
}

// ---------------------------------------------------------------------------
// Withdrawals list
// ---------------------------------------------------------------------------

function getWithdrawals(PDO $db): array
{
    $stmt = $db->query("SELECT id, amount, note, logged_by, created_at FROM withdrawals ORDER BY created_at DESC");
    return $stmt->fetchAll();
}
