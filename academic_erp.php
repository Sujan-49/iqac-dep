<?php
require_once __DIR__ . '/include/auth.php';
require_once __DIR__ . '/include/navigation.php';

$user = iqac_require_login(['staff', 'tutor', 'hod', 'iqac', 'admin', 'super_admin']);
$role = iqac_effective_role($user);
$storedRole = iqac_normalize_role($user['role']);
$isAdminLike = in_array($role, ['admin', 'super_admin', 'hod', 'iqac'], true);
$isStaff = $role === 'staff';
$isTutor = $role === 'tutor';
$isStudent = $role === 'student';
$sessionStaffId = (int)($_SESSION['staff_id'] ?? 0);
$sessionStudentId = (int)($_SESSION['student_id'] ?? 0);

function erp_table(string $table): bool
{
    global $conn;
    return iqac_table_exists($conn, $table);
}

function erp_col(string $table, string $column): bool
{
    global $conn;
    return iqac_column_exists($conn, $table, $column);
}

function erp_scalar(string $sql, string $types = '', array $params = []): int|float|string
{
    global $conn;
    if ($types !== '') {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_row();
        $stmt->close();
        return $row[0] ?? 0;
    }

    $result = $conn->query($sql);
    if (!$result) {
        return 0;
    }
    $row = $result->fetch_row();
    return $row[0] ?? 0;
}

function erp_rows(string $sql, string $types = '', array $params = []): array
{
    global $conn;
    if ($types !== '') {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function erp_count(string $table): int
{
    return erp_table($table) ? (int)erp_scalar("SELECT COUNT(*) FROM `$table`") : 0;
}

function erp_percent(int|float $part, int|float $total): float
{
    return $total > 0 ? round(($part / $total) * 100, 1) : 0.0;
}

function erp_kpi(string $label, int|float|string $value, string $note = '', string $tone = ''): array
{
    return ['label' => $label, 'value' => $value, 'note' => $note, 'tone' => $tone];
}

function erp_card(array $card): void
{
    $tone = $card['tone'] ? ' ' . $card['tone'] : '';
    echo '<article class="stat-card' . htmlspecialchars($tone) . '">';
    echo '<span>' . htmlspecialchars($card['label']) . '</span>';
    echo '<strong>' . htmlspecialchars((string)$card['value']) . '</strong>';
    if ($card['note'] !== '') {
        echo '<em>' . htmlspecialchars($card['note']) . '</em>';
    }
    echo '</article>';
}

$students = erp_count('student_details');
$staff = erp_count('staff_details');
$subjects = erp_count('subjects');
$batches = erp_col('student_details', 'batch') ? (int)erp_scalar("SELECT COUNT(DISTINCT NULLIF(batch, '')) FROM student_details") : erp_count('batches');
$departments = erp_col('student_details', 'department') ? (int)erp_scalar("SELECT COUNT(DISTINCT NULLIF(department, '')) FROM student_details") : erp_count('departments');
$totalMarks = erp_table('student_marks') ? (int)erp_scalar('SELECT COUNT(*) FROM student_marks') : 0;
$verifiedMarks = erp_table('student_marks') ? (int)erp_scalar("SELECT COUNT(*) FROM student_marks WHERE status IN ('verified','locked')") : 0;
$lockedMarks = erp_table('student_marks') ? (int)erp_scalar("SELECT COUNT(*) FROM student_marks WHERE status = 'locked'") : 0;
$pendingMarks = erp_table('student_marks') ? (int)erp_scalar("SELECT COUNT(*) FROM student_marks WHERE status IN ('submitted','returned')") : 0;
$arrears = erp_table('student_marks') && erp_col('student_marks', 'is_arrear') ? (int)erp_scalar('SELECT COUNT(*) FROM student_marks WHERE is_arrear = 1') : 0;
$passedMarks = erp_table('student_marks') && erp_col('student_marks', 'is_arrear') ? (int)erp_scalar("SELECT COUNT(*) FROM student_marks WHERE status IN ('verified','locked') AND is_arrear = 0") : 0;
$failedMarks = erp_table('student_marks') && erp_col('student_marks', 'is_arrear') ? (int)erp_scalar("SELECT COUNT(*) FROM student_marks WHERE status IN ('verified','locked') AND is_arrear = 1") : 0;
$passPercent = erp_percent($passedMarks, $passedMarks + $failedMarks);
$failPercent = erp_percent($failedMarks, $passedMarks + $failedMarks);
$placementCount = erp_table('placement_statistics') ? (int)erp_scalar('SELECT COALESCE(SUM(placed_count), 0) FROM placement_statistics') : 0;
$sportsCount = erp_count('student_sports');
$achievementCount = erp_count('student_achievements') ?: erp_count('achievement_details');
$publicationCount = erp_count('student_publications');
$higherStudiesCount = erp_count('higher_studies') ?: erp_count('student_higher_studies');
$internshipCount = erp_count('student_internships');
$industryVisitCount = erp_count('industry_visits');
$odApplications = erp_table('applications') ? (int)erp_scalar("SELECT COUNT(*) FROM applications WHERE LOWER(application_type) LIKE '%od%'") : 0;
$leaveApplications = erp_table('student_leaves') ? erp_count('student_leaves') : (erp_table('applications') ? (int)erp_scalar("SELECT COUNT(*) FROM applications WHERE LOWER(application_type) LIKE '%leave%'") : 0);
$pendingApprovals = erp_table('applications') ? (int)erp_scalar("SELECT COUNT(*) FROM applications WHERE status IN ('pending','under_review')") : 0;
$erpFormSubmitted = erp_table('erp_form_submissions') ? (int)erp_scalar("SELECT COUNT(*) FROM erp_form_submissions WHERE status = 'submitted'") : 0;
$erpFormPending = erp_table('erp_form_submissions') ? (int)erp_scalar("SELECT COUNT(*) FROM erp_form_submissions WHERE status = 'pending'") : 0;
$erpFormApproved = erp_table('erp_form_submissions') ? (int)erp_scalar("SELECT COUNT(*) FROM erp_form_submissions WHERE status = 'approved'") : 0;
$erpFormRejected = erp_table('erp_form_submissions') ? (int)erp_scalar("SELECT COUNT(*) FROM erp_form_submissions WHERE status = 'rejected'") : 0;
$ownFormSubmitted = erp_table('erp_form_submissions') ? (int)erp_scalar("SELECT COUNT(*) FROM erp_form_submissions WHERE submitted_by_user_id = ? AND status = 'submitted'", 'i', [$user['id']]) : 0;
$ownFormPending = erp_table('erp_form_submissions') ? (int)erp_scalar("SELECT COUNT(*) FROM erp_form_submissions WHERE submitted_by_user_id = ? AND status = 'pending'", 'i', [$user['id']]) : 0;
$ownFormApproved = erp_table('erp_form_submissions') ? (int)erp_scalar("SELECT COUNT(*) FROM erp_form_submissions WHERE submitted_by_user_id = ? AND status = 'approved'", 'i', [$user['id']]) : 0;
$ownFormRejected = erp_table('erp_form_submissions') ? (int)erp_scalar("SELECT COUNT(*) FROM erp_form_submissions WHERE submitted_by_user_id = ? AND status = 'rejected'", 'i', [$user['id']]) : 0;

$userContext = [];
$staffSubjectRows = [];
$staffSubjectCodes = [];
$tutorStudents = 0;
$studentProfile = null;
$studentMarksRows = [];

if (($isStaff || $isTutor) && $sessionStaffId > 0 && erp_table('staff_subject_allocation')) {
    $staffSubjectRows = erp_rows(
        "SELECT ssa.subject_code, COALESCE(sub.subject_name, ssa.subject_code) AS subject_name, ssa.class_year
         FROM staff_subject_allocation ssa
         LEFT JOIN subjects sub ON sub.subject_code COLLATE utf8mb4_unicode_ci = ssa.subject_code COLLATE utf8mb4_unicode_ci
         WHERE ssa.staff_id = ?
         ORDER BY ssa.class_year, ssa.subject_code",
        'i',
        [$sessionStaffId]
    );
    $staffSubjectCodes = array_column($staffSubjectRows, 'subject_code');
}

if ($isStaff && $sessionStaffId > 0) {
    $assignedSubjectCount = count($staffSubjectCodes);
    $assignedStudentCount = 0;
    if ($staffSubjectCodes && erp_table('student_marks')) {
        $placeholders = implode(',', array_fill(0, count($staffSubjectCodes), '?'));
        $types = str_repeat('s', count($staffSubjectCodes));
        $assignedStudentCount = (int)erp_scalar("SELECT COUNT(DISTINCT student_id) FROM student_marks WHERE subject_code IN ($placeholders)", $types, $staffSubjectCodes);
    }
    $userContext = [
        erp_kpi('Assigned Subjects', $assignedSubjectCount, 'Staff restriction active'),
        erp_kpi('Assigned Students', $assignedStudentCount, 'From assigned subjects'),
        erp_kpi('Pending Verification', $pendingMarks, 'Submitted or returned', 'warn'),
        erp_kpi('Verified Marks', $verifiedMarks, 'Verified and locked'),
        erp_kpi('Returned Marks', erp_table('student_marks') ? (int)erp_scalar("SELECT COUNT(*) FROM student_marks WHERE status = 'returned'") : 0, 'Needs student action'),
        erp_kpi('Submitted Forms', $ownFormSubmitted, 'Staff workflow'),
        erp_kpi('Approved Forms', $ownFormApproved, 'Completed'),
    ];
}

if ($isTutor && $sessionStaffId > 0 && erp_col('student_details', 'tutor_staff_id')) {
    $tutorStudents = (int)erp_scalar('SELECT COUNT(*) FROM student_details WHERE tutor_staff_id = ?', 'i', [$sessionStaffId]);
    $tutorMarksTotal = erp_table('student_marks')
        ? (int)erp_scalar('SELECT COUNT(*) FROM student_marks sm INNER JOIN student_details sd ON sd.student_id = sm.student_id WHERE sd.tutor_staff_id = ?', 'i', [$sessionStaffId])
        : 0;
    $tutorArrears = erp_table('student_marks') && erp_col('student_marks', 'is_arrear')
        ? (int)erp_scalar('SELECT COUNT(*) FROM student_marks sm INNER JOIN student_details sd ON sd.student_id = sm.student_id WHERE sd.tutor_staff_id = ? AND sm.is_arrear = 1', 'i', [$sessionStaffId])
        : 0;
    $userContext = [
        erp_kpi('Assigned Students', $tutorStudents, 'Tutor batch'),
        erp_kpi('Marks Records', $tutorMarksTotal, 'Read-only monitoring'),
        erp_kpi('Arrears', $tutorArrears, 'Student support focus', 'danger'),
        erp_kpi('Applications', $pendingApprovals, 'Pending review'),
        erp_kpi('Submitted Forms', $erpFormSubmitted, 'Batch/admin review queue'),
        erp_kpi('Approved Forms', $erpFormApproved, 'Completed forms'),
    ];
}

if ($isStudent && $sessionStudentId > 0) {
    $studentProfileRows = erp_rows('SELECT full_name, roll_number, department, class_year, batch, section, current_semester FROM student_details WHERE student_id = ? LIMIT 1', 'i', [$sessionStudentId]);
    $studentProfile = $studentProfileRows[0] ?? null;
    if (erp_table('student_marks')) {
        $studentMarksRows = erp_rows(
            "SELECT sm.subject_code, COALESCE(sub.subject_name, sm.subject_code) AS subject_name, sm.semester, sm.theory_total, sm.practical_total, sm.semester_grade, sm.status
             FROM student_marks sm
             LEFT JOIN subjects sub ON sub.subject_code COLLATE utf8mb4_unicode_ci = sm.subject_code COLLATE utf8mb4_unicode_ci
             WHERE sm.student_id = ?
             ORDER BY sm.semester DESC, sm.updated_at DESC
             LIMIT 8",
            'i',
            [$sessionStudentId]
        );
    }
    $userContext = [
        erp_kpi('My Marks', count($studentMarksRows), 'Recent records'),
        erp_kpi('Achievements', erp_table('student_achievements') ? (int)erp_scalar('SELECT COUNT(*) FROM student_achievements WHERE student_id = ?', 'i', [$sessionStudentId]) : 0, 'Profile card'),
        erp_kpi('Sports', erp_table('student_sports') ? (int)erp_scalar('SELECT COUNT(*) FROM student_sports WHERE student_id = ?', 'i', [$sessionStudentId]) : 0, 'Participation'),
        erp_kpi('Applications', erp_table('applications') ? (int)erp_scalar('SELECT COUNT(*) FROM applications WHERE student_id = ?', 'i', [$sessionStudentId]) : 0, 'All requests'),
    ];
}

$adminKpis = [
    erp_kpi('Total Students', $students, 'Live student_details'),
    erp_kpi('Total Staff', $staff, 'Live staff_details'),
    erp_kpi('Total Subjects', $subjects, 'subjects.subject_code'),
    erp_kpi('Total Batches', $batches, 'From student batches'),
    erp_kpi('Departments', $departments, 'Active departments'),
    erp_kpi('Pass %', $passPercent . '%', $passedMarks . ' pass records', 'success'),
    erp_kpi('Fail %', $failPercent . '%', $failedMarks . ' fail records', $failedMarks ? 'danger' : ''),
    erp_kpi('Arrear Count', $arrears, 'Marked RA/fail', $arrears ? 'danger' : ''),
    erp_kpi('Placement Count', $placementCount, 'Placement statistics'),
    erp_kpi('Sports Participation', $sportsCount, 'student_sports'),
    erp_kpi('Achievements', $achievementCount, 'Approved and pending'),
    erp_kpi('Publications', $publicationCount, 'student_publications'),
    erp_kpi('Higher Studies', $higherStudiesCount, 'If table exists'),
    erp_kpi('Internships', $internshipCount, 'student_internships'),
    erp_kpi('Industry Visits', $industryVisitCount, 'industry_visits'),
    erp_kpi('OD Applications', $odApplications, 'applications'),
    erp_kpi('Leave Applications', $leaveApplications, 'student_leaves/applications'),
    erp_kpi('Pending Approvals', $pendingApprovals, 'Workflow queue', $pendingApprovals ? 'warn' : ''),
    erp_kpi('Submitted Forms', $erpFormSubmitted, 'ERP form intake'),
    erp_kpi('Pending Forms', $erpFormPending, 'Review queue', $erpFormPending ? 'warn' : ''),
    erp_kpi('Approved Forms', $erpFormApproved, 'Completed workflows', 'success'),
    erp_kpi('Rejected Forms', $erpFormRejected, 'Rejected workflows', $erpFormRejected ? 'danger' : ''),
    erp_kpi('Verified Marks', $verifiedMarks, 'Staff/admin verified'),
    erp_kpi('Locked Marks', $lockedMarks, 'Admin locked'),
];

$criteriaCards = [
    ['Criteria 1', 'Curriculum', 'Subjects, semesters, course outcomes, program outcomes', $subjects],
    ['Criteria 2', 'Academic Performance', 'Pass %, fail %, backlog %, success rate, batch analysis', $passPercent . '%'],
    ['Criteria 3', 'Research', 'Publications, patents, projects, funded research', $publicationCount],
    ['Criteria 4', 'Student Activities', 'Achievements, sports, competitions, clubs', $achievementCount + $sportsCount],
    ['Criteria 5', 'Student Support', 'Placement, higher studies, scholarships, mentoring', $placementCount + $higherStudiesCount],
];

$admissionRows = erp_col('student_details', 'department')
    ? erp_rows(
        "SELECT COALESCE(NULLIF(department, ''), 'Unassigned') AS department,
                COALESCE(NULLIF(batch, ''), class_year, 'Unassigned') AS batch,
                COUNT(*) AS total,
                SUM(CASE WHEN is_lateral = 1 THEN 1 ELSE 0 END) AS lateral,
                SUM(CASE WHEN caste_category IS NOT NULL AND caste_category <> '' THEN 1 ELSE 0 END) AS category_count
         FROM student_details
         GROUP BY COALESCE(NULLIF(department, ''), 'Unassigned'), COALESCE(NULLIF(batch, ''), class_year, 'Unassigned')
         ORDER BY department, batch
         LIMIT 12"
    )
    : [];

$resultRows = erp_table('student_marks')
    ? erp_rows(
        "SELECT sm.subject_code,
                COALESCE(sub.subject_name, sm.subject_code) AS subject_name,
                COALESCE(st.full_name, 'Unassigned') AS teacher_name,
                COUNT(*) AS appeared,
                SUM(CASE WHEN sm.is_arrear = 0 AND sm.status IN ('verified','locked') THEN 1 ELSE 0 END) AS passed,
                SUM(CASE WHEN sm.is_arrear = 1 THEN 1 ELSE 0 END) AS failed,
                ROUND(AVG(NULLIF(sm.theory_total + sm.practical_total, 0)), 2) AS average_mark,
                MAX(sm.theory_total + sm.practical_total) AS highest_mark,
                MIN(NULLIF(sm.theory_total + sm.practical_total, 0)) AS lowest_mark
         FROM student_marks sm
         LEFT JOIN subjects sub ON sub.subject_code COLLATE utf8mb4_unicode_ci = sm.subject_code COLLATE utf8mb4_unicode_ci
         LEFT JOIN staff_subject_allocation ssa ON ssa.subject_code COLLATE utf8mb4_unicode_ci = sm.subject_code COLLATE utf8mb4_unicode_ci
         LEFT JOIN staff_details st ON st.staff_id = ssa.staff_id
         GROUP BY sm.subject_code, subject_name, teacher_name
         ORDER BY sm.subject_code
         LIMIT 12"
    )
    : [];

$teacherRows = erp_table('staff_subject_allocation')
    ? erp_rows(
        "SELECT ssa.subject_code,
                COALESCE(sub.subject_name, ssa.subject_code) AS subject_name,
                COALESCE(st.full_name, 'Unassigned') AS teacher_name,
                COALESCE(st.department, sub.department, 'General') AS department,
                COALESCE(sub.semester, 0) AS semester,
                ssa.class_year AS batch
         FROM staff_subject_allocation ssa
         LEFT JOIN subjects sub ON sub.subject_code COLLATE utf8mb4_unicode_ci = ssa.subject_code COLLATE utf8mb4_unicode_ci
         LEFT JOIN staff_details st ON st.staff_id = ssa.staff_id
         ORDER BY department, semester, ssa.subject_code
         LIMIT 16"
    )
    : [];

$batchRows = erp_col('student_details', 'batch')
    ? erp_rows(
        "SELECT COALESCE(NULLIF(sd.batch, ''), sd.class_year, 'Unassigned') AS batch_name,
                COALESCE(MAX(t.full_name), 'Unassigned') AS tutor_name,
                COUNT(DISTINCT sd.student_id) AS student_count,
                SUM(CASE WHEN sm.is_arrear = 1 THEN 1 ELSE 0 END) AS arrears,
                SUM(CASE WHEN sm.is_arrear = 0 AND sm.status IN ('verified','locked') THEN 1 ELSE 0 END) AS pass_records,
                SUM(CASE WHEN sm.status IN ('verified','locked') THEN 1 ELSE 0 END) AS result_records
         FROM student_details sd
         LEFT JOIN staff_details t ON t.staff_id = sd.tutor_staff_id
         LEFT JOIN student_marks sm ON sm.student_id = sd.student_id
         GROUP BY COALESCE(NULLIF(sd.batch, ''), sd.class_year, 'Unassigned')
         ORDER BY batch_name
         LIMIT 12"
    )
    : [];

$notifications = [];
if (erp_table('notifications')) {
    $notifications = erp_rows(
        'SELECT title, message, created_at FROM notifications WHERE (user_id = ? OR role = ? OR role IS NULL) ORDER BY created_at DESC LIMIT 6',
        'is',
        [(int)$user['id'], $storedRole]
    );
}

$announcements = [];
if (erp_table('announcements')) {
    $announcements = erp_rows(
        'SELECT title, category, created_at FROM announcements WHERE is_active = 1 AND (audience_role = ? OR audience_role IS NULL) ORDER BY created_at DESC LIMIT 6',
        's',
        [$storedRole]
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PSG PTC ERP Dashboard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/erp.css">
</head>
<body>
<div class="erp-layout">
    <?php iqac_render_sidebar($user, 'academic_erp.php'); ?>

    <main class="erp-main">
        <header class="erp-topbar">
            <div>
                <h1>PSG PTC Department Portal</h1>
                <span><?= htmlspecialchars($user['username']) ?> &middot; <?= htmlspecialchars($user['department'] ?: 'All Departments') ?></span>
            </div>
            <div class="toolbar-actions">
                <?php if ($isStaff || $isAdminLike): ?><a class="secondary-btn" href="marks_entry.php">Marks Workflow</a><?php endif; ?>
                <?php if ($isStaff): ?><a class="secondary-btn" href="erp_forms.php?scope=staff">Staff Forms</a><?php endif; ?>
                <?php if ($isTutor): ?><a class="secondary-btn" href="erp_forms.php?scope=student">Form Reviews</a><?php endif; ?>
                <?php if ($isAdminLike): ?><a class="secondary-btn" href="erp_forms.php?scope=admin">Admin Forms</a><?php endif; ?>
                <?php if ($isAdminLike): ?><a class="primary-btn" href="admin_controls.php">Admin Controls</a><?php endif; ?>
            </div>
        </header>

        <section class="erp-content">
            <?php if ($userContext): ?>
                <section class="role-hero">
                    <div>
                        <span class="eyebrow"><?= htmlspecialchars(strtoupper($role)) ?> DASHBOARD</span>
                        <h2><?= $isStudent ? 'Student Profile Card' : ($isStaff ? 'Assigned Subject Workspace' : 'Tutor Monitoring Workspace') ?></h2>
                        <p>Role restrictions are applied from the live ERP mappings. No duplicate student or subject tables are used.</p>
                    </div>
                    <div class="toolbar-actions">
                        <?php if ($isStaff || $isAdminLike): ?><a class="link-btn" href="marks_entry.php">Open Marks</a><?php endif; ?>
                        <?php if ($isStaff): ?><a class="link-btn" href="erp_forms.php?scope=staff">Open Staff Forms</a><?php endif; ?>
                        <?php if ($isTutor): ?><a class="link-btn" href="erp_forms.php?scope=student">Open Reviews</a><?php endif; ?>
                    </div>
                </section>
                <div class="stat-grid compact">
                    <?php foreach ($userContext as $card) { erp_card($card); } ?>
                </div>
            <?php endif; ?>

            <?php if ($isAdminLike || (!$isStaff && !$isTutor && !$isStudent)): ?>
                <section class="role-hero">
                    <div>
                        <span class="eyebrow">NBA ACCREDITATION VIEW</span>
                        <h2>Department ERP Performance Dashboard</h2>
                        <p>Academic, IQAC, applications, marks, placement, achievements, sports, and research indicators from existing live tables.</p>
                    </div>
                    <div class="progress-stack">
                        <div><span>Pass</span><strong><?= htmlspecialchars((string)$passPercent) ?>%</strong></div>
                        <div class="progress-line"><i style="width: <?= min(100, $passPercent) ?>%"></i></div>
                        <div><span>Pending Approvals</span><strong data-pending-count><?= $pendingApprovals + $pendingMarks ?></strong></div>
                    </div>
                </section>

                <div class="stat-grid nba-grid">
                    <?php foreach ($adminKpis as $card) { erp_card($card); } ?>
                </div>
            <?php endif; ?>

            <?php if ($isAdminLike): ?>
            <section class="panel" id="criteria">
                <div class="panel-header">
                    <h2>Criteria Wise Dashboards</h2>
                    <span class="badge gold">NBA Style</span>
                </div>
                <div class="criteria-grid">
                    <?php foreach ($criteriaCards as $item): ?>
                        <article class="criteria-card">
                            <span><?= htmlspecialchars($item[0]) ?></span>
                            <h3><?= htmlspecialchars($item[1]) ?></h3>
                            <p><?= htmlspecialchars($item[2]) ?></p>
                            <strong><?= htmlspecialchars((string)$item[3]) ?></strong>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="panel" id="analytics">
                <div class="panel-header">
                    <h2>Analytics Charts</h2>
                    <span class="badge">Progress Indicators</span>
                </div>
                <div class="chart-grid">
                    <div class="chart-card">
                        <h3>Pass vs Fail</h3>
                        <div class="bar-row"><span>Pass</span><div><i style="width: <?= min(100, $passPercent) ?>%"></i></div><b><?= $passPercent ?>%</b></div>
                        <div class="bar-row danger"><span>Fail</span><div><i style="width: <?= min(100, $failPercent) ?>%"></i></div><b><?= $failPercent ?>%</b></div>
                    </div>
                    <div class="chart-card">
                        <h3>Workflow Health</h3>
                        <div class="bar-row"><span>Verified</span><div><i style="width: <?= min(100, erp_percent($verifiedMarks, max(1, $totalMarks))) ?>%"></i></div><b><?= $verifiedMarks ?></b></div>
                        <div class="bar-row gold"><span>Locked</span><div><i style="width: <?= min(100, erp_percent($lockedMarks, max(1, $totalMarks))) ?>%"></i></div><b><?= $lockedMarks ?></b></div>
                    </div>
                    <div class="chart-card">
                        <h3>Student Growth</h3>
                        <div class="bar-row"><span>Students</span><div><i style="width: <?= min(100, $students ? 100 : 0) ?>%"></i></div><b><?= $students ?></b></div>
                        <div class="bar-row gold"><span>Staff</span><div><i style="width: <?= min(100, erp_percent($staff, max(1, $students))) ?>%"></i></div><b><?= $staff ?></b></div>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <?php if ($isStudent && $studentProfile): ?>
                <section class="panel">
                    <div class="panel-header">
                        <h2>Student Profile Card</h2>
                        <span class="badge"><?= htmlspecialchars($studentProfile['roll_number']) ?></span>
                    </div>
                    <div class="profile-grid">
                        <div><span>Name</span><strong><?= htmlspecialchars($studentProfile['full_name']) ?></strong></div>
                        <div><span>Department</span><strong><?= htmlspecialchars($studentProfile['department'] ?? 'General') ?></strong></div>
                        <div><span>Batch</span><strong><?= htmlspecialchars($studentProfile['batch'] ?? $studentProfile['class_year'] ?? 'Not set') ?></strong></div>
                        <div><span>Section</span><strong><?= htmlspecialchars($studentProfile['section'] ?? 'Not set') ?></strong></div>
                        <div><span>Semester</span><strong><?= htmlspecialchars((string)$studentProfile['current_semester']) ?></strong></div>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($isStaff && $staffSubjectRows): ?>
                <section class="panel">
                    <div class="panel-header">
                        <h2>Assigned Subjects</h2>
                        <span class="badge">Restricted</span>
                    </div>
                    <div class="table-wrap">
                        <table class="erp-table">
                            <thead><tr><th>Subject Code</th><th>Subject Name</th><th>Batch</th></tr></thead>
                            <tbody>
                                <?php foreach ($staffSubjectRows as $row): ?>
                                    <tr><td><?= htmlspecialchars($row['subject_code']) ?></td><td><?= htmlspecialchars($row['subject_name']) ?></td><td><?= htmlspecialchars($row['class_year']) ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($isAdminLike): ?>
            <section class="panel" id="results">
                <div class="panel-header">
                    <h2>Result Analytics</h2>
                    <span class="badge">Subject and Teacher Wise</span>
                </div>
                <div class="table-wrap">
                    <table class="erp-table">
                        <thead><tr><th>Subject</th><th>Teacher</th><th>Appeared</th><th>Passed</th><th>Failed/RA</th><th>Pass %</th><th>Highest</th><th>Lowest</th><th>Average</th></tr></thead>
                        <tbody>
                            <?php if (!$resultRows): ?>
                                <tr><td colspan="9">No result records available yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($resultRows as $row): ?>
                                <?php $rowPassPercent = erp_percent((int)$row['passed'], (int)$row['passed'] + (int)$row['failed']); ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['subject_code']) ?></strong><br><?= htmlspecialchars($row['subject_name']) ?></td>
                                    <td><?= htmlspecialchars($row['teacher_name']) ?></td>
                                    <td><?= htmlspecialchars((string)$row['appeared']) ?></td>
                                    <td><?= htmlspecialchars((string)$row['passed']) ?></td>
                                    <td><?= htmlspecialchars((string)$row['failed']) ?></td>
                                    <td><?= $rowPassPercent ?>%</td>
                                    <td><?= htmlspecialchars((string)($row['highest_mark'] ?? 0)) ?></td>
                                    <td><?= htmlspecialchars((string)($row['lowest_mark'] ?? 0)) ?></td>
                                    <td><?= htmlspecialchars((string)($row['average_mark'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel" id="teacher-mapping">
                <div class="panel-header">
                    <h2>Teacher Name Mapping</h2>
                    <span class="badge gold">subjects.subject_code</span>
                </div>
                <div class="table-wrap">
                    <table class="erp-table">
                        <thead><tr><th>Subject Code</th><th>Subject Name</th><th>Teacher Name</th><th>Department</th><th>Semester</th><th>Batch</th></tr></thead>
                        <tbody>
                            <?php if (!$teacherRows): ?>
                                <tr><td colspan="6">No teacher allocations available yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($teacherRows as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['subject_code']) ?></td>
                                    <td><?= htmlspecialchars($row['subject_name']) ?></td>
                                    <td><?= htmlspecialchars($row['teacher_name']) ?></td>
                                    <td><?= htmlspecialchars($row['department']) ?></td>
                                    <td><?= htmlspecialchars((string)$row['semester']) ?></td>
                                    <td><?= htmlspecialchars($row['batch']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel" id="batch-report">
                <div class="panel-header">
                    <h2>Batch Report</h2>
                    <span class="badge">Tutor and Performance</span>
                </div>
                <div class="table-wrap">
                    <table class="erp-table">
                        <thead><tr><th>Batch Name</th><th>Tutor Name</th><th>Students</th><th>Pass %</th><th>Arrears</th><th>Placement %</th><th>Achievements</th><th>Sports</th></tr></thead>
                        <tbody>
                            <?php if (!$batchRows): ?>
                                <tr><td colspan="8">No batch records available yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($batchRows as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['batch_name']) ?></td>
                                    <td><?= htmlspecialchars($row['tutor_name']) ?></td>
                                    <td><?= htmlspecialchars((string)$row['student_count']) ?></td>
                                    <td><?= erp_percent((int)$row['pass_records'], (int)$row['result_records']) ?>%</td>
                                    <td><?= htmlspecialchars((string)$row['arrears']) ?></td>
                                    <td><?= $students ? erp_percent($placementCount, $students) : 0 ?>%</td>
                                    <td><?= htmlspecialchars((string)$achievementCount) ?></td>
                                    <td><?= htmlspecialchars((string)$sportsCount) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel" id="admissions">
                <div class="panel-header">
                    <h2>Admission Analytics</h2>
                    <span class="badge">Department and Batch Wise</span>
                </div>
                <div class="table-wrap">
                    <table class="erp-table">
                        <thead><tr><th>Department</th><th>Batch/Year</th><th>Total Admissions</th><th>Government Quota</th><th>Management Quota</th><th>Lateral Entry</th><th>Male</th><th>Female</th><th>Category Wise</th></tr></thead>
                        <tbody>
                            <?php if (!$admissionRows): ?>
                                <tr><td colspan="9">No admission rows available yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($admissionRows as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['department']) ?></td>
                                    <td><?= htmlspecialchars($row['batch']) ?></td>
                                    <td><?= htmlspecialchars((string)$row['total']) ?></td>
                                    <td>Data pending</td>
                                    <td>Data pending</td>
                                    <td><?= htmlspecialchars((string)$row['lateral']) ?></td>
                                    <td>Data pending</td>
                                    <td>Data pending</td>
                                    <td><?= htmlspecialchars((string)$row['category_count']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel" id="reports">
                <div class="panel-header">
                    <h2>Admin Reports and Exports</h2>
                    <span class="badge gold">PDF Excel CSV Print</span>
                </div>
                <div class="module-grid">
                    <a href="nba_report.php?report_type=department">Department Report</a>
                    <a href="nba_report.php?report_type=batch">Batch Report</a>
                    <a href="nba_report.php?report_type=teacher">Teacher Report</a>
                    <a href="nba_report.php?report_type=student">Student Report</a>
                    <a href="nba_report.php?report_type=subject">Subject Report</a>
                    <a href="semester_details.php">Semester Report</a>
                    <a href="nba_report.php?report_type=nba">NBA Report</a>
                    <a href="nba_report.php?report_type=iqac">PSG PTC Report</a>
                    <a href="password_resets.php">Password Reset History</a>
                    <a href="erp_forms.php?scope=student">Student Form Reviews</a>
                    <a href="erp_forms.php?scope=staff">Staff Form Reviews</a>
                    <a href="erp_forms.php?scope=admin">Admin Forms</a>
                </div>
            </section>

            <section class="panel" id="legacy-modules">
                <div class="panel-header">
                    <h2>Preserved IQAC Modules</h2>
                    <span class="badge gold">PSG PTC ERP</span>
                </div>
                <div class="module-grid">
                    <a href="std_upload.php">Student Uploads</a>
                    <a href="std_achiev.php">Achievements</a>
                    <a href="std_publication.php">Publications</a>
                    <a href="std_higher.php">Higher Studies</a>
                    <a href="std_indus.php">Industry Visits</a>
                    <a href="std_sports_details.php">Sports</a>
                    <a href="staff_fdp.php">Staff FDP</a>
                    <a href="curr_gap.php">Curriculum Gap</a>
                    <a href="gallery.php">Gallery</a>
                    <a href="upload.php">Upload</a>
                    <a href="mou_index.php">MOU</a>
                    <a href="applications.php">Applications</a>
                    <a href="marks_entry.php">Marks Entry</a>
                    <a href="erp_forms.php?scope=student">Student Forms</a>
                    <a href="erp_forms.php?scope=staff">Staff Forms</a>
                    <a href="erp_forms.php?scope=admin">Admin Forms</a>
                </div>
            </section>
            <?php endif; ?>

            <section class="panel" id="notifications">
                <div class="panel-header">
                    <h2>Notifications</h2>
                    <span class="badge">AJAX Ready</span>
                </div>
                <?php if (!$notifications): ?>
                    <p>No notifications yet.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="erp-table">
                            <thead><tr><th>Title</th><th>Message</th><th>Date</th></tr></thead>
                            <tbody>
                                <?php foreach ($notifications as $note): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($note['title']) ?></td>
                                        <td><?= htmlspecialchars($note['message']) ?></td>
                                        <td><?= htmlspecialchars($note['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section class="panel" id="announcements">
                <div class="panel-header">
                    <h2>Announcements</h2>
                    <span class="badge">Admin HOD IQAC</span>
                </div>
                <?php if (!$announcements): ?>
                    <p>No announcements posted.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="erp-table">
                            <thead><tr><th>Title</th><th>Category</th><th>Date</th></tr></thead>
                            <tbody>
                                <?php foreach ($announcements as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['title']) ?></td>
                                        <td><?= htmlspecialchars($item['category'] ?? 'General') ?></td>
                                        <td><?= htmlspecialchars($item['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </section>
    </main>
</div>
<script>
setInterval(function () {
  fetch('api/marks_updates.php', {credentials: 'same-origin'})
    .then(function (response) { return response.ok ? response.json() : null; })
    .then(function (data) {
      if (!data || typeof data.pending_count === 'undefined') return;
      document.querySelectorAll('[data-pending-count]').forEach(function (node) {
        node.textContent = data.pending_count;
      });
    })
    .catch(function () {});
}, 15000);
</script>
<?php include __DIR__ . '/include/evidence_modal.php'; ?>
</body>
</html>
