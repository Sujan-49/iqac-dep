<?php
session_start();
// Ensure staff is logged in; add your auth check as needed
// if (!isset($_SESSION['staff_id'])) { header('Location: staff_login.php'); exit(); }

// Database connection
$conn = new mysqli("localhost", "root", "", "iqac");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch distinct semester options from semester_marks joined with semester_details
$result_sem = $conn->query("SELECT DISTINCT sm.semester_number, sd.semester_name, sd.year
                            FROM semester_marks sm
                            LEFT JOIN semester_details sd ON sm.semester_number = sd.id
                            ORDER BY sd.year DESC, sd.semester_name ASC");
$semester_options = [];
if ($result_sem) {
    while ($row = $result_sem->fetch_assoc()) {
        $semester_options[] = $row;
    }
}

// Get filter parameters
$semester_filter = isset($_GET['semester_number']) ? $_GET['semester_number'] : '';
$year_filter = isset($_GET['year']) ? $_GET['year'] : '';
$filter_condition = isset($_GET['filter_condition']) ? $_GET['filter_condition'] : '';

// Build filter SQL
$filter_sql = "";
if ($semester_filter != '') {
    $filter_sql .= " AND sm.semester_number = '" . $conn->real_escape_string($semester_filter) . "'";
}
if ($year_filter != '') {
    $filter_sql .= " AND sd.year = '" . $conn->real_escape_string($year_filter) . "'";
}

// Main query: join students, semester_marks, semester_details
$student_query = "
SELECT s.id, s.first_name, s.last_name, s.department_name,
       SUM(sm.total_marks) AS total_marks,
       COUNT(sm.id) AS subject_count,
       AVG(sm.total_marks) AS avg_marks,
       sd.semester_name,
       sd.year,
       CASE WHEN MIN(CASE WHEN sm.pass_fail='fail' THEN 1 ELSE 0 END) = 1 THEN 'Fail' ELSE 'Pass' END AS pass_status
FROM students s
LEFT JOIN semester_marks sm ON s.id = sm.student_id
LEFT JOIN semester_details sd ON sm.semester_number = sd.id
WHERE 1=1
" . $filter_sql . "
GROUP BY s.id, s.first_name, s.last_name, s.department_name, sd.semester_name, sd.year
";

// Fetch students
$result_students = $conn->query($student_query);
$students_data = [];
if ($result_students) {
    while ($row = $result_students->fetch_assoc()) {
        $student_id = $row['id'];
        // Fetch subjects for each student
        $subjects_query = "
        SELECT subject_code, subject_name, marks, pass_fail
        FROM semester_marks WHERE student_id = $student_id
        AND pass_fail IS NOT NULL
        ";
        $subjects_res = $conn->query($subjects_query);
        $subjects = [];
        $less_than_50_subjects = 0;
        if ($subjects_res) {
            while ($sub_row = $subjects_res->fetch_assoc()) {
                $subjects[] = $sub_row;
                if (intval($sub_row['marks']) < 50) {
                    $less_than_50_subjects++;
                }
            }
        }

        // Determine overall pass/fail
        $overall_pass = true;
        $overall_fail = false;
        foreach ($subjects as $sub) {
            if (strtolower($sub['pass_fail']) == 'fail') {
                $overall_pass = false;
                $overall_fail = true;
                break;
            }
        }

        $students_data[] = [
            'id' => $row['id'],
            'name' => $row['first_name'] . ' ' . $row['last_name'],
            'department_name' => $row['department_name'],
            'total_marks' => intval($row['total_marks']),
            'avg_marks' => floatval($row['avg_marks']),
            'less_than_50_subjects' => $less_than_50_subjects,
            'subjects' => $subjects,
            'pass_status' => ($overall_pass ? 'Pass' : ($overall_fail ? 'Fail' : 'Mixed')),
            'semester' => $row['semester_name'],
            'year' => $row['year']
        ];
    }
}

// Compute overall stats
$max_total = 0;
$top_students = [];
foreach ($students_data as $stu) {
    if ($stu['total_marks'] > $max_total) {
        $max_total = $stu['total_marks'];
        $top_students = [$stu];
    } elseif ($stu['total_marks'] == $max_total) {
        $top_students[] = $stu;
    }
}
$less_than_50_students = array_filter($students_data, fn($s) => $s['less_than_50_subjects'] > 0);
$fail_students = array_filter($students_data, fn($s) => $s['pass_status'] == 'Fail');
$pass_students = array_filter($students_data, fn($s) => $s['pass_status'] == 'Pass');

$total_marks_sum = 0;
$total_students_count = count($students_data);
foreach ($students_data as $s) {
    $total_marks_sum += $s['total_marks'];
}
$overall_avg = $total_students_count > 0 ? ($total_marks_sum / $total_students_count) : 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Students Details - Staff View</title>
<style>
body { font-family: 'Inter', sans-serif; background: #f0f4f8; margin: 20px; }
h2 { text-align: center; color: #002c77; }
form { margin-bottom: 20px; }
table { width: 100%; border-collapse: collapse; margin-top: 15px; }
th, td { border: 1px solid #999; padding: 8px; text-align: center; }
th { background-color: #b0e0e6; }
</style>
</head>
<body>

<h2>Student Details & Analytics</h2>

<!-- Filter/Search form for semester and year -->
<form method="GET" action="">
    <label for="semester_number">Semester:</label>
    <select name="semester_number" id="semester_number">
        <option value="" <?= ($semester_filter=='') ? 'selected' : '' ?>>--Select Semester--</option>
        <?php foreach ($semester_options as $sem): ?>
            <option value="<?= htmlspecialchars($sem['semester_number']) ?>" <?= ($semester_filter==$sem['semester_number']) ? 'selected' : '' ?>>
                Semester <?= htmlspecialchars($sem['semester_number']) ?> (<?= htmlspecialchars($sem['semester_name']) ?> <?= htmlspecialchars($sem['year']) ?>)
            </option>
        <?php endforeach; ?>
    </select>
    &nbsp;&nbsp;
    <label for="year">Year:</label>
    <select name="year" id="year">
        <option value="" <?= ($year_filter=='') ? 'selected' : '' ?>>--Select--</option>
        <?php
        for ($y = 2020; $y <= 2025; $y++) {
            echo "<option value='$y' " . (($year_filter==$y)? 'selected':'') . ">$y</option>";
        }
        ?>
    </select>
    &nbsp;&nbsp;
    <label for="filter_condition">Filter by:</label>
    <select name="filter_condition" id="filter_condition">
        <option value="" <?= ($filter_condition=='') ? 'selected' : '' ?>>--Select--</option>
        <option value="less_than_50" <?= ($filter_condition=='less_than_50') ? 'selected' : '' ?>>Total < 50</option>
        <option value="greater_than_50" <?= ($filter_condition=='greater_than_50') ? 'selected' : '' ?>>Total > 50</option>
        <option value="fail" <?= ($filter_condition=='fail') ? 'selected' : '' ?>>Fail Students</option>
        <option value="pass" <?= ($filter_condition=='pass') ? 'selected' : '' ?>>Pass Students</option>
        <option value="topper" <?= ($filter_condition=='topper') ? 'selected' : '' ?>>Top Scorers</option>
        <option value="average" <?= ($filter_condition=='average') ? 'selected' : '' ?>>Overall Average</option>
    </select>
    &nbsp;&nbsp;
    <button type="submit">Apply Filter</button>
    &nbsp;&nbsp;
    <!-- Show All button -->
    <a href="?" style="text-decoration:none; padding:6px 12px; background:#007bff; color:#fff; border-radius:4px;">Show All Students</a>
</form>

<h3>Overall Statistics</h3>
<p>Number of students: <?= $total_students_count ?></p>
<p>Average total marks: <?= round($overall_avg,2) ?></p>
<p>Top scorer(s): <?= implode(', ', array_map(function($s){ return $s['name']; }, $top_students)) ?> with <?= $max_total ?> marks</p>
<p>Students with <50 in any subject: <?= count($less_than_50_students) ?></p>
<p>Failing students: <?= count($fail_students) ?></p>
<p>Passing students: <?= count($pass_students) ?></p>

<!-- Students with subjects <50 -->
<h3>Students with Subjects <50 Marks</h3>
<table>
<tr><th>Name</th><th>Total Marks</th><th>Subjects <50</th><th>Department</th><th>Semester</th><th>Year</th></tr>
<?php foreach ($less_than_50_students as $s): ?>
<tr>
    <td><?= htmlspecialchars($s['name']) ?></td>
    <td><?= $s['total_marks'] ?></td>
    <td><?= $s['less_than_50_subjects'] ?></td>
    <td><?= htmlspecialchars($s['department_name']) ?></td>
    <td><?= htmlspecialchars($s['semester']) ?></td>
    <td><?= htmlspecialchars($s['year']) ?></td>
</tr>
<?php endforeach; ?>
</table>

<!-- Fail Students -->
<h3>Fail Students</h3>
<table>
<tr><th>Name</th><th>Total Marks</th><th>Department</th><th>Semester</th><th>Year</th></tr>
<?php foreach ($fail_students as $s): ?>
<tr>
    <td><?= htmlspecialchars($s['name']) ?></td>
    <td><?= $s['total_marks'] ?></td>
    <td><?= htmlspecialchars($s['department_name']) ?></td>
    <td><?= htmlspecialchars($s['semester']) ?></td>
    <td><?= htmlspecialchars($s['year']) ?></td>
</tr>
<?php endforeach; ?>
</table>

<!-- Pass Students -->
<h3>Pass Students</h3>
<table>
<tr><th>Name</th><th>Total Marks</th><th>Department</th><th>Semester</th><th>Year</th></tr>
<?php foreach ($pass_students as $s): ?>
<tr>
    <td><?= htmlspecialchars($s['name']) ?></td>
    <td><?= $s['total_marks'] ?></td>
    <td><?= htmlspecialchars($s['department_name']) ?></td>
    <td><?= htmlspecialchars($s['semester']) ?></td>
    <td><?= htmlspecialchars($s['year']) ?></td>
</tr>
<?php endforeach; ?>
</table>

</body>
</html>