<?php
require_once __DIR__ . '/include/auth.php';
require_once __DIR__ . '/include/navigation.php';
require_once __DIR__ . '/include/student_portal_ui.php';

$user = iqac_require_login(['student', 'tutor', 'hod', 'iqac', 'admin', 'super_admin']);
$role = iqac_effective_role($user);
$isStudent = $role === 'student';
$token = iqac_csrf_token();
$message = '';
$error = '';
$studentProfile = psg_student_profile($conn, $user);
$hasHigherTable = iqac_table_exists($conn, 'higher_studies') || iqac_table_exists($conn, 'student_higher_studies');
$higherTable = iqac_table_exists($conn, 'higher_studies') ? 'higher_studies' : (iqac_table_exists($conn, 'student_higher_studies') ? 'student_higher_studies' : null);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_higher_study') {
    if (!iqac_verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (!$hasHigherTable || $higherTable === null) {
        $error = 'Higher studies table is not available in the current IQAC database.';
    } else {
        $studentId = $isStudent ? (int)$studentProfile['student_id'] : (int)($_POST['student_id'] ?? 0);
        $studentName = $isStudent ? (string)$studentProfile['full_name'] : trim((string)($_POST['student_name'] ?? ''));
        $previousRoll = trim((string)($_POST['previous_roll_no'] ?? ''));
        $currentRoll = $isStudent ? (string)$studentProfile['roll_number'] : trim((string)($_POST['current_roll_no'] ?? ''));
        $collegeName = trim((string)($_POST['college_name'] ?? ''));
        $quotaType = trim((string)($_POST['quota_type'] ?? ''));
        $percentage = (float)($_POST['percentage'] ?? 0);
        $filePath = '';

        if (isset($_FILES['higher_study_file']) && $_FILES['higher_study_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['higher_study_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
                $folder = __DIR__ . '/uploads/higher_studies/';
                if (!is_dir($folder)) {
                    mkdir($folder, 0755, true);
                }
                $filePath = 'uploads/higher_studies/' . uniqid('higher_', true) . '.' . $ext;
                move_uploaded_file($_FILES['higher_study_file']['tmp_name'], __DIR__ . '/' . $filePath);
            }
        }

        $stmt = $conn->prepare("INSERT INTO `$higherTable` (student_id, student_name, previous_roll_no, current_roll_no, college_name, quota_type, percentage, file_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('isssssds', $studentId, $studentName, $previousRoll, $currentRoll, $collegeName, $quotaType, $percentage, $filePath);
            if ($stmt->execute()) {
                $message = 'Higher studies record submitted successfully.';
                iqac_audit($conn, 'higher_studies_submitted', $user['id'], $role, $collegeName);
                iqac_notify($conn, null, 'tutor', 'Higher studies submitted', $studentName . ' submitted higher studies record: ' . $collegeName, 'std_higher.php');
                iqac_notify($conn, null, 'admin', 'Higher studies submitted', $studentName . ' submitted higher studies record: ' . $collegeName, 'std_higher.php');
            } else {
                $error = 'Unable to save higher studies record.';
            }
            $stmt->close();
        } else {
            $error = 'Higher studies table columns do not match the expected legacy form.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_student') {
    if (!iqac_verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (!iqac_table_exists($conn, 'students')) {
        $error = 'Legacy students table is not available in the current IQAC database.';
    } else {
        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $lastName = trim((string)($_POST['last_name'] ?? ''));
        $dob = trim((string)($_POST['dob'] ?? ''));
        $gender = trim((string)($_POST['gender'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));
        $stmt = $conn->prepare('INSERT INTO students (first_name, last_name, dob, gender, phone, email, address) VALUES (?, ?, ?, ?, ?, ?, ?)');
        if ($stmt) {
            $stmt->bind_param('sssssss', $firstName, $lastName, $dob, $gender, $phone, $email, $address);
            $message = $stmt->execute() ? 'Student registration record added.' : 'Unable to save student registration record.';
            $stmt->close();
        } else {
            $error = 'Legacy students table columns do not match the old registration form.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_placement') {
    if (!iqac_verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (!iqac_table_exists($conn, 'placements')) {
        $error = 'Legacy placements table is not available in the current IQAC database.';
    } else {
        $studentId = $isStudent ? (int)$studentProfile['student_id'] : (int)($_POST['student_id'] ?? 0);
        $companyName = trim((string)($_POST['company_name'] ?? ''));
        $package = trim((string)($_POST['package'] ?? ''));
        $date = trim((string)($_POST['date'] ?? ''));
        $designation = trim((string)($_POST['designation'] ?? ''));
        $filePath = '';
        if (isset($_FILES['placement_file']) && $_FILES['placement_file']['error'] === UPLOAD_ERR_OK) {
            $folder = __DIR__ . '/uploads/placements/';
            if (!is_dir($folder)) {
                mkdir($folder, 0755, true);
            }
            $filePath = 'uploads/placements/' . uniqid('placement_', true) . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', basename($_FILES['placement_file']['name']));
            move_uploaded_file($_FILES['placement_file']['tmp_name'], __DIR__ . '/' . $filePath);
        }
        $stmt = $conn->prepare('INSERT INTO placements (student_id, company_name, package, date, designation, file_path) VALUES (?, ?, ?, ?, ?, ?)');
        if ($stmt) {
            $stmt->bind_param('isssss', $studentId, $companyName, $package, $date, $designation, $filePath);
            $message = $stmt->execute() ? 'Placement record added.' : 'Unable to save placement record.';
            $stmt->close();
        } else {
            $error = 'Legacy placements table columns do not match the old placement form.';
        }
    }
}

$students = $isStudent ? [] : iqac_student_list($conn);
$higherRows = [];
if ($higherTable !== null) {
    $where = '';
    $types = '';
    $params = [];
    if ($isStudent && (int)$studentProfile['student_id'] > 0 && iqac_column_exists($conn, $higherTable, 'student_id')) {
        $where = 'WHERE hs.student_id = ?';
        $types = 'i';
        $params[] = (int)$studentProfile['student_id'];
    }
    $sql = "SELECT hs.* FROM `$higherTable` hs $where ORDER BY hs.id DESC";
    if ($types !== '') {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $higherRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $result = $conn->query($sql);
        $higherRows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}

$internshipRows = [];
if (iqac_table_exists($conn, 'student_internships')) {
    $where = '';
    $types = '';
    $params = [];
    if ($isStudent && (int)$studentProfile['student_id'] > 0) {
        $where = 'WHERE si.student_id = ?';
        $types = 'i';
        $params[] = (int)$studentProfile['student_id'];
    }
    $sql = "SELECT si.*, sd.full_name, sd.roll_number FROM student_internships si JOIN student_details sd ON sd.student_id = si.student_id $where ORDER BY si.created_at DESC";
    if ($types !== '') {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $internshipRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $result = $conn->query($sql);
        $internshipRows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}

$placementRows = [];
if (iqac_table_exists($conn, 'placement_statistics')) {
    $result = $conn->query('SELECT * FROM placement_statistics ORDER BY recorded_date DESC LIMIT 12');
    $placementRows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Higher Studies | PSG PTC ERP</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/erp.css">
</head>
<body>
<div class="erp-layout">
    <?php iqac_render_sidebar($user, 'std_higher.php'); ?>
    <main class="erp-main">
        <header class="erp-topbar">
            <div>
                <h1>Higher Studies & Placement</h1>
                <span>PSG PTC student progression records</span>
            </div>
            <div class="toolbar-actions">
                <button class="secondary-btn" type="button" onclick="window.print()">Print</button>
                <button class="primary-btn" type="button" data-export-table="higher-table" data-export-name="higher_studies">Export</button>
            </div>
        </header>
        <section class="erp-content">
            <?php psg_render_student_hero($conn, $user, 'Progression Portfolio', 'Track higher studies, placement readiness, and industry progression records.'); ?>
            <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if (!$hasHigherTable): ?><div class="alert">Higher studies table is not available in the current live schema. The page remains available with placement and internship context.</div><?php endif; ?>

            <section class="panel glass-panel">
                <div class="panel-header">
                    <h2>Student Registration</h2>
                    <span class="badge gold">Legacy Form Restored</span>
                </div>
                <form method="post" class="erp-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                    <input type="hidden" name="action" value="add_student">
                    <div class="form-grid">
                        <label><span>First Name</span><input type="text" name="first_name" required></label>
                        <label><span>Last Name</span><input type="text" name="last_name" required></label>
                        <label><span>Date of Birth</span><input type="date" name="dob" required></label>
                        <label><span>Gender</span><select name="gender" required><option value="">Select</option><option value="Male">Male</option><option value="Female">Female</option></select></label>
                        <label><span>Phone</span><input type="text" name="phone" required></label>
                        <label><span>Email</span><input type="email" name="email" required></label>
                        <label class="wide"><span>Address</span><input type="text" name="address" required></label>
                    </div>
                    <button class="primary-btn" type="submit">Register Student</button>
                </form>
            </section>

            <section class="panel glass-panel">
                <div class="panel-header">
                    <h2>Add Higher Studies Record</h2>
                    <span class="badge gold"><?= htmlspecialchars($higherTable ?? 'Schema pending') ?></span>
                </div>
                <form method="post" enctype="multipart/form-data" class="erp-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                    <input type="hidden" name="action" value="add_higher_study">
                    <div class="form-grid">
                        <?php if ($isStudent): ?>
                            <label><span>Student</span><input type="text" name="student_name" value="<?= htmlspecialchars((string)$studentProfile['full_name']) ?>" readonly></label>
                            <input type="hidden" name="student_id" value="<?= (int)$studentProfile['student_id'] ?>">
                            <label><span>Current Register Number</span><input type="text" name="current_roll_no" value="<?= htmlspecialchars((string)$studentProfile['roll_number']) ?>" readonly></label>
                        <?php else: ?>
                            <label><span>Student</span><select name="student_id" required><option value="">Select student</option><?php foreach ($students as $student): ?><option value="<?= (int)$student['id'] ?>"><?= htmlspecialchars($student['student_name']) ?></option><?php endforeach; ?></select></label>
                            <label><span>Student Name</span><input type="text" name="student_name" required></label>
                            <label><span>Current Register Number</span><input type="text" name="current_roll_no" required></label>
                        <?php endif; ?>
                        <label><span>Previous Register Number</span><input type="text" name="previous_roll_no"></label>
                        <label><span>College / University</span><input type="text" name="college_name" required></label>
                        <label><span>Quota Type</span><select name="quota_type"><option value="Merit">Merit</option><option value="Management">Management</option><option value="Government">Government</option></select></label>
                        <label><span>Percentage</span><input type="number" step="0.01" name="percentage"></label>
                        <label class="wide upload-zone">
                            <strong>Admission Proof / Offer Letter</strong>
                            <span data-file-name>Drop file here or browse</span>
                            <input type="file" name="higher_study_file" accept=".pdf,.jpg,.jpeg,.png" data-file-preview <?= !$hasHigherTable ? 'disabled' : '' ?>>
                        </label>
                    </div>
                    <button class="primary-btn" type="submit" <?= !$hasHigherTable ? 'disabled' : '' ?>>Submit Higher Studies Record</button>
                </form>
            </section>

            <section class="panel glass-panel">
                <div class="panel-header">
                    <h2>Add Placement Details</h2>
                    <span class="badge gold">Legacy Form Restored</span>
                </div>
                <form method="post" enctype="multipart/form-data" class="erp-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                    <input type="hidden" name="action" value="add_placement">
                    <div class="form-grid">
                        <?php if ($isStudent): ?>
                            <label><span>Student</span><input type="text" value="<?= htmlspecialchars((string)$studentProfile['full_name']) ?>" readonly></label>
                            <input type="hidden" name="student_id" value="<?= (int)$studentProfile['student_id'] ?>">
                        <?php else: ?>
                            <label><span>Student</span><select name="student_id" required><option value="">Select student</option><?php foreach ($students as $student): ?><option value="<?= (int)$student['id'] ?>"><?= htmlspecialchars($student['student_name']) ?></option><?php endforeach; ?></select></label>
                        <?php endif; ?>
                        <label><span>Company Name</span><input type="text" name="company_name" required></label>
                        <label><span>Package</span><input type="text" name="package" required></label>
                        <label><span>Date of Placement</span><input type="date" name="date" required></label>
                        <label><span>Designation</span><input type="text" name="designation" required></label>
                        <label class="wide upload-zone"><strong>Upload Document</strong><span data-file-name>Drop file here or browse</span><input type="file" name="placement_file" data-file-preview></label>
                    </div>
                    <button class="primary-btn" type="submit">Add Placement</button>
                </form>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <h2>Higher Studies History</h2>
                    <input class="table-search" type="search" placeholder="Search higher studies" data-search-table="higher-table">
                </div>
                <div class="table-wrap">
                    <table class="erp-table interactive-table" id="higher-table">
                        <thead><tr><th>Student</th><th>Previous Reg No</th><th>Current Reg No</th><th>College</th><th>Quota</th><th>Percentage</th><th>Evidence</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php if (!$higherRows): ?><tr><td colspan="8">No higher studies records found.</td></tr><?php endif; ?>
                        <?php foreach ($higherRows as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($row['student_name'] ?? $studentProfile['full_name'])) ?></td>
                                <td><?= htmlspecialchars((string)($row['previous_roll_no'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($row['current_roll_no'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($row['college_name'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($row['quota_type'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($row['percentage'] ?? '')) ?></td>
                                <td><?php if (!empty($row['file_path'])): ?><a href="<?= htmlspecialchars((string)$row['file_path']) ?>" target="_blank">View</a><?php else: ?>N/A<?php endif; ?></td>
                                <td><span class="status-badge verified">Recorded</span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="pagination-pills"><span>1</span><span><?= max(1, (int)ceil(count($higherRows) / 10)) ?></span></div>
            </section>

            <section class="panel">
                <div class="panel-header"><h2>Internship & Placement Context</h2><span class="badge">Progression Signals</span></div>
                <div class="table-wrap">
                    <table class="erp-table interactive-table">
                        <thead><tr><th>Type</th><th>Student / Batch</th><th>Company / Year</th><th>Duration / Eligible</th><th>Status / Placed</th></tr></thead>
                        <tbody>
                        <?php if (!$internshipRows && !$placementRows): ?><tr><td colspan="5">No internship or placement records found.</td></tr><?php endif; ?>
                        <?php foreach ($internshipRows as $row): ?>
                            <tr><td>Internship</td><td><?= htmlspecialchars((string)$row['full_name']) ?></td><td><?= htmlspecialchars((string)$row['company_name']) ?></td><td><?= htmlspecialchars((string)$row['duration']) ?></td><td><span class="status-badge <?= strtolower((string)$row['status']) === 'approved' ? 'verified' : 'pending' ?>"><?= htmlspecialchars((string)$row['status']) ?></span></td></tr>
                        <?php endforeach; ?>
                        <?php foreach ($placementRows as $row): ?>
                            <tr><td>Placement</td><td><?= htmlspecialchars((string)$row['batch_year']) ?></td><td><?= htmlspecialchars((string)$row['academic_year']) ?></td><td><?= htmlspecialchars((string)$row['total_eligible']) ?></td><td><span class="status-badge verified"><?= htmlspecialchars((string)$row['placed_count']) ?> placed</span></td></tr>
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
