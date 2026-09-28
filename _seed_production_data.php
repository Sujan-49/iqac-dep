<?php
/**
 * PSG PTC ERP — Final Production Academic Dataset Seeder
 * ======================================================
 * Cleans out old demo data and inserts the official production academic structure:
 * - 2 Departments: DI (Diploma Information Technology), AI (Artificial Intelligence & Machine Learning)
 * - 2 Batches: 24DI, 24AI
 * - 120 Students: 24DI01-24DI60, 24AI01-24AI60
 * - Staff: Tutors, Teachers, HODs, IQAC, Principal, Super Admin
 * - Semester 1-4 Complete Historical Marks
 * - Semester 5 Open for Active Entry
 */

require_once __DIR__ . '/include/auth.php';

echo "=== STARTING PSG PTC ERP PRODUCTION DATA SEEDER ===\n\n";

// Disable foreign key checks for clean wiping
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

$tablesToClear = [
    'users', 'student_details', 'staff_details', 'subjects', 'student_marks',
    'semester_marks', 'ca_marks', 'staff_subject_allocation', 'subject_teachers',
    'departments', 'batches', 'sections', 'semester_management', 'notifications',
    'student_achievements', 'student_clubs', 'student_entrepreneurship',
    'student_higher_studies', 'student_internships', 'student_leaves',
    'student_publications', 'student_sports', 'student_subjects',
    'batch_subjects', 'academic_success_rate'
];

foreach ($tablesToClear as $table) {
    if (iqac_table_exists($conn, $table)) {
        $conn->query("TRUNCATE TABLE `$table`");
        echo "Truncated table: $table\n";
    }
}

$conn->query("SET FOREIGN_KEY_CHECKS = 1");
echo "\n--- All old dummy data cleared ---\n\n";

// Helper function to insert user
function createUser(mysqli $conn, string $username, string $password, string $role, string $dept = '', string $batch = '', int $sem = 5, string $sec = 'A'): int
{
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password_hash, role, department, batch, semester, section, is_active, first_login) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 0)");
    $stmt->bind_param('sssssis', $username, $hash, $role, $dept, $batch, $sem, $sec);
    $stmt->execute();
    $id = $conn->insert_id;
    $stmt->close();
    return $id;
}

// ── 1. Administrative Accounts ────────────────────────────────────────────────
echo "Creating Admin Accounts...\n";
$admin_uid     = createUser($conn, 'admin', 'admin123', 'super_admin', 'DI');
$principal_uid = createUser($conn, 'principal', 'principal123', 'principal', 'DI');
$iqac_uid      = createUser($conn, 'iqac', 'iqac123', 'iqac', 'DI');

// ── 2. HOD Accounts ───────────────────────────────────────────────────────────
echo "Creating HOD Accounts...\n";
$hod_di_uid = createUser($conn, 'hod_di', 'hod123', 'hod', 'DI');
$hod_ai_uid = createUser($conn, 'hod_ai', 'hod123', 'hod', 'AI');

// ── 3. Departments ────────────────────────────────────────────────────────────
echo "Creating Departments (DI, AI)...\n";
if (iqac_table_exists($conn, 'departments')) {
    $conn->query("INSERT INTO departments (id, department_code, department_name, hod_user_id) VALUES 
        (1, 'DI', 'Diploma Information Technology', $hod_di_uid),
        (2, 'AI', 'Artificial Intelligence & Machine Learning', $hod_ai_uid)");
}

// ── 4. Staff & Tutor Accounts (DI & AI) ───────────────────────────────────────
echo "Creating Staff & Tutors...\n";

// DI Staff
$tutor_di_uid = createUser($conn, 'tutor_di', 'staff123', 'tutor', 'DI', '24DI');
$wt_di_uid    = createUser($conn, 'wt_di', 'staff123', 'staff', 'DI');
$crypto_di_uid= createUser($conn, 'crypto_di', 'staff123', 'staff', 'DI');
$cloud_di_uid = createUser($conn, 'cloud_di', 'staff123', 'staff', 'DI');
$ai_di_uid    = createUser($conn, 'ai_di', 'staff123', 'staff', 'DI');
$lab_di_uid   = createUser($conn, 'lab_di', 'staff123', 'staff', 'DI');

// AI Staff
$tutor_ai_uid = createUser($conn, 'tutor_ai', 'staff123', 'tutor', 'AI', '24AI');
$python_ai_uid= createUser($conn, 'python_ai', 'staff123', 'staff', 'AI');
$ml_ai_uid    = createUser($conn, 'ml_ai', 'staff123', 'staff', 'AI');
$dl_ai_uid    = createUser($conn, 'dl_ai', 'staff123', 'staff', 'AI');
$cv_ai_uid    = createUser($conn, 'cv_ai', 'staff123', 'staff', 'AI');
$ailab_ai_uid = createUser($conn, 'ailab_ai', 'staff123', 'staff', 'AI');

// Insert Staff Details
$staffMap = [];

$conn->query("INSERT INTO staff_details (user_id, full_name, department, is_tutor) VALUES 
    ($hod_di_uid, 'Dr. K. Senthil Kumar (HOD - DI)', 'DI', 0),
    ($hod_ai_uid, 'Dr. M. Arunkumar (HOD - AI)', 'AI', 0)");

$conn->query("INSERT INTO staff_details (user_id, full_name, department, is_tutor) VALUES ($tutor_di_uid, 'Prof. R. Ramesh (Tutor DI)', 'DI', 1)");
$staffMap['tutor_di'] = $conn->insert_id;
$conn->query("INSERT INTO staff_details (user_id, full_name, department, is_tutor) VALUES ($wt_di_uid, 'Prof. A. Priya (Web Technology)', 'DI', 0)");
$staffMap['wt_di'] = $conn->insert_id;
$conn->query("INSERT INTO staff_details (user_id, full_name, department, is_tutor) VALUES ($crypto_di_uid, 'Prof. B. Vijay (Cryptography)', 'DI', 0)");
$staffMap['crypto_di'] = $conn->insert_id;
$conn->query("INSERT INTO staff_details (user_id, full_name, department, is_tutor) VALUES ($cloud_di_uid, 'Prof. C. Anand (Cloud Computing)', 'DI', 0)");
$staffMap['cloud_di'] = $conn->insert_id;
$conn->query("INSERT INTO staff_details (user_id, full_name, department, is_tutor) VALUES ($ai_di_uid, 'Prof. D. Divya (AI)', 'DI', 0)");
$staffMap['ai_di'] = $conn->insert_id;
$conn->query("INSERT INTO staff_details (user_id, full_name, department, is_tutor) VALUES ($lab_di_uid, 'Prof. E. Karthik (WT Lab)', 'DI', 0)");
$staffMap['lab_di'] = $conn->insert_id;

$conn->query("INSERT INTO staff_details (user_id, full_name, department, is_tutor) VALUES ($tutor_ai_uid, 'Prof. S. Suresh (Tutor AI)', 'AI', 1)");
$staffMap['tutor_ai'] = $conn->insert_id;
$conn->query("INSERT INTO staff_details (user_id, full_name, department, is_tutor) VALUES ($python_ai_uid, 'Prof. F. Kavitha (Python for AI)', 'AI', 0)");
$staffMap['python_ai'] = $conn->insert_id;
$conn->query("INSERT INTO staff_details (user_id, full_name, department, is_tutor) VALUES ($ml_ai_uid, 'Prof. G. Rajesh (Machine Learning)', 'AI', 0)");
$staffMap['ml_ai'] = $conn->insert_id;
$conn->query("INSERT INTO staff_details (user_id, full_name, department, is_tutor) VALUES ($dl_ai_uid, 'Prof. H. Deepika (Deep Learning)', 'AI', 0)");
$staffMap['dl_ai'] = $conn->insert_id;
$conn->query("INSERT INTO staff_details (user_id, full_name, department, is_tutor) VALUES ($cv_ai_uid, 'Prof. I. Manoj (Computer Vision)', 'AI', 0)");
$staffMap['cv_ai'] = $conn->insert_id;
$conn->query("INSERT INTO staff_details (user_id, full_name, department, is_tutor) VALUES ($ailab_ai_uid, 'Prof. J. Nithya (AI Lab)', 'AI', 0)");
$staffMap['ailab_ai'] = $conn->insert_id;

// Update associated_id in users
$conn->query("UPDATE users SET associated_id = {$staffMap['tutor_di']} WHERE id = $tutor_di_uid");
$conn->query("UPDATE users SET associated_id = {$staffMap['wt_di']} WHERE id = $wt_di_uid");
$conn->query("UPDATE users SET associated_id = {$staffMap['crypto_di']} WHERE id = $crypto_di_uid");
$conn->query("UPDATE users SET associated_id = {$staffMap['cloud_di']} WHERE id = $cloud_di_uid");
$conn->query("UPDATE users SET associated_id = {$staffMap['ai_di']} WHERE id = $ai_di_uid");
$conn->query("UPDATE users SET associated_id = {$staffMap['lab_di']} WHERE id = $lab_di_uid");

$conn->query("UPDATE users SET associated_id = {$staffMap['tutor_ai']} WHERE id = $tutor_ai_uid");
$conn->query("UPDATE users SET associated_id = {$staffMap['python_ai']} WHERE id = $python_ai_uid");
$conn->query("UPDATE users SET associated_id = {$staffMap['ml_ai']} WHERE id = $ml_ai_uid");
$conn->query("UPDATE users SET associated_id = {$staffMap['dl_ai']} WHERE id = $dl_ai_uid");
$conn->query("UPDATE users SET associated_id = {$staffMap['cv_ai']} WHERE id = $cv_ai_uid");
$conn->query("UPDATE users SET associated_id = {$staffMap['ailab_ai']} WHERE id = $ailab_ai_uid");

// ── 5. Batches & Sections ─────────────────────────────────────────────────────
echo "Creating Batches (24DI, 24AI)...\n";
if (iqac_table_exists($conn, 'batches')) {
    $conn->query("INSERT INTO batches (batch_id, batch_name, batch_prefix, year_start, department) VALUES 
        (1, '24DI', '24DI', 2024, 'DI'),
        (2, '24AI', '24AI', 2024, 'AI')");
}
if (iqac_table_exists($conn, 'sections')) {
    $conn->query("INSERT INTO sections (id, batch_id, section_name, tutor_user_id) VALUES 
        (1, 1, 'A', $tutor_di_uid),
        (2, 2, 'A', $tutor_ai_uid)");
}

// ── 6. Create Subjects ────────────────────────────────────────────────────────
echo "Creating Subjects...\n";

$subIdMap = [];
$subCounter = 1;

// Historical Subjects (Sem 1-4)
$histSubjects = [
    1 => [
        ['SUB101', 'Communication English I', 'theory'],
        ['SUB102', 'Engineering Mathematics I', 'theory'],
        ['SUB103', 'Engineering Physics', 'theory'],
        ['SUB104', 'Engineering Chemistry', 'theory'],
        ['SUB105', 'Fundamentals of Computing', 'lab'],
    ],
    2 => [
        ['SUB201', 'Communication English II', 'theory'],
        ['SUB202', 'Engineering Mathematics II', 'theory'],
        ['SUB203', 'Programming in C', 'theory'],
        ['SUB204', 'Digital Electronics', 'theory'],
        ['SUB205', 'C Programming Lab', 'lab'],
    ],
    3 => [
        ['SUB301', 'Data Structures', 'theory'],
        ['SUB302', 'Object Oriented Programming in C++', 'theory'],
        ['SUB303', 'Operating Systems', 'theory'],
        ['SUB304', 'Computer Architecture', 'theory'],
        ['SUB305', 'Data Structures Lab', 'lab'],
    ],
    4 => [
        ['SUB401', 'Java Programming', 'theory'],
        ['SUB402', 'Database Management Systems', 'theory'],
        ['SUB403', 'Software Engineering', 'theory'],
        ['SUB404', 'Computer Networks', 'theory'],
        ['SUB405', 'DBMS Lab', 'lab'],
    ],
];

foreach ($histSubjects as $sem => $subs) {
    $yr = (int)ceil($sem / 2);
    foreach ($subs as $s) {
        $conn->query("INSERT INTO subjects (subject_code, subject_name, subject_type, semester, year, department) 
            VALUES ('{$s[0]}', '{$s[1]}', '{$s[2]}', $sem, $yr, 'General')");
        $subIdMap[$s[0]] = $subCounter++;
    }
}

// Semester 5 DI Subjects
$di5Subjects = [
    ['DI501', 'Web Technology', 'theory', $wt_di_uid, $staffMap['wt_di']],
    ['DI502', 'Cryptography & Security', 'theory', $crypto_di_uid, $staffMap['crypto_di']],
    ['DI503', 'Cloud Computing', 'theory', $cloud_di_uid, $staffMap['cloud_di']],
    ['DI504', 'Artificial Intelligence', 'theory', $ai_di_uid, $staffMap['ai_di']],
    ['DI505', 'Web Technology Lab', 'lab', $lab_di_uid, $staffMap['lab_di']],
];

foreach ($di5Subjects as $s) {
    $conn->query("INSERT INTO subjects (subject_code, subject_name, subject_type, semester, year, department) 
        VALUES ('{$s[0]}', '{$s[1]}', '{$s[2]}', 5, 3, 'DI')");
    $subIdMap[$s[0]] = $subCounter++;
    
    // Allocate to teacher
    $conn->query("INSERT INTO staff_subject_allocation (staff_id, subject_code, class_year) VALUES ({$s[4]}, '{$s[0]}', '3rd Year')");
    if (iqac_table_exists($conn, 'subject_teachers')) {
        $conn->query("INSERT INTO subject_teachers (subject_id, teacher_user_id, department, semester, batch, section, assigned_by) 
            VALUES ({$subIdMap[$s[0]]}, {$s[3]}, 'DI', 5, '24DI', 'A', $admin_uid)");
    }
}

// Semester 5 AI Subjects
$ai5Subjects = [
    ['AI501', 'Python for AI', 'theory', $python_ai_uid, $staffMap['python_ai']],
    ['AI502', 'Machine Learning', 'theory', $ml_ai_uid, $staffMap['ml_ai']],
    ['AI503', 'Deep Learning', 'theory', $dl_ai_uid, $staffMap['dl_ai']],
    ['AI504', 'Computer Vision', 'theory', $cv_ai_uid, $staffMap['cv_ai']],
    ['AI505', 'AI Laboratory', 'lab', $ailab_ai_uid, $staffMap['ailab_ai']],
];

foreach ($ai5Subjects as $s) {
    $conn->query("INSERT INTO subjects (subject_code, subject_name, subject_type, semester, year, department) 
        VALUES ('{$s[0]}', '{$s[1]}', '{$s[2]}', 5, 3, 'AI')");
    $subIdMap[$s[0]] = $subCounter++;
    
    // Allocate to teacher
    $conn->query("INSERT INTO staff_subject_allocation (staff_id, subject_code, class_year) VALUES ({$s[4]}, '{$s[0]}', '3rd Year')");
    if (iqac_table_exists($conn, 'subject_teachers')) {
        $conn->query("INSERT INTO subject_teachers (subject_id, teacher_user_id, department, semester, batch, section, assigned_by) 
            VALUES ({$subIdMap[$s[0]]}, {$s[3]}, 'AI', 5, '24AI', 'A', $admin_uid)");
    }
}

// ── 7. Generate 120 Students & Profiles ───────────────────────────────────────
echo "Generating 120 Students (60 DI + 60 AI)...\n";

$firstNamesMale = ['Aravind', 'Bala', 'Chandran', 'Dinesh', 'Gokul', 'Karthik', 'Lokesh', 'Manoj', 'Naveen', 'Pradeep', 'Rahul', 'Santhosh', 'Vignesh', 'Vijay', 'Yogesh', 'Surya', 'Pravin', 'Siddharth', 'Vishal', 'Ashwin'];
$firstNamesFemale = ['Ananya', 'Bhavana', 'Divya', 'Harini', 'Kavya', 'Keerthana', 'Meena', 'Nivetha', 'Priya', 'Ramya', 'Sruthi', 'Swetha', 'Vaishnavi', 'Yamini', 'Abirami', 'Janani', 'Madhumitha', 'Pavithra', 'Subhashini', 'Vidyashree'];
$lastNames = ['Subramanian', 'Ramesh', 'Kumar', 'Senthil', 'Murugan', 'Natarajan', 'Balakrishnan', 'Venkatesan', 'Sundaram', 'Ganesan', 'Elango', 'Manoharan', 'Chellappa', 'Viswanathan'];
$bloodGroups = ['O+', 'A+', 'B+', 'AB+', 'O-', 'A-'];

function generateTamilName(int $idx): array {
    global $firstNamesMale, $firstNamesFemale, $lastNames;
    $isFemale = ($idx % 2 === 0);
    $first = $isFemale ? $firstNamesFemale[$idx % count($firstNamesFemale)] : $firstNamesMale[$idx % count($firstNamesMale)];
    $last = $lastNames[($idx * 3) % count($lastNames)];
    $gender = $isFemale ? 'Female' : 'Male';
    return ["$first $last", $gender];
}

$studentsList = [];

// Generate DI Students (24DI01 - 24DI60)
for ($i = 1; $i <= 60; $i++) {
    $rollNo = sprintf('24DI%02d', $i);
    [$fullName, $gender] = generateTamilName($i);
    $bg = $bloodGroups[$i % count($bloodGroups)];
    $phone = sprintf('9842%06d', 100000 + $i * 1234);
    $email = strtolower($rollNo) . '@psgpolytech.ac.in';
    $father = $lastNames[($i * 2) % count($lastNames)] . ' ' . $lastNames[($i + 1) % count($lastNames)];
    $mother = $firstNamesFemale[($i + 2) % count($firstNamesFemale)] . ' ' . $lastNames[$i % count($lastNames)];
    $parentPhone = sprintf('9443%06d', 200000 + $i * 5678);
    $addr = 'Peelamedu, Coimbatore - 641004';
    $dept = 'DI';
    $batch = '24DI';

    $uid = createUser($conn, $rollNo, $rollNo, 'student', $dept, $batch, 5, 'A');
    
    // Insert student details
    $tId = $staffMap['tutor_di'];
    $stmt = $conn->prepare("INSERT INTO student_details 
        (user_id, full_name, gender, blood_group, department, roll_number, class_year, batch, section, student_phone, email, father_name, mother_name, parent_phone, address_full, current_semester, tutor_staff_id, stage1_status) 
        VALUES (?, ?, ?, ?, ?, ?, '3rd Year', ?, 'A', ?, ?, ?, ?, ?, ?, 5, ?, 'approved')");
    $stmt->bind_param('issssssssssssi', $uid, $fullName, $gender, $bg, $dept, $rollNo, $batch, $phone, $email, $father, $mother, $parentPhone, $addr, $tId);
    $stmt->execute();
    $studentId = $conn->insert_id;
    $stmt->close();

    // Update associated_id in users
    $conn->query("UPDATE users SET associated_id = $studentId WHERE id = $uid");

    $studentsList[] = [
        'id' => $studentId,
        'user_id' => $uid,
        'roll_number' => $rollNo,
        'name' => $fullName,
        'department' => 'DI',
        'batch' => '24DI',
        'tutor_id' => $tId,
        'rank' => $i
    ];
}

// Generate AI Students (24AI01 - 24AI60)
for ($i = 1; $i <= 60; $i++) {
    $rollNo = sprintf('24AI%02d', $i);
    [$fullName, $gender] = generateTamilName($i + 60);
    $bg = $bloodGroups[($i + 2) % count($bloodGroups)];
    $phone = sprintf('9843%06d', 300000 + $i * 4321);
    $email = strtolower($rollNo) . '@psgpolytech.ac.in';
    $father = $lastNames[($i * 4) % count($lastNames)] . ' ' . $lastNames[($i + 2) % count($lastNames)];
    $mother = $firstNamesFemale[($i + 3) % count($firstNamesFemale)] . ' ' . $lastNames[($i + 1) % count($lastNames)];
    $parentPhone = sprintf('9442%06d', 400000 + $i * 8765);
    $addr = 'Avinashi Road, Coimbatore - 641004';
    $dept = 'AI';
    $batch = '24AI';

    $uid = createUser($conn, $rollNo, $rollNo, 'student', $dept, $batch, 5, 'A');
    
    // Insert student details
    $tId = $staffMap['tutor_ai'];
    $stmt = $conn->prepare("INSERT INTO student_details 
        (user_id, full_name, gender, blood_group, department, roll_number, class_year, batch, section, student_phone, email, father_name, mother_name, parent_phone, address_full, current_semester, tutor_staff_id, stage1_status) 
        VALUES (?, ?, ?, ?, ?, ?, '3rd Year', ?, 'A', ?, ?, ?, ?, ?, ?, 5, ?, 'approved')");
    $stmt->bind_param('issssssssssssi', $uid, $fullName, $gender, $bg, $dept, $rollNo, $batch, $phone, $email, $father, $mother, $parentPhone, $addr, $tId);
    $stmt->execute();
    $studentId = $conn->insert_id;
    $stmt->close();

    // Update associated_id in users
    $conn->query("UPDATE users SET associated_id = $studentId WHERE id = $uid");

    $studentsList[] = [
        'id' => $studentId,
        'user_id' => $uid,
        'roll_number' => $rollNo,
        'name' => $fullName,
        'department' => 'AI',
        'batch' => '24AI',
        'tutor_id' => $tId,
        'rank' => $i
    ];
}

// ── 8. Generate Complete Historical Marks (Semesters 1 - 4) ───────────────────
echo "Generating Historical Marks (Semesters 1 to 4) for 120 Students...\n";

function getGradeDetails(float $mark, bool $isArrear = false): array {
    if ($isArrear || $mark < 40) {
        return ['RA', 0.00, 1];
    }
    if ($mark >= 90) return ['O', 10.00, 0];
    if ($mark >= 80) return ['A+', 9.00, 0];
    if ($mark >= 70) return ['A', 8.00, 0];
    if ($mark >= 60) return ['B+', 7.00, 0];
    if ($mark >= 50) return ['B', 6.00, 0];
    return ['C', 5.00, 0];
}

$stmtMark = $conn->prepare("INSERT INTO student_marks 
    (student_id, subject_id, subject_code, semester, academic_year, class_year, batch, section, ca1, ca2, ca3, best_two, ca_converted_30, assignment_mark, theory_total, practical_total, semester_grade, grade_point, is_arrear, status, entered_by, submitted_at, verified_at, locked_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, 'A', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'locked', 1, NOW(), NOW(), NOW())");

$stmtSemMark = $conn->prepare("INSERT INTO semester_marks 
    (student_id, subject_code, marks_secured, semester_number, year_of_study, staff_id_entered) 
    VALUES (?, ?, ?, ?, ?, 1)");

foreach ($studentsList as $st) {
    $rank = $st['rank'];
    
    // Iterate Semesters 1 to 4
    for ($sem = 1; $sem <= 4; $sem++) {
        $ay = ($sem <= 2) ? '2024-2025' : '2025-2026';
        $classYr = ($sem <= 2) ? '1st Year' : '2nd Year';
        $studyYr = ($sem <= 2) ? 1 : 2;

        $subs = $histSubjects[$sem];
        foreach ($subs as $sIdx => $sub) {
            $subCode = $sub[0];
            $subType = $sub[2];
            $subId   = $subIdMap[$subCode] ?? 1;

            if ($rank <= 10) {
                $base = rand(90, 98);
            } elseif ($rank <= 25) {
                $base = rand(80, 89);
            } elseif ($rank <= 45) {
                $base = rand(66, 79);
            } elseif ($rank <= 55) {
                $base = rand(55, 65);
            } else {
                $base = rand(42, 54);
            }

            $isArrear = false;
            if ($rank >= 59 && $sem == 2 && $sIdx == 1) {
                $isArrear = true;
                $base = rand(25, 38);
            }

            [$grade, $gp, $arrFlag] = getGradeDetails((float)$base, $isArrear);

            $ca1 = round($base * 0.4, 2);
            $ca2 = round($base * 0.42, 2);
            $ca3 = round($base * 0.45, 2);
            $best2 = round(($ca2 + $ca3) / 2, 2);
            $ca30 = round(($best2 / 50) * 30, 2);
            $assign = round(rand(8, 10), 2);
            $tot = (float)$base;

            $theoryTot = ($subType === 'theory') ? $tot : 0.00;
            $pracTot   = ($subType === 'lab') ? $tot : 0.00;
            $intTot = (int)round($tot);

            $stmtMark->bind_param('iisisssddddddddsdi',
                $st['id'], $subId, $subCode, $sem, $ay, $classYr, $st['batch'],
                $ca1, $ca2, $ca3, $best2, $ca30, $assign, $theoryTot, $pracTot,
                $grade, $gp, $arrFlag
            );
            $stmtMark->execute();

            $stmtSemMark->bind_param('isiii', $st['id'], $subCode, $intTot, $sem, $studyYr);
            $stmtSemMark->execute();
        }
    }
}
$stmtMark->close();
$stmtSemMark->close();

// ── 9. Semester Management Setup ──────────────────────────────────────────────
echo "Setting Semester 5 as Open in semester_management...\n";
if (iqac_table_exists($conn, 'semester_management')) {
    $conn->query("INSERT INTO semester_management (semester, academic_year, status, opened_at, created_by) 
        VALUES (5, '2026-2027', 'open', NOW(), $admin_uid)");
}

// ── 10. Initial Notifications ──────────────────────────────────────────────────
echo "Creating Notifications...\n";
if (iqac_table_exists($conn, 'notifications')) {
    $conn->query("INSERT INTO notifications (user_id, role, title, message, link_url, is_read) VALUES 
        ($admin_uid, 'super_admin', 'System Initialized', 'PSG PTC ERP Academic Year 2026-2027 production dataset seeded successfully.', 'academic_erp.php', 0),
        ($iqac_uid, 'iqac', 'NBA System Ready', 'Academic data for DI & AI departments populated. Semester 5 is open for live mark entry.', 'nba_report.php', 0),
        ($hod_di_uid, 'hod', 'Semester 5 Started', 'Department of Diploma Information Technology Semester 5 is active.', 'nba_report.php', 0),
        ($hod_ai_uid, 'hod', 'Semester 5 Started', 'Department of Artificial Intelligence & Machine Learning Semester 5 is active.', 'nba_report.php', 0)");
}

echo "\n=== PRODUCTION SEEDING COMPLETED SUCCESSFULLY! ===\n";
echo "Total Students Created: 120 (60 DI + 60 AI)\n";
echo "Total Staff Accounts: 14 (HODs, Tutors, Teachers)\n";
echo "Historical Marks (Sem 1-4): 2,400 mark entries generated\n";
echo "Semester 5: Open & Ready for Live Entry\n";
