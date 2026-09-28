<?php
require_once __DIR__ . '/include/auth.php';
require_once __DIR__ . '/include/navigation.php';

$user = iqac_require_login(['admin', 'super_admin', 'hod', 'iqac']);
$message = '';
$error = '';
$token = iqac_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!iqac_verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (($_POST['action'] ?? '') === 'assign_subject') {
        $staffId = (int)($_POST['staff_id'] ?? 0);
        $subjectCode = trim((string)($_POST['subject_code'] ?? ''));
        $classYear = trim((string)($_POST['class_year'] ?? ''));
        if (!$staffId || $subjectCode === '' || $classYear === '') {
            $error = 'Staff, subject, and class year are required.';
        } else {
            $stmt = $conn->prepare('INSERT IGNORE INTO staff_subject_allocation (staff_id, subject_code, class_year) VALUES (?, ?, ?)');
            $stmt->bind_param('iss', $staffId, $subjectCode, $classYear);
            $stmt->execute();
            $stmt->close();
            iqac_audit($conn, 'subject_assigned', $user['id'], $user['role'], $subjectCode . ' to staff #' . $staffId);
            $message = 'Subject assigned.';
        }
    } elseif (($_POST['action'] ?? '') === 'assign_tutor') {
        $staffId = (int)($_POST['staff_id'] ?? 0);
        $classYear = trim((string)($_POST['class_year'] ?? ''));
        if (!$staffId || $classYear === '') {
            $error = 'Tutor and class year are required.';
        } else {
            $stmt = $conn->prepare('UPDATE student_details SET tutor_staff_id = ? WHERE class_year = ?');
            $stmt->bind_param('is', $staffId, $classYear);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            iqac_audit($conn, 'tutor_assigned', $user['id'], $user['role'], 'Tutor #' . $staffId . ' to ' . $classYear);
            $message = 'Tutor assigned to ' . $affected . ' students.';
        }
    } elseif (($_POST['action'] ?? '') === 'promote_batch') {
        $fromYear = trim((string)($_POST['from_class_year'] ?? ''));
        $toYear = trim((string)($_POST['to_class_year'] ?? ''));
        $targetSemester = (int)($_POST['target_semester'] ?? 0);
        $department = trim((string)($_POST['department'] ?? ''));
        if ($fromYear === '' || $toYear === '' || !$targetSemester) {
            $error = 'Current year, target year, and target semester are required.';
        } else {
            $sql = "UPDATE student_details SET class_year = ?, batch = ?, current_semester = ?";
            $types = 'ssi';
            $params = [$toYear, $toYear, $targetSemester];
            $where = " WHERE class_year = ?";
            $types .= 's';
            $params[] = $fromYear;
            if ($department !== '') {
                $where .= " AND department = ?";
                $types .= 's';
                $params[] = $department;
            }
            $stmt = $conn->prepare($sql . $where);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $message = 'Batch promotion completed for ' . $stmt->affected_rows . ' students.';
            $stmt->close();
        }
    } elseif (($_POST['action'] ?? '') === 'move_student') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $classYear = trim((string)($_POST['class_year'] ?? ''));
        $batch = trim((string)($_POST['batch'] ?? ''));
        $section = trim((string)($_POST['section'] ?? ''));
        $semester = (int)($_POST['semester'] ?? 0);
        $department = trim((string)($_POST['department'] ?? ''));
        if (!$studentId || !$semester) {
            $error = 'Student and semester are required.';
        } else {
            $stmt = $conn->prepare('UPDATE student_details SET class_year = COALESCE(NULLIF(?, ""), class_year), batch = COALESCE(NULLIF(?, ""), batch), section = COALESCE(NULLIF(?, ""), section), current_semester = ?, department = COALESCE(NULLIF(?, ""), department) WHERE student_id = ?');
            $stmt->bind_param('sssisi', $classYear, $batch, $section, $semester, $department, $studentId);
            $stmt->execute();
            $message = 'Student movement updated.';
            $stmt->close();
        }
    } elseif (($_POST['action'] ?? '') === 'semester_manage') {
        $semester = (int)($_POST['semester'] ?? 0);
        $academicYear = trim((string)($_POST['academic_year'] ?? ''));
        $status = $_POST['status'] ?? 'open';
        if (!$semester || $academicYear === '' || !in_array($status, ['open', 'closed', 'archived'], true)) {
            $error = 'Semester, academic year, and status are required.';
        } else {
            $openedAt = $status === 'open' ? date('Y-m-d H:i:s') : null;
            $closedAt = $status === 'closed' ? date('Y-m-d H:i:s') : null;
            $archivedAt = $status === 'archived' ? date('Y-m-d H:i:s') : null;
            $stmt = $conn->prepare('INSERT INTO semester_management (semester, academic_year, status, opened_at, closed_at, archived_at, created_by) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status=VALUES(status), opened_at=VALUES(opened_at), closed_at=VALUES(closed_at), archived_at=VALUES(archived_at), created_by=VALUES(created_by)');
            $stmt->bind_param('isssssi', $semester, $academicYear, $status, $openedAt, $closedAt, $archivedAt, $user['id']);
            $stmt->execute();
            $stmt->close();
            $message = 'Semester status updated.';
        }
    } elseif (($_POST['action'] ?? '') === 'bulk_import_students') {
        if (!isset($_FILES['student_csv']) || $_FILES['student_csv']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Upload a CSV file for bulk student creation.';
        } else {
            $handle = fopen($_FILES['student_csv']['tmp_name'], 'r');
            $created = 0;
            $skipped = 0;
            if ($handle) {
                $header = fgetcsv($handle);
                $map = array_flip(array_map(fn($v) => strtolower(trim((string)$v)), $header ?: []));
                $conn->begin_transaction();
                try {
                    while (($row = fgetcsv($handle)) !== false) {
                        $name = trim((string)($row[$map['student name'] ?? -1] ?? ''));
                        $roll = trim((string)($row[$map['roll number'] ?? -1] ?? ''));
                        $department = trim((string)($row[$map['department'] ?? -1] ?? ''));
                        $semester = (int)($row[$map['semester'] ?? -1] ?? 1);
                        $batch = trim((string)($row[$map['batch'] ?? -1] ?? ''));
                        $section = trim((string)($row[$map['section'] ?? -1] ?? ''));
                        $phone = trim((string)($row[$map['phone'] ?? -1] ?? ''));
                        $tutor = trim((string)($row[$map['tutor'] ?? -1] ?? ''));
                        if ($name === '' || $roll === '') {
                            $skipped++;
                            continue;
                        }
                        $exists = $conn->prepare('SELECT student_id FROM student_details WHERE roll_number = ? LIMIT 1');
                        $exists->bind_param('s', $roll);
                        $exists->execute();
                        $duplicate = $exists->get_result()->num_rows > 0;
                        $exists->close();
                        if ($duplicate) {
                            $skipped++;
                            continue;
                        }
                        $hash = password_hash($roll, PASSWORD_DEFAULT);
                        $role = 'student';
                        $active = 1;
                        $firstLogin = 1;
                        $userStmt = $conn->prepare('INSERT INTO users (username, password_hash, role, is_active, department, semester, section, batch, first_login) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                        $userStmt->bind_param('sssissssi', $roll, $hash, $role, $active, $department, $semester, $section, $batch, $firstLogin);
                        $userStmt->execute();
                        $newUserId = $conn->insert_id;
                        $userStmt->close();
                        $tutorStaffId = null;
                        if ($tutor !== '') {
                            $tutorStmt = $conn->prepare('SELECT staff_id FROM staff_details WHERE full_name = ? OR staff_id = ? LIMIT 1');
                            $tutorInt = (int)$tutor;
                            $tutorStmt->bind_param('si', $tutor, $tutorInt);
                            $tutorStmt->execute();
                            $tutorRow = $tutorStmt->get_result()->fetch_assoc();
                            $tutorStmt->close();
                            $tutorStaffId = $tutorRow ? (int)$tutorRow['staff_id'] : null;
                        }
                        $insert = $conn->prepare('INSERT INTO student_details (user_id, full_name, department, roll_number, class_year, batch, section, student_phone, current_semester, tutor_staff_id, stage1_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "active")');
                        $classYear = $batch;
                        $insert->bind_param('isssssssii', $newUserId, $name, $department, $roll, $classYear, $batch, $section, $phone, $semester, $tutorStaffId);
                        $insert->execute();
                        $insert->close();
                        $created++;
                    }
                    $conn->commit();
                    iqac_audit($conn, 'bulk_student_import', $user['id'], $user['role'], "Created $created, skipped $skipped");
                    $message = "Bulk student import completed. Created $created, skipped $skipped.";
                } catch (Throwable $e) {
                    $conn->rollback();
                    $error = 'Bulk import failed: ' . $e->getMessage();
                }
                fclose($handle);
            }
        }
    }
}

$staff = $conn->query('SELECT staff_id, full_name, department, is_tutor FROM staff_details ORDER BY full_name')->fetch_all(MYSQLI_ASSOC);
$subjects = $conn->query('SELECT subject_code, subject_name, semester, year FROM subjects ORDER BY semester, subject_code')->fetch_all(MYSQLI_ASSOC);
$classYears = $conn->query('SELECT DISTINCT class_year FROM student_details WHERE class_year IS NOT NULL ORDER BY class_year')->fetch_all(MYSQLI_ASSOC);
$allocations = $conn->query('SELECT ssa.*, sd.full_name, sub.subject_name FROM staff_subject_allocation ssa JOIN staff_details sd ON sd.staff_id = ssa.staff_id JOIN subjects sub ON sub.subject_code = ssa.subject_code ORDER BY ssa.created_at DESC')->fetch_all(MYSQLI_ASSOC);
$students = $conn->query('SELECT student_id, full_name, roll_number, class_year, current_semester FROM student_details ORDER BY full_name')->fetch_all(MYSQLI_ASSOC);
$semesters = $conn->query('SELECT * FROM semester_management ORDER BY academic_year DESC, semester DESC')->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Controls</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/erp.css">
</head>
<body>
<div class="erp-layout">
    <?php iqac_render_sidebar($user, 'admin_controls.php'); ?>
    <main class="erp-main">
        <header class="erp-topbar"><div><h1>Academic Assignments</h1><span>Subject staff and tutor mapping.</span></div></header>
        <section class="erp-content">
            <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <section class="panel">
                <div class="panel-header"><h2>Bulk Student Creation</h2><span class="badge gold">CSV Import</span></div>
                <form method="post" enctype="multipart/form-data" class="erp-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                    <input type="hidden" name="action" value="bulk_import_students">
                    <div class="form-grid">
                        <label class="wide"><span>CSV File</span><input type="file" name="student_csv" accept=".csv" required><small>Headers: Student Name, Roll Number, Department, Semester, Batch, Section, Tutor, Email, Phone. Existing roll numbers are skipped.</small></label>
                    </div>
                    <button class="primary-btn" type="submit">Import Students</button>
                </form>
            </section>
            <section class="panel">
                <div class="panel-header"><h2>Assign Subject Teacher</h2><span class="badge">staff_subject_allocation</span></div>
                <form method="post" class="erp-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                    <input type="hidden" name="action" value="assign_subject">
                    <div class="form-grid">
                        <label><span>Staff</span><select name="staff_id" required><option value="">Select staff</option><?php foreach ($staff as $item): ?><option value="<?= (int)$item['staff_id'] ?>"><?= htmlspecialchars($item['full_name'] . ' - ' . $item['department']) ?></option><?php endforeach; ?></select></label>
                        <label><span>Subject</span><select name="subject_code" required><option value="">Select subject</option><?php foreach ($subjects as $subject): ?><option value="<?= htmlspecialchars($subject['subject_code']) ?>"><?= htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']) ?></option><?php endforeach; ?></select></label>
                        <label><span>Class Year</span><select name="class_year" required><option value="">Select year</option><?php foreach ($classYears as $year): ?><option><?= htmlspecialchars($year['class_year']) ?></option><?php endforeach; ?></select></label>
                    </div>
                    <button class="primary-btn" type="submit">Assign</button>
                </form>
            </section>
            <section class="panel">
                <div class="panel-header"><h2>Assign Tutor</h2><span class="badge">student_details.tutor_staff_id</span></div>
                <form method="post" class="erp-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                    <input type="hidden" name="action" value="assign_tutor">
                    <div class="form-grid">
                        <label><span>Tutor</span><select name="staff_id" required><option value="">Select tutor</option><?php foreach ($staff as $item): ?><option value="<?= (int)$item['staff_id'] ?>"><?= htmlspecialchars($item['full_name']) ?></option><?php endforeach; ?></select></label>
                        <label><span>Class Year</span><select name="class_year" required><option value="">Select year</option><?php foreach ($classYears as $year): ?><option><?= htmlspecialchars($year['class_year']) ?></option><?php endforeach; ?></select></label>
                    </div>
                    <button class="primary-btn" type="submit">Assign Tutor</button>
                </form>
            </section>
            <section class="panel">
                <div class="panel-header"><h2>Current Subject Allocations</h2><span class="badge gold"><?= count($allocations) ?></span></div>
                <div class="table-wrap"><table class="erp-table"><thead><tr><th>Staff</th><th>Subject</th><th>Class Year</th><th>Created</th></tr></thead><tbody>
                    <?php foreach ($allocations as $row): ?><tr><td><?= htmlspecialchars($row['full_name']) ?></td><td><?= htmlspecialchars($row['subject_code'] . ' - ' . $row['subject_name']) ?></td><td><?= htmlspecialchars($row['class_year']) ?></td><td><?= htmlspecialchars($row['created_at']) ?></td></tr><?php endforeach; ?>
                    <?php if (!$allocations): ?><tr><td colspan="4">No allocations yet.</td></tr><?php endif; ?>
                </tbody></table></div>
            </section>
            <section class="panel">
                <div class="panel-header"><h2>Bulk Promotion</h2><span class="badge">Admin only</span></div>
                <form method="post" class="erp-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                    <input type="hidden" name="action" value="promote_batch">
                    <div class="form-grid">
                        <label><span>Current Year</span><select name="from_class_year" required><option value="">Select</option><?php foreach ($classYears as $year): ?><option><?= htmlspecialchars($year['class_year']) ?></option><?php endforeach; ?></select></label>
                        <label><span>Target Year</span><input name="to_class_year" placeholder="3rd Year" required></label>
                        <label><span>Target Semester</span><input type="number" name="target_semester" min="1" max="8" required></label>
                        <label><span>Department</span><input name="department" placeholder="Optional"></label>
                    </div>
                    <button class="primary-btn" type="submit" onclick="return confirm('Confirm batch promotion?')">Promote Batch</button>
                </form>
            </section>
            <section class="panel">
                <div class="panel-header"><h2>Move Individual Student</h2><span class="badge">Student movement</span></div>
                <form method="post" class="erp-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                    <input type="hidden" name="action" value="move_student">
                    <div class="form-grid">
                        <label><span>Student</span><select name="student_id" required><option value="">Select</option><?php foreach ($students as $student): ?><option value="<?= (int)$student['student_id'] ?>"><?= htmlspecialchars($student['roll_number'] . ' - ' . $student['full_name']) ?></option><?php endforeach; ?></select></label>
                        <label><span>Class Year</span><input name="class_year"></label>
                        <label><span>Batch</span><input name="batch"></label>
                        <label><span>Section</span><input name="section"></label>
                        <label><span>Semester</span><input type="number" name="semester" min="1" max="8" required></label>
                        <label><span>Department</span><input name="department"></label>
                    </div>
                    <button class="primary-btn" type="submit">Move Student</button>
                </form>
            </section>
            <section class="panel">
                <div class="panel-header"><h2>Semester Management</h2><span class="badge gold">Open / Close / Archive</span></div>
                <form method="post" class="erp-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                    <input type="hidden" name="action" value="semester_manage">
                    <div class="form-grid">
                        <label><span>Semester</span><input type="number" name="semester" min="1" max="8" required></label>
                        <label><span>Academic Year</span><input name="academic_year" placeholder="2026-2027" required></label>
                        <label><span>Status</span><select name="status"><option value="open">Open/Reopen</option><option value="closed">Close</option><option value="archived">Archive</option></select></label>
                    </div>
                    <button class="primary-btn" type="submit">Save Semester</button>
                </form>
                <div class="table-wrap" style="margin-top:16px"><table class="erp-table"><thead><tr><th>Semester</th><th>Academic Year</th><th>Status</th><th>Updated</th></tr></thead><tbody><?php foreach ($semesters as $sem): ?><tr><td><?= (int)$sem['semester'] ?></td><td><?= htmlspecialchars($sem['academic_year']) ?></td><td><span class="badge"><?= htmlspecialchars($sem['status']) ?></span></td><td><?= htmlspecialchars($sem['updated_at']) ?></td></tr><?php endforeach; ?></tbody></table></div>
            </section>
        </section>
    </main>
</div>
</body>
</html>
