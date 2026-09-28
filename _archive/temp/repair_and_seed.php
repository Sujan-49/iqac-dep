<?php
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

require_once __DIR__ . '/../include/auth.php';

function marks_subject_compat_id(string $subjectCode): int
{
    return (int)(sprintf('%u', crc32($subjectCode)) % 2147483647);
}

if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . ($conn->connect_error ?? 'Connection is not set') . "\n");
}

$db_res = $conn->query("SELECT DATABASE() AS db");
$db_row = $db_res ? $db_res->fetch_assoc() : null;
$dbName = $db_row ? (string)$db_row['db'] : 'unknown';

echo "==================================================\n";
echo "PSG PTC ERP – REPAIR AND SEED SCRIPT (40DI BATCH)\n";
echo "==================================================\n";
echo "Database: $dbName\n";

if ($dbName !== 'iqac') {
    die("CRITICAL ERROR: Connection is using database '$dbName' instead of 'iqac'. Aborting.\n");
}

// 1. Data Definitions
$deptName = 'Diploma in Information Technology';
$batchName = '40DI';
$classYear = '3';
$sectionName = 'A';

$studentsInfo = [
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

$conn->begin_transaction();
try {
    // 2. Clear old obsolete/duplicate users that could conflict with the exact required names
    $conn->query("DELETE FROM users WHERE username = 'staff40'");
    $conn->query("DELETE FROM users WHERE username = 'mainadmin40'");

    // 3. Create or update Tutor account: tutor40 / staff123
    $tutorUsername = 'tutor40';
    $tutorPassHash = password_hash('staff123', PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param('s', $tutorUsername);
    $stmt->execute();
    $tRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($tRow) {
        $tUserId = (int)$tRow['id'];
        $stmt = $conn->prepare("UPDATE users SET password_hash = ?, role = 'staff', is_active = 1, department = ?, semester = 5, section = ?, batch = ?, failed_login_attempts = 0, locked_until = NULL WHERE id = ?");
        $stmt->bind_param('ssssi', $tutorPassHash, $deptName, $sectionName, $batchName, $tUserId);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, password_hash, role, is_active, department, semester, section, batch, failed_login_attempts, locked_until, first_login, force_password_change) VALUES (?, ?, 'staff', 1, ?, 5, ?, ?, 0, NULL, 0, 0)");
        $stmt->bind_param('sssss', $tutorUsername, $tutorPassHash, $deptName, $sectionName, $batchName);
        $stmt->execute();
        $tUserId = (int)$conn->insert_id;
        $stmt->close();
    }
    
    // Create/update staff_details profile for tutor40
    $stmt = $conn->prepare("SELECT staff_id FROM staff_details WHERE user_id = ? LIMIT 1");
    $stmt->bind_param('i', $tUserId);
    $stmt->execute();
    $tpRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($tpRow) {
        $tutorStaffId = (int)$tpRow['staff_id'];
        $stmt = $conn->prepare("UPDATE staff_details SET full_name = 'Batch Tutor', department = ?, is_tutor = 1 WHERE staff_id = ?");
        $stmt->bind_param('si', $deptName, $tutorStaffId);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO staff_details (user_id, full_name, department, is_tutor) VALUES (?, 'Batch Tutor', ?, 1)");
        $stmt->bind_param('is', $tUserId, $deptName);
        $stmt->execute();
        $tutorStaffId = (int)$conn->insert_id;
        $stmt->close();
    }
    
    // Associate the staff_id back to users
    $stmt = $conn->prepare("UPDATE users SET associated_id = ? WHERE id = ?");
    $stmt->bind_param('ii', $tutorStaffId, $tUserId);
    $stmt->execute();
    $stmt->close();
    
    echo "Tutor tutor40 synced successfully (staff_id: $tutorStaffId, user_id: $tUserId).\n";

    // 4. Create or update Subject Staff: dsa40, dbms40, cn40
    $staffPassHash = password_hash('staff123', PASSWORD_DEFAULT);
    $staffList = [
        'dsa40' => 'DSA Faculty',
        'dbms40' => 'DBMS Faculty',
        'cn40' => 'CN Faculty'
    ];
    $staffIds = [];
    
    foreach ($staffList as $sUser => $sName) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $sUser);
        $stmt->execute();
        $sRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($sRow) {
            $suid = (int)$sRow['id'];
            $stmt = $conn->prepare("UPDATE users SET password_hash = ?, role = 'staff', is_active = 1, department = ?, failed_login_attempts = 0, locked_until = NULL WHERE id = ?");
            $stmt->bind_param('ssi', $staffPassHash, $deptName, $suid);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, password_hash, role, is_active, department, failed_login_attempts, locked_until, first_login, force_password_change) VALUES (?, ?, 'staff', 1, ?, 0, NULL, 0, 0)");
            $stmt->bind_param('sss', $sUser, $staffPassHash, $deptName);
            $stmt->execute();
            $suid = (int)$conn->insert_id;
            $stmt->close();
        }
        
        $stmt = $conn->prepare("SELECT staff_id FROM staff_details WHERE user_id = ? LIMIT 1");
        $stmt->bind_param('i', $suid);
        $stmt->execute();
        $spRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($spRow) {
            $staffId = (int)$spRow['staff_id'];
            $stmt = $conn->prepare("UPDATE staff_details SET full_name = ?, department = ?, is_tutor = 0 WHERE staff_id = ?");
            $stmt->bind_param('ssi', $sName, $deptName, $staffId);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO staff_details (user_id, full_name, department, is_tutor) VALUES (?, ?, ?, 0)");
            $stmt->bind_param('iss', $suid, $sName, $deptName);
            $stmt->execute();
            $staffId = (int)$conn->insert_id;
            $stmt->close();
        }
        
        $stmt = $conn->prepare("UPDATE users SET associated_id = ? WHERE id = ?");
        $stmt->bind_param('ii', $staffId, $suid);
        $stmt->execute();
        $stmt->close();
        
        $staffIds[$sUser] = $staffId;
        echo "Staff $sUser synced successfully (staff_id: $staffId, user_id: $suid).\n";
    }

    // 5. Create or update Admin: admin40 / admin123
    $adminUsername = 'admin40';
    $adminPassHash = password_hash('admin123', PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param('s', $adminUsername);
    $stmt->execute();
    $aRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($aRow) {
        $aUserId = (int)$aRow['id'];
        $stmt = $conn->prepare("UPDATE users SET password_hash = ?, role = 'admin', is_active = 1, department = ?, failed_login_attempts = 0, locked_until = NULL WHERE id = ?");
        $stmt->bind_param('ssi', $adminPassHash, $deptName, $aUserId);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, password_hash, role, is_active, department, failed_login_attempts, locked_until, first_login, force_password_change) VALUES (?, ?, 'admin', 1, ?, 0, NULL, 0, 0)");
        $stmt->bind_param('sss', $adminUsername, $adminPassHash, $deptName);
        $stmt->execute();
        $aUserId = (int)$conn->insert_id;
        $stmt->close();
    }
    
    // Admin staff details profile (to satisfy references and verification tools)
    $stmt = $conn->prepare("SELECT staff_id FROM staff_details WHERE user_id = ? LIMIT 1");
    $stmt->bind_param('i', $aUserId);
    $stmt->execute();
    $apRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($apRow) {
        $adminStaffId = (int)$apRow['staff_id'];
        $stmt = $conn->prepare("UPDATE staff_details SET full_name = 'Demo Admin', department = 'Administration', is_tutor = 0 WHERE staff_id = ?");
        $stmt->bind_param('i', $adminStaffId);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO staff_details (user_id, full_name, department, is_tutor) VALUES (?, 'Demo Admin', 'Administration', 0)");
        $stmt->bind_param('i', $aUserId);
        $stmt->execute();
        $adminStaffId = (int)$conn->insert_id;
        $stmt->close();
    }
    
    $stmt = $conn->prepare("UPDATE users SET associated_id = ? WHERE id = ?");
    $stmt->bind_param('ii', $adminStaffId, $aUserId);
    $stmt->execute();
    $stmt->close();
    
    echo "Admin admin40 synced successfully (staff_id: $adminStaffId, user_id: $aUserId).\n";

    // 6. Create or update Students: 40DI01 to 40DI27
    $studentIds = [];
    foreach ($studentsInfo as $sInfo) {
        $roll = $sInfo['roll'];
        $sPassHash = password_hash($roll, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $roll);
        $stmt->execute();
        $suRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($suRow) {
            $suid = (int)$suRow['id'];
            $stmt = $conn->prepare("UPDATE users SET password_hash = ?, role = 'student', is_active = 1, department = ?, semester = 5, section = ?, batch = ?, failed_login_attempts = 0, locked_until = NULL WHERE id = ?");
            $stmt->bind_param('ssssi', $sPassHash, $deptName, $sectionName, $batchName, $suid);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, password_hash, role, is_active, department, semester, section, batch, failed_login_attempts, locked_until, first_login, force_password_change) VALUES (?, ?, 'student', 1, ?, 5, ?, ?, 0, NULL, 0, 0)");
            $stmt->bind_param('sssss', $roll, $sPassHash, $deptName, $sectionName, $batchName);
            $stmt->execute();
            $suid = (int)$conn->insert_id;
            $stmt->close();
        }
        
        $stmt = $conn->prepare("SELECT student_id FROM student_details WHERE roll_number = ? LIMIT 1");
        $stmt->bind_param('s', $roll);
        $stmt->execute();
        $spRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($spRow) {
            $studId = (int)$spRow['student_id'];
            $stmt = $conn->prepare("UPDATE student_details SET user_id = ?, full_name = ?, gender = ?, blood_group = ?, department = ?, class_year = ?, batch = ?, section = ?, current_semester = 5, stage1_status = 'approved', tutor_staff_id = ? WHERE student_id = ?");
            $stmt->bind_param('isssssssii', $suid, $sInfo['name'], $sInfo['gender'], $sInfo['blood'], $deptName, $classYear, $batchName, $sectionName, $tutorStaffId, $studId);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO student_details (user_id, roll_number, full_name, gender, blood_group, department, class_year, batch, section, is_lateral, current_semester, stage1_status, tutor_staff_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 5, 'approved', ?)");
            $stmt->bind_param('issssssssi', $suid, $roll, $sInfo['name'], $sInfo['gender'], $sInfo['blood'], $deptName, $classYear, $batchName, $sectionName, $tutorStaffId);
            $stmt->execute();
            $studId = (int)$conn->insert_id;
            $stmt->close();
        }
        
        // Ensure user associated_id is correct
        $stmt = $conn->prepare("UPDATE users SET associated_id = ? WHERE id = ?");
        $stmt->bind_param('ii', $studId, $suid);
        $stmt->execute();
        $stmt->close();
        
        $studentIds[$roll] = $studId;
    }
    echo "Successfully synced all 27 student user accounts & details.\n";

    // 7. Create Semester 5 subjects if they don't exist
    $sem5Subjects = [
        ['code' => '40DI501', 'name' => 'Data Structures and Algorithms', 'type' => 'theory'],
        ['code' => '40DI502', 'name' => 'Database Management Systems', 'type' => 'theory'],
        ['code' => '40DI503', 'name' => 'Computer Networks', 'type' => 'theory']
    ];
    
    foreach ($sem5Subjects as $sub) {
        $stmt = $conn->prepare("SELECT subject_code FROM subjects WHERE subject_code = ? LIMIT 1");
        $stmt->bind_param('s', $sub['code']);
        $stmt->execute();
        $subExists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        
        if (!$subExists) {
            $stmt = $conn->prepare("INSERT INTO subjects (subject_code, subject_name, subject_type, semester, year, department) VALUES (?, ?, ?, 5, 3, ?)");
            $stmt->bind_param('ssss', $sub['code'], $sub['name'], $sub['type'], $deptName);
            $stmt->execute();
            $stmt->close();
            echo "Subject {$sub['code']} ({$sub['name']}) created.\n";
        } else {
            $stmt = $conn->prepare("UPDATE subjects SET subject_name = ?, subject_type = ?, semester = 5, year = 3, department = ? WHERE subject_code = ?");
            $stmt->bind_param('ssss', $sub['name'], $sub['type'], $deptName, $sub['code']);
            $stmt->execute();
            $stmt->close();
            echo "Subject {$sub['code']} ({$sub['name']}) updated.\n";
        }
    }

    // 8. Create real staff subject allocations in staff_subject_allocation
    $conn->query("DELETE FROM staff_subject_allocation WHERE subject_code IN ('40DI501', '40DI502', '40DI503')");
    
    $allocations = [
        ['staff_id' => $staffIds['dsa40'], 'code' => '40DI501'],
        ['staff_id' => $staffIds['dbms40'], 'code' => '40DI502'],
        ['staff_id' => $staffIds['cn40'], 'code' => '40DI503']
    ];
    
    foreach ($allocations as $alloc) {
        $stmt = $conn->prepare("INSERT INTO staff_subject_allocation (staff_id, subject_code, class_year) VALUES (?, ?, ?)");
        $stmt->bind_param('iss', $alloc['staff_id'], $alloc['code'], $classYear);
        $stmt->execute();
        $stmt->close();
        echo "Allocated {$alloc['code']} to staff_id {$alloc['staff_id']} for class_year {$classYear}.\n";
    }

    // 9. Open Mark Entry Window for Semester 5 for Batch 40DI
    // Delete any old entry windows for Semester 5 of batch 40DI
    $conn->query("DELETE FROM mark_entry_windows WHERE batch = '40DI' AND semester = 5");
    
    $windowTitle = '40DI Sem 5 Mark Entry Window';
    $startAt = '2026-07-01 00:00:00';
    $endAt = '2026-12-31 23:59:59';
    $stmt = $conn->prepare("INSERT INTO mark_entry_windows (title, department, semester, class_year, batch, section, subject_code, start_at, end_at, is_active, created_by) VALUES (?, ?, 5, ?, ?, ?, NULL, ?, ?, 1, ?)");
    $stmt->bind_param('sssssssi', $windowTitle, $deptName, $classYear, $batchName, $sectionName, $startAt, $endAt, $aUserId);
    $stmt->execute();
    $stmt->close();
    echo "Opened Mark Entry Window for batch 40DI semester 5.\n";

    // 10. Automatically populate historical marks for Sem 1-4
    // Subject codes for semesters 1-4 (6 subjects per semester)
    $histSubjects = [
        1 => [
            ['code' => 'DI101', 'type' => 'communication'],
            ['code' => 'DI102', 'type' => 'theory'],
            ['code' => 'DI103', 'type' => 'theory'],
            ['code' => 'DI104', 'type' => 'theory'],
            ['code' => 'DI105', 'type' => 'lab'],
            ['code' => 'DI106', 'type' => 'lab']
        ],
        2 => [
            ['code' => 'DI201', 'type' => 'communication'],
            ['code' => 'DI202', 'type' => 'theory'],
            ['code' => 'DI203', 'type' => 'theory'],
            ['code' => 'DI204', 'type' => 'theory'],
            ['code' => 'DI205', 'type' => 'lab'],
            ['code' => 'DI206', 'type' => 'lab']
        ],
        3 => [
            ['code' => 'DI301', 'type' => 'theory'],
            ['code' => 'DI302', 'type' => 'theory'],
            ['code' => 'DI303', 'type' => 'theory'],
            ['code' => 'DI304', 'type' => 'theory'],
            ['code' => 'DI305', 'type' => 'lab'],
            ['code' => 'DI306', 'type' => 'lab']
        ],
        4 => [
            ['code' => 'DI401', 'type' => 'theory'],
            ['code' => 'DI402', 'type' => 'theory'],
            ['code' => 'DI403', 'type' => 'theory'],
            ['code' => 'DI404', 'type' => 'theory'],
            ['code' => 'DI405', 'type' => 'lab'],
            ['code' => 'DI406', 'type' => 'lab']
        ]
    ];

    // Delete existing marks for these students first to avoid duplicates
    $studIdsList = implode(',', array_values($studentIds));
    $conn->query("DELETE FROM student_marks WHERE student_id IN ($studIdsList) AND semester BETWEEN 1 AND 4");
    echo "Cleared old student marks for semesters 1-4.\n";

    // Helper functions for mark generation
    function get_marks_and_grade(string $perfGroup, string $subType, bool $shouldFail = false): array {
        if ($shouldFail) {
            $grade = 'RA';
        } else {
            $grades = [
                'top' => ['O', 'A+'],
                'above_avg' => ['A+', 'A'],
                'avg' => ['A', 'B+', 'B'],
                'low' => ['B', 'C'],
                'arrear' => ['B', 'C']
            ];
            $pool = $grades[$perfGroup] ?? ['B'];
            $grade = $pool[array_rand($pool)];
        }
        
        $gp = match ($grade) {
            'O' => 10.0,
            'A+' => 9.0,
            'A' => 8.0,
            'B+' => 7.0,
            'B' => 6.0,
            'C' => 5.0,
            'RA' => 0.0,
        };
        
        $isArrear = ($grade === 'RA') ? 1 : 0;
        
        $ca1 = 0.0; $ca2 = 0.0; $ca3 = 0.0; $best_two = 0.0; $ca_converted_30 = 0.0; $assignment = 0.0; $theory_total = 0.0;
        $c1_exec = 0.0; $c1_test = 0.0; $c2_rec = 0.0; $c2_test = 0.0; $practical_total = 0.0;
        
        if ($subType === 'lab') {
            if ($grade === 'O') {
                $c1_exec = rand(90, 100) / 10;
                $c1_test = rand(90, 100) / 10;
                $c2_rec = rand(90, 100) / 10;
                $c2_test = rand(90, 100) / 10;
            } elseif ($grade === 'A+') {
                $c1_exec = rand(80, 90) / 10;
                $c1_test = rand(80, 90) / 10;
                $c2_rec = rand(80, 90) / 10;
                $c2_test = rand(80, 90) / 10;
            } elseif ($grade === 'A') {
                $c1_exec = rand(70, 80) / 10;
                $c1_test = rand(70, 80) / 10;
                $c2_rec = rand(70, 80) / 10;
                $c2_test = rand(70, 80) / 10;
            } elseif ($grade === 'B+') {
                $c1_exec = rand(60, 70) / 10;
                $c1_test = rand(60, 70) / 10;
                $c2_rec = rand(60, 70) / 10;
                $c2_test = rand(60, 70) / 10;
            } elseif ($grade === 'B') {
                $c1_exec = rand(55, 60) / 10;
                $c1_test = rand(55, 60) / 10;
                $c2_rec = rand(55, 60) / 10;
                $c2_test = rand(55, 60) / 10;
            } elseif ($grade === 'C') {
                $c1_exec = rand(50, 55) / 10;
                $c1_test = rand(50, 55) / 10;
                $c2_rec = rand(50, 55) / 10;
                $c2_test = rand(50, 55) / 10;
            } else { // RA
                $c1_exec = rand(20, 45) / 10;
                $c1_test = rand(20, 45) / 10;
                $c2_rec = rand(20, 45) / 10;
                $c2_test = rand(20, 45) / 10;
            }
            $practical_total = $c1_exec + $c1_test + $c2_rec + $c2_test;
        } else {
            // Theory or Communication
            if ($grade === 'O') {
                $ca1 = rand(45, 50); $ca2 = rand(45, 50); $ca3 = rand(45, 50);
                $assignment = rand(9, 10);
            } elseif ($grade === 'A+') {
                $ca1 = rand(40, 44); $ca2 = rand(40, 44); $ca3 = rand(40, 44);
                $assignment = rand(8, 9);
            } elseif ($grade === 'A') {
                $ca1 = rand(35, 39); $ca2 = rand(35, 39); $ca3 = rand(35, 39);
                $assignment = rand(7, 8);
            } elseif ($grade === 'B+') {
                $ca1 = rand(30, 34); $ca2 = rand(30, 34); $ca3 = rand(30, 34);
                $assignment = rand(6, 7);
            } elseif ($grade === 'B') {
                $ca1 = rand(25, 29); $ca2 = rand(25, 29); $ca3 = rand(25, 29);
                $assignment = rand(5, 6);
            } elseif ($grade === 'C') {
                $ca1 = rand(20, 24); $ca2 = rand(20, 24); $ca3 = rand(20, 24);
                $assignment = rand(5, 5);
            } else { // RA
                $ca1 = rand(10, 18); $ca2 = rand(10, 18); $ca3 = rand(10, 18);
                $assignment = rand(2, 4);
            }
            
            $cas = [$ca1, $ca2, $ca3];
            rsort($cas);
            $best_two = ($cas[0] + $cas[1]) / 2;
            $ca_converted_30 = round(($best_two / 50) * 30, 2);
            $theory_total = min(40.0, $ca_converted_30 + $assignment);
        }
        
        return [
            'grade' => $grade,
            'grade_point' => $gp,
            'is_arrear' => $isArrear,
            'ca1' => $ca1,
            'ca2' => $ca2,
            'ca3' => $ca3,
            'best_two' => $best_two,
            'ca_converted_30' => $ca_converted_30,
            'assignment' => $assignment,
            'theory_total' => $theory_total,
            'c1_exec' => $c1_exec,
            'c1_test' => $c1_test,
            'c2_rec' => $c2_rec,
            'c2_test' => $c2_test,
            'practical_total' => $practical_total
        ];
    }

    // Insert historical marks for each student for semesters 1-4
    $insertStmt = $conn->prepare("INSERT INTO student_marks
        (student_id, subject_id, subject_code, semester, academic_year, class_year, batch, section,
         ca1, ca2, ca3, best_two, ca_converted_30, assignment_mark, theory_total,
         cycle1_execution, cycle1_test, cycle2_record, cycle2_test, practical_total,
         semester_grade, grade_point, is_arrear, status, entered_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'locked', ?)");

    foreach ($studentsInfo as $sInfo) {
        $roll = $sInfo['roll'];
        $studId = $studentIds[$roll];
        $perfGroup = $sInfo['group'];
        
        for ($sem = 1; $sem <= 4; $sem++) {
            $ay = ($sem <= 2) ? '2023-2024' : '2024-2025';
            $cy = ($sem <= 2) ? '1' : '2';
            
            $subjects = $histSubjects[$sem];
            foreach ($subjects as $sub) {
                $subCode = $sub['code'];
                $subType = $sub['type'];
                $subIdCompat = marks_subject_compat_id($subCode);
                
                // Determine if this student fails this subject in this semester
                $shouldFail = false;
                if ($roll === '40DI24' && $sem === 2 && $subCode === 'DI202') {
                    $shouldFail = true;
                } elseif ($roll === '40DI25' && $sem === 3 && $subCode === 'DI302') {
                    $shouldFail = true;
                } elseif ($roll === '40DI25' && $sem === 4 && $subCode === 'DI402') {
                    $shouldFail = true;
                } elseif ($roll === '40DI26' && $sem === 3 && $subCode === 'DI304') {
                    $shouldFail = true;
                } elseif ($roll === '40DI27' && $sem === 1 && $subCode === 'DI102') {
                    $shouldFail = true;
                } elseif ($roll === '40DI27' && $sem === 4 && $subCode === 'DI404') {
                    $shouldFail = true;
                }
                
                $data = get_marks_and_grade($perfGroup, $subType, $shouldFail);
                
                $insertStmt->bind_param(
                    'iisissssddddddddddddsdii',
                    $studId,
                    $subIdCompat,
                    $subCode,
                    $sem,
                    $ay,
                    $cy,
                    $batchName,
                    $sectionName,
                    $data['ca1'],
                    $data['ca2'],
                    $data['ca3'],
                    $data['best_two'],
                    $data['ca_converted_30'],
                    $data['assignment'],
                    $data['theory_total'],
                    $data['c1_exec'],
                    $data['c1_test'],
                    $data['c2_rec'],
                    $data['c2_test'],
                    $data['practical_total'],
                    $data['grade'],
                    $data['grade_point'],
                    $data['is_arrear'],
                    $tutorStaffId
                );
                $insertStmt->execute();
            }
        }
    }
    $insertStmt->close();
    echo "Successfully generated and locked realistic historical marks for semesters 1-4.\n";

    $conn->commit();
    echo "==================================================\n";
    echo "DATABASE REPAIR & SEED COMPLETED SUCCESSFULLY!\n";
    echo "==================================================\n";
} catch (Exception $e) {
    $conn->rollback();
    die("DATABASE SEPAIR & SEED FAILED: " . $e->getMessage() . "\n");
}
