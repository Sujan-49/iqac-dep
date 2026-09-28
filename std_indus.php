<?php
require_once __DIR__ . '/include/auth.php';
require_once __DIR__ . '/include/navigation.php';
require_once __DIR__ . '/include/student_portal_ui.php';

$user = iqac_require_login(['student', 'tutor', 'hod', 'iqac', 'admin', 'super_admin']);
$role = iqac_effective_role($user);
$message = '';
$error = '';
$token = iqac_csrf_token();
$hasIndustryTable = iqac_table_exists($conn, 'industry_visits');

$studentTable = iqac_student_table($conn);
$studentPk = $studentTable ? iqac_student_pk($conn, $studentTable) : null;
$studentNameExpr = $studentTable ? iqac_student_name_expr($studentTable, 's') : 'NULL';
$studentProfile = null;

if ($role === 'student') {
    $stmt = $conn->prepare('SELECT student_id, roll_number, full_name, department, current_semester FROM student_details WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $studentProfile = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
}

function indus_column(string $column): bool
{
    global $conn;
    return iqac_table_exists($conn, 'industry_visits') && iqac_column_exists($conn, 'industry_visits', $column);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['industry_save'])) {
    if (!iqac_verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (!$hasIndustryTable) {
        $error = 'Industry visit table is not available in the current IQAC database.';
    } else {
        $studentId = $studentProfile ? (int)$studentProfile['student_id'] : (int)($_POST['student_id'] ?? 0);
        $rollNumber = $studentProfile ? (string)$studentProfile['roll_number'] : trim((string)($_POST['roll_number'] ?? ''));
        $courseName = trim((string)($_POST['course_name'] ?? ''));
        $subjectName = trim((string)($_POST['subject_name'] ?? ''));
        $companyName = trim((string)($_POST['company_name'] ?? ''));
        $visitDuration = trim((string)($_POST['visit_duration'] ?? ''));
        $evidencePath = '';

        if (isset($_FILES['evidence_file']) && $_FILES['evidence_file']['error'] === UPLOAD_ERR_OK && indus_column('evidence_file')) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileName = basename($_FILES['evidence_file']['name']);
            $target = 'uploads/' . time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $fileName);
            if (move_uploaded_file($_FILES['evidence_file']['tmp_name'], __DIR__ . '/' . $target)) {
                $evidencePath = $target;
            }
        }

        $columns = ['student_id', 'roll_number', 'course_name', 'subject_name', 'company_name', 'visit_duration'];
        $values = [$studentId, $rollNumber, $courseName, $subjectName, $companyName, $visitDuration];
        $types = 'isssss';
        if (indus_column('evidence_file')) {
            $columns[] = 'evidence_file';
            $values[] = $evidencePath;
            $types .= 's';
        }
        $columnSql = implode(', ', $columns);
        $placeholderSql = implode(', ', array_fill(0, count($columns), '?'));
        $stmt = $conn->prepare("INSERT INTO industry_visits ($columnSql) VALUES ($placeholderSql)");
        if ($stmt) {
            $stmt->bind_param($types, ...$values);
            if ($stmt->execute()) {
                $message = 'Industry visit record added successfully.';
                iqac_audit($conn, 'industry_visit_submitted', $user['id'], $role, $companyName);
                iqac_notify($conn, null, 'tutor', 'Industry visit submitted', (string)($studentProfile['full_name'] ?? $user['username']) . ' submitted visit: ' . $companyName, 'std_indus.php');
                iqac_notify($conn, null, 'admin', 'Industry visit submitted', (string)($studentProfile['full_name'] ?? $user['username']) . ' submitted visit: ' . $companyName, 'std_indus.php');
            } else {
                $error = 'Unable to save industry visit record.';
            }
            $stmt->close();
        } else {
            $error = 'Industry visit table columns do not match the legacy form.';
        }
    }
}

$students = [];
if ($studentTable && $studentPk) {
    $studentSql = $studentTable === 'student_details'
        ? 'SELECT student_id AS id, full_name AS name, roll_number FROM student_details ORDER BY full_name'
        : "SELECT `$studentPk` AS id, $studentNameExpr AS name, register_number AS roll_number FROM `$studentTable` s ORDER BY name";
    $result = $conn->query($studentSql);
    $students = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

$records = [];
if ($hasIndustryTable && $studentTable && $studentPk) {
    $where = '';
    $types = '';
    $params = [];
    if ($role === 'student' && $studentProfile) {
        $where = 'WHERE iv.student_id = ?';
        $types = 'i';
        $params[] = (int)$studentProfile['student_id'];
    }
    $rollExpr = indus_column('roll_number') ? 'iv.roll_number' : "''";
    $courseExpr = indus_column('course_name') ? 'iv.course_name' : "''";
    $subjectExpr = indus_column('subject_name') ? 'iv.subject_name' : "''";
    $durationExpr = indus_column('visit_duration') ? 'iv.visit_duration' : "COALESCE(iv.duration, '')";
    $evidenceExpr = indus_column('evidence_file') ? 'iv.evidence_file' : "''";
    $statusExpr = indus_column('status') ? 'iv.status' : "'recorded'";
    $sql = "SELECT iv.*, $studentNameExpr AS student_name, $rollExpr AS roll_number, $courseExpr AS course_name,
                   $subjectExpr AS subject_name, iv.company_name, $durationExpr AS visit_duration,
                   $evidenceExpr AS evidence_file, $statusExpr AS status_label
            FROM industry_visits iv
            JOIN `$studentTable` s ON iv.student_id = s.`$studentPk`
            $where
            ORDER BY iv.id DESC";
    if ($types !== '') {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $result = $conn->query($sql);
        $records = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}

$total = count($records);
$approved = 0;
$pending = 0;
$industries = [];
foreach ($records as $record) {
    $status = strtolower((string)$record['status_label']);
    if ($status === 'approved') {
        $approved++;
    } elseif ($status === 'pending') {
        $pending++;
    }
    if (!empty($record['company_name'])) {
        $industries[$record['company_name']] = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Industry Visits</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/erp.css">
</head>
<body>
<div class="erp-layout">
    <?php iqac_render_sidebar($user, 'std_indus.php'); ?>
    <main class="erp-main">
        <header class="erp-topbar">
            <div>
                <h1>Industry Visit Management</h1>
                <span><?= htmlspecialchars($studentProfile['department'] ?? $user['department'] ?: 'Department Portal') ?></span>
            </div>
            <div class="toolbar-actions">
                <button class="secondary-btn" type="button" data-view="timeline">Timeline</button>
                <button class="secondary-btn" type="button" data-view="table">Table</button>
                <button class="secondary-btn" type="button" data-view="gallery">Gallery</button>
            </div>
        </header>

        <section class="erp-content">
            <?php psg_render_student_hero($conn, $user, 'Industry Visit Portfolio', 'Track industry exposure, visit evidence, and approval-ready activity records.'); ?>
            <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if (!$hasIndustryTable): ?><div class="alert">Industry visit records are not available in the current live schema. The page is ready and will render records when the legacy table exists.</div><?php endif; ?>

            <div class="stat-grid">
                <article class="stat-card"><span>Total Visits</span><strong><?= $total ?></strong><em>All visible records</em></article>
                <article class="stat-card success"><span>Approved Visits</span><strong><?= $approved ?></strong><em>Approval workflow</em></article>
                <article class="stat-card warn"><span>Pending Visits</span><strong><?= $pending ?></strong><em>Awaiting action</em></article>
                <article class="stat-card"><span>Industries Visited</span><strong><?= count($industries) ?></strong><em>Unique companies</em></article>
            </div>

            <section class="panel glass-panel">
                <div class="panel-header">
                    <h2>Add Industry Visit</h2>
                    <span class="badge gold">Evidence upload supported when legacy column exists</span>
                </div>
                <form method="post" enctype="multipart/form-data" class="erp-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                    <div class="form-grid">
                        <?php if ($studentProfile): ?>
                            <label><span>Student</span><input type="text" value="<?= htmlspecialchars($studentProfile['full_name']) ?>" readonly></label>
                            <input type="hidden" name="student_id" value="<?= (int)$studentProfile['student_id'] ?>">
                            <label><span>Roll Number</span><input type="text" name="roll_number" value="<?= htmlspecialchars($studentProfile['roll_number']) ?>" readonly></label>
                        <?php else: ?>
                            <label><span>Student</span><select name="student_id" required><option value="">Select student</option><?php foreach ($students as $student): ?><option value="<?= (int)$student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option><?php endforeach; ?></select></label>
                            <label><span>Roll Number</span><input type="text" name="roll_number" required></label>
                        <?php endif; ?>
                        <label><span>Course Name</span><input type="text" name="course_name" required></label>
                        <label><span>Subject Name</span><input type="text" name="subject_name" required></label>
                        <label><span>Company Name</span><input type="text" name="company_name" required></label>
                        <label><span>Visit Duration</span><input type="text" name="visit_duration" required></label>
                        <label><span>Industry Type</span><select disabled><option>Academic Visit</option><option>Internship</option><option>Training</option></select></label>
                        <label class="upload-zone"><strong>Company Logo Preview</strong><span data-file-name>Preview only</span><input type="file" disabled></label>
                        <label class="wide upload-zone"><strong>Evidence Upload</strong><span data-file-name>Drop file here or browse</span><input type="file" name="evidence_file" data-file-preview><small>Stored only when the existing table has an evidence_file column.</small></label>
                    </div>
                    <button class="primary-btn" type="submit" name="industry_save" <?= !$hasIndustryTable ? 'disabled' : '' ?>>Add Industry Visit</button>
                </form>
            </section>

            <section class="panel view-panel" data-panel="timeline">
                <div class="panel-header">
                    <h2>Visit Timeline</h2>
                    <span class="badge">Timeline View</span>
                </div>
                <div class="timeline-list">
                    <?php if (!$records): ?><p>No industry visit records found.</p><?php endif; ?>
                    <?php foreach ($records as $record): ?>
                        <article>
                            <span><?= htmlspecialchars((string)$record['status_label']) ?></span>
                            <strong><?= htmlspecialchars((string)$record['company_name']) ?></strong>
                            <p><?= htmlspecialchars((string)$record['student_name']) ?> - <?= htmlspecialchars((string)$record['visit_duration']) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="panel view-panel" data-panel="table">
                <div class="panel-header">
                    <h2>Visit History</h2>
                    <div class="table-tools"><input class="table-search" type="search" placeholder="Search visits" data-search-table="industry-table"></div>
                </div>
                <div class="table-wrap">
                    <table class="erp-table interactive-table" id="industry-table">
                        <thead><tr><th>Student</th><th>Roll Number</th><th>Course</th><th>Subject</th><th>Company</th><th>Duration</th><th>Evidence</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php if (!$records): ?><tr><td colspan="8">No industry visit records found.</td></tr><?php endif; ?>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$record['student_name']) ?></td>
                                    <td><?= htmlspecialchars((string)$record['roll_number']) ?></td>
                                    <td><?= htmlspecialchars((string)$record['course_name']) ?></td>
                                    <td><?= htmlspecialchars((string)$record['subject_name']) ?></td>
                                    <td><?= htmlspecialchars((string)$record['company_name']) ?></td>
                                    <td><?= htmlspecialchars((string)$record['visit_duration']) ?></td>
                                    <td><?php if (!empty($record['evidence_file'])): ?><a href="<?= htmlspecialchars((string)$record['evidence_file']) ?>" target="_blank">View File</a><?php else: ?>N/A<?php endif; ?></td>
                                    <td><span class="status-badge <?= strtolower((string)$record['status_label']) === 'approved' ? 'verified' : 'pending' ?>"><?= htmlspecialchars((string)$record['status_label']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel view-panel" data-panel="gallery">
                <div class="panel-header">
                    <h2>Gallery View</h2>
                    <span class="badge gold">Evidence</span>
                </div>
                <div class="gallery-grid">
                    <?php if (!$records): ?><p>No gallery evidence found.</p><?php endif; ?>
                    <?php foreach ($records as $record): ?>
                        <article class="record-card">
                            <strong><?= htmlspecialchars((string)$record['company_name']) ?></strong>
                            <span><?= htmlspecialchars((string)$record['student_name']) ?></span>
                            <?php if (!empty($record['evidence_file'])): ?><a href="<?= htmlspecialchars((string)$record['evidence_file']) ?>" target="_blank">Open Evidence</a><?php else: ?><em>No evidence uploaded</em><?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </section>
    </main>
</div>
<?php psg_render_portal_scripts(); ?>
<script>
document.querySelectorAll('[data-view]').forEach(function (button) {
  button.addEventListener('click', function () {
    document.querySelectorAll('.view-panel').forEach(function (panel) {
      panel.style.display = panel.dataset.panel === button.dataset.view ? '' : 'none';
    });
  });
});
document.querySelector('[data-view="timeline"]')?.click();
</script>
</body>
</html>
