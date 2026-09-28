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
echo "PSG PTC ERP – BATCH 40DI DEMO SEEDER & AUDIT\n";
echo "==================================================\n";
echo "Database Host: $hostInfo\n";
echo "Database Name: $dbName\n";
echo "Connection file: c:\\xampp\\htdocs\\Iqac\\include\\auth.php\n";
echo "==================================================\n";

if ($dbName !== 'iqac') {
    die("CRITICAL ERROR: Connection is using database '$dbName' instead of 'iqac'. Aborting to protect data.\n");
}

// Parse CLI options
$options = getopt('', ['dry-run', 'seed', 'verify', 'repair-auth']);
$isDryRun = isset($options['dry-run']);
$isSeed = isset($options['seed']);
$isVerify = isset($options['verify']);
$isRepairAuth = isset($options['repair-auth']);

if (!$isDryRun && !$isSeed && !$isVerify && !$isRepairAuth) {
    echo "Usage:\n";
    echo "  php seed_40di_demo.php --dry-run     Inspect schema, report planned changes\n";
    echo "  php seed_40di_demo.php --seed        Perform full relational demo data seeding\n";
    echo "  php seed_40di_demo.php --verify      Run comprehensive integrity checks and verification\n";
    echo "  php seed_40di_demo.php --repair-auth  Reset and verify credentials for demo accounts only\n";
    exit(0);
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

if ($isDryRun) {
    echo "MODE: DRY RUN (Scanning database schemas & counting rows)\n\n";
    
    foreach (['users', 'student_details', 'staff_details', 'subjects', 'staff_subject_allocation', 'student_marks', 'student_attendance', 'notifications'] as $tbl) {
        if (iqac_table_exists($conn, $tbl)) {
            $res = $conn->query("SELECT COUNT(*) AS total FROM `$tbl`");
            $total = $res ? $res->fetch_assoc()['total'] : 0;
            echo "Table '$tbl' exists. Current row count: $total\n";
        } else {
            echo "Table '$tbl' DOES NOT EXIST.\n";
        }
    }
    
    echo "\nDry Run Action Plan:\n";
    echo "1. Seed/Update 27 student users and profiles (40DI01 to 40DI27).\n";
    echo "2. Seed/Update 4 staff users and profiles (staff40, dsa40, dbms40, cn40).\n";
    echo "3. Seed/Update 1 admin user (mainadmin40).\n";
    echo "4. Set up Tutor-Student links (staff40 as tutor of all 27 students).\n";
    echo "5. Create subjects 40DI101-106, 201-206, 301-306, 401-406, 501-506, 601-606.\n";
    echo "6. Allocate Sem 3 subjects to the staff accounts.\n";
    echo "7. Seed academic marks for semesters 1-6 across performance groups.\n";
    echo "8. Create 'student_attendance' table and seed attendance.\n";
    echo "9. Seed comparison batch data (41CS, 42EE, 43ME).\n";
    echo "No modifications will be made in dry-run mode.\n";
    exit(0);
}

if ($isRepairAuth) {
    echo "MODE: REPAIR AUTHENTICATION (Resetting passwords/status of demo accounts only)\n\n";
    $conn->begin_transaction();
    try {
        $repairedUsers = 0;
        $repairedProfiles = 0;
        
        // Find staff40 staff_id to associate with students
        $tutorStaffId = null;
        $tutor_stmt = $conn->prepare("SELECT staff_id FROM staff_details WHERE user_id = (SELECT id FROM users WHERE username = 'staff40' LIMIT 1) LIMIT 1");
        if ($tutor_stmt) {
            $tutor_stmt->execute();
            $res_tutor = $tutor_stmt->get_result();
            if ($row_tutor = $res_tutor->fetch_assoc()) {
                $tutorStaffId = (int)$row_tutor['staff_id'];
            }
            $tutor_stmt->close();
        }

        // 1. Repair students
        foreach ($students as $s) {
            $roll = $s['roll'];
            $passHash = password_hash($roll, PASSWORD_DEFAULT);
            
            // Check users row
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $stmt->bind_param('s', $roll);
            $stmt->execute();
            $user_row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($user_row) {
                $uid = (int)$user_row['id'];
                $update = $conn->prepare("UPDATE users SET password_hash = ?, role = 'student', is_active = 1, department = 'Diploma Information Technology', semester = 3, section = 'A', batch = '2040-2043', failed_login_attempts = 0, locked_until = NULL WHERE id = ?");
                $update->bind_param('si', $passHash, $uid);
                $update->execute();
                $update->close();
                $repairedUsers++;
            } else {
                $insert = $conn->prepare("INSERT INTO users (username, password_hash, role, is_active, department, semester, section, batch, failed_login_attempts, locked_until, first_login, force_password_change) VALUES (?, ?, 'student', 1, 'Diploma Information Technology', 3, 'A', '2040-2043', 0, NULL, 0, 0)");
                $insert->bind_param('ss', $roll, $passHash);
                $insert->execute();
                $uid = (int)$conn->insert_id;
                $insert->close();
                $repairedUsers++;
            }
            
            // Check student_details profile row
            $stmt = $conn->prepare("SELECT student_id FROM student_details WHERE roll_number = ? LIMIT 1");
            $stmt->bind_param('s', $roll);
            $stmt->execute();
            $profile_row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($profile_row) {
                $studId = (int)$profile_row['student_id'];
                $update = $conn->prepare("UPDATE student_details SET user_id = ?, full_name = ?, gender = ?, blood_group = ?, department = 'Diploma Information Technology', class_year = '2', batch = '2040-2043', section = 'A', current_semester = 3, stage1_status = 'approved', tutor_staff_id = ? WHERE student_id = ?");
                $update->bind_param('issii', $uid, $s['name'], $s['gender'], $s['blood'], $tutorStaffId, $studId);
                $update->execute();
                $update->close();
                
                // Keep circular reference users.associated_id -> student_details.student_id
                $update_u = $conn->prepare("UPDATE users SET associated_id = ? WHERE id = ?");
                $update_u->bind_param('ii', $studId, $uid);
                $update_u->execute();
                $update_u->close();
                $repairedProfiles++;
            } else {
                $insert = $conn->prepare("INSERT INTO student_details (user_id, roll_number, full_name, gender, blood_group, department, class_year, batch, section, is_lateral, current_semester, stage1_status, tutor_staff_id) VALUES (?, ?, ?, ?, ?, 'Diploma Information Technology', '2', '2040-2043', 'A', 0, 3, 'approved', ?)");
                $insert->bind_param('isssssi', $uid, $roll, $s['name'], $s['gender'], $s['blood'], $tutorStaffId);
                $insert->execute();
                $studId = (int)$conn->insert_id;
                $insert->close();
                
                $update_u = $conn->prepare("UPDATE users SET associated_id = ? WHERE id = ?");
                $update_u->bind_param('ii', $studId, $uid);
                $update_u->execute();
                $update_u->close();
                $repairedProfiles++;
            }
        }
        
        // 2. Repair Staff
        $staffPass = password_hash('staff123', PASSWORD_DEFAULT);
        foreach ($staffAccounts as $st) {
            $username = $st['username'];
            
            // Check user row
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $user_row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($user_row) {
                $uid = (int)$user_row['id'];
                $update = $conn->prepare("UPDATE users SET password_hash = ?, role = 'staff', is_active = 1, department = 'Diploma Information Technology', failed_login_attempts = 0, locked_until = NULL WHERE id = ?");
                $update->bind_param('si', $staffPass, $uid);
                $update->execute();
                $update->close();
                $repairedUsers++;
            } else {
                $insert = $conn->prepare("INSERT INTO users (username, password_hash, role, is_active, department, failed_login_attempts, locked_until, first_login, force_password_change) VALUES (?, ?, 'staff', 1, 'Diploma Information Technology', 0, NULL, 0, 0)");
                $insert->bind_param('ss', $username, $staffPass);
                $insert->execute();
                $uid = (int)$conn->insert_id;
                $insert->close();
                $repairedUsers++;
            }
            
            // Check profile row
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
                
                // update circular ref
                $update_u = $conn->prepare("UPDATE users SET associated_id = ? WHERE id = ?");
                $update_u->bind_param('ii', $staffId, $uid);
                $update_u->execute();
                $update_u->close();
                $repairedProfiles++;
            } else {
                $insert = $conn->prepare("INSERT INTO staff_details (user_id, full_name, department, is_tutor) VALUES (?, ?, 'Diploma Information Technology', ?)");
                $insert->bind_param('isi', $uid, $st['name'], $st['is_tutor']);
                $insert->execute();
                $staffId = (int)$conn->insert_id;
                $insert->close();
                
                $update_u = $conn->prepare("UPDATE users SET associated_id = ? WHERE id = ?");
                $update_u->bind_param('ii', $staffId, $uid);
                $update_u->execute();
                $update_u->close();
                $repairedProfiles++;
            }
        }
        
        // 3. Repair admin account
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
            $repairedUsers++;
        } else {
            $insert = $conn->prepare("INSERT INTO users (username, password_hash, role, is_active, department, failed_login_attempts, locked_until, first_login, force_password_change) VALUES (?, ?, 'admin', 1, 'Diploma Information Technology', 0, NULL, 0, 0)");
            $insert->bind_param('ss', $adminAccount['username'], $adminPass);
            $insert->execute();
            $uid = (int)$conn->insert_id;
            $insert->close();
            $repairedUsers++;
        }
        
        $conn->commit();
        echo "REPAIR COMPLETE. Repaired $repairedUsers users, $repairedProfiles profile links.\n";
    } catch (Exception $e) {
        $conn->rollback();
        die("REPAIR FAILED: " . $e->getMessage() . "\n");
    }
    exit(0);
}

if ($isSeed) {
    echo "MODE: FULL SEED (Seeding working demo data into database iqac)\n\n";
    
    // Create attendance table if missing
    $conn->query("
        CREATE TABLE IF NOT EXISTS student_attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            subject_code VARCHAR(50) NOT NULL,
            semester INT NOT NULL,
            academic_year VARCHAR(20) NOT NULL,
            attendance_percent DECIMAL(5,2) NOT NULL,
            updated_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_student_subject_period (student_id, subject_code, semester, academic_year),
            INDEX idx_attendance_student (student_id),
            INDEX idx_attendance_subject (subject_code),
            INDEX idx_attendance_period (semester, academic_year),
            CONSTRAINT fk_attendance_student FOREIGN KEY (student_id) REFERENCES student_details(student_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    $conn->begin_transaction();
    try {
        // Step 1: Create/Update users & profiles for Staff
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
        }
        
        $tutorStaffId = $staffIds['staff40'];
        
        // Step 2: Create/Update Student Users & Profiles
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
                $update->bind_param('issii', $uid, $s['name'], $s['gender'], $s['blood'], $tutorStaffId, $studId);
                $update->execute();
                $update->close();
            } else {
                $insert = $conn->prepare("INSERT INTO student_details (user_id, roll_number, full_name, gender, blood_group, department, class_year, batch, section, is_lateral, current_semester, stage1_status, tutor_staff_id) VALUES (?, ?, ?, ?, ?, 'Diploma Information Technology', '2', '2040-2043', 'A', 0, 3, 'approved', ?)");
                $insert->bind_param('isssssi', $uid, $roll, $s['name'], $s['gender'], $s['blood'], $tutorStaffId);
                $insert->execute();
                $studId = (int)$conn->insert_id;
                $insert->close();
            }
            
            $update_u = $conn->prepare("UPDATE users SET associated_id = ? WHERE id = ?");
            $update_u->bind_param('ii', $studId, $uid);
            $update_u->execute();
            $update_u->close();
            
            $studentIds[$roll] = $studId;
        }

        // Step 3: Admin account
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

        // Step 4: Curriculum Subjects (Sem 1-6)
        $curriculum = [];
        for ($sem = 1; $sem <= 6; $sem++) {
            $year = (int)ceil($sem / 2);
            $curriculum[$sem] = [
                ['code' => "40DI{$sem}01", 'name' => "Subject {$sem}01", 'type' => 'theory'],
                ['code' => "40DI{$sem}02", 'name' => "Subject {$sem}02", 'type' => 'theory'],
                ['code' => "40DI{$sem}03", 'name' => "Subject {$sem}03", 'type' => 'theory'],
                ['code' => "40DI{$sem}04", 'name' => "Subject {$sem}04", 'type' => 'theory'],
                ['code' => "40DI{$sem}05", 'name' => "Practical {$sem}05", 'type' => 'lab'],
                ['code' => "40DI{$sem}06", 'name' => "Practical {$sem}06", 'type' => 'lab']
            ];
            
            // Override specific subjects from guidelines
            if ($sem === 3) {
                $curriculum[3] = [
                    ['code' => '40DI301', 'name' => 'Data Structures and Algorithms', 'type' => 'theory'],
                    ['code' => '40DI302', 'name' => 'Database Management Systems', 'type' => 'theory'],
                    ['code' => '40DI303', 'name' => 'Computer Networks', 'type' => 'theory'],
                    ['code' => '40DI304', 'name' => 'Object Oriented Programming', 'type' => 'theory'],
                    ['code' => '40DI305', 'name' => 'DBMS Practical', 'type' => 'lab'],
                    ['code' => '40DI306', 'name' => 'Data Structures Practical', 'type' => 'lab']
                ];
            }
        }

        // Insert Subjects
        $subj_stmt = $conn->prepare("INSERT INTO subjects (subject_code, subject_name, subject_type, semester, year, department) VALUES (?, ?, ?, ?, ?, 'Diploma Information Technology') ON DUPLICATE KEY UPDATE subject_name = VALUES(subject_name), subject_type = VALUES(subject_type), semester = VALUES(semester), year = VALUES(year), department = VALUES(department)");
        foreach ($curriculum as $sem => $subjs) {
            $year = (int)ceil($sem / 2);
            foreach ($subjs as $s) {
                $subj_stmt->bind_param('ssiii', $s['code'], $s['name'], $s['type'], $sem, $year);
                $subj_stmt->execute();
            }
        }
        $subj_stmt->close();

        // Step 5: Allocations
        $alloc_stmt = $conn->prepare("INSERT INTO staff_subject_allocation (staff_id, subject_code, class_year) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE staff_id = VALUES(staff_id)");
        
        // Year 1 alloc
        foreach (['40DI101', '40DI102', '40DI201', '40DI202'] as $c) {
            $y = '1'; $alloc_stmt->bind_param('iss', $staffIds['staff40'], $c, $y); $alloc_stmt->execute();
        }
        foreach (['40DI103', '40DI105', '40DI203', '40DI205'] as $c) {
            $y = '1'; $alloc_stmt->bind_param('iss', $staffIds['dsa40'], $c, $y); $alloc_stmt->execute();
        }
        foreach (['40DI104', '40DI106', '40DI204', '40DI206'] as $c) {
            $y = '1'; $alloc_stmt->bind_param('iss', $staffIds['dbms40'], $c, $y); $alloc_stmt->execute();
        }
        
        // Year 2 Alloc (Sem 3 allocated subjects)
        $y = '2';
        $alloc_stmt->bind_param('iss', $staffIds['dsa40'], $c = '40DI301', $y); $alloc_stmt->execute();
        $alloc_stmt->bind_param('iss', $staffIds['dsa40'], $c = '40DI306', $y); $alloc_stmt->execute();
        $alloc_stmt->bind_param('iss', $staffIds['dsa40'], $c = '40DI401', $y); $alloc_stmt->execute();
        $alloc_stmt->bind_param('iss', $staffIds['dsa40'], $c = '40DI405', $y); $alloc_stmt->execute();

        $alloc_stmt->bind_param('iss', $staffIds['dbms40'], $c = '40DI302', $y); $alloc_stmt->execute();
        $alloc_stmt->bind_param('iss', $staffIds['dbms40'], $c = '40DI305', $y); $alloc_stmt->execute();
        $alloc_stmt->bind_param('iss', $staffIds['dbms40'], $c = '40DI402', $y); $alloc_stmt->execute();

        $alloc_stmt->bind_param('iss', $staffIds['cn40'], $c = '40DI303', $y); $alloc_stmt->execute();
        $alloc_stmt->bind_param('iss', $staffIds['cn40'], $c = '40DI403', $y); $alloc_stmt->execute();
        $alloc_stmt->bind_param('iss', $staffIds['cn40'], $c = '40DI406', $y); $alloc_stmt->execute();

        $alloc_stmt->bind_param('iss', $staffIds['staff40'], $c = '40DI304', $y); $alloc_stmt->execute();
        $alloc_stmt->bind_param('iss', $staffIds['staff40'], $c = '40DI404', $y); $alloc_stmt->execute();

        // Year 3 alloc
        $y = '3';
        foreach (['40DI501', '40DI506', '40DI601', '40DI605'] as $c) {
            $alloc_stmt->bind_param('iss', $staffIds['staff40'], $c, $y); $alloc_stmt->execute();
        }
        foreach (['40DI502', '40DI602'] as $c) {
            $alloc_stmt->bind_param('iss', $staffIds['dsa40'], $c, $y); $alloc_stmt->execute();
        }
        foreach (['40DI503', '40DI505', '40DI603'] as $c) {
            $alloc_stmt->bind_param('iss', $staffIds['dbms40'], $c, $y); $alloc_stmt->execute();
        }
        foreach (['40DI504', '40DI604', '40DI606'] as $c) {
            $alloc_stmt->bind_param('iss', $staffIds['cn40'], $c, $y); $alloc_stmt->execute();
        }
        $alloc_stmt->close();

        // Step 6: Deterministic Student Marks & Attendance (Sem 1-6)
        $marks_stmt = $conn->prepare("
            INSERT INTO student_marks (
                student_id, subject_id, subject_code, semester, academic_year, class_year, batch, section,
                ca1, ca2, ca3, best_two, ca_converted_30, assignment_mark, theory_total,
                cycle1_execution, cycle1_test, cycle2_record, cycle2_test, practical_total,
                semester_grade, grade_point, is_arrear, status, entered_by, submitted_at
            ) VALUES (?, ?, ?, ?, ?, ?, '2040-2043', 'A', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'locked', ?, NOW())
            ON DUPLICATE KEY UPDATE
                ca1=VALUES(ca1), ca2=VALUES(ca2), ca3=VALUES(ca3), best_two=VALUES(best_two),
                ca_converted_30=VALUES(ca_converted_30), assignment_mark=VALUES(assignment_mark),
                theory_total=VALUES(theory_total), cycle1_execution=VALUES(cycle1_execution),
                cycle1_test=VALUES(cycle1_test), cycle2_record=VALUES(cycle2_record),
                cycle2_test=VALUES(cycle2_test), practical_total=VALUES(practical_total),
                semester_grade=VALUES(semester_grade), grade_point=VALUES(grade_point),
                is_arrear=VALUES(is_arrear), status=VALUES(status)
        ");

        $att_stmt = $conn->prepare("
            INSERT INTO student_attendance (
                student_id, subject_code, semester, academic_year, attendance_percent
            ) VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE attendance_percent = VALUES(attendance_percent)
        ");

        foreach ($students as $idx => $s) {
            $studId = $studentIds[$s['roll']];
            
            // Base ability seed
            $base_abilities = [
                'top' => 90.0,
                'above_avg' => 80.0,
                'avg' => 70.0,
                'low' => 58.0,
                'arrear' => 45.0
            ];
            $ability = $base_abilities[$s['group']] + ($idx % 5) - 2;

            for ($sem = 1; $sem <= 6; $sem++) {
                $y = (int)ceil($sem / 2);
                $yearLabel = (string)$y;
                $acadYear = sprintf("%d-%d", 2039 + $y, 2040 + $y);
                
                foreach ($curriculum[$sem] as $subIdx => $subj) {
                    $code = $subj['code'];
                    $subIdCompat = (int)(sprintf('%u', crc32($code)) % 2147483647);
                    
                    // Marks Math
                    $isLab = $subj['type'] === 'lab';
                    $hasArrear = $s['group'] === 'arrear' && ($sem < 4) && ($subIdx === 0 || $subIdx === 3);

                    if ($hasArrear) {
                        $ca1 = 15.0; $ca2 = 18.0; $ca3 = 12.0; $best_two = 16.5;
                        $ca_conv = 9.9; $assign = 4.0; $theory_tot = 13.9;
                        $grade = 'RA'; $gp = 0.0; $is_arrear = 1;
                        $cy1_ex = $cy1_t = $cy2_r = $cy2_t = $prac_tot = 0.0;
                    } else {
                        $dev = ($idx + $sem + $subIdx) % 10 - 5; // -5 to +4
                        $score = clamp($ability + $dev, 0.0, 100.0);
                        
                        $ca1 = clamp(($score / 2) + (($idx * 3) % 5) - 2, 0.0, 50.0);
                        $ca2 = clamp(($score / 2) + (($idx * 7) % 5) - 2, 0.0, 50.0);
                        $ca3 = clamp(($score / 2) + (($idx * 11) % 5) - 2, 0.0, 50.0);
                        
                        $cas = [$ca1, $ca2, $ca3];
                        rsort($cas);
                        $best_two = ($cas[0] + $cas[1]) / 2;
                        $ca_conv = ($best_two / 50) * 30;
                        $assign = clamp(($score / 10) + (($idx * 2) % 3) - 1, 0.0, 10.0);
                        $theory_tot = clamp($ca_conv + $assign, 0.0, 40.0);
                        
                        if ($isLab) {
                            $cy1_ex = clamp(($score / 10) + (($idx * 2) % 3) - 1, 0.0, 10.0);
                            $cy1_t = clamp(($score / 10) + (($idx * 4) % 3) - 1, 0.0, 10.0);
                            $cy2_r = clamp(($score / 10) + (($idx * 6) % 3) - 1, 0.0, 10.0);
                            $cy2_t = clamp(($score / 10) + (($idx * 8) % 3) - 1, 0.0, 10.0);
                            $prac_tot = clamp($cy1_ex + $cy1_t + $cy2_r + $cy2_t, 0.0, 40.0);
                            $theory_tot = 0.0;
                        } else {
                            $cy1_ex = $cy1_t = $cy2_r = $cy2_t = $prac_tot = 0.0;
                        }
                        
                        // Grade and GP
                        $total_percent = $score;
                        if ($total_percent >= 90.0) { $grade = 'O'; $gp = 10.0; }
                        elseif ($total_percent >= 80.0) { $grade = 'A+'; $gp = 9.0; }
                        elseif ($total_percent >= 70.0) { $grade = 'A'; $gp = 8.0; }
                        elseif ($total_percent >= 60.0) { $grade = 'B+'; $gp = 7.0; }
                        elseif ($total_percent >= 50.0) { $grade = 'B'; $gp = 6.0; }
                        elseif ($total_percent >= 40.0) { $grade = 'C'; $gp = 5.0; }
                        else { $grade = 'RA'; $gp = 0.0; }
                        
                        $is_arrear = ($grade === 'RA') ? 1 : 0;
                    }
                    
                    $teacher_id = $staffIds['staff40']; // Fallback
                    if ($sem === 3) {
                        if ($code === '40DI301' || $code === '40DI306') $teacher_id = $staffIds['dsa40'];
                        elseif ($code === '40DI302' || $code === '40DI305') $teacher_id = $staffIds['dbms40'];
                        elseif ($code === '40DI303') $teacher_id = $staffIds['cn40'];
                        elseif ($code === '40DI304') $teacher_id = $staffIds['staff40'];
                    }
                    
                    // Bind & execute marks
                    $marks_stmt->bind_param(
                        'iisisdddddddddddddsdii',
                        $studId, $subIdCompat, $code, $sem, $acadYear, $yearLabel,
                        $ca1, $ca2, $ca3, $best_two, $ca_conv, $assign, $theory_tot,
                        $cy1_ex, $cy1_t, $cy2_r, $cy2_t, $prac_tot,
                        $grade, $gp, $is_arrear, $teacher_id
                    );
                    $marks_stmt->execute();
                    
                    // Attendance Math
                    $att_ranges = [
                        'top' => [88.0, 99.0],
                        'above_avg' => [82.0, 96.0],
                        'avg' => [75.0, 90.0],
                        'low' => [65.0, 82.0],
                        'arrear' => [55.0, 78.0]
                    ];
                    $range = $att_ranges[$s['group']];
                    $dev_att = ($idx * 3 + $sem * 5 + $subIdx) % 15 - 7;
                    $attendance = clamp(($range[0] + $range[1])/2 + $dev_att, 0.0, 100.0);
                    
                    if ($s['roll'] === '40DI24' && $code === '40DI303') {
                        $attendance = 72.00;
                    }
                    
                    $att_stmt->bind_param('isiss', $studId, $code, $sem, $acadYear, $attendance);
                    $att_stmt->execute();
                }
            }
        }
        $marks_stmt->close();
        $att_stmt->close();

        // Step 7: Seed Placement, Sports, Achievements, Higher Studies
        $conn->query("INSERT INTO placement_statistics (academic_year, batch_year, total_eligible, placed_count, average_package, highest_package, lowest_package) VALUES ('2042-2043', '2043', 27, 24, 4.50, 8.50, 3.20) ON DUPLICATE KEY UPDATE placed_count = VALUES(placed_count)");
        
        $place_stmt = $conn->prepare("INSERT INTO placement_opportunities (mou_id, job_title, eligibility_criteria, salary_package, status) VALUES (1, 'Associate Developer', 'DI GPA > 7.0', '4.5 LPA', 'open')");
        if (iqac_table_exists($conn, 'placement_opportunities')) {
            $conn->query("INSERT IGNORE INTO mou_master (id, mou_number, organization_name, organization_type) VALUES (1, 'MOU-40DI-01', 'PSG Software Solutions', 'industry')");
            $place_stmt->execute();
            $place_stmt->close();
        }
        
        // Sports
        $sports_stmt = $conn->prepare("INSERT INTO student_sports (student_id, sport_name, level, position, year_participated) VALUES (?, ?, ?, ?, ?)");
        $sport_data = [
            [$studentIds['40DI02'], 'Athletics 100m', 'State', '1st', 2041],
            [$studentIds['40DI11'], 'Volleyball', 'District', 'Runner-up', 2042],
            [$studentIds['40DI15'], 'Chess', 'Zonal', 'Winner', 2042]
        ];
        foreach ($sport_data as $sd) {
            $sports_stmt->bind_param('isssi', $sd[0], $sd[1], $sd[2], $sd[3], $sd[4]);
            $sports_stmt->execute();
        }
        $sports_stmt->close();

        // Achievements
        $ach_stmt = $conn->prepare("INSERT INTO student_achievements (student_id, title, achievement_type, description, achievement_date, status) VALUES (?, ?, ?, ?, '2042-02-15', 'approved')");
        $ach_data = [
            [$studentIds['40DI01'], 'Smart India Hackathon Zonal Winner', 'technical', 'Led team for IoT prototype'],
            [$studentIds['40DI05'], 'Paper Presentation 1st Prize', 'academic', 'Presented AI Trends paper'],
            [$studentIds['40DI09'], 'Android App Launch on PlayStore', 'technical', 'Developed attendance utility app']
        ];
        foreach ($ach_data as $ad) {
            $ach_stmt->bind_param('isss', $ad[0], $ad[1], $ad[2], $ad[3]);
            $ach_stmt->execute();
        }
        $ach_stmt->close();

        // Publications
        $pub_stmt = $conn->prepare("INSERT INTO student_publications (roll_no, student_name, paper_title, journal_conference, issn_isbn, date_of_publication) VALUES (?, ?, ?, ?, ?, '2042-03-20')");
        $pub_data = [
            ['40DI01', 'Aadhavan K.', 'Edge Computing in IoT', 'IEEE Conference', '978-1-2345'],
            ['40DI03', 'Chitra M.', 'Responsive Web Technologies', 'Intl Journal of Web Dev', '1234-5678']
        ];
        foreach ($pub_data as $pd) {
            $pub_stmt->bind_param('sssss', $pd[0], $pd[1], $pd[2], $pd[3], $pd[4]);
            $pub_stmt->execute();
        }
        $pub_stmt->close();

        // Higher Studies
        $hs_stmt = $conn->prepare("INSERT INTO student_higher_studies (student_id, college_name, course_name, admission_year) VALUES (?, ?, ?, ?)");
        $hs_data = [
            [$studentIds['40DI03'], 'PSG College of Technology', 'B.Tech Information Technology', 2043],
            [$studentIds['40DI04'], 'Coimbatore Institute of Technology', 'B.E. Computer Science Engineering', 2043]
        ];
        foreach ($hs_data as $hd) {
            $hs_stmt->bind_param('issi', $hd[0], $hd[1], $hd[2], $hd[3]);
            $hs_stmt->execute();
        }
        $hs_stmt->close();

        // Step 8: Seed comparison streams (41CS, 42EE, 43ME)
        $cs_users = [
            ['username' => '41CS01', 'name' => 'CS Student One', 'gender' => 'Male', 'blood' => 'O+', 'tutor' => $staffIds['staff40']],
            ['username' => '41CS02', 'name' => 'CS Student Two', 'gender' => 'Female', 'blood' => 'A+', 'tutor' => $staffIds['staff40']]
        ];
        $ee_users = [
            ['username' => '42EE01', 'name' => 'EE Student One', 'gender' => 'Male', 'blood' => 'B+', 'tutor' => $staffIds['staff40']],
            ['username' => '42EE02', 'name' => 'EE Student Two', 'gender' => 'Female', 'blood' => 'O-', 'tutor' => $staffIds['staff40']]
        ];
        $me_users = [
            ['username' => '43ME01', 'name' => 'ME Student One', 'gender' => 'Male', 'blood' => 'AB+', 'tutor' => $staffIds['staff40']],
            ['username' => '43ME02', 'name' => 'ME Student Two', 'gender' => 'Female', 'blood' => 'A-', 'tutor' => $staffIds['staff40']]
        ];
        
        $compare_batches = [
            '41CS' => ['dept' => 'Computer Science', 'batch' => '2041-2044', 'class_year' => '1', 'semester' => 1, 'data' => $cs_users],
            '42EE' => ['dept' => 'Electrical Engineering', 'batch' => '2042-2045', 'class_year' => '1', 'semester' => 1, 'data' => $ee_users],
            '43ME' => ['dept' => 'Mechanical Engineering', 'batch' => '2043-2046', 'class_year' => '1', 'semester' => 1, 'data' => $me_users]
        ];

        foreach ($compare_batches as $bCode => $bMeta) {
            foreach ($bMeta['data'] as $cb) {
                $cPass = password_hash($cb['username'], PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
                $stmt->bind_param('s', $cb['username']);
                $stmt->execute();
                $c_user = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                if ($c_user) {
                    $uid = (int)$c_user['id'];
                    $update = $conn->prepare("UPDATE users SET password_hash = ?, role = 'student', is_active = 1, department = ?, semester = ?, section = 'A', batch = ?, failed_login_attempts = 0, locked_until = NULL WHERE id = ?");
                    $update->bind_param('ssisi', $cPass, $bMeta['dept'], $bMeta['semester'], $bMeta['batch'], $uid);
                    $update->execute();
                    $update->close();
                } else {
                    $insert = $conn->prepare("INSERT INTO users (username, password_hash, role, is_active, department, semester, section, batch, failed_login_attempts, locked_until, first_login, force_password_change) VALUES (?, ?, 'student', 1, ?, ?, 'A', ?, 0, NULL, 0, 0)");
                    $insert->bind_param('sssiss', $cb['username'], $cPass, $bMeta['dept'], $bMeta['semester'], $bMeta['batch']);
                    $insert->execute();
                    $uid = (int)$conn->insert_id;
                    $insert->close();
                }
                
                $stmt = $conn->prepare("SELECT student_id FROM student_details WHERE roll_number = ? LIMIT 1");
                $stmt->bind_param('s', $cb['username']);
                $stmt->execute();
                $c_profile = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                if ($c_profile) {
                    $studId = (int)$c_profile['student_id'];
                    $update = $conn->prepare("UPDATE student_details SET user_id = ?, full_name = ?, gender = ?, blood_group = ?, department = ?, class_year = ?, batch = ?, section = 'A', current_semester = ?, stage1_status = 'approved', tutor_staff_id = ? WHERE student_id = ?");
                    $update->bind_param('issssssiii', $uid, $cb['name'], $cb['gender'], $cb['blood'], $bMeta['dept'], $bMeta['class_year'], $bMeta['batch'], $bMeta['semester'], $cb['tutor'], $studId);
                    $update->execute();
                    $update->close();
                } else {
                    $insert = $conn->prepare("INSERT INTO student_details (user_id, roll_number, full_name, gender, blood_group, department, class_year, batch, section, is_lateral, current_semester, stage1_status, tutor_staff_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'A', 0, ?, 'approved', ?)");
                    $insert->bind_param('isssssssii', $uid, $cb['username'], $cb['name'], $cb['gender'], $cb['blood'], $bMeta['dept'], $bMeta['class_year'], $bMeta['batch'], $bMeta['semester'], $cb['tutor']);
                    $insert->execute();
                    $studId = (int)$conn->insert_id;
                    $insert->close();
                }
                
                $update_u = $conn->prepare("UPDATE users SET associated_id = ? WHERE id = ?");
                $update_u->bind_param('ii', $studId, $uid);
                $update_u->execute();
                $update_u->close();
            }
        }

        $conn->commit();
        echo "SEEDING COMPLETE. Seeder executed successfully inside database transaction.\n";
    } catch (Exception $e) {
        $conn->rollback();
        die("CRITICAL SEEDING FAILURE: " . $e->getMessage() . "\n");
    }
    exit(0);
}

if ($isVerify) {
    echo "MODE: VERIFY (Running comprehensive integrity & profile mapping checks)\n\n";
    
    $failures = 0;
    
    // Check 27 student accounts
    echo "1. Checking 27 Student Accounts:\n";
    foreach ($students as $s) {
        $roll = $s['roll'];
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $roll);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$user) {
            echo "  [FAIL] Student $roll: No row in 'users' table!\n";
            $failures++;
            continue;
        }
        
        $pOk = password_verify($roll, $user['password_hash']);
        $pStatus = $pOk ? 'PASS' : 'FAIL (Hash mismatch)';
        if (!$pOk) $failures++;
        
        $stmt = $conn->prepare("SELECT * FROM student_details WHERE roll_number = ? LIMIT 1");
        $stmt->bind_param('s', $roll);
        $stmt->execute();
        $profile = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$profile) {
            echo "  [FAIL] Student $roll: No row in 'student_details' profile table!\n";
            $failures++;
            continue;
        }
        
        $linkOk = ((int)$user['associated_id'] === (int)$profile['student_id'] && (int)$profile['user_id'] === (int)$user['id']);
        $linkStatus = $linkOk ? 'PASS' : 'FAIL (Circular link invalid)';
        if (!$linkOk) $failures++;
        
        $appOk = ($profile['stage1_status'] === 'approved' || $profile['stage1_status'] === 'active');
        $appStatus = $appOk ? 'PASS' : 'FAIL (Status: ' . $profile['stage1_status'] . ')';
        if (!$appOk) $failures++;
        
        echo "  - Student $roll: PassVerify=$pStatus, CircularLink=$linkStatus, ApprovedStatus=$appStatus\n";
    }
    
    // Check 4 staff accounts
    echo "\n2. Checking 4 Staff Accounts:\n";
    foreach ($staffAccounts as $st) {
        $username = $st['username'];
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$user) {
            echo "  [FAIL] Staff $username: No row in 'users' table!\n";
            $failures++;
            continue;
        }
        
        $pOk = password_verify('staff123', $user['password_hash']);
        $pStatus = $pOk ? 'PASS' : 'FAIL (Hash mismatch)';
        if (!$pOk) $failures++;
        
        $stmt = $conn->prepare("SELECT * FROM staff_details WHERE user_id = ? LIMIT 1");
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $profile = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$profile) {
            echo "  [FAIL] Staff $username: No row in 'staff_details' profile table!\n";
            $failures++;
            continue;
        }
        
        $linkOk = ((int)$user['associated_id'] === (int)$profile['staff_id']);
        $linkStatus = $linkOk ? 'PASS' : 'FAIL (Circular link invalid)';
        if (!$linkOk) $failures++;
        
        $tutorStatus = ($st['is_tutor'] === (int)$profile['is_tutor']) ? 'PASS' : 'FAIL (Tutor flag mismatch)';
        if ($st['is_tutor'] !== (int)$profile['is_tutor']) $failures++;
        
        echo "  - Staff $username: PassVerify=$pStatus, CircularLink=$linkStatus, TutorStatus=$tutorStatus\n";
    }
    
    // Check mainadmin40
    echo "\n3. Checking Admin Account:\n";
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param('s', $adminAccount['username']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        echo "  [FAIL] Admin mainadmin40: No row in 'users' table!\n";
        $failures++;
    } else {
        $pOk = password_verify('admin123', $user['password_hash']);
        $pStatus = $pOk ? 'PASS' : 'FAIL (Hash mismatch)';
        if (!$pOk) $failures++;
        echo "  - Admin mainadmin40: PassVerify=$pStatus\n";
    }
    
    // Count records
    echo "\n4. Checking Record Summaries:\n";
    foreach (['subjects', 'student_marks', 'student_attendance'] as $tbl) {
        $res = $conn->query("SELECT COUNT(*) AS total FROM `$tbl`");
        $total = $res ? $res->fetch_assoc()['total'] : 0;
        echo "  - Table '$tbl' total rows: $total\n";
    }
    
    echo "\n==================================================\n";
    if ($failures === 0) {
        echo "VERIFICATION SUMMARY: ALL CHECKS PASSED!\n";
    } else {
        echo "VERIFICATION SUMMARY: FAILED! Total failures: $failures\n";
    }
    echo "==================================================\n";
    exit($failures === 0 ? 0 : 1);
}

function clamp($val, $min, $max) {
    return max($min, min($max, $val));
}
