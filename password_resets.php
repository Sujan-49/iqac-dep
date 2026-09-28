<?php
require_once __DIR__ . '/include/auth.php';
require_once __DIR__ . '/include/navigation.php';

$user = iqac_require_login(['admin', 'super_admin']);
$role = iqac_effective_role($user);
$token = iqac_csrf_token();
$message = '';
$error = '';

function reset_target_visible(mysqli $conn, array $actor, array $target): bool
{
    $actorRole = iqac_normalize_role((string)$actor['role']);
    $targetRole = iqac_normalize_role((string)$target['role']);
    if ($actorRole === 'super_admin') {
        return true;
    }
    if ($actorRole === 'admin' || $actorRole === 'iqac') {
        return !in_array($targetRole, ['admin', 'super_admin'], true);
    }
    return false;
}

function reset_temp_password(): string
{
    return 'Psg@' . random_int(1000, 9999) . substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ'), 0, 2);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!iqac_verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $requestId = (int)($_POST['request_id'] ?? 0);
        $decision = (string)($_POST['decision'] ?? '');
        $stmt = $conn->prepare('SELECT pr.*, u.username, u.role, u.department, u.batch, u.semester FROM password_reset_requests pr JOIN users u ON u.id = pr.target_user_id WHERE pr.id = ? LIMIT 1');
        $stmt->bind_param('i', $requestId);
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$request) {
            $error = 'Password reset request not found.';
        } elseif (!reset_target_visible($conn, $user, $request)) {
            $error = 'You are not authorized to reset this account.';
            iqac_audit($conn, 'password_reset_unauthorized_attempt', $user['id'], $role, 'Request #' . $requestId);
        } elseif ($decision === 'reject') {
            $stmt = $conn->prepare("UPDATE password_reset_requests SET status='rejected', approved_by_user_id=?, approved_by_role=?, approved_at=NOW() WHERE id=?");
            $stmt->bind_param('isi', $user['id'], $role, $requestId);
            $stmt->execute();
            $stmt->close();
            iqac_audit($conn, 'password_reset_rejected', (int)$request['target_user_id'], (string)$request['role'], 'Old Role: ' . $request['role'] . '; New Role: ' . $request['role'] . '; Reset By: ' . $user['username']);
            $message = 'Password reset request rejected.';
        } elseif ($decision === 'approve') {
            $tempPassword = reset_temp_password();
            $hash = password_hash($tempPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE users SET password_hash=?, first_login=1, force_password_change=1, failed_login_attempts=0, locked_until=NULL WHERE id=?');
            $stmt->bind_param('si', $hash, $request['target_user_id']);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("UPDATE password_reset_requests SET status='completed', temp_password=?, approved_by_user_id=?, approved_by_role=?, approved_at=NOW(), completed_at=NOW() WHERE id=?");
            $stmt->bind_param('sisi', $tempPassword, $user['id'], $role, $requestId);
            $stmt->execute();
            $stmt->close();

            iqac_audit($conn, 'password_reset_completed', (int)$request['target_user_id'], (string)$request['role'], 'Old Role: ' . $request['role'] . '; New Role: ' . $request['role'] . '; Reset By: ' . $user['username']);
            iqac_notify($conn, (int)$request['target_user_id'], null, 'Password reset approved', 'Temporary password issued. Change password on next login.', 'password_change.php');
            $message = 'Temporary password generated for ' . (string)$request['username'] . ': ' . $tempPassword;
        }
    }
}

$requests = [];
if (iqac_table_exists($conn, 'password_reset_requests')) {
    $result = $conn->query("SELECT pr.*, u.username, u.role, u.department, u.batch, u.semester
                            FROM password_reset_requests pr
                            JOIN users u ON u.id = pr.target_user_id
                            ORDER BY pr.created_at DESC
                            LIMIT 100");
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    foreach ($rows as $row) {
        if (reset_target_visible($conn, $user, $row)) {
            $requests[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Password Reset Requests | PSG PTC ERP</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/erp.css">
</head>
<body>
<div class="erp-layout">
    <?php iqac_render_sidebar($user, 'password_resets.php'); ?>
    <main class="erp-main">
        <header class="erp-topbar">
            <div><h1>Password Reset Requests</h1><span>Tutor/Admin controlled recovery</span></div>
        </header>
        <section class="erp-content">
            <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <section class="panel" id="password-security">
                <div class="panel-header"><h2>Password Reset History</h2><span class="badge gold"><?= count($requests) ?> Visible</span></div>
                <div class="table-wrap">
                    <table class="erp-table interactive-table">
                        <thead><tr><th>User</th><th>Role</th><th>Status</th><th>Reason</th><th>Requested</th><th>Approved By</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php if (!$requests): ?><tr><td colspan="7">No password reset requests found.</td></tr><?php endif; ?>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)$request['username']) ?></td>
                                <td><?= htmlspecialchars((string)$request['role']) ?></td>
                                <td><span class="status-badge <?= $request['status'] === 'completed' ? 'verified' : 'pending' ?>"><?= htmlspecialchars((string)$request['status']) ?></span></td>
                                <td><?= htmlspecialchars((string)$request['reason']) ?></td>
                                <td><?= htmlspecialchars((string)$request['created_at']) ?></td>
                                <td><?= htmlspecialchars((string)($request['approved_by_role'] ?? '')) ?></td>
                                <td>
                                    <?php if ($request['status'] === 'pending'): ?>
                                        <form method="post" class="erp-form">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                                            <input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>">
                                            <button class="secondary-btn" name="decision" value="reject" type="submit">Reject</button>
                                            <button class="primary-btn" name="decision" value="approve" type="submit">Approve Reset</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="muted-action">Closed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </section>
    </main>
</div>
</body>
</html>
