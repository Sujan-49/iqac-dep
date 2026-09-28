<?php
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

require_once __DIR__ . '/../include/auth.php';

if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . ($conn->connect_error ?? 'Connection is not set') . "\n");
}

$students = [
    '40DI01', '40DI02', '40DI03', '40DI04', '40DI05', '40DI06', '40DI07', '40DI08', '40DI09', '40DI10',
    '40DI11', '40DI12', '40DI13', '40DI14', '40DI15', '40DI16', '40DI17', '40DI18', '40DI19', '40DI20',
    '40DI21', '40DI22', '40DI23', '40DI24', '40DI25', '40DI26', '40DI27'
];

$staff = [
    'staff40' => 'staff123',
    'dsa40' => 'staff123',
    'dbms40' => 'staff123',
    'cn40' => 'staff123'
];

$admin = [
    'mainadmin40' => 'admin123'
];

$passedCount = 0;
$failedCount = 0;

echo "==================================================\n";
echo "PSG PTC ERP – 40DI LOGIN AUTHENTICATION VERIFICATION\n";
echo "==================================================\n";

// Verify Student Accounts
foreach ($students as $roll) {
    $error = '';
    
    // Simulate real login query
    $passwordColumn = iqac_column_exists($conn, 'users', 'password_hash') ? 'password_hash' : 'password';
    $select = "id, username, `$passwordColumn` AS password_secret, role, is_active, associated_id, failed_login_attempts, locked_until";
    $stmt = $conn->prepare("SELECT $select FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param('s', $roll);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        $error = 'USER_ROW_MISSING';
    } else {
        $secret = (string)($user['password_secret'] ?? '');
        $passwordOk = $secret !== '' && password_verify($roll, $secret);
        
        if (!$passwordOk) {
            $error = 'PASSWORD_VERIFY_FALSE';
        } elseif (iqac_user_is_locked($user)) {
            $error = 'ACCOUNT_LOCKED';
        } elseif ((int)$user['is_active'] !== 1) {
            $error = 'ACCOUNT_INACTIVE';
        } elseif ($user['role'] !== 'student') {
            $error = 'ROLE_INVALID';
        } else {
            // Check student_details profile row
            $stmt = $conn->prepare("SELECT student_id, user_id, roll_number, stage1_status, department, batch, section, current_semester, tutor_staff_id FROM student_details WHERE user_id = ? LIMIT 1");
            $stmt->bind_param('i', $user['id']);
            $stmt->execute();
            $profile = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$profile) {
                $error = 'PROFILE_LINK_MISSING';
            } elseif ($profile['roll_number'] !== $roll) {
                $error = 'PROFILE_LINK_INVALID (Roll mismatch)';
            } elseif (!in_array(strtolower(trim((string)$profile['stage1_status'])), ['approved', 'active'], true)) {
                $error = 'APPROVAL_PENDING (Status: ' . $profile['stage1_status'] . ')';
            } elseif ((int)$user['associated_id'] !== (int)$profile['student_id']) {
                $error = 'PROFILE_LINK_INVALID (Associated ID mismatch)';
            }
        }
    }
    
    if ($error === '') {
        echo "$roll PASS\n";
        $passedCount++;
    } else {
        echo "$roll FAIL - $error\n";
        $failedCount++;
    }
}

// Verify Staff Accounts
echo "\nChecking Staff Accounts:\n";
foreach ($staff as $username => $password) {
    $error = '';
    
    $passwordColumn = iqac_column_exists($conn, 'users', 'password_hash') ? 'password_hash' : 'password';
    $select = "id, username, `$passwordColumn` AS password_secret, role, is_active, associated_id, failed_login_attempts, locked_until";
    $stmt = $conn->prepare("SELECT $select FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        $error = 'USER_ROW_MISSING';
    } else {
        $secret = (string)($user['password_secret'] ?? '');
        $passwordOk = $secret !== '' && password_verify($password, $secret);
        
        if (!$passwordOk) {
            $error = 'PASSWORD_VERIFY_FALSE';
        } elseif (iqac_user_is_locked($user)) {
            $error = 'ACCOUNT_LOCKED';
        } elseif ((int)$user['is_active'] !== 1) {
            $error = 'ACCOUNT_INACTIVE';
        } elseif ($user['role'] !== 'staff') {
            $error = 'ROLE_INVALID';
        } else {
            // Check staff_details profile row
            $stmt = $conn->prepare("SELECT staff_id, user_id, full_name, is_tutor, department FROM staff_details WHERE user_id = ? LIMIT 1");
            $stmt->bind_param('i', $user['id']);
            $stmt->execute();
            $profile = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$profile) {
                $error = 'PROFILE_LINK_MISSING';
            } elseif ((int)$user['associated_id'] !== (int)$profile['staff_id']) {
                $error = 'PROFILE_LINK_INVALID (Associated ID mismatch)';
            }
        }
    }
    
    if ($error === '') {
        echo "$username PASS\n";
        $passedCount++;
    } else {
        echo "$username FAIL - $error\n";
        $failedCount++;
    }
}

// Verify Admin Account
echo "\nChecking Admin Account:\n";
foreach ($admin as $username => $password) {
    $error = '';
    
    $passwordColumn = iqac_column_exists($conn, 'users', 'password_hash') ? 'password_hash' : 'password';
    $select = "id, username, `$passwordColumn` AS password_secret, role, is_active, associated_id, failed_login_attempts, locked_until";
    $stmt = $conn->prepare("SELECT $select FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        $error = 'USER_ROW_MISSING';
    } else {
        $secret = (string)($user['password_secret'] ?? '');
        $passwordOk = $secret !== '' && password_verify($password, $secret);
        
        if (!$passwordOk) {
            $error = 'PASSWORD_VERIFY_FALSE';
        } elseif (iqac_user_is_locked($user)) {
            $error = 'ACCOUNT_LOCKED';
        } elseif ((int)$user['is_active'] !== 1) {
            $error = 'ACCOUNT_INACTIVE';
        } elseif ($user['role'] !== 'admin') {
            $error = 'ROLE_INVALID';
        }
    }
    
    if ($error === '') {
        echo "$username PASS\n";
        $passedCount++;
    } else {
        echo "$username FAIL - $error\n";
        $failedCount++;
    }
}

echo "\nVerification Summary:\n";
echo "Total Accounts Checked: " . (count($students) + count($staff) + count($admin)) . "\n";
echo "Passed: $passedCount\n";
echo "Failed: $failedCount\n";
echo "==================================================\n";

if ($failedCount > 0) {
    exit(1);
} else {
    exit(0);
}
