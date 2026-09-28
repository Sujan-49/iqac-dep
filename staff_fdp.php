<?php
mysqli_report(MYSQLI_REPORT_OFF);
// Connect to database
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'iqac';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("DB connection error: " . $conn->connect_error);

$message = '';

// Fetch staff list for dropdown
$staff_list = [];
$result_staff = $conn->query("SELECT * FROM staff_details");
if ($result_staff) {
    while ($row = $result_staff->fetch_assoc()) {
        $staff_list[] = $row;
    }
}

// Fetch existing FDP records
$fdp_records = [];
$result_fdp = $conn->query("SELECT sf.*, s.full_name AS first_name, '' AS last_name FROM staff_fdp sf JOIN staff_details s ON sf.staff_id=s.staff_id");
if ($result_fdp) {
    while ($row = $result_fdp->fetch_assoc()) {
        $fdp_records[] = $row;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_fdp'])) {
    $staff_id = intval($_POST['staff_id']);
    $enrollment_course = $_POST['enrollment_course'];
    $organization_name = $_POST['organization_name']; // from dropdown
    $course_name = $_POST['course_name'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Handle file upload
    $certificate_path = '';
    if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/certificates/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $filename = basename($_FILES['certificate']['name']);
        $target_file = $upload_dir . time() . '_' . $filename;
        if (move_uploaded_file($_FILES['certificate']['tmp_name'], $target_file)) {
            $certificate_path = $target_file;
        } else {
            $message .= "Failed to upload certificate.<br>";
        }
    }

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO staff_fdp (staff_id, enrollment_course, organization_name, course_name, start_date, end_date, certificate_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $staff_id, $enrollment_course, $organization_name, $course_name, $start_date, $end_date, $certificate_path);
    if ($stmt->execute()) {
        $message .= "FDP record added successfully.<br>";
        // Refresh FDP records
        $result_fdp = $conn->query("SELECT sf.*, s.first_name, s.last_name FROM staff_fdp sf JOIN staff_details s ON sf.staff_id=s.id");
        $fdp_records = [];
        if ($result_fdp) {
            while ($row = $result_fdp->fetch_assoc()) {
                $fdp_records[] = $row;
            }
        }
    } else {
        $message .= "Error adding FDP record: " . $conn->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Staff FDP Program</title>
<!-- Font Awesome & Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<style>
body {
    font-family: 'Inter', sans-serif; 
    background: #f9f9f9; 
    margin: 0; padding: 20px;
}
div.container {
    max-width: 1200px;
    margin: 0 auto;
}
h2 {
    text-align: center;
    margin-bottom: 20px;
    position: relative;
}
h2::before {
    content: "\f0c0"; /* users icon */
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    margin-right: 10px;
    color: #007bff;
}
.message {
    padding: 10px;
    margin: 10px 0;
    border-radius: 4px;
}
.message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

section {
    background: #fff;
    padding: 20px;
    margin-bottom: 50px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    border: 2px solid #007bff;
    position: relative;
}
section::before {
    content: "\f5f3"; /* notebook icon */
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    position: absolute;
    top: -15px;
    left: 20px;
    background: #fff;
    padding: 0 8px;
    font-size: 20px;
    color: #007bff;
}
form {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}
form > .form-group {
    flex: 1 1 250px;
    display: flex;
    flex-direction: column;
}
label {
    margin-bottom: 5px;
    font-weight: 600;
}
input[type="text"], input[type="number"], input[type="date"], textarea, select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-family: 'Inter', sans-serif;
    box-sizing: border-box;
}
button {
    display: block;
    margin: 10px auto;
    padding: 10px 20px;
    background-color: #007bff;
    color: #fff;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}
button:hover {
    background-color: #0056b3;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}
table, th, td {
    border: 1px solid #333;
}
th, td {
    padding: 10px;
    text-align: left;
}
tr:nth-child(even) {
    background-color: #fafafa;
}
</style>
</head>
<body>

<div class="container">

<?php if($message): ?>
<div class="message <?= strpos($message, 'Error') !== false ? 'error' : 'success' ?>"><?= $message ?></div>
<?php endif; ?>

<h2><i class="fas fa-project-diagram"></i> Staff Faculty Development Program (FDP)</h2>

<!-- Add FDP Record Section -->
<section>
<h3><i class="fas fa-plus-circle"></i> Add FDP Record</h3>
<form method="POST" enctype="multipart/form-data">
  <div class="form-group">
    <label for="staff_id"><i class="fas fa-user"></i> Staff Member:</label>
    <select name="staff_id" required>
      <option value="">--Select Staff--</option>
      <?php foreach($staff_list as $s): ?>
        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['first_name'].' '.$s['last_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group">
    <label for="enrollment_course"><i class="fas fa-book"></i> Enrollment Course:</label>
    <input type="text" name="enrollment_course" required />
  </div>
  <!-- Replace Organization Name input with dropdown -->
  <div class="form-group">
    <label for="organization_name"><i class="fas fa-building"></i> Organized/Attended By:</label>
    <select name="organization_name" required>
        <option value="">--Select--</option>
        <option value="Organized by Staff">Organized by Staff</option>
        <option value="Attended by Staff">Attended by Staff</option>
        <option value="Partner Organization">Partner Organization</option>
        <!-- Add more options as needed -->
    </select>
  </div>
  <div class="form-group">
    <label for="course_name"><i class="fas fa-file-alt"></i> Course Name:</label>
    <input type="text" name="course_name" required />
  </div>
  <div class="form-group">
    <label for="start_date"><i class="fas fa-calendar-alt"></i> Start Date:</label>
    <input type="date" name="start_date" required />
  </div>
  <div class="form-group">
    <label for="end_date"><i class="fas fa-calendar-alt"></i> End Date:</label>
    <input type="date" name="end_date" required />
  </div>
  <div class="form-group">
    <label for="certificate"><i class="fas fa-file-upload"></i> Upload Course Certificate:</label>
    <input type="file" name="certificate" accept=".pdf,.jpg,.png" />
  </div>
  <div style="width:100%; text-align:center;">
    <button type="submit" name="add_fdp"><i class="fas fa-plus"></i> Add FDP</button>
  </div>
</form>
</section>

<!-- Display FDP Records -->
<h3><i class="fas fa-list"></i> FDP Records</h3>
<table>
  <thead>
    <tr>
      <th>Staff Member</th>
      <th>Enrollment Course</th>
      <th>Organization</th>
      <th>Course Name</th>
      <th>Start Date</th>
      <th>End Date</th>
      <th>Certificate</th>
    </tr>
  </thead>
  <tbody>
    <?php if($fdp_records): ?>
      <?php foreach($fdp_records as $rec): ?>
      <tr>
        <td><?= htmlspecialchars($rec['first_name'].' '.$rec['last_name']) ?></td>
        <td><?= htmlspecialchars($rec['enrollment_course']) ?></td>
        <td><?= htmlspecialchars($rec['organization_name']) ?></td>
        <td><?= htmlspecialchars($rec['course_name']) ?></td>
        <td><?= htmlspecialchars($rec['start_date']) ?></td>
        <td><?= htmlspecialchars($rec['end_date']) ?></td>
        <td>
          <?php if($rec['certificate_path']): ?>
            <a href="<?= $rec['certificate_path'] ?>" target="_blank">View Certificate</a>
          <?php else: ?>
            N/A
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr><td colspan="7">No FDP records found.</td></tr>
    <?php endif; ?>
  </tbody>
</table>

</div>
</body>
</html>
