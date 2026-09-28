<?php
require_once __DIR__ . '/include/auth.php';
require_once __DIR__ . '/include/navigation.php';
require_once __DIR__ . '/include/student_portal_ui.php';

$user = iqac_require_login(['student']);
$student = null;
$stmt = $conn->prepare('SELECT sd.*, st.full_name AS tutor_name FROM student_details sd LEFT JOIN staff_details st ON st.staff_id = sd.tutor_staff_id WHERE sd.user_id = ? LIMIT 1');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

$studentId = (int)($student['student_id'] ?? 0);
function student_count_for(string $table, string $studentColumn, int $studentId): int
{
    global $conn;
    if (!$studentId || !iqac_table_exists($conn, $table) || !iqac_column_exists($conn, $table, $studentColumn)) {
        return 0;
    }
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM `$table` WHERE `$studentColumn` = ?");
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $total = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();
    return $total;
}

$marks = student_count_for('student_marks', 'student_id', $studentId);
$achievements = student_count_for(iqac_table_exists($conn, 'student_achievements') ? 'student_achievements' : 'achievement_details', 'student_id', $studentId);
$sports = student_count_for('student_sports', 'student_id', $studentId);
$applications = student_count_for('applications', 'student_id', $studentId);
$industry = iqac_table_exists($conn, 'industry_visits') ? student_count_for('industry_visits', 'student_id', $studentId) : 0;
$formCounts = ['submitted' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
if (iqac_table_exists($conn, 'erp_form_submissions')) {
    $stmt = $conn->prepare('SELECT status, COUNT(*) AS total FROM erp_form_submissions WHERE submitted_by_user_id = ? GROUP BY status');
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $formCounts[$row['status']] = (int)$row['total'];
    }
    $stmt->close();
}
$publications = 0;
if (!empty($student['roll_number']) && iqac_table_exists($conn, 'student_publications')) {
    $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM student_publications WHERE roll_no = ?');
    $stmt->bind_param('s', $student['roll_number']);
    $stmt->execute();
    $publications = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/erp.css">
</head>
<body>
<div class="erp-layout">
    <?php iqac_render_sidebar($user, 'student_dashboard.php'); ?>
    <main class="erp-main">
        <header class="erp-topbar">
            <div>
                <h1>Student Dashboard</h1>
                <span><?= htmlspecialchars($student['roll_number'] ?? $user['username']) ?></span>
            </div>
            <a class="secondary-btn" href="marks_entry.php">Open My Marks</a>
        </header>
        <section class="erp-content">
            <?php psg_render_student_hero($conn, $user, 'Student Dashboard', 'Your PSG PTC academic workspace, profile summary, and activity status.'); ?>

            <div class="stat-grid">
                <article class="stat-card"><span>Marks</span><strong><?= $marks ?></strong><em>Semester records</em></article>
                <article class="stat-card"><span>Achievements</span><strong><?= $achievements ?></strong><em>Student activities</em></article>
                <article class="stat-card"><span>Sports</span><strong><?= $sports ?></strong><em>Participation</em></article>
                <article class="stat-card"><span>Publications</span><strong><?= $publications ?></strong><em>Research output</em></article>
                <article class="stat-card"><span>Industry Visits</span><strong><?= $industry ?></strong><em>Visits recorded</em></article>
                <article class="stat-card"><span>Applications</span><strong><?= $applications ?></strong><em>Submitted requests</em></article>
                <article class="stat-card"><span>Pending Forms</span><strong><?= $formCounts['pending'] + $formCounts['submitted'] ?></strong><em>ERP workflow</em></article>
                <article class="stat-card"><span>Approved Forms</span><strong><?= $formCounts['approved'] ?></strong><em>Completed requests</em></article>
            </div>

            <section class="panel">
                <div class="panel-header"><h2>Student Modules</h2><span class="badge gold">Allowed access only</span></div>
                <div class="module-grid">
                    <a href="marks_entry.php">Semester Marks</a>
                    <a href="std_achiev.php">Achievements</a>
                    <a href="std_sports_details.php">Sports</a>
                    <a href="std_publication.php">Publications</a>
                    <a href="std_indus.php">Industry Visit</a>
                    <a href="std_higher.php">Higher Studies</a>
                    <a href="applications.php">Applications</a>
                    <a href="erp_forms.php?scope=student">Student Forms</a>
                    <a href="std_index.php">Profile</a>
                </div>
            </section>

            <section class="panel">
                <div class="panel-header"><h2>Form Status</h2><span class="badge">Submitted Pending Approved Rejected</span></div>
                <div class="stat-grid">
                    <article class="stat-card"><span>Submitted</span><strong><?= $formCounts['submitted'] ?></strong></article>
                    <article class="stat-card"><span>Pending</span><strong><?= $formCounts['pending'] ?></strong></article>
                    <article class="stat-card"><span>Approved</span><strong><?= $formCounts['approved'] ?></strong></article>
                    <article class="stat-card"><span>Rejected</span><strong><?= $formCounts['rejected'] ?></strong></article>
                </div>
            </section>

            <section class="panel" id="notifications">
                <div class="panel-header"><h2>Notifications</h2><span class="badge">Student</span></div>
                <p>No new student notifications.</p>
            </section>
        </section>
    </main>
</div>
</body>
</html>
