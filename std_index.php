<?php
require_once __DIR__ . '/include/auth.php';
require_once __DIR__ . '/include/navigation.php';
require_once __DIR__ . '/include/student_portal_ui.php';

$user = iqac_require_login(['student', 'admin', 'super_admin']);
$profile = psg_student_profile($conn, $user);
$token = iqac_csrf_token();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_profile' && $user['role'] === 'student') {
    if (!iqac_verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (!iqac_table_exists($conn, 'student_details')) {
        $error = 'Student profile table is not available.';
    } else {
        $fields = [
            'full_name' => trim((string)($_POST['full_name'] ?? '')),
            'department' => trim((string)($_POST['department'] ?? '')),
            'batch' => trim((string)($_POST['batch'] ?? '')),
            'section' => trim((string)($_POST['section'] ?? '')),
            'student_phone' => trim((string)($_POST['student_phone'] ?? '')),
            'parent_phone' => trim((string)($_POST['parent_phone'] ?? '')),
            'father_name' => trim((string)($_POST['father_name'] ?? '')),
            'mother_name' => trim((string)($_POST['mother_name'] ?? '')),
            'parent_occupation' => trim((string)($_POST['parent_occupation'] ?? '')),
            'address_full' => trim((string)($_POST['address_full'] ?? '')),
            'caste_category' => trim((string)($_POST['caste_category'] ?? '')),
            'marks_sslc' => (int)($_POST['marks_sslc'] ?? 0),
            'marks_hsc' => (int)($_POST['marks_hsc'] ?? 0),
            'current_semester' => (int)($_POST['current_semester'] ?? 1),
        ];
        $statusSql = (!iqac_student_is_approved((string)($profile['status'] ?? '')) && iqac_column_exists($conn, 'student_details', 'stage1_status')) ? ', stage1_status = "pending"' : '';
        $stmt = $conn->prepare("UPDATE student_details SET full_name=?, department=?, batch=?, section=?, student_phone=?, parent_phone=?, father_name=?, mother_name=?, parent_occupation=?, address_full=?, caste_category=?, marks_sslc=?, marks_hsc=?, current_semester=?$statusSql WHERE user_id=?");
        $stmt->bind_param(
            'sssssssssssiiii',
            $fields['full_name'],
            $fields['department'],
            $fields['batch'],
            $fields['section'],
            $fields['student_phone'],
            $fields['parent_phone'],
            $fields['father_name'],
            $fields['mother_name'],
            $fields['parent_occupation'],
            $fields['address_full'],
            $fields['caste_category'],
            $fields['marks_sslc'],
            $fields['marks_hsc'],
            $fields['current_semester'],
            $user['id']
        );
        if ($stmt->execute()) {
            $message = 'Admission profile saved and queued for verification.';
            $_SESSION['approval_status'] = iqac_student_is_approved((string)($profile['status'] ?? '')) ? (string)$profile['status'] : 'pending';
            $_SESSION['admission_approved'] = iqac_student_is_approved((string)$_SESSION['approval_status']) ? 1 : 0;
            iqac_audit($conn, 'admission_profile_saved', $user['id'], $user['role'], 'Student profile completion');
            iqac_notify($conn, null, 'tutor', 'Admission profile submitted', $fields['full_name'] . ' updated admission profile.', 'std_index.php');
            iqac_notify($conn, null, 'admin', 'Admission profile submitted', $fields['full_name'] . ' updated admission profile.', 'std_index.php');
        } else {
            $error = 'Unable to save admission profile.';
        }
        $stmt->close();
    }
}

$details = [];
if (iqac_table_exists($conn, 'student_details')) {
    if ($user['role'] === 'student') {
        $stmt = $conn->prepare('SELECT sd.*, st.full_name AS tutor_name FROM student_details sd LEFT JOIN staff_details st ON st.staff_id = sd.tutor_staff_id WHERE sd.user_id = ? LIMIT 1');
        $stmt->bind_param('i', $user['id']);
    } else {
        $studentId = (int)($_GET['student_id'] ?? 0);
        $stmt = $conn->prepare('SELECT sd.*, st.full_name AS tutor_name FROM student_details sd LEFT JOIN staff_details st ON st.staff_id = sd.tutor_staff_id WHERE sd.student_id = ? LIMIT 1');
        $stmt->bind_param('i', $studentId);
    }
    $stmt->execute();
    $details = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();
}

$fields = [
    'Full Name' => $details['full_name'] ?? $profile['full_name'],
    'Register Number' => $details['roll_number'] ?? $profile['roll_number'],
    'Department' => $details['department'] ?? $profile['department'],
    'Class Year' => $details['class_year'] ?? '-',
    'Batch' => $details['batch'] ?? $profile['batch'],
    'Section' => $details['section'] ?? $profile['section'],
    'Semester' => $details['current_semester'] ?? $profile['current_semester'],
    'Tutor' => $details['tutor_name'] ?? 'Not assigned',
    'Student Phone' => $details['student_phone'] ?? '-',
    'Parent Phone' => $details['parent_phone'] ?? '-',
    'Father Name' => $details['father_name'] ?? '-',
    'Mother Name' => $details['mother_name'] ?? '-',
    'Address' => $details['address_full'] ?? '-',
    'Status' => $details['stage1_status'] ?? $profile['status'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Profile | PSG PTC ERP</title>
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
            <div>
                <h1>Student Profile</h1>
                <span>PSG PTC Department Portal</span>
            </div>
            <a class="secondary-btn" href="<?= htmlspecialchars(iqac_role_home($user['role'])) ?>">Dashboard</a>
        </header>
        <section class="erp-content">
            <?php psg_render_student_hero($conn, $user, 'Profile Overview', 'Academic identity, tutor mapping, contact details, and student progression context.'); ?>
            <?php if (isset($_GET['approval_required'])): ?><div class="alert">Your admission approval is pending. Complete your profile and wait for verification before accessing ERP modules.</div><?php endif; ?>
            <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <section class="panel glass-panel">
                <div class="panel-header"><h2>Personal & Academic Details</h2><span class="badge gold">Live student_details</span></div>
                <div class="profile-grid">
                    <?php foreach ($fields as $label => $value): ?>
                        <div><span><?= htmlspecialchars($label) ?></span><strong><?= htmlspecialchars((string)$value) ?></strong></div>
                    <?php endforeach; ?>
                </div>
            </section>

            <?php if ($user['role'] === 'student'): ?>
            <section class="panel glass-panel">
                <div class="panel-header"><h2>Admission Profile Completion</h2><span class="badge gold">Verification required</span></div>
                <form method="post" class="erp-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                    <input type="hidden" name="action" value="save_profile">
                    <div class="form-grid">
                        <label><span>Full Name</span><input name="full_name" value="<?= htmlspecialchars((string)($details['full_name'] ?? '')) ?>" required></label>
                        <label><span>Department</span><input name="department" value="<?= htmlspecialchars((string)($details['department'] ?? '')) ?>" required></label>
                        <label><span>Semester</span><input type="number" name="current_semester" min="1" max="8" value="<?= htmlspecialchars((string)($details['current_semester'] ?? 1)) ?>" required></label>
                        <label><span>Batch</span><input name="batch" value="<?= htmlspecialchars((string)($details['batch'] ?? '')) ?>"></label>
                        <label><span>Section</span><input name="section" value="<?= htmlspecialchars((string)($details['section'] ?? '')) ?>"></label>
                        <label><span>Community</span><select name="caste_category"><option value="">Select</option><?php foreach (['OC','BC','BCM','MBC','DNC','SC','SCA','ST'] as $community): ?><option value="<?= $community ?>" <?= (($details['caste_category'] ?? '') === $community) ? 'selected' : '' ?>><?= $community ?></option><?php endforeach; ?></select></label>
                        <label><span>Student Mobile</span><input name="student_phone" value="<?= htmlspecialchars((string)($details['student_phone'] ?? '')) ?>"></label>
                        <label><span>Parent Mobile</span><input name="parent_phone" value="<?= htmlspecialchars((string)($details['parent_phone'] ?? '')) ?>"></label>
                        <label><span>Father Name</span><input name="father_name" value="<?= htmlspecialchars((string)($details['father_name'] ?? '')) ?>"></label>
                        <label><span>Mother Name</span><input name="mother_name" value="<?= htmlspecialchars((string)($details['mother_name'] ?? '')) ?>"></label>
                        <label><span>Parent Occupation</span><input name="parent_occupation" value="<?= htmlspecialchars((string)($details['parent_occupation'] ?? '')) ?>"></label>
                        <label><span>10th Marks</span><input type="number" name="marks_sslc" value="<?= htmlspecialchars((string)($details['marks_sslc'] ?? 0)) ?>"></label>
                        <label><span>12th Marks</span><input type="number" name="marks_hsc" value="<?= htmlspecialchars((string)($details['marks_hsc'] ?? 0)) ?>"></label>
                        <label class="wide"><span>Address</span><textarea name="address_full"><?= htmlspecialchars((string)($details['address_full'] ?? '')) ?></textarea></label>
                    </div>
                    <button class="primary-btn" type="submit">Save Admission Profile</button>
                </form>
            </section>
            <?php endif; ?>

            <section class="panel">
                <div class="panel-header"><h2>Student Workspace</h2><span class="badge">Allowed Modules</span></div>
                <div class="module-grid">
                    <a href="marks_entry.php">My Marks</a>
                    <a href="std_achiev.php">Achievements</a>
                    <a href="std_sports_details.php">Sports</a>
                    <a href="std_publication.php">Publications</a>
                    <a href="std_indus.php">Industry Visit</a>
                    <a href="std_higher.php">Higher Studies</a>
                    <a href="applications.php">Applications</a>
                    <a href="password_change.php">Change Password</a>
                </div>
            </section>
        </section>
    </main>
</div>
</body>
</html>
