<?php
declare(strict_types=1);

require_once __DIR__ . '/../dp_connection.php';
require_once __DIR__ . '/compat.php';

const IQAC_SESSION_TIMEOUT = 1800;
const IQAC_MAX_FAILED_LOGINS = 5;
const IQAC_LOCK_MINUTES = 15;

function iqac_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

function iqac_no_cache_headers(): void
{
    if (headers_sent()) {
        return;
    }
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

function iqac_normalize_role(string $role): string
{
    $role = strtolower(trim($role));
    return match ($role) {
        'teacher', 'subject_staff' => 'staff',
        'iqac coordinator', 'iqac_coordinator' => 'iqac',
        'superadmin', 'super_admin' => 'super_admin',
        default => $role,
    };
}

function iqac_allowed_roles(): array
{
    return ['student', 'staff', 'tutor', 'hod', 'iqac', 'admin', 'super_admin'];
}

function iqac_role_home(string $role): string
{
    $role = iqac_normalize_role($role);
    $currentUser = iqac_current_user();
    if ($currentUser && iqac_has_tutor_access($currentUser)) {
        return 'dashboard_tutor.php';
    }

    return match ($role) {
        'student' => 'dashboard_student.php',
        'staff' => 'dashboard_staff.php',
        'tutor' => 'dashboard_tutor.php',
        'hod', 'iqac', 'admin' => 'dashboard_admin.php',
        'super_admin' => 'dashboard_super.php',
        default => 'login.php',
    };
}

function iqac_effective_role(array $user): string
{
    if (iqac_has_tutor_access($user)) {
        return 'tutor';
    }
    return iqac_normalize_role((string)($user['role'] ?? ''));
}

function iqac_has_tutor_access(array $user): bool
{
    $role = iqac_normalize_role((string)($user['role'] ?? ''));
    return in_array($role, ['staff', 'tutor'], true)
        && (int)($user['is_tutor'] ?? 0) === 1
        && trim((string)($user['batch'] ?? '')) !== ''
        && trim((string)($user['semester'] ?? '')) !== ''
        && trim((string)($user['section'] ?? '')) !== ''
        && (int)($user['tutor_assigned_students'] ?? 0) > 0;
}

function iqac_student_is_approved(?string $status): bool
{
    $status = strtolower(trim((string)$status));
    return in_array($status, ['approved', 'active'], true);
}

function iqac_csrf_token(): string
{
    iqac_start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function iqac_verify_csrf(?string $token): bool
{
    iqac_start_session();
    return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function iqac_reset_auth_session(): void
{
    iqac_start_session();
    $_SESSION = [];
    session_regenerate_id(true);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['user_id'] = 0;
    $_SESSION['username'] = '';
    $_SESSION['role'] = '';
    $_SESSION['logged_in'] = false;
    $_SESSION['student_id'] = 0;
    $_SESSION['staff_id'] = 0;
    $_SESSION['is_tutor'] = 0;
    $_SESSION['tutor_assigned_students'] = 0;
    $_SESSION['department'] = '';
    $_SESSION['batch'] = '';
    $_SESSION['semester'] = '';
    $_SESSION['section'] = '';
    $_SESSION['approval_status'] = '';
    $_SESSION['admission_approved'] = 0;
    $_SESSION['first_login'] = 0;
    $_SESSION['force_password_change'] = 0;
}

function iqac_table_exists(mysqli $conn, string $table): bool
{
    $stmt = $conn->prepare('SELECT COUNT(*) AS found FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row['found'] ?? 0) > 0;
}

function iqac_column_exists(mysqli $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare('SELECT COUNT(*) AS found FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row['found'] ?? 0) > 0;
}

function iqac_audit(mysqli $conn, string $action, ?int $userId = null, ?string $role = null, ?string $details = null): void
{
    if (!iqac_table_exists($conn, 'audit_logs')) {
        return;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $stmt = $conn->prepare('INSERT INTO audit_logs (user_id, role, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('isssss', $userId, $role, $action, $details, $ip, $agent);
    $stmt->execute();
    $stmt->close();
}

function iqac_notify(mysqli $conn, ?int $userId, ?string $role, string $title, string $message, string $link = ''): void
{
    if (!iqac_table_exists($conn, 'notifications')) {
        return;
    }
    $stmt = $conn->prepare('INSERT INTO notifications (user_id, role, title, message, link_url) VALUES (?, ?, ?, ?, ?)');
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('issss', $userId, $role, $title, $message, $link);
    $stmt->execute();
    $stmt->close();
}

function iqac_password_errors(string $password): array
{
    $errors = [];
    if (strlen($password) < 8) {
        $errors[] = 'at least 8 characters';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'one uppercase letter';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'one lowercase letter';
    }
    if (!preg_match('/\d/', $password)) {
        $errors[] = 'one number';
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'one special character';
    }
    return $errors;
}

function iqac_user_is_locked(array $user): bool
{
    if (empty($user['locked_until'])) {
        return false;
    }
    return strtotime((string)$user['locked_until']) > time();
}

function iqac_register_failed_login(mysqli $conn, ?array $user, string $username): void
{
    iqac_audit($conn, 'failed_login', $user ? (int)$user['id'] : null, $user['role'] ?? null, 'Username: ' . $username);
    if (!$user || !iqac_column_exists($conn, 'users', 'failed_login_attempts')) {
        return;
    }

    $attempts = (int)($user['failed_login_attempts'] ?? 0) + 1;
    if ($attempts >= IQAC_MAX_FAILED_LOGINS && iqac_column_exists($conn, 'users', 'locked_until')) {
        $stmt = $conn->prepare('UPDATE users SET failed_login_attempts = ?, locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?');
        $minutes = IQAC_LOCK_MINUTES;
        $stmt->bind_param('iii', $attempts, $minutes, $user['id']);
        $stmt->execute();
        $stmt->close();
        iqac_audit($conn, 'account_locked', (int)$user['id'], (string)$user['role'], 'Locked after failed login attempts');
        iqac_notify($conn, null, 'admin', 'Account locked', 'Account ' . $username . ' locked for ' . IQAC_LOCK_MINUTES . ' minutes after failed login attempts.', 'admin_controls.php#password-security');
        iqac_notify($conn, null, 'super_admin', 'Account locked', 'Account ' . $username . ' locked for ' . IQAC_LOCK_MINUTES . ' minutes after failed login attempts.', 'admin_controls.php#password-security');
        return;
    }

    $stmt = $conn->prepare('UPDATE users SET failed_login_attempts = ? WHERE id = ?');
    $stmt->bind_param('ii', $attempts, $user['id']);
    $stmt->execute();
    $stmt->close();
}

function iqac_clear_failed_login(mysqli $conn, int $userId): void
{
    if (!iqac_column_exists($conn, 'users', 'failed_login_attempts')) {
        return;
    }
    $sql = iqac_column_exists($conn, 'users', 'locked_until')
        ? 'UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?'
        : 'UPDATE users SET failed_login_attempts = 0 WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
}

function iqac_current_user(): ?array
{
    iqac_start_session();
    if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
        return null;
    }
    return [
        'id' => (int)$_SESSION['user_id'],
        'username' => (string)($_SESSION['username'] ?? ''),
        'role' => iqac_normalize_role((string)$_SESSION['role']),
        'effective_role' => iqac_normalize_role((string)($_SESSION['effective_role'] ?? $_SESSION['role'])),
        'department' => (string)($_SESSION['department'] ?? ''),
        'semester' => (string)($_SESSION['semester'] ?? ''),
        'section' => (string)($_SESSION['section'] ?? ''),
        'batch' => (string)($_SESSION['batch'] ?? ''),
        'is_tutor' => (int)($_SESSION['is_tutor'] ?? 0),
        'tutor_assigned_students' => (int)($_SESSION['tutor_assigned_students'] ?? 0),
        'staff_id' => (int)($_SESSION['staff_id'] ?? 0),
        'student_id' => (int)($_SESSION['student_id'] ?? 0),
        'approval_status' => (string)($_SESSION['approval_status'] ?? ''),
        'admission_approved' => (int)($_SESSION['admission_approved'] ?? 0),
        'first_login' => (int)($_SESSION['first_login'] ?? 0),
        'force_password_change' => (int)($_SESSION['force_password_change'] ?? 0),
    ];
}

function iqac_login_user(mysqli $conn, array $user): void
{
    iqac_reset_auth_session();

    $role = iqac_normalize_role((string)$user['role']);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = (string)$user['username'];
    $_SESSION['role'] = $role;
    $_SESSION['effective_role'] = $role;
    $_SESSION['logged_in'] = true;
    $_SESSION['department'] = (string)($user['department'] ?? '');
    $_SESSION['semester'] = (string)($user['semester'] ?? '');
    $_SESSION['section'] = (string)($user['section'] ?? '');
    $_SESSION['batch'] = (string)($user['batch'] ?? '');
    $_SESSION['first_login'] = (int)($user['first_login'] ?? 0);
    $_SESSION['force_password_change'] = (int)($user['force_password_change'] ?? 0);
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['user_agent_hash'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
    iqac_csrf_token();

    if ($role === 'student' && iqac_student_table($conn) !== null) {
        $studentTable = iqac_student_table($conn);
        $studentPk = iqac_student_pk($conn, $studentTable);
        $studentId = null;
        if ($studentTable === 'student_details' && iqac_column_exists($conn, 'student_details', 'user_id')) {
            $selectParts = ["$studentPk AS id"];
            foreach (['stage1_status', 'department', 'batch', 'section', 'current_semester'] as $column) {
                if (iqac_column_exists($conn, 'student_details', $column)) {
                    $selectParts[] = $column;
                }
            }
            $stmt = $conn->prepare('SELECT ' . implode(', ', $selectParts) . ' FROM student_details WHERE user_id = ? LIMIT 1');
            $stmt->bind_param('i', $_SESSION['user_id']);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $studentId = $row ? (int)$row['id'] : null;
            if ($row && array_key_exists('stage1_status', $row)) {
                $_SESSION['approval_status'] = (string)$row['stage1_status'];
                $_SESSION['admission_approved'] = iqac_student_is_approved((string)$row['stage1_status']) ? 1 : 0;
            }
            if ($row) {
                $_SESSION['department'] = (string)($row['department'] ?? $_SESSION['department']);
                $_SESSION['batch'] = (string)($row['batch'] ?? $_SESSION['batch']);
                $_SESSION['section'] = (string)($row['section'] ?? $_SESSION['section']);
                $_SESSION['semester'] = (string)($row['current_semester'] ?? $_SESSION['semester']);
            }
        }
        if ($studentId === null && iqac_column_exists($conn, $studentTable, 'register_number')) {
            $stmt = $conn->prepare("SELECT $studentPk AS id FROM `$studentTable` WHERE register_number = ? LIMIT 1");
            $stmt->bind_param('s', $_SESSION['username']);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $studentId = $row ? (int)$row['id'] : null;
        }
        if ($studentId === null && iqac_column_exists($conn, $studentTable, 'roll_number')) {
            $stmt = $conn->prepare("SELECT $studentPk AS id FROM `$studentTable` WHERE roll_number = ? LIMIT 1");
            $stmt->bind_param('s', $_SESSION['username']);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $studentId = $row ? (int)$row['id'] : null;
        }
        if ($studentId === null) {
            $stmt = $conn->prepare("SELECT $studentPk AS id FROM `$studentTable` WHERE $studentPk = ? LIMIT 1");
            $stmt->bind_param('i', $_SESSION['user_id']);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $studentId = $row ? (int)$row['id'] : null;
        }
        if ($studentId !== null) {
            $_SESSION['student_id'] = $studentId;
        }
        $_SESSION['approval_status'] = (string)($_SESSION['approval_status'] ?? 'pending');
        $_SESSION['admission_approved'] = (int)($_SESSION['admission_approved'] ?? 0);
    }

    if (in_array($role, ['staff', 'tutor', 'hod', 'iqac', 'admin', 'super_admin'], true) && iqac_table_exists($conn, 'staff_details')) {
        $selectParts = ['staff_id'];
        $selectParts[] = iqac_column_exists($conn, 'staff_details', 'is_tutor') ? 'is_tutor' : '0 AS is_tutor';
        if (iqac_column_exists($conn, 'staff_details', 'department')) {
            $selectParts[] = 'department';
        }
        $select = implode(', ', $selectParts);
        $stmt = $conn->prepare("SELECT $select FROM staff_details WHERE user_id = ? LIMIT 1");
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $_SESSION['staff_id'] = (int)$row['staff_id'];
            $_SESSION['is_tutor'] = (int)($row['is_tutor'] ?? 0);
            $_SESSION['department'] = (string)($row['department'] ?? $_SESSION['department']);
            if (iqac_table_exists($conn, 'student_details') && iqac_column_exists($conn, 'student_details', 'tutor_staff_id')) {
                $stmt = $conn->prepare("SELECT COUNT(*) AS assigned_count, MAX(NULLIF(batch, '')) AS batch, MAX(NULLIF(section, '')) AS section, MAX(current_semester) AS semester FROM student_details WHERE tutor_staff_id = ?");
                $stmt->bind_param('i', $_SESSION['staff_id']);
                $stmt->execute();
                $assignment = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                $_SESSION['tutor_assigned_students'] = (int)($assignment['assigned_count'] ?? 0);
                if ($_SESSION['tutor_assigned_students'] > 0) {
                    $_SESSION['batch'] = (string)($assignment['batch'] ?? $_SESSION['batch']);
                    $_SESSION['section'] = (string)($assignment['section'] ?? $_SESSION['section']);
                    $_SESSION['semester'] = (string)($assignment['semester'] ?? $_SESSION['semester']);
                }
            }
        }
    }
    $sessionUser = iqac_current_user() ?? ['role' => $role];
    $_SESSION['effective_role'] = iqac_effective_role($sessionUser);

    iqac_audit($conn, 'login', (int)$user['id'], $role, 'Unified authentication login');
}

function iqac_require_login(array $roles = []): array
{
    global $conn;
    iqac_start_session();
    iqac_no_cache_headers();

    $timedOut = isset($_SESSION['last_activity']) && (time() - (int)$_SESSION['last_activity']) > IQAC_SESSION_TIMEOUT;
    $agentChanged = isset($_SESSION['user_agent_hash']) && $_SESSION['user_agent_hash'] !== hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($timedOut || $agentChanged) {
        iqac_audit($conn, $timedOut ? 'auto_logout' : 'session_hijack_blocked', $_SESSION['user_id'] ?? null, $_SESSION['role'] ?? null);
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool)$params['secure'], (bool)$params['httponly']);
        }
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }

    $user = iqac_current_user();
    if ($user === null) {
        header('Location: login.php');
        exit;
    }

    $_SESSION['last_activity'] = time();
    $roles = array_map('iqac_normalize_role', $roles);
    $effectiveRole = iqac_effective_role($user);
    if ($roles !== [] && !in_array($user['role'], $roles, true) && !in_array($effectiveRole, $roles, true)) {
        header('Location: ' . iqac_role_home($user['role']));
        exit;
    }

    return $user;
}

function iqac_logout(mysqli $conn): void
{
    iqac_start_session();
    iqac_audit($conn, 'logout', $_SESSION['user_id'] ?? null, $_SESSION['role'] ?? null);
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool)$params['secure'], (bool)$params['httponly']);
    }
    session_destroy();
}
?>
