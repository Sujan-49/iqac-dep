<?php
require_once __DIR__ . '/include/auth.php';
require_once __DIR__ . '/include/navigation.php';
require_once __DIR__ . '/include/student_portal_ui.php';

$user = iqac_require_login(['student', 'tutor', 'hod', 'iqac', 'admin', 'super_admin']);
$role = iqac_effective_role($user);
$message = '';
$error = '';
$token = iqac_csrf_token();
$studentProfile = null;

if ($role === 'student') {
    $stmt = $conn->prepare('SELECT student_id, full_name, roll_number, department, current_semester, batch FROM student_details WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $studentProfile = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_sport'])) {
    if (!iqac_verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $studentId = $studentProfile ? (int)$studentProfile['student_id'] : (int)($_POST['student_id'] ?? 0);
        $sportName = trim((string)($_POST['sport_name'] ?? ''));
        $level = trim((string)($_POST['level'] ?? ''));
        $position = trim((string)($_POST['position'] ?? ''));
        $yearParticipated = (int)($_POST['year_participated'] ?? date('Y'));
        $certificatePath = '';

        if (isset($_FILES['sports_certificate']) && $_FILES['sports_certificate']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['sports_certificate']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
                $uploadDir = __DIR__ . '/uploads/certificates/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $certificatePath = 'uploads/certificates/' . uniqid('cert_', true) . '.' . $ext;
                move_uploaded_file($_FILES['sports_certificate']['tmp_name'], __DIR__ . '/' . $certificatePath);
            } else {
                $error = 'Invalid file type. Allowed: pdf, jpg, jpeg, png.';
            }
        }

        if ($error === '') {
            $stmt = $conn->prepare('INSERT INTO student_sports (student_id, sport_name, level, position, year_participated, certificate_path) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('isssis', $studentId, $sportName, $level, $position, $yearParticipated, $certificatePath);
            if ($stmt->execute()) {
                $message = 'Sports record added successfully.';
                iqac_audit($conn, 'sports_upload', $user['id'], $role, $sportName);
                iqac_notify($conn, null, 'tutor', 'Sports entry submitted', (string)($studentProfile['full_name'] ?? $user['username']) . ' submitted sports entry: ' . $sportName, 'std_sports_details.php');
                iqac_notify($conn, null, 'admin', 'Sports entry submitted', (string)($studentProfile['full_name'] ?? $user['username']) . ' submitted sports entry: ' . $sportName, 'std_sports_details.php');
            } else {
                $error = 'Unable to save sports record.';
            }
            $stmt->close();
        }
    }
}

$students = [];
if ($role !== 'student') {
    $result = $conn->query('SELECT student_id, full_name, roll_number FROM student_details ORDER BY full_name');
    $students = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

$where = '';
$types = '';
$params = [];
if ($studentProfile) {
    $where = 'WHERE ss.student_id = ?';
    $types = 'i';
    $params[] = (int)$studentProfile['student_id'];
}
$sql = "SELECT ss.*, sd.full_name, sd.roll_number, sd.department, sd.batch
        FROM student_sports ss
        INNER JOIN student_details sd ON sd.student_id = ss.student_id
        $where
        ORDER BY ss.created_at DESC, ss.id DESC";
if ($types !== '') {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $sportsRecords = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $result = $conn->query($sql);
    $sportsRecords = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

$total = count($sportsRecords);
$levels = [];
foreach ($sportsRecords as $record) {
    if (!empty($record['level'])) {
        $levels[$record['level']] = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sports Details</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/erp.css">
</head>
<body>
<div class="erp-layout">
    <?php iqac_render_sidebar($user, 'std_sports_details.php'); ?>
    <main class="erp-main">
        <header class="erp-topbar">
            <div>
                <h1>Sports Participation</h1>
                <span><?= htmlspecialchars($studentProfile['department'] ?? $user['department'] ?: 'Department Portal') ?></span>
            </div>
            <div class="toolbar-actions">
                <button class="secondary-btn" type="button" onclick="window.print()">Print</button>
                <button class="primary-btn" type="button" data-export-table="sports-table">Export</button>
            </div>
        </header>
        <section class="erp-content">
            <?php psg_render_student_hero($conn, $user, 'Sports Portfolio', 'Track sports participation, levels, certificates, and PSG PTC activity records.'); ?>
            <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <div class="stat-grid">
                <article class="stat-card"><span>Total Sports Records</span><strong><?= $total ?></strong><em>Visible participation records</em></article>
                <article class="stat-card"><span>Competition Levels</span><strong><?= count($levels) ?></strong><em>District, state, national etc.</em></article>
                <article class="stat-card"><span>Certificates</span><strong><?= count(array_filter($sportsRecords, fn($r) => !empty($r['certificate_path']))) ?></strong><em>Uploaded evidence</em></article>
                <article class="stat-card"><span>This Year</span><strong><?= count(array_filter($sportsRecords, fn($r) => (int)$r['year_participated'] === (int)date('Y'))) ?></strong><em><?= date('Y') ?></em></article>
            </div>

            <section class="panel glass-panel">
                <div class="panel-header">
                    <h2>Add Sports Record</h2>
                    <span class="badge gold">Auto-filled student details</span>
                </div>
                <form method="post" enctype="multipart/form-data" class="erp-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                    <div class="form-grid">
                        <?php if ($studentProfile): ?>
                            <label><span>Student</span><input type="text" value="<?= htmlspecialchars($studentProfile['full_name']) ?>" readonly></label>
                            <input type="hidden" name="student_id" value="<?= (int)$studentProfile['student_id'] ?>">
                            <label><span>Roll Number</span><input type="text" value="<?= htmlspecialchars($studentProfile['roll_number']) ?>" readonly></label>
                        <?php else: ?>
                            <label><span>Student</span><select name="student_id" required><option value="">Select student</option><?php foreach ($students as $student): ?><option value="<?= (int)$student['student_id'] ?>"><?= htmlspecialchars($student['full_name'] . ' - ' . $student['roll_number']) ?></option><?php endforeach; ?></select></label>
                        <?php endif; ?>
                        <label><span>Sport Name</span><input type="text" name="sport_name" required></label>
                        <label><span>Level</span><input type="text" name="level" required></label>
                        <label><span>Position / Achievement</span><input type="text" name="position"></label>
                        <label><span>Year Participated</span><input type="number" name="year_participated" value="<?= date('Y') ?>" required></label>
                        <label class="wide upload-zone"><strong>Certificate Preview / Upload</strong><span data-file-name>Drop file here or browse</span><input type="file" name="sports_certificate" accept=".pdf,.jpg,.jpeg,.png" data-file-preview><small>Allowed: PDF, JPG, JPEG, PNG.</small></label>
                    </div>
                    <button class="primary-btn" type="submit" name="add_sport">Add Sports Record</button>
                </form>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <h2>Sports History</h2>
                    <div class="table-tools"><input class="table-search" type="search" placeholder="Search sports records" data-search-table="sports-table"></div>
                </div>
                <div class="table-wrap">
                    <table class="erp-table interactive-table" id="sports-table">
                        <thead><tr><th>Student</th><th>Roll Number</th><th>Sport</th><th>Level</th><th>Position</th><th>Year</th><th>Certificate</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php if (!$sportsRecords): ?><tr><td colspan="8">No sports records found.</td></tr><?php endif; ?>
                            <?php foreach ($sportsRecords as $record): ?>
                                <tr>
                                    <td><?= htmlspecialchars($record['full_name']) ?></td>
                                    <td><?= htmlspecialchars($record['roll_number']) ?></td>
                                    <td><?= htmlspecialchars($record['sport_name']) ?></td>
                                    <td><?= htmlspecialchars((string)$record['level']) ?></td>
                                    <td><?= htmlspecialchars((string)$record['position']) ?></td>
                                    <td><?= htmlspecialchars((string)$record['year_participated']) ?></td>
                                    <td><?php if (!empty($record['certificate_path'])): ?><a href="<?= htmlspecialchars($record['certificate_path']) ?>" target="_blank">View File</a><?php else: ?>N/A<?php endif; ?></td>
                                    <td><span class="status-badge verified">Recorded</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </section>
    </main>
</div>
<?php psg_render_portal_scripts(); ?>
</body>
</html>
