<?php
require_once __DIR__ . '/include/auth.php';
require_once __DIR__ . '/include/navigation.php';
require_once __DIR__ . '/include/student_portal_ui.php';

$user = iqac_require_login(['student', 'tutor', 'hod', 'iqac', 'admin', 'super_admin']);
$role = iqac_effective_role($user);
$studentId = $role === 'student' ? (int)($_SESSION['student_id'] ?? 0) : (int)($_GET['student_id'] ?? 0);
$student = [];

if ($studentId > 0 && iqac_table_exists($conn, 'student_details')) {
    $stmt = $conn->prepare('SELECT sd.*, st.full_name AS tutor_name FROM student_details sd LEFT JOIN staff_details st ON st.staff_id = sd.tutor_staff_id WHERE sd.student_id = ? LIMIT 1');
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();
}

$profile = $student ?: psg_student_profile($conn, $user);
$achievementRows = [];
if ($studentId > 0 && iqac_table_exists($conn, 'student_achievements')) {
    $stmt = $conn->prepare('SELECT title, achievement_type, achievement_date, status FROM student_achievements WHERE student_id = ? ORDER BY created_at DESC LIMIT 20');
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $achievementRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$sportsRows = [];
if ($studentId > 0 && iqac_table_exists($conn, 'student_sports')) {
    $stmt = $conn->prepare('SELECT sport_name, level, position, year_participated FROM student_sports WHERE student_id = ? ORDER BY created_at DESC LIMIT 20');
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $sportsRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$applicationRows = [];
if ($studentId > 0 && iqac_table_exists($conn, 'applications')) {
    $stmt = $conn->prepare('SELECT application_type, title, status, current_level, updated_at FROM applications WHERE student_id = ? ORDER BY updated_at DESC LIMIT 20');
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $applicationRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$academicRows = [];
if ($studentId > 0 && iqac_table_exists($conn, 'academic_details')) {
    $stmt = $conn->prepare('SELECT * FROM academic_details WHERE student_id = ? ORDER BY id DESC');
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $academicRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$nonAcademicRows = [];
if ($studentId > 0 && iqac_table_exists($conn, 'non_academic_details')) {
    $stmt = $conn->prepare('SELECT * FROM non_academic_details WHERE student_id = ? ORDER BY id DESC');
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $nonAcademicRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Profile View | PSG PTC ERP</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/erp.css">
</head>
<body>
<div class="erp-layout">
    <?php iqac_render_sidebar($user, 'std_index.php'); ?>
    <main class="erp-main">
        <header class="erp-topbar">
            <div><h1>Student Profile View</h1><span>PSG Polytechnic College</span></div>
            <div class="toolbar-actions"><button class="secondary-btn" type="button" onclick="window.print()">Print</button><a class="primary-btn" href="<?= htmlspecialchars(iqac_role_home($role)) ?>">Dashboard</a></div>
        </header>
        <section class="erp-content">
            <?php psg_render_student_hero($conn, $user, 'Student Record Summary', 'Profile, achievements, sports, and application activity in a PSG PTC ERP format.'); ?>

            <section class="panel glass-panel">
                <div class="panel-header"><h2>Student Details</h2><span class="badge gold"><?= htmlspecialchars((string)($profile['roll_number'] ?? $user['username'])) ?></span></div>
                <div class="profile-grid">
                    <?php foreach ([
                        'Name' => $profile['full_name'] ?? '',
                        'Department' => $profile['department'] ?? '',
                        'Batch' => $profile['batch'] ?? '',
                        'Section' => $profile['section'] ?? '',
                        'Semester' => $profile['current_semester'] ?? '',
                        'Tutor' => $profile['tutor_name'] ?? 'Not assigned',
                        'Phone' => $profile['student_phone'] ?? '-',
                        'Parent Phone' => $profile['parent_phone'] ?? '-',
                        'Status' => $profile['stage1_status'] ?? $profile['status'] ?? 'Active',
                    ] as $label => $value): ?>
                        <div><span><?= htmlspecialchars($label) ?></span><strong><?= htmlspecialchars((string)$value) ?></strong></div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="panel">
                <div class="panel-header"><h2>Academic Qualifications</h2><span class="badge"><?= count($academicRows) ?> Records</span></div>
                <div class="table-wrap"><table class="erp-table interactive-table"><thead><tr><th>Institution</th><th>Type</th><th>Total Marks</th><th>Percentage</th><th>Year</th><th>Roll No</th></tr></thead><tbody>
                <?php if (!$academicRows): ?><tr><td colspan="6">No academic records available.</td></tr><?php endif; ?>
                <?php foreach ($academicRows as $row): ?><tr><td><?= htmlspecialchars((string)($row['institution_name'] ?? '')) ?></td><td><?= htmlspecialchars((string)($row['institution_type'] ?? '')) ?></td><td><?= htmlspecialchars((string)($row['total_marks'] ?? '')) ?></td><td><?= htmlspecialchars((string)($row['percentage'] ?? '')) ?></td><td><?= htmlspecialchars((string)($row['year_completed'] ?? '')) ?></td><td><?= htmlspecialchars((string)($row['roll_number'] ?? '')) ?></td></tr><?php endforeach; ?>
                </tbody></table></div>
            </section>

            <section class="panel">
                <div class="panel-header"><h2>Achievements</h2><span class="badge"><?= count($achievementRows) ?> Records</span></div>
                <div class="table-wrap"><table class="erp-table interactive-table"><thead><tr><th>Title</th><th>Type</th><th>Date</th><th>Status</th></tr></thead><tbody>
                <?php if (!$achievementRows): ?><tr><td colspan="4">No achievement records found.</td></tr><?php endif; ?>
                <?php foreach ($achievementRows as $row): ?><tr><td><?= htmlspecialchars($row['title']) ?></td><td><?= htmlspecialchars((string)$row['achievement_type']) ?></td><td><?= htmlspecialchars((string)$row['achievement_date']) ?></td><td><span class="status-badge <?= $row['status'] === 'approved' ? 'verified' : 'pending' ?>"><?= htmlspecialchars($row['status']) ?></span></td></tr><?php endforeach; ?>
                </tbody></table></div>
            </section>

            <section class="panel">
                <div class="panel-header"><h2>Non-Academic Activities</h2><span class="badge"><?= count($nonAcademicRows) ?> Records</span></div>
                <div class="table-wrap"><table class="erp-table interactive-table"><thead><tr><th>Activity Type</th><th>Organization</th><th>Role</th><th>Duration</th><th>Remarks</th></tr></thead><tbody>
                <?php if (!$nonAcademicRows): ?><tr><td colspan="5">No non-academic activities.</td></tr><?php endif; ?>
                <?php foreach ($nonAcademicRows as $row): ?><tr><td><?= htmlspecialchars((string)($row['activity_type'] ?? '')) ?></td><td><?= htmlspecialchars((string)($row['organization_name'] ?? '')) ?></td><td><?= htmlspecialchars((string)($row['role'] ?? '')) ?></td><td><?= htmlspecialchars((string)($row['duration'] ?? '')) ?></td><td><?= htmlspecialchars((string)($row['remarks'] ?? '')) ?></td></tr><?php endforeach; ?>
                </tbody></table></div>
            </section>

            <section class="panel">
                <div class="panel-header"><h2>Sports & Applications</h2><span class="badge">Activity Summary</span></div>
                <div class="table-wrap"><table class="erp-table interactive-table"><thead><tr><th>Category</th><th>Title</th><th>Detail</th><th>Status</th></tr></thead><tbody>
                <?php if (!$sportsRows && !$applicationRows): ?><tr><td colspan="4">No sports or application records found.</td></tr><?php endif; ?>
                <?php foreach ($sportsRows as $row): ?><tr><td>Sports</td><td><?= htmlspecialchars($row['sport_name']) ?></td><td><?= htmlspecialchars(($row['level'] ?? '') . ' ' . ($row['position'] ?? '') . ' ' . ($row['year_participated'] ?? '')) ?></td><td><span class="status-badge verified">Recorded</span></td></tr><?php endforeach; ?>
                <?php foreach ($applicationRows as $row): ?><tr><td><?= htmlspecialchars($row['application_type']) ?></td><td><?= htmlspecialchars($row['title']) ?></td><td><?= htmlspecialchars($row['current_level']) ?></td><td><span class="status-badge <?= $row['status'] === 'approved' ? 'verified' : 'pending' ?>"><?= htmlspecialchars($row['status']) ?></span></td></tr><?php endforeach; ?>
                </tbody></table></div>
            </section>
        </section>
    </main>
</div>
</body>
</html>
