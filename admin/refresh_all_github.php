<?php

declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/github.php';

set_page_cache_control('admin');
require_admin();
verify_csrf();

const GITHUB_REFRESH_BATCH_SIZE = 2;
$stateKey = 'github_refresh_all_state';

// Bulk refresh can require hundreds of API calls. GitHub's unauthenticated
// rate limit is too low for the full directory, so require an API token.
if (!config('github.token')) {
    flash(
        'Bulk GitHub refresh requires a GitHub token. Set GITHUB_TOKEN in your local .env file and try again. Unauthenticated API access is limited to 60 requests per hour.',
        'warning'
    );
    redirect('dashboard.php');
}

$state = $_SESSION[$stateKey] ?? null;

// A new dashboard submission starts a fresh run; continuation posts include cursor_id.
if (!isset($_POST['cursor_id']) || !is_array($state)) {
    $total = (int) db()->query(
        'SELECT COUNT(*) FROM profiles WHERE visibility != "hidden"'
    )->fetchColumn();

    $state = [
        'cursor_id' => 0,
        'total' => $total,
        'processed' => 0,
        'success' => 0,
        'failed' => 0,
    ];
    $_SESSION[$stateKey] = $state;
}

$submittedCursor = isset($_POST['cursor_id']) ? max(0, (int) $_POST['cursor_id']) : 0;
$hasMore = true;

// A browser retry can submit an older form. Do not process the same batch twice.
if ($submittedCursor === (int) $state['cursor_id']) {
    $stmt = db()->prepare(
        'SELECT id, github_username
         FROM profiles
         WHERE visibility != "hidden" AND id > ?
         ORDER BY id
         LIMIT ' . GITHUB_REFRESH_BATCH_SIZE
    );
    $stmt->execute([(int) $state['cursor_id']]);
    $profiles = $stmt->fetchAll();
    $hasMore = count($profiles) === GITHUB_REFRESH_BATCH_SIZE;

    foreach ($profiles as $profile) {
        $state['cursor_id'] = (int) $profile['id'];
        $state['processed']++;

        try {
            refresh_profile_from_github((int) $profile['id']);
            $state['success']++;
        } catch (Throwable $e) {
            $state['failed']++;
            error_log(
                'DEVSMW GitHub refresh failed for profile #' .
                (int) $profile['id'] . ': ' . $e->getMessage()
            );

            // Stop on authentication failures and GitHub rate-limit responses.
            // Support both cURL ("HTTP 403") and PHP stream ("HTTP/1.1 403") formats.
            $httpStatus = null;
            if (preg_match('/\\bHTTP(?:\\/\\d+(?:\\.\\d+)?)?\\s+(\\d{3})\\b/i', $e->getMessage(), $matches) === 1) {
                $httpStatus = (int) $matches[1];
            }

            $isTransportFailure = str_starts_with($e->getMessage(), 'GitHub request failed');
            $isServerFailure = $httpStatus !== null && $httpStatus >= 500 && $httpStatus <= 599;

            if (
                $isTransportFailure
                || in_array($httpStatus, [401, 403, 408, 429], true)
                || $isServerFailure
            ) {
                $state['blocked'] = true;
                break;
            }
        }

        // Small pause between profiles to avoid making requests too aggressively.
        usleep(250000);
    }

    $_SESSION[$stateKey] = $state;
}

$done = !$hasMore || !empty($state['blocked']);
$percentage = (int) ($state['total'] > 0
    ? min(100, round(($state['processed'] / $state['total']) * 100))
    : 100);

if ($done) {
    audit_log(
        'refresh_all_github',
        'profile',
        null,
        null,
        [
            'total' => $state['total'],
            'processed' => $state['processed'],
            'success' => $state['success'],
            'failed' => $state['failed'],
        ]
    );

    unset($_SESSION[$stateKey]);

    if (!empty($state['blocked'])) {
        flash(
            "GitHub refresh stopped because a request could not continue due to authentication, rate limiting, or a network/server error. {$state['processed']} of {$state['total']} profiles processed; {$state['success']} updated, {$state['failed']} failed. Check GITHUB_TOKEN and GitHub API limits before retrying.",
            'warning'
        );
    } else {
        flash(
            "GitHub refresh complete. {$state['success']} updated, {$state['failed']} failed.",
            $state['failed'] > 0 ? 'warning' : 'info'
        );
    }
    redirect('dashboard.php');
}

$cursorId = (int) $state['cursor_id'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Refreshing GitHub Profiles</title>
    <link rel="stylesheet" href="<?= e(asset('assets/app.css')) ?>">
</head>
<body>
<main class="page">
    <section class="admin-head">
        <div>
            <h1>Refreshing GitHub Profiles</h1>
            <p class="muted">Profiles are processed in small batches to avoid request timeouts. Keep this tab open.</p>
        </div>
    </section>

    <section class="recent-audit">
        <p>
            <strong><?= e((string) $state['processed']) ?></strong>
            of
            <strong><?= e((string) $state['total']) ?></strong>
            profiles processed
        </p>
        <progress value="<?= $percentage ?>" max="100" style="width: 100%;"></progress>
        <p class="muted">
            <?= $percentage ?>% complete
            · <?= e((string) $state['success']) ?> updated
            · <?= e((string) $state['failed']) ?> failed
        </p>

        <form method="post" action="refresh_all_github.php" id="continue-refresh">
            <?= csrf_field() ?>
            <input type="hidden" name="cursor_id" value="<?= $cursorId ?>">
            <button type="submit">Continue refresh</button>
        </form>
    </section>
</main>
<script>
    window.setTimeout(function () {
        document.getElementById('continue-refresh').requestSubmit();
    }, 400);
</script>
</body>
</html>
