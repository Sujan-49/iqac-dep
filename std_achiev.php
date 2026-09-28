<?php
require_once __DIR__ . '/include/auth.php';
require_once __DIR__ . '/include/navigation.php';
require_once __DIR__ . '/include/student_portal_ui.php';

$user = iqac_require_login(['student', 'tutor', 'hod', 'iqac', 'admin', 'super_admin']);
$role = iqac_effective_role($user);
$token = iqac_csrf_token();
$message = '';
$error = '';
$studentProfile = psg_student_profile($conn, $user);
$isStudent = $role === 'student';
$activeTable = iqac_table_exists($conn, 'student_achievements') ? 'student_achievements' : (iqac_table_exists($conn, 'achievement_details') ? 'achievement_details' : null);
$levels = ['National Level', 'State Level', 'District Level', 'Local Level'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['achievement_save'])) {
    if (!iqac_verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } elseif ($activeTable === null) {
        $error = 'Achievement table is not available in the current IQAC database.';
    } else {
        $studentId = $isStudent ? (int)$studentProfile['student_id'] : (int)($_POST['student_id'] ?? 0);
        $title = trim((string)($_POST['achievement_title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $dateAwarded = trim((string)($_POST['date_awarded'] ?? ''));
        $level = trim((string)($_POST['achievement_level'] ?? 'General'));
        $certificatePath = '';
        $photoPath = '';
        foreach (['certificate' => 'uploads/certificates/', 'photo' => 'uploads/photos/'] as $input => $dir) {
            if (isset($_FILES[$input]) && $_FILES[$input]['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES[$input]['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
                    $folder = __DIR__ . '/' . $dir;
                    if (!is_dir($folder)) {
                        mkdir($folder, 0755, true);
                    }
                    $target = $dir . uniqid($input . '_', true) . '.' . $ext;
                    move_uploaded_file($_FILES[$input]['tmp_name'], __DIR__ . '/' . $target);
                    if ($input === 'certificate') {
                        $certificatePath = $target;
                    } else {
                        $photoPath = $target;
                    }
                }
            }
        }
        $evidenceNote = trim(($certificatePath ? "\nCertificate: " . $certificatePath : '') . ($photoPath ? "\nPhoto: " . $photoPath : ''));
        if ($evidenceNote !== '' && $activeTable === 'student_achievements') {
            $description = trim($description . "\n" . $evidenceNote);
        }

        if ($studentId <= 0 || $title === '') {
            $error = 'Student and achievement title are required.';
        } elseif ($activeTable === 'student_achievements') {
            $stmt = $conn->prepare("INSERT INTO student_achievements (student_id, title, achievement_type, description, achievement_date, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param('issss', $studentId, $title, $level, $description, $dateAwarded);
            if ($stmt->execute()) {
                $message = 'Achievement submitted successfully for verification.';
                iqac_audit($conn, 'achievement_submitted', $user['id'], $role, $title);
                iqac_notify($conn, null, 'tutor', 'Student achievement submitted', (string)$studentProfile['full_name'] . ' submitted achievement: ' . $title, 'std_achiev.php');
                iqac_notify($conn, null, 'admin', 'Student achievement submitted', (string)$studentProfile['full_name'] . ' submitted achievement: ' . $title, 'std_achiev.php');
            } else {
                $error = 'Unable to save achievement record.';
            }
            $stmt->close();
        } else {
            $stmt = $conn->prepare('INSERT INTO achievement_details (student_id, achievement_title, description, date_awarded, certificate_path, photo_path, achievement_level) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('issssss', $studentId, $title, $description, $dateAwarded, $certificatePath, $photoPath, $level);
            if ($stmt->execute()) {
                $message = 'Achievement added successfully.';
                iqac_audit($conn, 'achievement_submitted', $user['id'], $role, $title);
                iqac_notify($conn, null, 'tutor', 'Student achievement submitted', (string)$studentProfile['full_name'] . ' submitted achievement: ' . $title, 'std_achiev.php');
                iqac_notify($conn, null, 'admin', 'Student achievement submitted', (string)$studentProfile['full_name'] . ' submitted achievement: ' . $title, 'std_achiev.php');
            } else {
                $error = 'Unable to save achievement record.';
            }
            $stmt->close();
        }
    }
}

$students = $isStudent ? [] : iqac_student_list($conn);
$records = [];
if ($activeTable !== null) {
    $where = '';
    $types = '';
    $params = [];
    if ($isStudent && (int)$studentProfile['student_id'] > 0) {
        $where = 'WHERE a.student_id = ?';
        $types = 'i';
        $params[] = (int)$studentProfile['student_id'];
    }

    if ($activeTable === 'student_achievements') {
        $sql = "SELECT a.id, a.student_id, a.title AS achievement_title, a.description, a.achievement_date AS date_awarded,
                       '' AS certificate_path, '' AS photo_path, a.achievement_type AS achievement_level, a.status,
                       sd.full_name AS student_name, sd.roll_number
                FROM student_achievements a
                JOIN student_details sd ON sd.student_id = a.student_id
                $where
                ORDER BY a.created_at DESC, a.id DESC";
    } else {
        $studentTable = iqac_student_table($conn);
        $studentPk = iqac_student_pk($conn, $studentTable);
        $studentName = iqac_student_name_expr($studentTable, 'sd');
        $rollExpr = $studentTable === 'student_details' ? 'sd.roll_number' : "''";
        $sql = "SELECT a.*, $studentName AS student_name, $rollExpr AS roll_number, 'recorded' AS status
                FROM achievement_details a
                JOIN `$studentTable` sd ON sd.`$studentPk` = a.student_id
                $where
                ORDER BY a.id DESC";
    }

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

$approved = count(array_filter($records, fn(array $record): bool => strtolower((string)$record['status']) === 'approved' || strtolower((string)$record['status']) === 'recorded'));
$pending = count(array_filter($records, fn(array $record): bool => strtolower((string)$record['status']) === 'pending'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Achievements | PSG PTC ERP</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/erp.css">
</head>
<body>
<div class="erp-layout">
    <?php iqac_render_sidebar($user, 'std_achiev.php'); ?>
    <main class="erp-main">
        <header class="erp-topbar">
            <div>
                <h1>PSG PTC Achievements</h1>
                <span>Student recognition and activity verification</span>
            </div>
            <div class="toolbar-actions">
                <button class="secondary-btn" type="button" onclick="window.print()">Print</button>
                <button class="primary-btn" type="button" data-export-table="achievement-table" data-export-name="achievement_history">Export</button>
            </div>
        </header>
        <section class="erp-content">
            <?php psg_render_student_hero($conn, $user, 'Achievement Portfolio', 'Submit, track, and present student achievements with verification-ready records.'); ?>
            <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($activeTable === null): ?><div class="alert error">No achievement table is available in the current IQAC database.</div><?php endif; ?>

            <section class="panel glass-panel">
                <div class="panel-header">
                    <h2>Submit Achievement</h2>
                    <span class="badge gold"><?= htmlspecialchars($activeTable ?? 'Schema pending') ?></span>
                </div>
                <form method="post" enctype="multipart/form-data" class="erp-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                    <div class="form-grid">
                        <?php if ($isStudent): ?>
                            <label><span>Student</span><input type="text" value="<?= htmlspecialchars((string)$studentProfile['full_name']) ?>" readonly></label>
                            <input type="hidden" name="student_id" value="<?= (int)$studentProfile['student_id'] ?>">
                            <label><span>Register Number</span><input type="text" value="<?= htmlspecialchars((string)$studentProfile['roll_number']) ?>" readonly></label>
                        <?php else: ?>
                            <label><span>Student</span><select name="student_id" required><option value="">Select student</option><?php foreach ($students as $student): ?><option value="<?= (int)$student['id'] ?>"><?= htmlspecialchars($student['student_name'] . ($student['roll_number'] ? ' - ' . $student['roll_number'] : '')) ?></option><?php endforeach; ?></select></label>
                        <?php endif; ?>
                        <label><span>Achievement Title</span><input type="text" name="achievement_title" required></label>
                        <label><span>Achievement Level</span><select name="achievement_level" required><?php foreach ($levels as $level): ?><option value="<?= htmlspecialchars($level) ?>"><?= htmlspecialchars($level) ?></option><?php endforeach; ?></select></label>
                        <label><span>Date Awarded</span><input type="date" name="date_awarded"></label>
                        <label class="wide"><span>Description</span><textarea name="description" rows="4"></textarea></label>
                        <label class="wide upload-zone">
                            <strong>Certificate Upload</strong>
                            <span data-file-name>Drop file here or browse</span>
                            <input type="file" name="certificate" accept=".pdf,.jpg,.jpeg,.png" data-file-preview>
                        </label>
                        <label class="wide upload-zone">
                            <strong>Photo Preview Upload</strong>
                            <span data-file-name>Drop image here or browse</span>
                            <input type="file" name="photo" accept=".jpg,.jpeg,.png" data-file-preview>
                        </label>
                    </div>
                    <button class="primary-btn" type="submit" name="achievement_save" <?= $activeTable === null ? 'disabled' : '' ?>>Submit Achievement</button>
                </form>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <div><h2>Achievement Records</h2><span class="badge">Approved <?= $approved ?> | Pending <?= $pending ?></span></div>
                    <div class="table-tools">
                        <input class="table-search" type="search" placeholder="Search achievements" data-search-table="achievement-table">
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="erp-table interactive-table" id="achievement-table">
                        <thead><tr><th>Student</th><th>Register No</th><th>Title</th><th>Description</th><th>Date</th><th>Level</th><th>Evidence</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php if (!$records): ?><tr><td colspan="8">No achievement records found.</td></tr><?php endif; ?>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)$record['student_name']) ?></td>
                                <td><?= htmlspecialchars((string)$record['roll_number']) ?></td>
                                <td><strong><?= htmlspecialchars((string)$record['achievement_title']) ?></strong></td>
                                <td><?= htmlspecialchars((string)$record['description']) ?></td>
                                <td><?= htmlspecialchars((string)$record['date_awarded']) ?></td>
                                <td><?= htmlspecialchars((string)$record['achievement_level']) ?></td>
                                <td><?php if (!empty($record['certificate_path'])): ?><a href="<?= htmlspecialchars((string)$record['certificate_path']) ?>" target="_blank">View</a><?php else: ?>N/A<?php endif; ?></td>
                                <td><span class="status-badge <?= strtolower((string)$record['status']) === 'approved' || strtolower((string)$record['status']) === 'recorded' ? 'verified' : 'pending' ?>"><?= htmlspecialchars((string)$record['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="pagination-pills"><span>1</span><span><?= max(1, (int)ceil(count($records) / 10)) ?></span></div>
            </section>
        </section>
    </main>
</div>
<?php psg_render_portal_scripts(); ?>
</body>
</html>
