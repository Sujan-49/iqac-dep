<?php
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

require_once __DIR__ . '/../include/auth.php';

if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . ($conn->connect_error ?? 'Connection is not set') . "\n");
}

$db_res = $conn->query("SELECT DATABASE() AS db");
$db_row = $db_res ? $db_res->fetch_assoc() : null;
$dbName = $db_row ? (string)$db_row['db'] : 'unknown';
$hostInfo = $conn->host_info;

echo "==================================================\n";
echo "PSG PTC ERP – BATCH 40DI DEMO SEEDER\n";
echo "==================================================\n";
echo "Database Host: $hostInfo\n";
echo "Database Name: $dbName\n";
echo "Connection file: c:\\xampp\\htdocs\\Iqac\\include\\auth.php\n";
echo "==================================================\n";

if ($dbName !== 'iqac') {
    die("CRITICAL ERROR: Connection is using database '$dbName' instead of 'iqac'. Aborting.\n");
}

// Student Performance Tiers
$students = [
    ['roll' => '40DI01', 'name' => 'Aadhavan K.', 'gender' => 'Male', 'blood' => 'O+', 'group' => 'top'],
    ['roll' => '40DI02', 'name' => 'Bala S.', 'gender' => 'Male', 'blood' => 'A+', 'group' => 'top'],
    ['roll' => '40DI03', 'name' => 'Chitra M.', 'gender' => 'Female', 'blood' => 'B+', 'group' => 'top'],
    ['roll' => '40DI04', 'name' => 'Dinesh R.', 'gender' => 'Male', 'blood' => 'O-', 'group' => 'top'],
    ['roll' => '40DI05', 'name' => 'Elango T.', 'gender' => 'Male', 'blood' => 'AB+', 'group' => 'above_avg'],
    ['roll' => '40DI06', 'name' => 'Farhana A.', 'gender' => 'Female', 'blood' => 'A-', 'group' => 'above_avg'],
    ['roll' => '40DI07', 'name' => 'Gopal V.', 'gender' => 'Male', 'blood' => 'B-', 'group' => 'above_avg'],
    ['roll' => '40DI08', 'name' => 'Harini P.', 'gender' => 'Female', 'blood' => 'O+', 'group' => 'above_avg'],
    ['roll' => '40DI09', 'name' => 'Indran S.', 'gender' => 'Male', 'blood' => 'A+', 'group' => 'above_avg'],
    ['roll' => '40DI10', 'name' => 'Janani K.', 'gender' => 'Female', 'blood' => 'B+', 'group' => 'above_avg'],
    ['roll' => '40DI11', 'name' => 'Karthik B.', 'gender' => 'Male', 'blood' => 'O-', 'group' => 'above_avg'],
    ['roll' => '40DI12', 'name' => 'Latha N.', 'gender' => 'Female', 'blood' => 'AB-', 'group' => 'avg'],
    ['roll' => '40DI13', 'name' => 'Manoj C.', 'gender' => 'Male', 'blood' => 'A-', 'group' => 'avg'],
    ['roll' => '40DI14', 'name' => 'Naveen K.', 'gender' => 'Male', 'blood' => 'B-', 'group' => 'avg'],
    ['roll' => '40DI15', 'name' => 'Oviya R.', 'gender' => 'Female', 'blood' => 'O+', 'group' => 'avg'],
    ['roll' => '40DI16', 'name' => 'Pranav S.', 'gender' => 'Male', 'blood' => 'A+', 'group' => 'avg'],
    ['roll' => '40DI17', 'name' => 'Ramya V.', 'gender' => 'Female', 'blood' => 'B+', 'group' => 'avg'],
    ['roll' => '40DI18', 'name' => 'Sanjay M.', 'gender' => 'Male', 'blood' => 'O-', 'group' => 'avg'],
    ['roll' => '40DI19', 'name' => 'Tharani T.', 'gender' => 'Female', 'blood' => 'AB+', 'group' => 'avg'],
    ['roll' => '40DI20', 'name' => 'Udaya P.', 'gender' => 'Male', 'blood' => 'A-', 'group' => 'low'],
    ['roll' => '40DI21', 'name' => 'Varshini S.', 'gender' => 'Female', 'blood' => 'B-', 'group' => 'low'],
    ['roll' => '40DI22', 'name' => 'Vignesh A.', 'gender' => 'Male', 'blood' => 'O+', 'group' => 'low'],
    ['roll' => '40DI23', 'name' => 'Yazhini K.', 'gender' => 'Female', 'blood' => 'A+', 'group' => 'low'],
    ['roll' => '40DI24', 'name' => 'Aakash R.', 'gender' => 'Male', 'blood' => 'B+', 'group' => 'arrear'],
    ['roll' => '40DI25', 'name' => 'Divya M.', 'gender' => 'Female', 'blood' => 'O-', 'group' => 'arrear'],
    ['roll' => '40DI26', 'name' => 'Hari Prasad S.', 'gender' => 'Male', 'blood' => 'AB-', 'group' => 'arrear'],
    ['roll' => '40DI27', 'name' => 'Swetha P.', 'gender' => 'Female', 'blood' => 'A-', 'group' => 'arrear']
];

$staffAccounts = [
    ['username' => 'staff40', 'name' => 'Staff Forty', 'is_tutor' => 1],
    ['username' => 'dsa40', 'name' => 'DSA Forty', 'is_tutor' => 0],
    ['username' => 'dbms40', 'name' => 'DBMS Forty', 'is_tutor' => 0],
    ['username' => 'cn40', 'name' => 'CN Forty', 'is_tutor' => 0]
];

$adminAccount = ['username' => 'mainadmin40', 'name' => 'Admin Forty'];

$conn->begin_transaction();
try {
    // 1. Seed/Update Staff
    $staffIds = [];
    $staffPass = password_hash('staff123', PASSWORD_DEFAULT);
    foreach ($staffAccounts as $st) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $st['username']);
        $stmt->execute();
        $user_row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($user_row) {
            $uid = (int)$user_row['id'];
            $update = $conn->prepare("UPDATE users SET password_hash = ?, role = 'staff', is_active = 1, department = 'Diploma Information Technology', failed_login_attempts = 0, locked_until = NULL WHERE id = ?");
            $update->bind_param('si', $staffPass, $uid);
            $update->execute();
            $update->close();
        } else {
            $insert = $conn->prepare("INSERT INTO users (username, password_hash, role, is_active, department, failed_login_attempts, locked_until, first_login, force_password_change) VALUES (?, ?, 'staff', 1, 'Diploma Information Technology', 0, NULL, 0, 0)");
            $insert->bind_param('ss', $st['username'], $staffPass);
            $insert->execute();
            $uid = (int)$conn->insert_id;
            $insert->close();
        }
        
        $stmt = $conn->prepare("SELECT staff_id FROM staff_details WHERE user_id = ? LIMIT 1");
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $profile_row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($profile_row) {
            $staffId = (int)$profile_row['staff_id'];
            $update = $conn->prepare("UPDATE staff_details SET full_name = ?, department = 'Diploma Information Technology', is_tutor = ? WHERE staff_id = ?");
            $update->bind_param('sii', $st['name'], $st['is_tutor'], $staffId);
            $update->execute();
            $update->close();
        } else {
            $insert = $conn->prepare("INSERT INTO staff_details (user_id, full_name, department, is_tutor) VALUES (?, ?, 'Diploma Information Technology', ?)");
            $insert->bind_param('isi', $uid, $st['name'], $st['is_tutor']);
            $insert->execute();
            $staffId = (int)$conn->insert_id;
            $insert->close();
        }
        
        $update_u = $conn->prepare("UPDATE users SET associated_id = ? WHERE id = ?");
        $update_u->bind_param('ii', $staffId, $uid);
        $update_u->execute();
        $update_u->close();
        
        $staffIds[$st['username']] = $staffId;
        echo "Staff {$st['username']} synced successfully (staff_id: $staffId).\n";
    }
    
    $tutorStaffId = $staffIds['staff40'];
    
    // 2. Seed/Update Students
    $studentIds = [];
    foreach ($students as $s) {
        $roll = $s['roll'];
        $studPass = password_hash($roll, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $roll);
        $stmt->execute();
        $user_row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($user_row) {
            $uid = (int)$user_row['id'];
            $update = $conn->prepare("UPDATE users SET password_hash = ?, role = 'student', is_active = 1, department = 'Diploma Information Technology', semester = 3, section = 'A', batch = '2040-2043', failed_login_attempts = 0, locked_until = NULL WHERE id = ?");
            $update->bind_param('si', $studPass, $uid);
            $update->execute();
            $update->close();
        } else {
            $insert = $conn->prepare("INSERT INTO users (username, password_hash, role, is_active, department, semester, section, batch, failed_login_attempts, locked_until, first_login, force_password_change) VALUES (?, ?, 'student', 1, 'Diploma Information Technology', 3, 'A', '2040-2043', 0, NULL, 0, 0)");
            $insert->bind_param('ss', $roll, $studPass);
            $insert->execute();
            $uid = (int)$conn->insert_id;
            $insert->close();
        }
        
        $stmt = $conn->prepare("SELECT student_id FROM student_details WHERE roll_number = ? LIMIT 1");
        $stmt->bind_param('s', $roll);
        $stmt->execute();
        $profile_row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($profile_row) {
            $studId = (int)$profile_row['student_id'];
            $update = $conn->prepare("UPDATE student_details SET user_id = ?, full_name = ?, gender = ?, blood_group = ?, department = 'Diploma Information Technology', class_year = '2', batch = '2040-2043', section = 'A', current_semester = 3, stage1_status = 'approved', tutor_staff_id = ? WHERE student_id = ?");
            $update->bind_param('isssii', $uid, $s['name'], $s['gender'], $s['blood'], $tutorStaffId, $studId);
            $update->execute();
            $update->close();
        } else {
            $insert = $conn->prepare("INSERT INTO student_details (user_id, roll_number, full_name, gender, blood_group, department, class_year, batch, section, is_lateral, current_semester, stage1_status, tutor_staff_id) VALUES (?, ?, ?, ?, ?, 'Diploma Information Technology', '2', '2040-2043', 'A', 0, 3, 'approved', ?)");
            $insert->bind_param('issssi', $uid, $roll, $s['name'], $s['gender'], $s['blood'], $tutorStaffId);
            $insert->execute();
            $studId = (int)$conn->insert_id;
            $insert->close();
        }
        
        $update_u = $conn->prepare("UPDATE users SET associated_id = ? WHERE id = ?");
        $update_u->bind_param('ii', $studId, $uid);
        $update_u->execute();
        $update_u->close();
        
        $studentIds[$roll] = $studId;
        echo "Student $roll synced successfully (student_id: $studId).\n";
    }

    // 3. Seed/Update Admin
    $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param('s', $adminAccount['username']);
    $stmt->execute();
    $user_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($user_row) {
        $uid = (int)$user_row['id'];
        $update = $conn->prepare("UPDATE users SET password_hash = ?, role = 'admin', is_active = 1, department = 'Diploma Information Technology', failed_login_attempts = 0, locked_until = NULL WHERE id = ?");
        $update->bind_param('si', $adminPass, $uid);
        $update->execute();
        $update->close();
    } else {
        $insert = $conn->prepare("INSERT INTO users (username, password_hash, role, is_active, department, failed_login_attempts, locked_until, first_login, force_password_change) VALUES (?, ?, 'admin', 1, 'Diploma Information Technology', 0, NULL, 0, 0)");
        $insert->bind_param('ss', $adminAccount['username'], $adminPass);
        $insert->execute();
        $uid = (int)$conn->insert_id;
        $insert->close();
    }
    echo "Admin {$adminAccount['username']} synced successfully.\n";

    $conn->commit();
    echo "\nSEEDING COMPLETED SUCCESSFULLY.\n";
} catch (Exception $e) {
    $conn->rollback();
    die("SEEDING FAILED: " . $e->getMessage() . "\n");
}
