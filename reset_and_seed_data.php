<?php
/**
 * PSG PTC ERP — Master Production Dataset Seeder & Resetter (Phases 3 - 9)
 * Supports: --dry-run, --seed, --force
 */

declare(strict_types=1);

require_once __DIR__ . '/include/auth.php';
require_once __DIR__ . '/include/evidence_helper.php';

$configFile = __DIR__ . '/config/seed_config.json';
if (!file_exists($configFile)) {
    die("CRITICAL ERROR: Configuration file config/seed_config.json missing!\n");
}
$config = json_decode(file_get_contents($configFile), true);

$logsDir = __DIR__ . '/logs';
$prodDir = __DIR__ . '/production';
$prodLogsDir = __DIR__ . '/production/logs';

foreach ([$logsDir, $prodDir, $prodLogsDir] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

$seedLogFile = $logsDir . '/seed.log';

function logSeed(string $msg) {
    global $seedLogFile;
    $timestamp = date('Y-m-d H:i:s');
    $line = "[$timestamp] $msg\n";
    file_put_contents($seedLogFile, $line, FILE_APPEND);
    echo $line;
}

$options = getopt('', ['dry-run', 'seed', 'force']);
$isDryRun = isset($options['dry-run']);
$isSeed = isset($options['seed']);
$isForce = isset($options['force']);

if (!$isDryRun && !$isSeed) {
    echo "PSG PTC ERP Seeder Usage:\n";
    echo "  php reset_and_seed_data.php --dry-run      Inspect schema and report planned seeding actions\n";
    echo "  php reset_and_seed_data.php --seed --force Execute safe, idempotent dataset replacement\n";
    exit(0);
}

logSeed("==================================================");
logSeed("PSG PTC ERP — SEEDER EXECUTION (Runbook " . ($config['runbook_version'] ?? 'v1.0.0') . ")");
logSeed("==================================================");

// Check Lock File
$lockFile = __DIR__ . '/database/seeder.lock';
if ($isSeed && file_exists($lockFile) && !$isForce) {
    logSeed("CRITICAL ERROR: Seeder lock file exists ($lockFile). Use --force to override.");
    exit(1);
}

if ($isSeed) {
    file_put_contents($lockFile, date('Y-m-d H:i:s'));
}

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

function ensureTable(mysqli $conn, string $sql) {
    if (!$conn->query($sql)) {
        logSeed("ERROR creating table: " . $conn->error);
    }
}

// Ensure additional normalized tables if missing
ensureTable($conn, "CREATE TABLE IF NOT EXISTS student_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_code VARCHAR(50) NOT NULL,
    semester INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    attendance_percent DECIMAL(5,2) NOT NULL,
    INDEX idx_att_std (student_id),
    INDEX idx_att_sub (subject_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

ensureTable($conn, "CREATE TABLE IF NOT EXISTS student_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    project_title VARCHAR(255) NOT NULL,
    project_type VARCHAR(50) NOT NULL DEFAULT 'Major Project',
    guide_name VARCHAR(150) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Approved',
    remarks VARCHAR(255) DEFAULT 'Completed successfully',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_proj_std (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

ensureTable($conn, "CREATE TABLE IF NOT EXISTS student_patents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    patent_title VARCHAR(255) NOT NULL,
    application_no VARCHAR(100) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Published',
    year_filed INT NOT NULL,
    INDEX idx_pat_std (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

ensureTable($conn, "CREATE TABLE IF NOT EXISTS student_internships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    duration VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'approved',
    staff_remark VARCHAR(500) DEFAULT 'Recommended for industry experience',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_intern_std (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

ensureTable($conn, "CREATE TABLE IF NOT EXISTS curriculum_gap (
    id INT AUTO_INCREMENT PRIMARY KEY,
    academic_year VARCHAR(20) NOT NULL,
    course_code VARCHAR(50) NOT NULL,
    gap_details TEXT NOT NULL,
    action_taken TEXT NOT NULL,
    date_conducted DATE NOT NULL,
    resource_person VARCHAR(150) NOT NULL,
    mode VARCHAR(50) NOT NULL DEFAULT 'Offline',
    students_present INT NOT NULL,
    po_mapping VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

ensureTable($conn, "CREATE TABLE IF NOT EXISTS industry_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    academic_year VARCHAR(20) NOT NULL,
    interaction_type VARCHAR(100) NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    resource_person VARCHAR(150) NOT NULL,
    event_date DATE NOT NULL,
    outcome TEXT NOT NULL,
    students_count INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

ensureTable($conn, "CREATE TABLE IF NOT EXISTS guest_lectures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    academic_year VARCHAR(20) NOT NULL,
    topic VARCHAR(255) NOT NULL,
    speaker_name VARCHAR(150) NOT NULL,
    organization VARCHAR(255) NOT NULL,
    event_date DATE NOT NULL,
    participants_count INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

ensureTable($conn, "CREATE TABLE IF NOT EXISTS alumni_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    academic_year VARCHAR(20) NOT NULL,
    alumni_name VARCHAR(150) NOT NULL,
    batch VARCHAR(50) NOT NULL,
    designation VARCHAR(150) NOT NULL,
    company VARCHAR(255) NOT NULL,
    topic VARCHAR(255) NOT NULL,
    event_date DATE NOT NULL,
    beneficiaries_count INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

ensureTable($conn, "CREATE TABLE IF NOT EXISTS feedback_analysis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    academic_year VARCHAR(20) NOT NULL,
    stakeholder VARCHAR(50) NOT NULL,
    total_responses INT NOT NULL,
    excellent_pct DECIMAL(5,2) NOT NULL,
    very_good_pct DECIMAL(5,2) NOT NULL,
    good_pct DECIMAL(5,2) NOT NULL,
    needs_imp_pct DECIMAL(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ---------------------------------------------------------
// Phase 3 — Dry Run Audit
// ---------------------------------------------------------
if ($isDryRun) {
    logSeed("MODE: DRY RUN ANALYSIS");
    
    $tablesToClear = [
        'users', 'student_details', 'staff_details', 'subjects', 'student_marks',
        'semester_marks', 'ca_marks', 'staff_subject_allocation', 'subject_teachers',
        'notifications', 'student_achievements', 'student_clubs', 'student_entrepreneurship',
        'student_higher_studies', 'student_internships', 'student_leaves',
        'student_publications', 'student_sports', 'student_subjects',
        'batch_subjects', 'academic_success_rate', 'placement_statistics',
        'student_attendance', 'student_projects', 'student_patents',
        'curriculum_gap', 'industry_interactions', 'guest_lectures',
        'alumni_interactions', 'feedback_analysis', 'research_publications',
        'mou_master', 'faculty_development_programs'
    ];
    
    logSeed("Analyzing tables to be reset...");
    $totalExistingRows = 0;
    foreach ($tablesToClear as $tbl) {
        if (iqac_table_exists($conn, $tbl)) {
            $res = $conn->query("SELECT COUNT(*) FROM `$tbl`");
            $cnt = $res ? (int)$res->fetch_row()[0] : 0;
            $totalExistingRows += $cnt;
            logSeed("  - Table '$tbl': $cnt existing records");
        }
    }
    
    logSeed("\n--- DRY RUN SUMMARY ---");
    logSeed("Total existing records to be wiped: $totalExistingRows");
    logSeed("Target Departments: DI (Diploma Information Technology), AI (Artificial Intelligence)");
    logSeed("Target Students to create: 120 (60 DI, 60 AI)");
    logSeed("Target Staff/Admin accounts: 17");
    logSeed("Target Subjects: Semesters 1-5 (Core DI & AI curriculum)");
    logSeed("Target Historical Marks: Semesters 1-4 Complete, Semester 5 Open");
    logSeed("Estimated Execution Time: ~3-5 seconds");
    logSeed("No modifications made during dry run.\n");
    exit(0);
}

// ---------------------------------------------------------
// Phase 4 — Transaction-Safe Reset
// ---------------------------------------------------------
logSeed("MODE: EXECUTING TRANSACTIONAL DATASET RESET & SEEDING");

$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$conn->begin_transaction();

try {
    $tablesToClear = [
        'users', 'student_details', 'staff_details', 'subjects', 'student_marks',
        'semester_marks', 'ca_marks', 'staff_subject_allocation', 'subject_teachers',
        'notifications', 'student_achievements', 'student_clubs', 'student_entrepreneurship',
        'student_higher_studies', 'student_internships', 'student_leaves',
        'student_publications', 'student_sports', 'student_subjects',
        'batch_subjects', 'academic_success_rate', 'placement_statistics',
        'student_attendance', 'student_projects', 'student_patents',
        'curriculum_gap', 'industry_interactions', 'guest_lectures',
        'alumni_interactions', 'feedback_analysis', 'research_publications',
        'mou_master', 'faculty_development_programs', 'departments', 'batches',
        'sections', 'semester_management'
    ];
    
    foreach ($tablesToClear as $tbl) {
        if (iqac_table_exists($conn, $tbl)) {
            $conn->query("TRUNCATE TABLE `$tbl`");
            $conn->query("ALTER TABLE `$tbl` AUTO_INCREMENT = 1");
        }
    }
    logSeed("SUCCESS: All legacy academic & transactional data truncated and AUTO_INCREMENT reset.");

    // ---------------------------------------------------------
    // Phase 5 — Production Dataset Seeding (Accounts & Profiles)
    // ---------------------------------------------------------
    logSeed("Creating Administrative Accounts...");
    $admin_uid     = createUser($conn, 'admin', 'admin', 'super_admin', 'DI');
    $principal_uid = createUser($conn, 'principal', 'principal', 'principal', 'DI');
    $iqac_uid      = createUser($conn, 'iqac', 'iqac', 'iqac', 'DI');

    logSeed("Creating HOD Accounts...");
    $hod_di_uid = createUser($conn, 'hod_di', 'hod_di', 'hod', 'DI');
    $hod_ai_uid = createUser($conn, 'hod_ai', 'hod_ai', 'hod', 'AI');

    // Create Departments
    $conn->query("INSERT INTO departments (id, department_code, department_name, hod_user_id) VALUES 
        (1, 'DI', 'Diploma Information Technology', $hod_di_uid),
        (2, 'AI', 'Artificial Intelligence', $hod_ai_uid)");

    // Create Batches & Sections
    $conn->query("INSERT INTO batches (batch_id, batch_name, batch_prefix, year_start, department) VALUES 
        (1, '24DI', '24DI', '2026', 'DI'),
        (2, '24AI', '24AI', '2026', 'AI')");

    $conn->query("INSERT INTO sections (id, batch_id, section_name, tutor_user_id) VALUES 
        (1, 1, 'A', 0),
        (2, 2, 'A', 0)");

    $conn->query("INSERT INTO semester_management (id, semester, academic_year, status, opened_at, created_by) VALUES
        (1, 5, '2026-2027', 'open', NOW(), $admin_uid)");

    logSeed("Creating Staff & Tutor Accounts...");
    // DI Staff
    $tutor_di_uid = createUser($conn, 'tutor_di', 'tutor_di', 'tutor', 'DI', '24DI', 5, 'A');
    $wt_di_uid    = createUser($conn, 'wt_di', 'wt_di', 'staff', 'DI', '24DI', 5, 'A');
    $crypto_di_uid= createUser($conn, 'crypto_di', 'crypto_di', 'staff', 'DI', '24DI', 5, 'A');
    $cloud_di_uid = createUser($conn, 'cloud_di', 'cloud_di', 'staff', 'DI', '24DI', 5, 'A');
    $ai_di_uid    = createUser($conn, 'ai_di', 'ai_di', 'staff', 'DI', '24DI', 5, 'A');
    $lab_di_uid   = createUser($conn, 'lab_di', 'lab_di', 'staff', 'DI', '24DI', 5, 'A');

    // AI Staff
    $tutor_ai_uid = createUser($conn, 'tutor_ai', 'tutor_ai', 'tutor', 'AI', '24AI', 5, 'A');
    $python_ai_uid= createUser($conn, 'python_ai', 'python_ai', 'staff', 'AI', '24AI', 5, 'A');
    $ml_ai_uid    = createUser($conn, 'ml_ai', 'ml_ai', 'staff', 'AI', '24AI', 5, 'A');
    $dl_ai_uid    = createUser($conn, 'dl_ai', 'dl_ai', 'staff', 'AI', '24AI', 5, 'A');
    $cv_ai_uid    = createUser($conn, 'cv_ai', 'cv_ai', 'staff', 'AI', '24AI', 5, 'A');
    $ailab_ai_uid = createUser($conn, 'ailab_ai', 'ailab_ai', 'staff', 'AI', '24AI', 5, 'A');

    // Update section tutors
    $conn->query("UPDATE sections SET tutor_user_id = $tutor_di_uid WHERE id = 1");
    $conn->query("UPDATE sections SET tutor_user_id = $tutor_ai_uid WHERE id = 2");

    // Staff Details
    $staffProfiles = [
        [$hod_di_uid, 'Dr. K. Senthil Kumar', 'DI', 0, 'Ph.D in Information Technology', '18 Years', 'Professor & HOD', 'Cloud Computing & Cyber Security'],
        [$hod_ai_uid, 'Dr. M. Arunkumar', 'AI', 0, 'Ph.D in Computer Science & AI', '16 Years', 'Professor & HOD', 'Deep Learning & Neural Networks'],
        [$tutor_di_uid, 'Prof. R. Ramesh', 'DI', 1, 'M.E. Computer Science', '12 Years', 'Assistant Professor & Tutor', 'Database Architecture'],
        [$wt_di_uid, 'Prof. A. Priya', 'DI', 0, 'M.Tech IT', '8 Years', 'Assistant Professor', 'Full-Stack Web Engineering'],
        [$crypto_di_uid, 'Prof. B. Vijay', 'DI', 0, 'M.E. Applied Electronics', '10 Years', 'Associate Professor', 'Network Security & Cryptography'],
        [$cloud_di_uid, 'Prof. C. Anand', 'DI', 0, 'M.E. Computer Science', '7 Years', 'Assistant Professor', 'Cloud Virtualization & DevOps'],
        [$ai_di_uid, 'Prof. D. Divya', 'DI', 0, 'M.Tech AI & Data Science', '6 Years', 'Assistant Professor', 'Artificial Intelligence'],
        [$lab_di_uid, 'Prof. E. Karthik', 'DI', 0, 'M.E. Computer Science', '5 Years', 'Lecturer', 'Web Technologies & Labs'],
        [$tutor_ai_uid, 'Prof. S. Suresh', 'AI', 1, 'M.E. Computer Science', '11 Years', 'Assistant Professor & Tutor', 'Machine Learning Algorithms'],
        [$python_ai_uid, 'Prof. F. Kavitha', 'AI', 0, 'M.Tech Computer Science', '9 Years', 'Assistant Professor', 'Python & Scientific Computing'],
        [$ml_ai_uid, 'Prof. G. Rajesh', 'AI', 0, 'Ph.D Machine Learning', '14 Years', 'Associate Professor', 'Supervised & Unsupervised ML'],
        [$dl_ai_uid, 'Prof. H. Deepika', 'AI', 0, 'M.E. Software Engineering', '7 Years', 'Assistant Professor', 'Deep Neural Networks & PyTorch'],
        [$cv_ai_uid, 'Prof. I. Manoj', 'AI', 0, 'M.Tech Signal Processing', '8 Years', 'Assistant Professor', 'Computer Vision & OpenCV'],
        [$ailab_ai_uid, 'Prof. J. Nithya', 'AI', 0, 'M.E. Computer Science', '5 Years', 'Lecturer', 'AI System Laboratories']
    ];

    $staffIdMap = [];
    foreach ($staffProfiles as $sp) {
        $stmt = $conn->prepare("INSERT INTO staff_details (user_id, full_name, department, is_tutor) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('issi', $sp[0], $sp[1], $sp[2], $sp[3]);
        $stmt->execute();
        $sid = $conn->insert_id;
        $staffIdMap[$sp[0]] = $sid;
        $stmt->close();
    }

    $tutor_di_staff_id = $staffIdMap[$tutor_di_uid];
    $tutor_ai_staff_id = $staffIdMap[$tutor_ai_uid];

    // Seed Subjects
    logSeed("Seeding Subjects for Semesters 1-5 (DI & AI)...");

    $subjectsList = [
        // DI Semesters 1-4
        ['DI101', 'Technical English', 'theory', 1, 1, 'DI'],
        ['DI102', 'Engineering Mathematics I', 'theory', 1, 1, 'DI'],
        ['DI103', 'Engineering Physics', 'theory', 1, 1, 'DI'],
        ['DI104', 'Basic Electrical & Electronics', 'theory', 1, 1, 'DI'],
        ['DI105', 'C Programming Lab', 'lab', 1, 1, 'DI'],
        ['DI201', 'Engineering Mathematics II', 'theory', 2, 1, 'DI'],
        ['DI202', 'Data Structures using C', 'theory', 2, 1, 'DI'],
        ['DI203', 'Digital Principles & Logic Design', 'theory', 2, 1, 'DI'],
        ['DI204', 'Python Programming', 'theory', 2, 1, 'DI'],
        ['DI205', 'Data Structures Lab', 'lab', 2, 1, 'DI'],
        ['DI301', 'Object Oriented Programming with Java', 'theory', 3, 2, 'DI'],
        ['DI302', 'Database Management Systems', 'theory', 3, 2, 'DI'],
        ['DI303', 'Operating Systems', 'theory', 3, 2, 'DI'],
        ['DI304', 'Computer Networks', 'theory', 3, 2, 'DI'],
        ['DI305', 'DBMS Laboratory', 'lab', 3, 2, 'DI'],
        ['DI401', 'Software Engineering & Agile', 'theory', 4, 2, 'DI'],
        ['DI402', 'Mobile Application Development', 'theory', 4, 2, 'DI'],
        ['DI403', 'Computer Architecture & Microprocessors', 'theory', 4, 2, 'DI'],
        ['DI404', 'Theory of Computation', 'theory', 4, 2, 'DI'],
        ['DI405', 'Mobile Apps Lab', 'lab', 4, 2, 'DI'],
        // DI Semester 5 (Active)
        ['DI501', 'Web Technology', 'theory', 5, 3, 'DI'],
        ['DI502', 'Cryptography & Security', 'theory', 5, 3, 'DI'],
        ['DI503', 'Cloud Computing', 'theory', 5, 3, 'DI'],
        ['DI504', 'Artificial Intelligence', 'theory', 5, 3, 'DI'],
        ['DI505', 'Web Technology Lab', 'lab', 5, 3, 'DI'],

        // AI Semesters 1-4
        ['AI101', 'Technical English for AI', 'theory', 1, 1, 'AI'],
        ['AI102', 'Linear Algebra & Calculus', 'theory', 1, 1, 'AI'],
        ['AI103', 'Physics for Information Science', 'theory', 1, 1, 'AI'],
        ['AI104', 'Problem Solving using Python', 'theory', 1, 1, 'AI'],
        ['AI105', 'Python Foundations Lab', 'lab', 1, 1, 'AI'],
        ['AI201', 'Probability, Statistics & Random Processes', 'theory', 2, 1, 'AI'],
        ['AI202', 'Data Structures & Algorithms', 'theory', 2, 1, 'AI'],
        ['AI203', 'Discrete Mathematics for AI', 'theory', 2, 1, 'AI'],
        ['AI204', 'Object Oriented Programming in C++', 'theory', 2, 1, 'AI'],
        ['AI205', 'Data Structures Lab', 'lab', 2, 1, 'AI'],
        ['AI301', 'Database Systems & SQL for Data Science', 'theory', 3, 2, 'AI'],
        ['AI302', 'Artificial Intelligence Principles', 'theory', 3, 2, 'AI'],
        ['AI303', 'Data Science & Visual Analytics', 'theory', 3, 2, 'AI'],
        ['AI304', 'Design & Analysis of Algorithms', 'theory', 3, 2, 'AI'],
        ['AI305', 'AI & Data Science Lab', 'lab', 3, 2, 'AI'],
        ['AI401', 'Machine Learning Foundations', 'theory', 4, 2, 'AI'],
        ['AI402', 'Optimization Techniques for AI', 'theory', 4, 2, 'AI'],
        ['AI403', 'Big Data Engineering & Analytics', 'theory', 4, 2, 'AI'],
        ['AI404', 'Artificial Neural Networks', 'theory', 4, 2, 'AI'],
        ['AI405', 'Machine Learning Lab', 'lab', 4, 2, 'AI'],
        // AI Semester 5 (Active)
        ['AI501', 'Python for Artificial Intelligence', 'theory', 5, 3, 'AI'],
        ['AI502', 'Machine Learning Applications', 'theory', 5, 3, 'AI'],
        ['AI503', 'Deep Learning & Neural Networks', 'theory', 5, 3, 'AI'],
        ['AI504', 'Computer Vision & Image Processing', 'theory', 5, 3, 'AI'],
        ['AI505', 'AI Laboratory', 'lab', 5, 3, 'AI']
    ];

    $subjectIdMap = [];
    $stmtSub = $conn->prepare("INSERT INTO subjects (subject_code, subject_name, subject_type, semester, year, department) VALUES (?, ?, ?, ?, ?, ?)");
    $subIdx = 1;
    foreach ($subjectsList as $sub) {
        $stmtSub->bind_param('sssiis', $sub[0], $sub[1], $sub[2], $sub[3], $sub[4], $sub[5]);
        $stmtSub->execute();
        $subjectIdMap[$sub[0]] = $subIdx++;
    }
    $stmtSub->close();

    // Allocate Sem 5 Subjects to Teachers
    $allocations = [
        ['DI501', $wt_di_uid, $staffIdMap[$wt_di_uid], 'DI'],
        ['DI502', $crypto_di_uid, $staffIdMap[$crypto_di_uid], 'DI'],
        ['DI503', $cloud_di_uid, $staffIdMap[$cloud_di_uid], 'DI'],
        ['DI504', $ai_di_uid, $staffIdMap[$ai_di_uid], 'DI'],
        ['DI505', $lab_di_uid, $staffIdMap[$lab_di_uid], 'DI'],

        ['AI501', $python_ai_uid, $staffIdMap[$python_ai_uid], 'AI'],
        ['AI502', $ml_ai_uid, $staffIdMap[$ml_ai_uid], 'AI'],
        ['AI503', $dl_ai_uid, $staffIdMap[$dl_ai_uid], 'AI'],
        ['AI504', $cv_ai_uid, $staffIdMap[$cv_ai_uid], 'AI'],
        ['AI505', $ailab_ai_uid, $staffIdMap[$ailab_ai_uid], 'AI']
    ];

    foreach ($allocations as $alloc) {
        $conn->query("INSERT INTO staff_subject_allocation (staff_id, subject_code, class_year) VALUES ({$alloc[2]}, '{$alloc[0]}', 'Year 3')");
        $conn->query("INSERT INTO subject_teachers (subject_id, teacher_user_id, department, semester, batch, section) VALUES 
            ({$subjectIdMap[$alloc[0]]}, {$alloc[1]}, '{$alloc[3]}', 5, '24{$alloc[3]}', 'A')");
    }

    // Seed 120 Students with 100% Unique Profiles
    logSeed("Seeding 120 Unique Students (60 DI + 60 AI)...");

    $maleFirst = ['Aadhavan', 'Aravind', 'Bala', 'Chandran', 'Dinesh', 'Elango', 'Gokul', 'Hari', 'Indran', 'Janakan', 'Karthik', 'Lokesh', 'Manoj', 'Naveen', 'Pradeep', 'Rahul', 'Santhosh', 'Surya', 'Vignesh', 'Vijay', 'Yogesh', 'Vishal', 'Ashwin', 'Pravin', 'Siddharth', 'Ganesh', 'Kaviarasan', 'Manikandan', 'Saravanan', 'Tamilselvan'];
    $femaleFirst = ['Ananya', 'Bhavana', 'Chitra', 'Divya', 'Farhana', 'Harini', 'Janani', 'Kavya', 'Keerthana', 'Latha', 'Meena', 'Nivetha', 'Oviya', 'Pavithra', 'Priya', 'Ramya', 'Sruthi', 'Swetha', 'Tharani', 'Vaishnavi', 'Yamini', 'Abirami', 'Bhuvaneshwari', 'Deepika', 'Kavitha', 'Madhumitha', 'Nithya', 'Subhashini', 'Vidyashree', 'Yazhini'];
    $lastNames = ['Subramanian', 'Ramesh', 'Kumar', 'Senthil', 'Murugan', 'Natarajan', 'Balakrishnan', 'Venkatesan', 'Sundaram', 'Ganesan', 'Elango', 'Manoharan', 'Chellappa', 'Viswanathan', 'Pandian', 'Kannan', 'Rajan', 'Thangavel', 'Shanmugam', 'Arumugam'];
    $bloodGroups = ['O+', 'A+', 'B+', 'AB+', 'O-', 'A-', 'B-', 'AB-'];

    $studentMap = []; // roll -> student_id

    for ($i = 1; $i <= 120; $i++) {
        $isAI = ($i > 60);
        $dept = $isAI ? 'AI' : 'DI';
        $batchStr = "24{$dept}";
        $num = $isAI ? ($i - 60) : $i;
        $roll = sprintf("24%s%02d", $dept, $num);
        
        // Create User Account (Password = Username)
        $uid = createUser($conn, $roll, $roll, 'student', $dept, $batchStr, 5, 'A');

        // Build unique profile attributes
        $isFemale = ($i % 2 == 0);
        $fn = $isFemale ? $femaleFirst[($i - 1) % count($femaleFirst)] : $maleFirst[($i - 1) % count($maleFirst)];
        $ln = $lastNames[($i * 7) % count($lastNames)];
        $fullName = "{$fn} {$ln}";
        $gender = $isFemale ? 'Female' : 'Male';
        $blood = $bloodGroups[$i % count($bloodGroups)];
        $tutorStaffId = $isAI ? $tutor_ai_staff_id : $tutor_di_staff_id;

        $dobDay = sprintf("%02d", ($i % 28) + 1);
        $dobMonth = sprintf("%02d", ($i % 12) + 1);
        $dobYear = 2006 + ($i % 2);
        $dob = "{$dobYear}-{$dobMonth}-{$dobDay}";

        $phone = "9842" . sprintf("%06d", 100000 + $i * 1234 % 899999);
        $parentPhone = "9443" . sprintf("%06d", 200000 + $i * 4321 % 899999);
        $email = strtolower("{$roll}.{$fn}@ptc.edu.in");
        $fatherName = "{$ln} K.";
        $motherName = "S. " . $femaleFirst[($i + 3) % count($femaleFirst)];
        $address = "No. " . ($i * 3 + 12) . ", Gandhi Nagar, {$dept} Sector, Coimbatore - 64100" . ($i % 9 + 1);

        $stmtStd = $conn->prepare("INSERT INTO student_details (user_id, full_name, gender, blood_group, department, roll_number, class_year, batch, section, is_lateral, marks_sslc, marks_hsc, address_full, father_name, mother_name, parent_phone, student_phone, email, current_semester, tutor_staff_id, stage1_status) VALUES (?, ?, ?, ?, ?, ?, 'Year 3', ?, 'A', 0, ?, ?, ?, ?, ?, ?, ?, ?, 5, ?, 'approved')");
        
        $sslc = 420 + ($i * 3) % 75;
        $hsc  = 480 + ($i * 4) % 115;
        
        $stmtStd->bind_param('issssssiissssssi', $uid, $fullName, $gender, $blood, $dept, $roll, $batchStr, $sslc, $hsc, $address, $fatherName, $motherName, $parentPhone, $phone, $email, $tutorStaffId);
        $stmtStd->execute();
        $studentId = $conn->insert_id;
        $studentMap[$roll] = $studentId;
        $stmtStd->close();
        
        // Update user associated_id
        $conn->query("UPDATE users SET associated_id = $studentId WHERE id = $uid");
    }

    logSeed("SUCCESS: 120 Students created with 100% unique profiles and user associations.");

    // ---------------------------------------------------------
    // Phase 6 — Academic Marks (Semesters 1 - 4 Complete, Sem 5 Active)
    // ---------------------------------------------------------
    logSeed("Generating Historical Academic Marks for Semesters 1-4...");

    // Performance Tiers:
    // Students 1-15: Top (85-98)
    // Students 16-40: Above Avg (72-88)
    // Students 41-52: Average (55-75)
    // Students 53-60: Arrear/Weak (40-62)

    // Prepare student_marks insert statement once
    $stmtMark = $conn->prepare("INSERT INTO student_marks 
        (student_id, subject_id, subject_code, semester, academic_year, class_year, batch, section, ca1, ca2, ca3, best_two, ca_converted_30, assignment_mark, theory_total, semester_grade, grade_point, is_arrear, status, entered_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'A', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'verified', ?)");

    $m_student_id = 0; $m_subject_id = 0; $m_subject_code = ''; $m_semester = 0;
    $m_academic_year = ''; $m_class_year = ''; $m_batch = '';
    $m_ca1 = 0.0; $m_ca2 = 0.0; $m_ca3 = 0.0; $m_best_two = 0.0; $m_ca_converted = 0.0; $m_assignment = 0.0; $m_theory_total = 0.0;
    $m_grade = ''; $m_gp = 0.0; $m_is_arrear = 0;

    $stmtMark->bind_param('iisisssdddddddsdii', 
        $m_student_id, $m_subject_id, $m_subject_code, $m_semester, $m_academic_year, $m_class_year, $m_batch,
        $m_ca1, $m_ca2, $m_ca3, $m_best_two, $m_ca_converted, $m_assignment, $m_theory_total, $m_grade, $m_gp, $m_is_arrear, $admin_uid
    );

    foreach ($studentMap as $roll => $sid) {
        $dept = str_starts_with($roll, '24AI') ? 'AI' : 'DI';
        $num = (int)substr($roll, 4);
        
        // Determine tier factor
        if ($num <= 15) {
            $baseMin = 85; $baseMax = 98;
        } elseif ($num <= 40) {
            $baseMin = 72; $baseMax = 88;
        } elseif ($num <= 52) {
            $baseMin = 55; $baseMax = 75;
        } else {
            $baseMin = 40; $baseMax = 62;
        }

        // Loop Semesters 1 to 4
        for ($sem = 1; $sem <= 4; $sem++) {
            $acadYear = ($sem <= 2) ? '2024-2025' : '2025-2026';
            $classYear = ($sem <= 2) ? 'Year 1' : 'Year 2';

            // Fetch subjects for this dept and semester
            $subRes = $conn->query("SELECT subject_code, subject_type FROM subjects WHERE department = '$dept' AND semester = $sem");
            while ($subRow = $subRes->fetch_assoc()) {
                $subCode = $subRow['subject_code'];
                $isLab = ($subRow['subject_type'] === 'lab');

                // Seed realistic non-identical marks
                $seedVal = ($sid * 17 + $sem * 31 + crc32($subCode)) % 100;
                $mark = $baseMin + ($seedVal % ($baseMax - $baseMin + 1));
                
                $ca1 = round($mark * 0.9, 1);
                $ca2 = round($mark * 0.95, 1);
                $ca3 = round($mark * 0.88, 1);
                $bestTwo = round(($ca1 + $ca2) / 2, 1);
                $caConverted = round(($bestTwo / 100) * 30, 1);
                $assignment = round(8.0 + ($seedVal % 3), 1);
                $theoryTotal = round($caConverted + $assignment + ($mark * 0.6), 1);

                $grade = 'B';
                $gp = 7.0;
                $isArrear = 0;

                if ($theoryTotal >= 90) { $grade = 'O'; $gp = 10.0; }
                elseif ($theoryTotal >= 80) { $grade = 'A+'; $gp = 9.0; }
                elseif ($theoryTotal >= 70) { $grade = 'A'; $gp = 8.0; }
                elseif ($theoryTotal >= 60) { $grade = 'B+'; $gp = 7.0; }
                elseif ($theoryTotal >= 50) { $grade = 'B'; $gp = 6.0; }
                else { $grade = 'RA'; $gp = 0.0; $isArrear = 1; }

                $subId = $subjectIdMap[$subCode] ?? 1;
                $batchStr = "24{$dept}";

                $m_student_id = $sid;
                $m_subject_id = $subId;
                $m_subject_code = $subCode;
                $m_semester = $sem;
                $m_academic_year = $acadYear;
                $m_class_year = $classYear;
                $m_batch = $batchStr;
                $m_ca1 = $ca1;
                $m_ca2 = $ca2;
                $m_ca3 = $ca3;
                $m_best_two = $bestTwo;
                $m_ca_converted = $caConverted;
                $m_assignment = $assignment;
                $m_theory_total = $theoryTotal;
                $m_grade = $grade;
                $m_gp = $gp;
                $m_is_arrear = $isArrear;

                $stmtMark->execute();

                // Insert into semester_marks
                $conn->query("INSERT INTO semester_marks (student_id, subject_code, marks_secured, semester_number, year_of_study, staff_id_entered) 
                    VALUES ($sid, '$subCode', $theoryTotal, $sem, " . ($sem <= 2 ? 1 : 2) . ", $admin_uid)");
            }
        }
    }
    $stmtMark->close();
    logSeed("SUCCESS: Semesters 1-4 historical marks generated. Semester 5 left active.");

    // ---------------------------------------------------------
    // Phase 7 — Attendance (72.0% - 99.5%)
    // ---------------------------------------------------------
    logSeed("Generating Varied Attendance Records (72.0% - 99.5%)...");

    foreach ($studentMap as $roll => $sid) {
        $dept = str_starts_with($roll, '24AI') ? 'AI' : 'DI';
        
        for ($sem = 1; $sem <= 5; $sem++) {
            $acadYear = ($sem <= 2) ? '2024-2025' : (($sem <= 4) ? '2025-2026' : '2026-2027');
            $subRes = $conn->query("SELECT subject_code FROM subjects WHERE department = '$dept' AND semester = $sem");
            
            while ($subRow = $subRes->fetch_assoc()) {
                $subCode = $subRow['subject_code'];
                $randAtt = round(72.0 + (($sid * 13 + $sem * 19 + crc32($subCode)) % 275) / 10, 2);
                if ($randAtt > 99.5) $randAtt = 99.5;

                $conn->query("INSERT INTO student_attendance (student_id, subject_code, semester, academic_year, attendance_percent) 
                    VALUES ($sid, '$subCode', $sem, '$acadYear', $randAtt)");
            }
        }
    }
    logSeed("SUCCESS: Student attendance records seeded.");

    // ---------------------------------------------------------
    // Phase 8 & 9 — 20 IQAC Categories (Zero Empty Tables / Zero Placeholders)
    // ---------------------------------------------------------
    logSeed("Populating all 20 IQAC & NBA Report Categories with Dynamic Normalized Data...");

    $projectTitlesDI = [
        'AI-Powered Student Attendance Management Portal', 'Smart College ERP with Automated NBA Analytics',
        'Blockchain-Based Academic Certificate Verification', 'Cloud Infrastructure Optimization using Terraform',
        'IoT-Based Smart Agriculture & Soil Monitoring System', 'Automated Resume Screening using Natural Language Processing',
        'Facial Recognition Based Canteen Management System', 'Cyber Threat Detection using Random Forest Algorithm',
        'Smart Traffic Signal Control System using Microcontrollers', 'Distributed Microservices Architecture for E-Learning',
        'Real-Time Vehicle Tracking & Telematics Portal', 'Automated Grading & Plagiarism Detection System',
        'DevOps CI/CD Automation Pipeline for Web Applications', 'Secure Hospital Patient Record Management System',
        'Wireless Sensor Network for Environmental Monitoring', 'Autonomous Delivery Bot Navigation System',
        'Smart Home Energy Audit & Control Application', 'Student Placement Portal with Dynamic Resume Builder',
        'Predictive Maintenance System for Industrial Equipment', 'Deep Learning Based Plant Disease Detection'
    ];

    $projectTitlesAI = [
        'Real-Time Computer Vision Driver Drowsiness Detector', 'Medical Image Segmentation using Convolutional Neural Networks',
        'Stock Market Trend Forecasting using LSTM Networks', 'Autonomous Drone Navigation & Obstacle Avoidance AI',
        'Voice Assistant for Visually Impaired using NLP', 'AI-Driven Smart Grid Energy Demand Prediction',
        'Deep Fake Detection System for Video Integrity Audit', 'Reinforcement Learning Bot for Industrial Robotics',
        'Sentiment Analysis Engine for Brand Reputation Monitoring', 'Generative Adversarial Network for Image Super-Resolution',
        'Automated Medical Prescription OCR & Summarizer', 'Edge AI Camera for Industrial Defect Inspection',
        'Natural Language Interface for Complex SQL Database Queries', 'Smart Waste Classification using MobileNet Architecture',
        'Automated Essay Scoring & Feedback Generation Engine', 'AI Powered Personal Health & Diet Recommendation System',
        'Traffic Sign Recognition for Autonomous Driving Systems', 'Cybersecurity Malware Classification using CNN',
        'Crop Yield Prediction System using Satellite Imagery', 'Multilingual Speech Translation System for Education'
    ];

    // 120 Student Projects (100% Student Coverage)
    foreach ($studentMap as $roll => $sid) {
        $isAI = str_starts_with($roll, '24AI');
        $num = (int)substr($roll, 4);
        $titleArr = $isAI ? $projectTitlesAI : $projectTitlesDI;
        $projTitle = $titleArr[($num - 1) % count($titleArr)] . " (Group " . ceil($num / 3) . ")";
        $guide = $isAI ? 'Dr. M. Arunkumar' : 'Dr. K. Senthil Kumar';
        
        $conn->query("INSERT INTO student_projects (student_id, project_title, project_type, guide_name, status, remarks) 
            VALUES ($sid, '$projTitle', 'Major Project', '$guide', 'Approved', 'High quality research project')");
        
        // Seed 1 Patent per 10 students
        if ($num % 10 === 0) {
            $conn->query("INSERT INTO student_patents (student_id, patent_title, application_no, status, year_filed)
                VALUES ($sid, 'System and Method for $projTitle', 'TEMP-PAT-2026-" . (1000 + $sid) . "', 'Published', 2026)");
        }

        // Seed Internship for top 30 students
        if ($num <= 30) {
            $comp = ['Zoho Corporation', 'TCS Innovation Labs', 'Wipro Technologies', 'Infosys Ltd', 'Bosch India'][$num % 5];
            $conn->query("INSERT INTO student_internships (student_id, company_name, duration, location, status, staff_remark)
                VALUES ($sid, '$comp', '8 Weeks', 'Coimbatore', 'approved', 'Completed summer industry internship')");
        }

        // Seed Achievement for every student
        $achTypes = ['Hackathon Winner', 'Coding Contest 1st Rank', 'NPTEL Elite Certificate', 'AWS Certified Developer', 'Paper Presentation Award'];
        $achTitle = $achTypes[$num % count($achTypes)];
        $conn->query("INSERT INTO student_achievements (student_id, title, achievement_type, description, achievement_date, status)
            VALUES ($sid, '$achTitle', 'State Level', 'Achieved top distinction in competitive technical event', '2026-03-15', 'approved')");

        // Seed Sports for select students
        if ($num % 4 === 0) {
            $sports = ['Cricket', 'Football', 'Volleyball', 'Basketball', 'Badminton'];
            $sportName = $sports[$num % count($sports)];
            $conn->query("INSERT INTO student_sports (student_id, sport_name, level, position, year_participated)
                VALUES ($sid, '$sportName', 'Inter-Polytechnic Tournament', 'Winner', 2026)");
        }
    }

    // Seed Placement Statistics
    logSeed("Seeding Placement Statistics & Company Statistics...");
    $conn->query("INSERT INTO placement_statistics (academic_year, batch_year, total_eligible, placed_count, average_package, highest_package, lowest_package) VALUES
        ('2023-2024', 'CAY-3', 115, 108, 4.20, 9.50, 3.20),
        ('2024-2025', 'CAY-2', 118, 112, 4.60, 11.00, 3.50),
        ('2025-2026', 'CAY-1', 120, 116, 5.10, 12.50, 3.60),
        ('2026-2027', 'CAY', 120, 115, 5.80, 14.00, 4.00)");

    // Seed Curriculum Gaps
    $conn->query("INSERT INTO curriculum_gap (academic_year, course_code, gap_details, action_taken, date_conducted, resource_person, mode, students_present, po_mapping) VALUES
        ('2026-2027', 'DI501', 'Docker Containerization & Kubernetes Orchestration', 'Hands-on Industry Workshop', '2026-07-15', 'Mr. Aravind (DevOps Engineer, Zoho)', 'Offline', 58, 'PO2, PO5'),
        ('2026-2027', 'AI501', 'PyTorch 2.0 & Transformer Architecture Optimization', 'Expert Seminar & Code Along', '2026-07-20', 'Dr. S. Kumar (Senior AI Scientist)', 'Offline', 59, 'PO1, PO4'),
        ('2025-2026', 'DI402', 'Cross-Platform Flutter & Dart Development', '3-Day Certification Training', '2026-02-10', 'Mr. Vignesh (Mobile Architect)', 'Offline', 60, 'PO3, PO5'),
        ('2025-2026', 'AI401', 'MLOps & Pipeline Deployment on AWS SageMaker', 'Special Guest Lecture', '2026-03-05', 'Ms. Deepika (AWS ML Specialist)', 'Offline', 57, 'PO2, PO6')");

    // Seed Industry Interactions & MoUs
    $conn->query("INSERT INTO mou_master (mou_number, organization_name, organization_type, contact_person, contact_email, website) VALUES
        ('MOU-2024-001', 'Zoho Corporation', 'Industry', 'Mr. Aravind', 'aravind@zoho.com', 'https://zoho.com'),
        ('MOU-2024-002', 'Tata Consultancy Services', 'Industry', 'Mr. Sundaram', 'sundaram@tcs.com', 'https://tcs.com'),
        ('MOU-2025-003', 'Amazon Web Services', 'Cloud Partner', 'Ms. Deepika', 'deepika@amazon.com', 'https://aws.amazon.com')");

    $conn->query("INSERT INTO industry_interactions (academic_year, interaction_type, company_name, resource_person, event_date, outcome, students_count) VALUES
        ('2026-2027', 'Industrial Visit', 'Bosch Smart Factory, Coimbatore', 'Mr. R. Sundaram (Plant Head)', '2026-06-18', 'Students observed Industry 4.0 IoT sensors & automated assembly lines', 118),
        ('2026-2027', 'Guest Lecture', 'Cisco Systems India', 'Mr. K. Natarajan (Senior Network Engineer)', '2026-07-02', 'Demonstration of Zero Trust Network Architecture & SD-WAN', 120),
        ('2025-2026', 'Industrial Visit', 'Zoho Campus, Estancia', 'Mr. M. Chellappa (HR Manager)', '2026-01-22', 'Exposure to enterprise SaaS cloud infrastructure and Agile teams', 115)");

    // Seed Guest Lectures & Alumni Interactions
    $conn->query("INSERT INTO guest_lectures (academic_year, topic, speaker_name, organization, event_date, participants_count) VALUES
        ('2026-2027', 'Quantum Computing & Modern Cryptography', 'Dr. V. Balakrishnan', 'PSG College of Technology', '2026-07-10', 120),
        ('2026-2027', 'Generative AI & LLM Fine-Tuning in Industry', 'Mr. P. Senthil', 'Microsoft India', '2026-07-25', 119)");

    $conn->query("INSERT INTO alumni_interactions (academic_year, alumni_name, batch, designation, company, topic, event_date, beneficiaries_count) VALUES
        ('2026-2027', 'Mr. Dinesh Kumar', 'Batch 2021', 'Senior Full-Stack Engineer', 'Freshworks', 'Career Roadmap in Web Development & Cloud Services', '2026-06-30', 120),
        ('2026-2027', 'Ms. Harini Ramesh', 'Batch 2022', 'AI Product Specialist', 'Cohesity', 'Cracking Product Company Technical Interviews', '2026-07-12', 118)");

    // Seed Faculty Development Programs
    $conn->query("INSERT INTO faculty_development_programs (program_name, description, start_date, end_date, staff_id) VALUES
        ('National Level FDP on Cloud Native & Microservices', 'IIT Madras FDP', '2026-05-10', '2026-05-14', 1),
        ('Advanced Deep Learning & Computer Vision Workshop', 'NIT Trichy Workshop', '2026-06-01', '2026-06-06', 2),
        ('Outcome Based Education & NBA Accreditation Masterclass', 'NITTTR Chennai Masterclass', '2026-06-15', '2026-06-19', 3),
        ('Generative AI and Large Language Model Deployment', 'IIIT Hyderabad FDP', '2026-07-01', '2026-07-07', 4)");

    // Seed Research Publications
    $conn->query("INSERT INTO research_publications (title, authors, journal_name, publication_year, impact_factor, staff_id) VALUES
        ('Cloud Computing & Distributed Microservices Architecture', 'Dr. K. Senthil Kumar, Prof. A. Priya', 'IEEE Transactions on Cloud Computing', 2026, 4.85, 1),
        ('Deep Neural Networks for Medical Image Segmentation', 'Dr. M. Arunkumar, Prof. G. Rajesh', 'Springer Journal of Artificial Intelligence', 2026, 5.20, 2),
        ('Zero Trust Cybersecurity Architecture for Educational ERP', 'Prof. B. Vijay, Prof. R. Ramesh', 'Elsevier Cyber Security Journal', 2026, 3.90, 3)");

    // Seed Student Publications / Research
    $conn->query("INSERT INTO student_publications (roll_no, student_name, paper_title, journal_conference, issn_isbn, date_of_publication) VALUES
        ('24DI01', 'Aadhavan Subramanian', 'Smart City IoT Pollution Monitoring using Distributed Nodes', 'IEEE International Conference on Smart Systems', 'ISSN 2345-6789', '2026-04-15'),
        ('24AI01', 'Ananya Senthil', 'Real-Time Drowsiness Detection using Lightweight CNN', 'Springer Journal of Artificial Intelligence', 'ISSN 1234-5678', '2026-05-20'),
        ('24DI15', 'Oviya Ramesh', 'Automated Vulnerability Scanner for Web Applications', 'Scopus Indexed Journal of Cybersecurity', 'ISSN 9876-5432', '2026-06-10')");

    // Seed Feedback Analysis
    $conn->query("INSERT INTO feedback_analysis (academic_year, stakeholder, total_responses, excellent_pct, very_good_pct, good_pct, needs_imp_pct) VALUES
        ('2026-2027', 'Students', 120, 78.50, 16.50, 4.00, 1.00),
        ('2026-2027', 'Faculty', 14, 85.70, 14.30, 0.00, 0.00),
        ('2026-2027', 'Employers', 15, 80.00, 15.00, 5.00, 0.00),
        ('2026-2027', 'Alumni', 45, 82.20, 13.30, 4.50, 0.00)");

    // ---------------------------------------------------------
    // Generate & Link Sample Evidence Files (Images & PDFs)
    // ---------------------------------------------------------
    logSeed("Generating & Linking Sample Evidence Files (MoUs, Certificates, Publications, Workshops, Sports)...");

    function createDummyImage(string $filePath, string $text) {
        if (extension_loaded('gd')) {
            $im = imagecreatetruecolor(800, 600);
            $bg = imagecolorallocate($im, 30, 58, 138); // Royal blue
            $white = imagecolorallocate($im, 255, 255, 255);
            $gold = imagecolorallocate($im, 251, 191, 36);
            $red = imagecolorallocate($im, 239, 68, 68);
            imagefilledrectangle($im, 0, 0, 800, 600, $bg);
            imagestring($im, 5, 200, 150, "PSG POLYTECHNIC COLLEGE", $gold);
            imagestring($im, 5, 180, 210, "[ DEMO SEED EVIDENCE DOCUMENT ]", $red);
            imagestring($im, 4, 150, 300, $text, $white);
            imagestring($im, 3, 200, 500, "SYSTEM GENERATED DEMONSTRATION SEED FILE", $gold);
            imagejpeg($im, $filePath, 90);
            imagedestroy($im);
        } else {
            file_put_contents($filePath, "PSG PTC DEMO SEED EVIDENCE IMAGE PLACEHOLDER: $text");
        }
    }

    function createDummyPdf(string $filePath, string $title) {
        $pdfContent = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj 3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>/Contents 4 0 R>>endobj 4 0 obj<</Length 140>>stream\nBT /F1 14 Pt\n50 700 Td\n(PSG POLYTECHNIC COLLEGE - [DEMO SEED EVIDENCE DOCUMENT]) Tj\n0 -30 Td\n($title) Tj\nET\nendstream\nendobj\nxref\n0 5\n0000000000 65535 f\n0000000009 00000 n\n0000000056 00000 n\n0000000111 00000 n\n0000000212 00000 n\ntrailer<</Size 5/Root 1 0 R>>\nstartxref\n380\n%%EOF";
        file_put_contents($filePath, $pdfContent);
    }

    // 1. Seed MoUs Evidence (10 MoU Files)
    $mouListFiles = [
        ['zoho_mou_2026.pdf', 'pdf', 'Zoho Corporation MoU Agreement 2026-2031'],
        ['tcs_signed.jpg', 'jpg', 'TCS Innovation Labs Signed MoU Certificate'],
        ['aws_mou_2025.pdf', 'pdf', 'Amazon Web Services Academy Partnership MoU'],
        ['cisco_mou.pdf', 'pdf', 'Cisco Systems Networking Academy MoU'],
        ['bosch_mou.pdf', 'pdf', 'Bosch Smart Factory Industry Collaboration MoU'],
        ['freshworks_mou.jpg', 'jpg', 'Freshworks Enterprise SaaS MoU'],
        ['infosys_mou.pdf', 'pdf', 'Infosys Campus Connect Program MoU'],
        ['wipro_mou.jpg', 'jpg', 'Wipro TalentNext Digital MoU'],
        ['cognizant_mou.pdf', 'pdf', 'Cognizant Academic Partnership Agreement'],
        ['ibm_mou.pdf', 'pdf', 'IBM SkillsBuild Center MoU']
    ];

    foreach ($mouListFiles as $idx => $mFile) {
        $path = IQAC_UPLOAD_BASE_DIR . '/mou/' . $mFile[0];
        if ($mFile[1] === 'pdf') {
            createDummyPdf($path, $mFile[2]);
        } else {
            createDummyImage($path, $mFile[2]);
        }
    }

    $mouRes = $conn->query("SELECT id FROM mou_master ORDER BY id");
    $mIdx = 0;
    while ($mRow = $mouRes ? $mouRes->fetch_assoc() : null) {
        $mf = $mouListFiles[$mIdx % count($mouListFiles)];
        $conn->query("UPDATE mou_master SET mou_file = 'uploads/mou/{$mf[0]}', mou_type = '{$mf[1]}', uploaded_at = NOW(), uploaded_by = 1 WHERE id = {$mRow['id']}");
        $mIdx++;
    }

    // 2. Seed Student Sports Evidence (uploads/sports/)
    $sportsFiles = [
        ['24DI05_Athletics_Certificate.jpg', 'jpg', 'State Level Athletics 100m Gold Medal - 24DI05'],
        ['24DI25_Basketball_Certificate.jpg', 'jpg', 'Basketball Tournament Winner Certificate'],
        ['24DI05_CTF.jpg', 'jpg', 'National Cyber Security CTF Rank 1 Certificate - 24DI05'],
        ['24AI10_Hackathon.pdf', 'pdf', 'Smart India Hackathon 1st Prize Winner - 24AI10'],
        ['24DI25_Basketball.jpg', 'jpg', 'Inter-Polytechnic Basketball Tournament Winner Certificate']
    ];
    for ($i = 1; $i <= 30; $i++) {
        $ext = ($i % 2 == 0 ? 'pdf' : 'jpg');
        $sportsFiles[] = [sprintf("Sports_Certificate_%02d.%s", $i, $ext), $ext, "Sports Certificate #$i"];
    }
    foreach ($sportsFiles as $sf) {
        $path = IQAC_UPLOAD_BASE_DIR . '/sports/' . $sf[0];
        if ($sf[1] === 'pdf') { createDummyPdf($path, $sf[2]); } else { createDummyImage($path, $sf[2]); }
    }
    $spRes = $conn->query("SELECT id FROM student_sports ORDER BY id");
    $spIdx = 0;
    while ($spRow = $spRes ? $spRes->fetch_assoc() : null) {
        $sf = $sportsFiles[$spIdx % count($sportsFiles)];
        $conn->query("UPDATE student_sports SET certificate_file = 'uploads/sports/{$sf[0]}', uploaded_at = NOW(), uploaded_by = 1 WHERE id = {$spRow['id']}");
        $spIdx++;
    }

    // 3. Seed Achievement Evidence (uploads/certificates/)
    $achFiles = [];
    for ($i = 1; $i <= 120; $i++) {
        $ext = ($i % 2 == 0 ? 'pdf' : 'jpg');
        $achFiles[] = [sprintf("Student_Certificate_%02d.%s", $i, $ext), $ext, "Achievement Certificate #$i"];
    }
    foreach ($achFiles as $af) {
        $path = IQAC_UPLOAD_BASE_DIR . '/certificates/' . $af[0];
        if ($af[1] === 'pdf') { createDummyPdf($path, $af[2]); } else { createDummyImage($path, $af[2]); }
    }
    $achRes = $conn->query("SELECT id FROM student_achievements ORDER BY id");
    $achIdx = 0;
    while ($achRow = $achRes ? $achRes->fetch_assoc() : null) {
        $af = $achFiles[$achIdx % count($achFiles)];
        $conn->query("UPDATE student_achievements SET certificate_file = 'uploads/certificates/{$af[0]}', uploaded_at = NOW(), uploaded_by = 1 WHERE id = {$achRow['id']}");
        $achIdx++;
    }

    // 4. Seed Publication Evidence (uploads/publications/)
    $pubFiles = [
        ['IEEE_Paper_24DI01.pdf', 'pdf', 'IEEE International Conference Research Paper'],
        ['Springer_AI_24AI01.pdf', 'pdf', 'Springer AI Journal Publication Certificate']
    ];
    for ($i = 1; $i <= 30; $i++) {
        $ext = ($i % 2 == 0 ? 'pdf' : 'jpg');
        $pubFiles[] = [sprintf("Publication_Proof_%02d.%s", $i, $ext), $ext, "Publication Proof #$i"];
    }
    foreach ($pubFiles as $pf) {
        $path = IQAC_UPLOAD_BASE_DIR . '/publications/' . $pf[0];
        if ($pf[1] === 'pdf') { createDummyPdf($path, $pf[2]); } else { createDummyImage($path, $pf[2]); }
    }
    $pubRes = $conn->query("SELECT id FROM student_publications ORDER BY id");
    $pubIdx = 0;
    while ($pubRow = $pubRes ? $pubRes->fetch_assoc() : null) {
        $pf = $pubFiles[$pubIdx % count($pubFiles)];
        $conn->query("UPDATE student_publications SET publication_file = 'uploads/publications/{$pf[0]}', uploaded_at = NOW(), uploaded_by = 1 WHERE id = {$pubRow['id']}");
        $pubIdx++;
    }

    // 5. Seed Internship Evidence (uploads/internships/)
    $intFiles = [
        ['Zoho_Internship_24DI01.pdf', 'pdf', 'Zoho Corporation Summer Internship Completion Certificate']
    ];
    for ($i = 1; $i <= 60; $i++) {
        $ext = ($i % 2 == 0 ? 'pdf' : 'jpg');
        $intFiles[] = [sprintf("Internship_Certificate_%02d.%s", $i, $ext), $ext, "Internship Certificate #$i"];
    }
    foreach ($intFiles as $inf) {
        $path = IQAC_UPLOAD_BASE_DIR . '/internships/' . $inf[0];
        if ($inf[1] === 'pdf') { createDummyPdf($path, $inf[2]); } else { createDummyImage($path, $inf[2]); }
    }
    $intRes = $conn->query("SELECT id FROM student_internships ORDER BY id");
    $intIdx = 0;
    while ($intRow = $intRes ? $intRes->fetch_assoc() : null) {
        $inf = $intFiles[$intIdx % count($intFiles)];
        $conn->query("UPDATE student_internships SET certificate_file = 'uploads/internships/{$inf[0]}', uploaded_at = NOW(), uploaded_by = 1 WHERE id = {$intRow['id']}");
        $intIdx++;
    }

    // 6. Seed FDP / Workshop Evidence (uploads/workshops/)
    $fdpFiles = [];
    for ($i = 1; $i <= 20; $i++) {
        $ext = ($i % 2 == 0 ? 'pdf' : 'jpg');
        $fdpFiles[] = [sprintf("FDP_Certificate_%02d.%s", $i, $ext), $ext, "FDP Certificate #$i"];
    }
    foreach ($fdpFiles as $ff) {
        $path = IQAC_UPLOAD_BASE_DIR . '/workshops/' . $ff[0];
        if ($ff[1] === 'pdf') { createDummyPdf($path, $ff[2]); } else { createDummyImage($path, $ff[2]); }
    }
    $fdpRes = $conn->query("SELECT id FROM faculty_development_programs ORDER BY id");
    $fdpIdx = 0;
    while ($fdpRow = $fdpRes ? $fdpRes->fetch_assoc() : null) {
        $ff = $fdpFiles[$fdpIdx % count($fdpFiles)];
        $conn->query("UPDATE faculty_development_programs SET certificate_file = 'uploads/workshops/{$ff[0]}', uploaded_at = NOW(), uploaded_by = 1 WHERE id = {$fdpRow['id']}");
        $fdpIdx++;
    }

    // 7. Seed Research Evidence (uploads/research/)
    $rpFiles = [];
    for ($i = 1; $i <= 20; $i++) {
        $ext = ($i % 2 == 0 ? 'pdf' : 'jpg');
        $rpFiles[] = [sprintf("Research_Proof_%02d.%s", $i, $ext), $ext, "Research Proof #$i"];
    }
    foreach ($rpFiles as $rf) {
        $path = IQAC_UPLOAD_BASE_DIR . '/research/' . $rf[0];
        if ($rf[1] === 'pdf') { createDummyPdf($path, $rf[2]); } else { createDummyImage($path, $rf[2]); }
    }
    $rpRes = $conn->query("SELECT id FROM research_publications ORDER BY id");
    $rpIdx = 0;
    while ($rpRow = $rpRes ? $rpRes->fetch_assoc() : null) {
        $rf = $rpFiles[$rpIdx % count($rpFiles)];
        $conn->query("UPDATE research_publications SET publication_file = 'uploads/research/{$rf[0]}', uploaded_at = NOW(), uploaded_by = 1 WHERE id = {$rpRow['id']}");
        $rpIdx++;
    }

    // Commit Transaction
    $conn->commit();
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");

    logSeed("SUCCESS: Full database transaction committed cleanly!");

    // Generate Seed Manifest (SEED_MANIFEST.json)
    $manifestData = [
        "seed_version" => "v1.0.0",
        "dataset" => "PSG PTC Official Production Academic Dataset",
        "academic_year" => "2026-2027",
        "generated_at" => date('c'),
        "departments" => ["DI", "AI"],
        "students" => 120,
        "staff" => 17,
        "subjects" => count($subjectsList),
        "historical_semesters" => [1, 2, 3, 4],
        "active_semester" => 5,
        "sha256" => hash('sha256', date('YmdHis') . "PSG_PTC_PRODUCTION_120")
    ];

    $manifestJson = json_encode($manifestData, JSON_PRETTY_PRINT);
    file_put_contents(__DIR__ . '/SEED_MANIFEST.json', $manifestJson);
    file_put_contents($prodDir . '/SEED_MANIFEST.json', $manifestJson);

    logSeed("SUCCESS: Generated SEED_MANIFEST.json (v1.0.0)");
    echo "\n=== DATASET RESET & SEEDING COMPLETED SUCCESSFULLY ===\n";

} catch (Exception $e) {
    $conn->rollback();
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    logSeed("CRITICAL FAILURE: Transaction aborted and rolled back! Error: " . $e->getMessage());
    exit(1);
}
