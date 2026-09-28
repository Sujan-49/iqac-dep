<?php
require_once __DIR__ . '/include/auth.php';

iqac_start_session();
iqac_no_cache_headers();

$message = '';
$error = '';
$token = iqac_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!iqac_verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (!iqac_table_exists($conn, 'password_reset_requests')) {
        $error = 'Password reset request system is not available. Contact administrator.';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $reason = trim((string)($_POST['reason'] ?? ''));
        if ($username === '') {
            $error = 'Username or register number is required.';
        } else {
            $stmt = $conn->prepare('SELECT id, username, role, department, batch, semester FROM users WHERE username = ? LIMIT 1');
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $target = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($target) {
                $targetUserId = (int)$target['id'];
                $requester = (string)$target['username'];
                $targetRole = iqac_normalize_role((string)$target['role']);
                $ip = $_SERVER['REMOTE_ADDR'] ?? '';
                $stmt = $conn->prepare("INSERT INTO password_reset_requests (target_user_id, requested_by_user_id, requester_username, requester_role, target_role, reason, requester_ip) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('iisssss', $targetUserId, $targetUserId, $requester, $targetRole, $targetRole, $reason, $ip);
                $stmt->execute();
                $stmt->close();

                iqac_audit($conn, 'password_reset_requested', $targetUserId, $targetRole, 'Old Role: ' . $targetRole . '; New Role: ' . $targetRole . '; Reset By: pending approval');
                iqac_notify($conn, null, 'admin', 'Password reset requested', 'Password reset requested for ' . $requester . '.', 'password_resets.php');
                iqac_notify($conn, null, 'super_admin', 'Password reset requested', 'Password reset requested for ' . $requester . '.', 'password_resets.php');

                if ($targetRole === 'student' && iqac_table_exists($conn, 'student_details')) {
                    $stmt = $conn->prepare('SELECT st.user_id FROM student_details sd JOIN staff_details st ON st.staff_id = sd.tutor_staff_id WHERE sd.user_id = ? LIMIT 1');
                    $stmt->bind_param('i', $targetUserId);
                    $stmt->execute();
                    $tutor = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    if ($tutor) {
                        iqac_notify($conn, (int)$tutor['user_id'], null, 'Student password reset requested', 'Student ' . $requester . ' requested password reset.', 'password_resets.php');
                    }
                }
            }

            $message = 'If the account exists, a password reset request has been sent for authorized review.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password | PSG PTC ERP</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/erp.css">
</head>
<body class="login-body">
<main class="login-shell">
    <section class="login-panel">
        <div class="brand-mark">PSG</div>
        <h1>Password Reset Request</h1>
        <p>Submit a request for tutor/admin approval. Passwords are never reset directly from this page.</p>
        <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post" class="erp-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
            <label><span>Username / Register Number</span><input type="text" name="username" required autofocus></label>
            <label><span>Reason</span><textarea name="reason" placeholder="Optional note for tutor/admin"></textarea></label>
            <button class="primary-btn" type="submit">Submit Reset Request</button>
            <a class="secondary-btn" href="login.php">Back to Login</a>
        </form>
    </section>
    <aside class="login-aside">
        <div class="metric"><span>Security</span><strong>Approval Required</strong></div>
        <div class="metric"><span>Student</span><strong>Request Only</strong></div>
    </aside>
</main>
</body>
</html>
