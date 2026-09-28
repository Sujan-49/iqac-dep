<?php
require_once __DIR__ . '/include/auth.php';

$user = iqac_require_login();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!iqac_verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        $stmt = $conn->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || !password_verify($currentPassword, (string)$row['password_hash'])) {
            $error = 'Current password is incorrect.';
        } elseif ($passwordErrors = iqac_password_errors($newPassword)) {
            $error = 'New password must include ' . implode(', ', $passwordErrors) . '.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New password and confirmation do not match.';
        } elseif (password_verify($newPassword, (string)$row['password_hash'])) {
            $error = 'New password must be different from the current password.';
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $hasForceChange = iqac_column_exists($conn, 'users', 'force_password_change');
            $hasPasswordChangedAt = iqac_column_exists($conn, 'users', 'password_changed_at');
            if ($hasForceChange && $hasPasswordChangedAt) {
                $updateSql = 'UPDATE users SET password_hash = ?, first_login = 0, force_password_change = 0, password_changed_at = CURRENT_TIMESTAMP WHERE id = ?';
            } elseif ($hasForceChange) {
                $updateSql = 'UPDATE users SET password_hash = ?, first_login = 0, force_password_change = 0 WHERE id = ?';
            } elseif ($hasPasswordChangedAt) {
                $updateSql = 'UPDATE users SET password_hash = ?, first_login = 0, password_changed_at = CURRENT_TIMESTAMP WHERE id = ?';
            } else {
                $updateSql = 'UPDATE users SET password_hash = ?, first_login = 0 WHERE id = ?';
            }
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param('si', $hash, $user['id']);
            $stmt->execute();
            $stmt->close();

            iqac_audit($conn, 'password_changed', $user['id'], $user['role'], 'Old Role: ' . $user['role'] . '; New Role: ' . $user['role'] . '; Reset By: self');
            $select = 'id, username, password_hash, role, associated_id, department, semester, section, batch, first_login, is_active';
            if (iqac_column_exists($conn, 'users', 'force_password_change')) {
                $select .= ', force_password_change';
            }
            $stmt = $conn->prepare("SELECT $select FROM users WHERE id = ? LIMIT 1");
            $stmt->bind_param('i', $user['id']);
            $stmt->execute();
            $freshUser = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($freshUser) {
                iqac_login_user($conn, $freshUser);
            } else {
                $_SESSION['first_login'] = 0;
                session_regenerate_id(true);
            }
            header('Location: ' . iqac_role_home($user['role']));
            exit;
        }
    }
}

$token = iqac_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Change Password</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/erp.css">
</head>
<body class="login-body">
<main class="login-shell">
    <section class="login-panel">
        <div class="brand-mark">PSG</div>
        <h1>Change Password</h1>
        <p>Set a new password before continuing to the PSG PTC ERP dashboard.</p>

        <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <?php if ($success): ?>
            <a class="primary-btn" href="<?= htmlspecialchars(iqac_role_home($user['role'])) ?>">Continue</a>
        <?php else: ?>
            <form method="post" class="erp-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                <label><span>Current Password</span><input type="password" name="current_password" required></label>
                <label><span>New Password</span><input type="password" name="new_password" minlength="8" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}" required><small>Minimum 8 characters with uppercase, lowercase, number, and special character.</small></label>
                <label><span>Confirm New Password</span><input type="password" name="confirm_password" minlength="8" required></label>
                <button class="primary-btn" type="submit">Update Password</button>
            </form>
        <?php endif; ?>
    </section>
    <aside class="login-aside">
        <div class="metric"><span>College</span><strong>PSG Polytechnic College</strong></div>
        <div class="metric"><span>Session</span><strong>Protected</strong></div>
    </aside>
</main>
</body>
</html>
