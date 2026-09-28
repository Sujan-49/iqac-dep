<?php
require_once __DIR__ . '/include/auth.php';
require_once __DIR__ . '/include/navigation.php';
require_once __DIR__ . '/include/student_portal_ui.php';

$user = iqac_require_login(['student', 'staff', 'tutor', 'hod', 'iqac', 'admin', 'super_admin']);
$schemaReady = iqac_table_exists($conn, 'applications') && iqac_table_exists($conn, 'application_actions') && iqac_student_table($conn) !== null;
$message = '';
$error = '';

function application_student_id(mysqli $conn, array $user): ?int
{
    $studentTable = iqac_student_table($conn);
    if ($studentTable === null) {
        return null;
    }
    $studentPk = iqac_student_pk($conn, $studentTable);
    if ($studentTable === 'student_details' && iqac_column_exists($conn, 'student_details', 'user_id')) {
        $stmt = $conn->prepare("SELECT $studentPk AS id FROM student_details WHERE user_id = ? LIMIT 1");
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            return (int)$row['id'];
        }
    }
    if (iqac_column_exists($conn, $studentTable, 'register_number')) {
        $stmt = $conn->prepare("SELECT $studentPk AS id FROM `$studentTable` WHERE register_number = ? LIMIT 1");
        $stmt->bind_param('s', $user['username']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            return (int)$row['id'];
        }
    }
    if (iqac_column_exists($conn, $studentTable, 'roll_number')) {
        $stmt = $conn->prepare("SELECT $studentPk AS id FROM `$studentTable` WHERE roll_number = ? LIMIT 1");
        $stmt->bind_param('s', $user['username']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            return (int)$row['id'];
        }
    }
    $stmt = $conn->prepare("SELECT $studentPk AS id FROM `$studentTable` WHERE $studentPk = ? LIMIT 1");
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? (int)$row['id'] : null;
}

function next_application_level(string $role): string
{
    return match ($role) {
        'tutor' => 'staff',
        'staff' => 'hod',
        'hod' => 'admin',
        default => 'complete',
    };
}

if ($schemaReady && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!iqac_verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (($_POST['action'] ?? '') === 'submit_application' && $user['role'] === 'student') {
        $studentId = application_student_id($conn, $user);
        $type = trim((string)($_POST['application_type'] ?? ''));
        $title = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        if (!$studentId || $type === '' || $title === '') {
            $error = 'Application type and title are required.';
        } else {
            $stmt = $conn->prepare("INSERT INTO applications (student_id, application_type, title, description, status, current_level) VALUES (?, ?, ?, ?, 'pending', 'tutor')");
            $stmt->bind_param('isss', $studentId, $type, $title, $description);
            $stmt->execute();
            $applicationId = $stmt->insert_id;
            $stmt->close();

            $action = 'submitted';
            $remarks = 'Student submitted application';
            $log = $conn->prepare('INSERT INTO application_actions (application_id, actor_user_id, actor_role, action, remarks) VALUES (?, ?, ?, ?, ?)');
            $log->bind_param('iisss', $applicationId, $user['id'], $user['role'], $action, $remarks);
            $log->execute();
            $log->close();
            iqac_audit($conn, 'application_submitted', $user['id'], $user['role'], 'Application ID ' . $applicationId);
            iqac_notify($conn, null, 'tutor', 'Application submitted', $title . ' submitted by ' . $user['username'], 'applications.php');
            iqac_notify($conn, null, 'admin', 'Application submitted', $title . ' submitted by ' . $user['username'], 'applications.php');
            $message = 'Application submitted to tutor level.';
        }
    } elseif (($_POST['action'] ?? '') === 'review_application' && $user['role'] !== 'student') {
        $applicationId = (int)($_POST['application_id'] ?? 0);
        $decision = in_array($_POST['decision'] ?? '', ['approve', 'reject', 'cancel'], true) ? $_POST['decision'] : 'approve';
        $remarks = trim((string)($_POST['remarks'] ?? ''));
        $newStatus = $decision === 'approve' ? 'under_review' : ($decision === 'reject' ? 'rejected' : 'cancelled');
        $nextLevel = $decision === 'approve' ? next_application_level($user['role']) : 'complete';
        if ($nextLevel === 'complete' && $decision === 'approve') {
            $newStatus = 'approved';
        }

        $stmt = $conn->prepare('UPDATE applications SET status = ?, current_level = ? WHERE id = ?');
        $stmt->bind_param('ssi', $newStatus, $nextLevel, $applicationId);
        $stmt->execute();
        $stmt->close();

        $log = $conn->prepare('INSERT INTO application_actions (application_id, actor_user_id, actor_role, action, remarks) VALUES (?, ?, ?, ?, ?)');
        $log->bind_param('iisss', $applicationId, $user['id'], $user['role'], $decision, $remarks);
        $log->execute();
        $log->close();
        iqac_audit($conn, 'application_' . $decision, $user['id'], $user['role'], 'Application ID ' . $applicationId);
        $message = 'Application workflow updated.';
    }
}

$applications = [];
if ($schemaReady) {
    if ($user['role'] === 'student') {
        $studentId = application_student_id($conn, $user);
        if ($studentId) {
            $stmt = $conn->prepare('SELECT * FROM applications WHERE student_id = ? ORDER BY updated_at DESC');
            $stmt->bind_param('i', $studentId);
            $stmt->execute();
            $applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    } else {
        $studentTable = iqac_student_table($conn);
        $studentPk = iqac_student_pk($conn, $studentTable);
        $studentName = iqac_student_name_expr($studentTable, 's');
        $stmt = $conn->prepare("SELECT a.*, $studentName AS student_name
                                FROM applications a
                                LEFT JOIN `$studentTable` s ON s.`$studentPk` = a.student_id
                                WHERE a.current_level = ? OR ? IN ('iqac','admin','super_admin')
                                ORDER BY a.updated_at DESC LIMIT 100");
        $stmt->bind_param('ss', $user['role'], $user['role']);
        $stmt->execute();
        $applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

$types = ['Leave', 'OD', 'Permission', 'Internship', 'Workshop', 'Hackathon', 'Industrial Visit', 'Sports', 'Club Activity', 'Scholarship', 'Placement Training', 'General Request', 'Higher Studies'];
$token = iqac_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Applications Workflow</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/erp.css">
</head>
<body>
<div class="erp-layout">
    <?php iqac_render_sidebar($user, 'applications.php'); ?>
    <main class="erp-main">
        <header class="erp-topbar">
            <div><h1>PSG PTC Applications</h1><span>Student -> Tutor -> Staff -> HOD -> Admin</span></div>
            <a class="secondary-btn" href="<?= htmlspecialchars(iqac_role_home($user['role'])) ?>">Dashboard</a>
        </header>
        <section class="erp-content">
            <?php if ($user['role'] === 'student') { psg_render_student_hero($conn, $user, 'Application Center', 'Submit requests and track approval status across the PSG PTC workflow.'); } ?>
            <?php if (!$schemaReady): ?><div class="alert error">Run <strong>database/enterprise_erp_upgrade.sql</strong> to enable applications.</div><?php endif; ?>
            <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <?php if ($schemaReady && $user['role'] === 'student'): ?>
                <section class="panel glass-panel">
                    <div class="panel-header"><h2>Submit Application</h2><span class="badge gold">Tutor review first</span></div>
                    <form method="post" class="erp-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                        <input type="hidden" name="action" value="submit_application">
                        <div class="form-grid">
                            <label><span>Type</span><select name="application_type" required><option value="">Select type</option><?php foreach ($types as $type): ?><option><?= htmlspecialchars($type) ?></option><?php endforeach; ?></select></label>
                            <label class="wide"><span>Title</span><input name="title" required></label>
                            <label class="wide"><span>Description</span><textarea name="description"></textarea></label>
                        </div>
                        <button class="primary-btn" type="submit">Submit Application</button>
                    </form>
                </section>
            <?php endif; ?>

            <section class="panel">
                <div class="panel-header"><h2><?= $user['role'] === 'student' ? 'My Applications' : 'Approval Queue' ?></h2><div class="table-tools"><span class="badge">Workflow</span><input class="table-search" type="search" placeholder="Search applications" data-search-table="applications-table"></div></div>
                <div class="table-wrap">
                    <table class="erp-table interactive-table" id="applications-table">
                        <thead><tr><?php if ($user['role'] !== 'student'): ?><th>Student</th><?php endif; ?><th>Type</th><th>Title</th><th>Status</th><th>Level</th><th>Updated</th><?php if ($user['role'] !== 'student'): ?><th>Action</th><?php endif; ?></tr></thead>
                        <tbody>
                        <?php if (!$applications): ?><tr><td colspan="<?= $user['role'] === 'student' ? 5 : 7 ?>">No applications found.</td></tr><?php endif; ?>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <?php if ($user['role'] !== 'student'): ?><td><?= htmlspecialchars($app['student_name'] ?? ('Student #' . $app['student_id'])) ?></td><?php endif; ?>
                                <td><?= htmlspecialchars($app['application_type']) ?></td>
                                <td><?= htmlspecialchars($app['title']) ?></td>
                                <td><span class="badge"><?= htmlspecialchars($app['status']) ?></span></td>
                                <td><?= htmlspecialchars($app['current_level']) ?></td>
                                <td><?= htmlspecialchars($app['updated_at']) ?></td>
                                <?php if ($user['role'] !== 'student'): ?>
                                    <td>
                                        <form method="post" class="erp-form">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                                            <input type="hidden" name="action" value="review_application">
                                            <input type="hidden" name="application_id" value="<?= (int)$app['id'] ?>">
                                            <select name="decision"><option value="approve">Approve / Forward</option><option value="reject">Reject</option><option value="cancel">Cancel</option></select>
                                            <textarea name="remarks" placeholder="Remarks"></textarea>
                                            <button class="secondary-btn" type="submit">Update</button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="pagination-pills"><span>1</span><span><?= max(1, (int)ceil(count($applications) / 10)) ?></span></div>
            </section>
        </section>
    </main>
</div>
<?php psg_render_portal_scripts(); ?>
</body>
</html>
