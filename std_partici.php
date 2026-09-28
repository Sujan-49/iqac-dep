<?php
require_once __DIR__ . '/include/auth.php';
require_once __DIR__ . '/include/navigation.php';
require_once __DIR__ . '/include/student_portal_ui.php';

$user = iqac_require_login(['student', 'tutor', 'hod', 'iqac', 'admin', 'super_admin']);
$role = iqac_effective_role($user);
$message = '';
$error = '';
$token = iqac_csrf_token();
$hasTable = iqac_table_exists($conn, 'student_participation');
$studentProfile = null;

if ($role === 'student') {
    $stmt = $conn->prepare('SELECT student_id, full_name, roll_number, department, current_semester, batch FROM student_details WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $studentProfile = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!iqac_verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (!$hasTable) {
        $error = 'Participation table is not available in the current IQAC database.';
    } else {
        $studentId = $studentProfile ? (int)$studentProfile['student_id'] : (int)($_POST['id'] ?? 0);
        $eventName = trim((string)($_POST['event_name'] ?? ''));
        $eventPlace = trim((string)($_POST['event_place'] ?? ''));
        $participationDate = trim((string)($_POST['participation_date'] ?? ''));
        $level = trim((string)($_POST['level'] ?? ''));
        $certificateCopy = '';

        if (isset($_FILES['certificate_copy']) && $_FILES['certificate_copy']['error'] === UPLOAD_ERR_OK) {
            $targetDir = __DIR__ . '/uploads/';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            $fileName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($_FILES['certificate_copy']['name']));
            $certificateCopy = 'uploads/' . time() . '_' . $fileName;
            move_uploaded_file($_FILES['certificate_copy']['tmp_name'], __DIR__ . '/' . $certificateCopy);
        }

        $stmt = $conn->prepare('INSERT INTO student_participation (id, event_name, event_place, participation_date, level, certificate_copy) VALUES (?, ?, ?, ?, ?, ?)');
        if ($stmt) {
            $stmt->bind_param('isssss', $studentId, $eventName, $eventPlace, $participationDate, $level, $certificateCopy);
            if ($stmt->execute()) {
                $message = 'Participation record added successfully.';
                iqac_audit($conn, 'participation_upload', $user['id'], $role, $eventName);
                iqac_notify($conn, null, 'tutor', 'Participation submitted', (string)($studentProfile['full_name'] ?? $user['username']) . ' submitted participation: ' . $eventName, 'std_partici.php');
                iqac_notify($conn, null, 'admin', 'Participation submitted', (string)($studentProfile['full_name'] ?? $user['username']) . ' submitted participation: ' . $eventName, 'std_partici.php');
            } else {
                $error = 'Unable to save participation record.';
            }
            $stmt->close();
        } else {
            $error = 'Participation table columns do not match the legacy form.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Participation</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/erp.css">
</head>
<body>
<div class="erp-layout">
    <?php iqac_render_sidebar($user, 'std_partici.php'); ?>
    <main class="erp-main">
        <header class="erp-topbar">
            <div>
                <h1>Student Participation</h1>
                <span><?= htmlspecialchars($studentProfile['department'] ?? $user['department'] ?: 'Department Portal') ?></span>
            </div>
        </header>
        <section class="erp-content">
            <?php psg_render_student_hero($conn, $user, 'Participation Portfolio', 'Record co-curricular participation with clean PSG PTC verification workflows.'); ?>
            <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if (!$hasTable): ?><div class="alert">Participation records are not available in the current live schema. This module is protected and ready for the legacy table.</div><?php endif; ?>

            <section class="panel glass-panel">
                <div class="panel-header">
                    <h2>Participation Entry</h2>
                    <span class="badge gold">Protected Module</span>
                </div>
                <form method="post" enctype="multipart/form-data" class="erp-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                    <div class="form-grid">
                        <?php if ($studentProfile): ?>
                            <label><span>Student</span><input type="text" value="<?= htmlspecialchars($studentProfile['full_name']) ?>" readonly></label>
                            <input type="hidden" name="id" value="<?= (int)$studentProfile['student_id'] ?>">
                            <label><span>Roll Number</span><input type="text" value="<?= htmlspecialchars($studentProfile['roll_number']) ?>" readonly></label>
                        <?php else: ?>
                            <label><span>Student ID</span><input type="number" name="id" required></label>
                        <?php endif; ?>
                        <label><span>Event Name</span><input type="text" name="event_name" required></label>
                        <label><span>Event Place</span><input type="text" name="event_place" required></label>
                        <label><span>Participation Date</span><input type="date" name="participation_date" required></label>
                        <label><span>Level</span><select name="level" required><option value="National">National</option><option value="State">State</option><option value="District">District</option><option value="Local">Local</option></select></label>
                        <label class="wide upload-zone"><strong>Certificate Copy</strong><span data-file-name>Drop file here or browse</span><input type="file" name="certificate_copy" data-file-preview></label>
                    </div>
                    <button class="primary-btn" type="submit" <?= !$hasTable ? 'disabled' : '' ?>>Submit</button>
                </form>
            </section>
        </section>
    </main>
</div>
<?php psg_render_portal_scripts(); ?>
</body>
</html>
