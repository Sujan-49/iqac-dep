<?php
require_once __DIR__ . '/include/auth.php';

iqac_start_session();
iqac_no_cache_headers();

$error = '';
$notice = isset($_GET['timeout']) ? 'Your session expired. Please sign in again.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!iqac_verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $passwordColumn = iqac_column_exists($conn, 'users', 'password_hash') ? 'password_hash' : 'password';
        $select = "id, username, `$passwordColumn` AS password_secret, role";
        foreach (['associated_id', 'department', 'semester', 'section', 'batch', 'first_login', 'force_password_change', 'failed_login_attempts', 'locked_until', 'is_active'] as $column) {
            if (iqac_column_exists($conn, 'users', $column)) {
                $select .= ', ' . $column;
            }
        }

        $stmt = $conn->prepare("SELECT $select FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        $secret = (string)($user['password_secret'] ?? '');
        $passwordOk = $secret !== '' && password_verify($password, $secret);

        if ($user && iqac_user_is_locked($user)) {
            $error = 'This account is temporarily locked. Contact the administrator or try again later.';
            iqac_audit($conn, 'locked_login_attempt', (int)$user['id'], (string)$user['role'], $username);
            iqac_reset_auth_session();
        } elseif (!$user || !$passwordOk) {
            $error = 'Invalid credentials.';
            iqac_register_failed_login($conn, $user ?: null, $username);
            iqac_reset_auth_session();
        } elseif (isset($user['is_active']) && (int)$user['is_active'] !== 1) {
            $error = 'This account is inactive. Contact the administrator.';
            iqac_reset_auth_session();
        } else {
            iqac_clear_failed_login($conn, (int)$user['id']);
            iqac_login_user($conn, $user);
            if (iqac_column_exists($conn, 'users', 'last_login')) {
                $update = $conn->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
                $update->bind_param('i', $user['id']);
                $update->execute();
                $update->close();
            }
            header('Location: ' . iqac_role_home((string)$user['role']));
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
<title>PSG PTC ERP Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/erp.css">
</head>
<body class="login-body">
<main class="login-shell">
    <section class="login-panel">
        <div class="brand-mark">PSG</div>
        <h1>PSG Polytechnic College</h1>
        <p><strong>Academic ERP Portal</strong><br>PSG PTC Department Portal for students, staff, tutors, HOD, IQAC, and administration.</p>

        <?php if ($notice): ?><div class="alert info"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="post" class="erp-form" autocomplete="on">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
            <label>
                <span>Username</span>
                <input type="text" name="username" placeholder="Register No / Staff ID" required autofocus>
            </label>
            <label>
                <span>Password</span>
                <span class="password-field">
                    <input id="login-password" type="password" name="password" placeholder="Password" required>
                    <button type="button" class="password-toggle" data-toggle-password aria-controls="login-password" aria-label="Show password">Show</button>
                </span>
            </label>
            <div class="login-options">
                <label><input type="checkbox" name="remember_me" value="1"> <span>Remember Me</span></label>
                <a href="forgot_password.php">Forgot Password?</a>
            </div>
            <button class="primary-btn" type="submit">Sign in</button>
        </form>

        <div class="login-hints">
            <strong>Student default:</strong> register number as username and password, for example 23CS001.
            First-login password change redirects to the PSG PTC password update workflow.
        </div>
    </section>
    <aside class="login-aside">
        <div class="metric"><span>College</span><strong>PSG Polytechnic College</strong></div>
        <div class="metric"><span>Portal</span><strong>Department ERP</strong></div>
        <div class="metric"><span>Security</span><strong>CSRF + Session Guard</strong></div>
    </aside>
</main>
<script>
document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
  button.addEventListener('click', function () {
    var input = document.getElementById(button.getAttribute('aria-controls'));
    if (!input) return;
    var hidden = input.type === 'password';
    input.type = hidden ? 'text' : 'password';
    button.textContent = hidden ? 'Hide' : 'Show';
    button.setAttribute('aria-label', hidden ? 'Hide password' : 'Show password');
  });
});
</script>
</body>
</html>
