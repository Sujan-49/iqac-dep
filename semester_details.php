<?php
require_once __DIR__ . '/include/auth.php';
require_once __DIR__ . '/include/navigation.php';
$user = iqac_require_login(['staff', 'tutor', 'hod', 'iqac', 'admin', 'super_admin']);

// Connect to database
$conn = new mysqli("localhost", "root", "", "iqac");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch students list
$students_list = iqac_student_list($conn);

// Get selected semester
$selected_semester = isset($_GET['semester']) ? intval($_GET['semester']) : 1;

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['semester_submit'])) {
    if (!iqac_table_exists($conn, 'achievement_mark')) {
        $message = 'Semester mark save is unavailable because achievement_mark is not present in the live schema.';
    } else {
    $student_id = intval($_POST['student_id']);
    $sem = intval($_POST['semester']);
    $register_number = trim($_POST["register_number_sem$sem"]);

    $subject_vars = [];
    $total_marks = 0;

    // Loop through 9 subjects
    for ($sub = 1; $sub <= 9; $sub++) {
        $code = trim($_POST["subject{$sem}_{$sub}_code"]);
        $name = trim($_POST["subject{$sem}_{$sub}_name"]);
        $marks_input = $_POST["subject{$sem}_{$sub}_marks"];
        $pass_fail = $_POST["subject{$sem}_{$sub}_pass_fail"];

        $marks = is_numeric($marks_input) ? intval($marks_input) : 0;

        $subject_vars[] = [
            'code' => $code,
            'name' => $name,
            'marks' => $marks,
            'pass_fail' => $pass_fail
        ];

        $total_marks += $marks;
    }

    // Assign variables for binding
    for ($i = 0; $i < 9; $i++) {
        ${"subject" . ($i + 1) . "_code"} = $subject_vars[$i]['code'];
        ${"subject" . ($i + 1) . "_name"} = $subject_vars[$i]['name'];
        ${"subject" . ($i + 1) . "_marks"} = $subject_vars[$i]['marks'];
        ${"subject" . ($i + 1) . "_pass_fail"} = $subject_vars[$i]['pass_fail'];
    }

    $grade_avg = trim($_POST["grade_avg_sem$sem"]);
    $file_path = '';

    // Handle file upload
    if (isset($_FILES['mark_sheet_sem' . $sem]) && $_FILES['mark_sheet_sem' . $sem]['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['mark_sheet_sem' . $sem];
        $upload_dir = 'uploads/mark_sheets/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $filename = basename($file['name']);
        $target_path = $upload_dir . uniqid() . '_' . $filename;
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            $file_path = $target_path;
        }
    }

    // Prepare SQL with 42 placeholders
    $sql = "INSERT INTO achievement_mark(
        student_id, semester_number, register_number,
        subject1_code, subject1_name, subject1_marks, subject1_pass_fail,
        subject2_code, subject2_name, subject2_marks, subject2_pass_fail,
        subject3_code, subject3_name, subject3_marks, subject3_pass_fail,
        subject4_code, subject4_name, subject4_marks, subject4_pass_fail,
        subject5_code, subject5_name, subject5_marks, subject5_pass_fail,
        subject6_code, subject6_name, subject6_marks, subject6_pass_fail,
        subject7_code, subject7_name, subject7_marks, subject7_pass_fail,
        subject8_code, subject8_name, subject8_marks, subject8_pass_fail,
        subject9_code, subject9_name, subject9_marks, subject9_pass_fail,
        grade_or_avg, mark_sheet_path, total_marks
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)";

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die('Prepare failed: ' . $conn->error);
    }

    // Bind parameters - 42 variables in correct order
    $stmt->bind_param(
        "iii" . str_repeat("ssss", 9) . "s" . "s" . "i",
        $student_id, $sem, $register_number,
        $subject1_code, $subject1_name, $subject1_marks, $subject1_pass_fail,
        $subject2_code, $subject2_name, $subject2_marks, $subject2_pass_fail,
        $subject3_code, $subject3_name, $subject3_marks, $subject3_pass_fail,
        $subject4_code, $subject4_name, $subject4_marks, $subject4_pass_fail,
        $subject5_code, $subject5_name, $subject5_marks, $subject5_pass_fail,
        $subject6_code, $subject6_name, $subject6_marks, $subject6_pass_fail,
        $subject7_code, $subject7_name, $subject7_marks, $subject7_pass_fail,
        $subject8_code, $subject8_name, $subject8_marks, $subject8_pass_fail,
        $subject9_code, $subject9_name, $subject9_marks, $subject9_pass_fail,
        $grade_avg,
        $file_path,
        $total_marks
    );

    $stmt->execute();
    $stmt->close();

    $message = "Semester $sem marks saved successfully! Total Marks: $total_marks";
    }
}

// Fetch success and backlog counts grouped by batch_year
$success_rates = [];
$years_result = iqac_table_exists($conn, 'students')
    ? $conn->query("SELECT DISTINCT batch_year FROM students ORDER BY batch_year")
    : $conn->query("SELECT DISTINCT current_semester AS batch_year FROM student_details ORDER BY current_semester");
if ($years_result) {
    while ($row = $years_result->fetch_assoc()) {
        $batch_year = intval($row['batch_year']);

        if (!iqac_table_exists($conn, 'students')) {
            $total_res = $conn->query("SELECT COUNT(*) AS total FROM student_details WHERE current_semester = $batch_year");
            $total_students = ($total_res && $total_res->num_rows > 0) ? $total_res->fetch_assoc()['total'] : 0;
            $success_rates[] = [
                'batch_year' => $batch_year,
                'total' => $total_students,
                'success' => 0,
                'backlogs' => 0
            ];
            continue;
        }

        $total_students_q = "SELECT COUNT(*) AS total FROM students WHERE batch_year = $batch_year";
        $total_res = $conn->query($total_students_q);
        $total_students = ($total_res && $total_res->num_rows > 0) ? $total_res->fetch_assoc()['total'] : 0;

        // Success rate without backlog
        $success_without_backlog_q = "
        SELECT COUNT(DISTINCT academic_details.student_id) AS success_count
        FROM academic_details
        JOIN students ON academic_details.student_id = students.id
        LEFT JOIN semester_marks ON academic_details.student_id = semester_marks.student_id
        WHERE students.batch_year = $batch_year
        AND NOT EXISTS (
            SELECT 1 FROM semester_marks sm2
            WHERE sm2.student_id = academic_details.student_id AND sm2.pass_fail = 'fail'
        )";

        $res2 = $conn->query($success_without_backlog_q);
        $success_count = ($res2 && $res2->num_rows > 0) ? $res2->fetch_assoc()['success_count'] : 0;

        // Backlog students count
        $backlog_q = "
        SELECT COUNT(DISTINCT academic_details.student_id) AS backlog_count
        FROM academic_details
        JOIN students ON academic_details.student_id = students.id
        LEFT JOIN semester_marks ON academic_details.student_id = semester_marks.student_id
        WHERE students.batch_year = $batch_year
        AND EXISTS (
            SELECT 1 FROM semester_marks sm2
            WHERE sm2.student_id = academic_details.student_id AND sm2.pass_fail = 'fail'
        )";

        $backlog_res = $conn->query($backlog_q);
        $backlog_count = ($backlog_res && $backlog_res->num_rows > 0) ? $backlog_res->fetch_assoc()['backlog_count'] : 0;

        $success_rates[] = [
            'batch_year' => $batch_year,
            'total' => $total_students,
            'success' => $success_count,
            'backlogs' => $backlog_count
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Semester Marks Entry | PSG PTC ERP</title>
<link rel="stylesheet" href="assets/erp.css">
<style>
.semester-grid {
  display: grid;
  grid-template-columns: minmax(260px, 1fr) minmax(220px, 320px);
  gap: 18px;
  align-items: start;
}

.semester-strip {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.semester-strip a {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 44px;
  min-height: 38px;
  padding: 0 12px;
  border: 1px solid var(--line);
  border-radius: 8px;
  background: #fff;
  color: var(--royal);
  font-weight: 800;
  text-decoration: none;
}

.semester-strip a.active {
  background: var(--royal);
  color: #fff;
  border-color: var(--royal);
  box-shadow: 0 8px 18px rgba(30, 58, 138, 0.18);
}

.entry-summary {
  display: grid;
  gap: 10px;
}

.entry-summary div {
  border: 1px solid var(--line);
  border-radius: 8px;
  padding: 12px;
  background: var(--soft);
}

.entry-summary span {
  display: block;
  color: var(--muted);
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
}

.entry-summary strong {
  display: block;
  margin-top: 6px;
  font-size: 22px;
  color: var(--royal);
}

.marks-table th,
.marks-table td {
  vertical-align: middle;
}

.marks-table input,
.marks-table select {
  width: 100%;
  min-height: 40px;
  border: 1px solid var(--line);
  border-radius: 8px;
  padding: 9px 10px;
  font: inherit;
}

.marks-table input:focus,
.marks-table select:focus {
  outline: 0;
  border-color: rgba(30, 58, 138, 0.72);
  box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
}

.code-cell {
  min-width: 130px;
}

.name-cell {
  min-width: 220px;
}

.marks-cell {
  min-width: 110px;
}

.status-cell {
  min-width: 130px;
}

.form-actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 18px;
}

.report-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
}

@media (max-width: 900px) {
  .semester-grid,
  .report-grid {
    grid-template-columns: 1fr;
  }
}
</style>
</head>
<body>
<div class="erp-layout">
  <?php iqac_render_sidebar($user, 'semester_details.php'); ?>
  <main class="erp-main">
    <header class="erp-topbar">
      <div>
        <h1>Semester Marks Entry</h1>
        <span>Enter subject-wise marks, attach mark sheets, and review success/backlog summaries.</span>
      </div>
      <a class="secondary-btn" href="<?= htmlspecialchars(iqac_role_home($user['role'])) ?>">Dashboard</a>
    </header>

    <section class="erp-content">
      <?php if ($message): ?>
        <div class="alert success"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <section class="panel">
        <div class="panel-header">
          <h2>Semester Selection</h2>
          <span class="badge gold">Semester <?= (int)$selected_semester ?></span>
        </div>
        <div class="semester-grid">
          <div>
            <div class="semester-strip" aria-label="Semester selector">
              <?php for ($s=1; $s<=6; $s++): ?>
                <a href="?semester=<?= $s ?>" class="<?= ($s == $selected_semester) ? 'active' : '' ?>">S<?= $s ?></a>
              <?php endfor; ?>
            </div>
          </div>
          <div class="entry-summary">
            <div><span>Available Students</span><strong><?= count($students_list) ?></strong></div>
            <div><span>Subjects Per Entry</span><strong>9</strong></div>
          </div>
        </div>
      </section>

      <section class="panel">
        <div class="panel-header">
          <h2>Semester <?= (int)$selected_semester ?> Entry</h2>
          <span class="badge">achievement_mark</span>
        </div>
        <form method="POST" enctype="multipart/form-data" action="" class="erp-form">
          <input type="hidden" name="semester" value="<?= (int)$selected_semester ?>">
          <div class="form-grid">
            <label class="wide">
              <span>Student</span>
              <select name="student_id" required>
                <option value="">Select student</option>
                <?php foreach($students_list as $s): ?>
                  <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars(trim($s['first_name'].' '.$s['last_name'])) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label>
              <span>Register Number</span>
              <input type="text" name="register_number_sem<?= (int)$selected_semester ?>" required>
            </label>
            <label>
              <span>Grade / Average</span>
              <input type="text" name="grade_avg_sem<?= (int)$selected_semester ?>" placeholder="Grade or average" required>
            </label>
            <label class="wide">
              <span>Mark Sheet</span>
              <input type="file" name="mark_sheet_sem<?= (int)$selected_semester ?>" accept=".pdf,.jpg,.jpeg,.png">
            </label>
          </div>

          <div class="table-wrap">
            <table class="erp-table marks-table">
              <thead>
                <tr>
                  <th>Subject</th>
                  <th class="code-cell">Code</th>
                  <th class="name-cell">Name</th>
                  <th class="marks-cell">Marks</th>
                  <th class="status-cell">Result</th>
                </tr>
              </thead>
              <tbody>
                <?php for($sub=1; $sub<=9; $sub++): ?>
                  <tr>
                    <td><span class="badge">S<?= $sub ?></span></td>
                    <td><input type="text" name="subject<?= (int)$selected_semester ?>_<?= $sub ?>_code" placeholder="Code"></td>
                    <td><input type="text" name="subject<?= (int)$selected_semester ?>_<?= $sub ?>_name" placeholder="Subject name"></td>
                    <td><input type="number" name="subject<?= (int)$selected_semester ?>_<?= $sub ?>_marks" min="0" max="100"></td>
                    <td>
                      <select name="subject<?= (int)$selected_semester ?>_<?= $sub ?>_pass_fail">
                        <option value="Pass">Pass</option>
                        <option value="Fail">Fail</option>
                      </select>
                    </td>
                  </tr>
                <?php endfor; ?>
              </tbody>
            </table>
          </div>

          <div class="form-actions">
            <a class="secondary-btn" href="view_sem.php">View Report</a>
            <button class="primary-btn" type="submit" name="semester_submit">Save Semester <?= (int)$selected_semester ?></button>
          </div>
        </form>
      </section>

      <div class="report-grid">
        <section class="panel">
          <div class="panel-header">
            <h2>Success Rate</h2>
            <span class="badge">No Backlogs</span>
          </div>
          <div class="table-wrap">
            <table class="erp-table">
              <thead>
                <tr><th>Year</th><th>Total</th><th>Success</th></tr>
              </thead>
              <tbody>
                <?php foreach ($success_rates as $rate): ?>
                  <?php $percent = ($rate['total'] > 0) ? round(($rate['success'] / $rate['total']) * 100, 2) : 0; ?>
                  <tr>
                    <td>Year <?= htmlspecialchars((string)$rate['batch_year']) ?></td>
                    <td><?= (int)$rate['total'] ?></td>
                    <td><?= $percent ?>% (<?= (int)$rate['success'] ?>)</td>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$success_rates): ?><tr><td colspan="3">No success-rate data available.</td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="panel">
          <div class="panel-header">
            <h2>Backlog Summary</h2>
            <span class="badge gold">Year Wise</span>
          </div>
          <div class="table-wrap">
            <table class="erp-table">
              <thead>
                <tr><th>Year</th><th>Total</th><th>Success</th><th>Backlogs</th></tr>
              </thead>
              <tbody>
                <?php foreach ($success_rates as $rate): ?>
                  <?php $percent = ($rate['total'] > 0) ? round(($rate['success'] / $rate['total']) * 100, 2) : 0; ?>
                  <tr>
                    <td>Year <?= htmlspecialchars((string)$rate['batch_year']) ?></td>
                    <td><?= (int)$rate['total'] ?></td>
                    <td><?= $percent ?>%</td>
                    <td><?= (int)$rate['backlogs'] ?></td>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$success_rates): ?><tr><td colspan="4">No backlog data available.</td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </section>
  </main>
</div>
</body>
</html>
