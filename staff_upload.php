<?php
require_once __DIR__ . '/include/auth.php';

$staffUploadUser = iqac_require_login(['staff', 'hod', 'iqac', 'admin', 'super_admin']);
if (iqac_has_tutor_access($staffUploadUser)) {
    header('Location: ' . iqac_role_home((string)$staffUploadUser['role']));
    exit;
}

mysqli_report(MYSQLI_REPORT_OFF);
// Connect to database
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'iqac';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("DB connection error: " . $conn->connect_error);

$message = '';

// Fetch all staff
$staff_list = [];
$result_staff = $conn->query("SELECT * FROM staff_details");
if ($result_staff) {
    while ($row = $result_staff->fetch_assoc()) {
        $staff_list[] = $row;
    }
}

// Fetch academic details
$academic_records = [];
$result_academic = $conn->query("SELECT sa.*, s.full_name AS first_name, '' AS last_name FROM staff_academic_details sa JOIN staff_details s ON sa.staff_id=s.staff_id");
if ($result_academic) {
    while ($row = $result_academic->fetch_assoc()) {
        $academic_records[] = $row;
    }
}

// Fetch non-academic activities
$non_academic_records = [];
$result_non_academic = $conn->query("SELECT nad.*, s.full_name AS first_name, '' AS last_name FROM staff_non_academic_details nad JOIN staff_details s ON nad.staff_id=s.staff_id");
if ($result_non_academic) {
    while ($row = $result_non_academic->fetch_assoc()) {
        $non_academic_records[] = $row;
    }
}

// Fetch work experience
$work_experience_records = [];
$result_work_exp = $conn->query("SELECT we.*, s.full_name AS first_name, '' AS last_name FROM staff_work_experience we JOIN staff_details s ON we.staff_id=s.staff_id");
if ($result_work_exp) {
    while ($row = $result_work_exp->fetch_assoc()) {
        $work_experience_records[] = $row;
    }
}

// Handle Add Staff
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_staff'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $address = $_POST['address'];
    $qualification = $_POST['qualification'];
    $mobile_number = $_POST['mobile_number'];
    $designation = $_POST['designation'];
    $department = $_POST['department'];
    $email = $_POST['email'];

    // Handle staff picture upload
    $picture_path = '';
    if (isset($_FILES['staff_picture']) && $_FILES['staff_picture']['error'] == UPLOAD_ERR_OK) {
        $tmp = $_FILES['staff_picture']['tmp_name'];
        $name = basename($_FILES['staff_picture']['name']);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $new_name = uniqid('staff_') . '.' . $ext;
        if (!is_dir('uploads/staff')) {
            mkdir('uploads/staff', 0755, true);
        }
        $dest = 'uploads/staff/' . $new_name;
        move_uploaded_file($tmp, $dest);
        $picture_path = $dest;
    }

    // Insert staff record including picture path
    $stmt = $conn->prepare("INSERT INTO staff_details (first_name, last_name, address, qualification, mobile_number, designation, department, email, picture_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssss", $first_name, $last_name, $address, $qualification, $mobile_number, $designation, $department, $email, $picture_path);
    if ($stmt->execute()) {
        $message .= "Staff added successfully.<br>";
        // Refresh staff list
        $result_staff = $conn->query("SELECT * FROM staff_details");
        $staff_list = [];
        if ($result_staff) {
            while ($row = $result_staff->fetch_assoc()) {
                $staff_list[] = $row;
            }
        }
    } else {
        $message .= "Error adding staff: " . $conn->error;
    }
    $stmt->close();
}

// Handle Academic Details
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_academic'])) {
    $staff_id = intval($_POST['staff_id']);
    $degree = $_POST['degree'];
    $university = $_POST['university'];
    $year_of_passing = intval($_POST['year_of_passing']);
    $percentage = floatval($_POST['percentage']);

    $stmt = $conn->prepare("INSERT INTO staff_academic_details (staff_id, degree, university, year_of_passing, percentage) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issii", $staff_id, $degree, $university, $year_of_passing, $percentage);
    if ($stmt->execute()) {
        $message .= "Academic record added.<br>";
        // Refresh records
        $result_academic = $conn->query("SELECT sa.*, s.first_name, s.last_name FROM staff_academic_details sa JOIN staff_details s ON sa.staff_id=s.id");
        $academic_records = [];
        if ($result_academic) {
            while ($row = $result_academic->fetch_assoc()) {
                $academic_records[] = $row;
            }
        }
    } else {
        $message .= "Error adding academic record: " . $conn->error;
    }
    $stmt->close();
}

// Handle Non-Academic Activities
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_non_academic'])) {
    $staff_id = intval($_POST['staff_id']);
    $activity_type = $_POST['activity_type'];
    $organization_name = $_POST['organization_name'];
    $role = $_POST['role'];
    $duration = $_POST['duration'];
    $remarks = $_POST['remarks'];

    $stmt = $conn->prepare("INSERT INTO staff_non_academic_details (staff_id, activity_type, organization_name, role, duration, remarks) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $staff_id, $activity_type, $organization_name, $role, $duration, $remarks);
    if ($stmt->execute()) {
        $message .= "Non-academic activity added.<br>";
        // Refresh records
        $result_non_academic = $conn->query("SELECT nad.*, s.first_name, s.last_name FROM staff_non_academic_details nad JOIN staff_details s ON nad.staff_id=s.id");
        $non_academic_records = [];
        if ($result_non_academic) {
            while ($row = $result_non_academic->fetch_assoc()) {
                $non_academic_records[] = $row;
            }
        }
    } else {
        $message .= "Error adding non-academic activity: " . $conn->error;
    }
    $stmt->close();
}

// Handle Work Experience
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_work_experience'])) {
    $staff_id = intval($_POST['staff_id']);
    $organization = $_POST['organization'];
    $role = $_POST['role'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $achievements = $_POST['achievements'];

    $stmt = $conn->prepare("INSERT INTO staff_work_experience (staff_id, organization, role, start_date, end_date, achievements) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $staff_id, $organization, $role, $start_date, $end_date, $achievements);
    if ($stmt->execute()) {
        $message .= "Work experience added.<br>";
        // Refresh records
        $result_work_exp = $conn->query("SELECT we.*, s.first_name, s.last_name FROM staff_work_experience we JOIN staff_details s ON we.staff_id=s.id");
        $work_experience_records = [];
        if ($result_work_exp) {
            while ($row = $result_work_exp->fetch_assoc()) {
                $work_experience_records[] = $row;
            }
        }
    } else {
        $message .= "Error adding work experience: " . $conn->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Staff Management Portal</title>
<!-- Font Awesome & Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<style>
/* Your existing CSS styles */
body {
    font-family: 'Inter', sans-serif; 
    background: #f9f9f9; 
    margin: 0;
    padding: 20px;
}
div.container {
    max-width: 1300px;
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
h3 {
    text-align: center;
    margin-top: 0;
    margin-bottom: 15px;
    color: #444;
    position: relative;
}
h3::before {
    content: "\f11d"; /* university icon */
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    margin-right: 8px;
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
    font-family: 'Inter', sans-serif;
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
th {
    background-color: #f2f2f2;
}
tr:nth-child(even) {
    background-color: #fafafa;
}
</style>
</head>
<body>

<div class="container">

<?php if ($message): ?>
<div class="message success"><?= $message ?></div>
<?php endif; ?>

<h2><i class="fas fa-user"></i> Staff Details</h2>
<!-- Staff Form -->
<section>
<h3><i class="fas fa-user"></i> Add Staff</h3>
<form method="POST" enctype="multipart/form-data">
  <div class="form-group">
    <label for="first_name"><i class="fas fa-user"></i> First Name:</label>
    <input type="text" name="first_name" required />
  </div>
  <div class="form-group">
    <label for="last_name"><i class="fas fa-user"></i> Last Name:</label>
    <input type="text" name="last_name" required />
  </div>
  <div class="form-group">
    <label for="address"><i class="fas fa-map-marker-alt"></i> Address:</label>
    <textarea name="address" rows="3" required></textarea>
  </div>
  <div class="form-group">
    <label for="qualification"><i class="fas fa-graduation-cap"></i> Qualification:</label>
    <select name="qualification" required>
      <option value="">--Select Qualification--</option>
      <option value="UG">UG</option>
      <option value="PG">PG</option>
      <option value="PG with PhD">PG with PhD</option>
    </select>
  </div>
  <div class="form-group">
    <label for="mobile_number"><i class="fas fa-phone"></i> Mobile Number:</label>
    <input type="text" name="mobile_number" required />
  </div>
  <div class="form-group">
    <label for="designation"><i class="fas fa-briefcase"></i> Designation:</label>
    <input type="text" name="designation" required />
  </div>
  <div class="form-group">
    <label for="department"><i class="fas fa-building"></i> Department:</label>
    <input type="text" name="department" required />
  </div>
  <div class="form-group">
    <label for="email"><i class="fas fa-envelope"></i> Email:</label>
    <input type="text" name="email" required />
  </div>
  <div class="form-group">
    <label for="staff_picture"><i class="fas fa-image"></i> Staff Photo:</label>
    <input type="file" name="staff_picture" accept=".jpg,.jpeg,.png" />
  </div>
  <div style="width:100%; text-align:center;">
    <button type="submit" name="add_staff"><i class="fas fa-plus"></i> Add Staff</button>
  </div>
</form>
</section>

<h2><i class="fas fa-user-graduate"></i> Academic Details</h2>
<!-- Academic Form -->
<section>
<h3><i class="fas fa-graduation-cap"></i> Add Academic Record</h3>
<form method="POST">
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
    <label for="degree"><i class="fas fa-book"></i> Degree:</label>
    <input type="text" name="degree" required />
  </div>
  <div class="form-group">
    <label for="university"><i class="fas fa-university"></i> University:</label>
    <input type="text" name="university" required />
  </div>
  <div class="form-group">
    <label for="year_of_passing"><i class="fas fa-calendar"></i> Year of Passing:</label>
    <input type="number" name="year_of_passing" min="1900" max="2100" required />
  </div>
  <div class="form-group">
    <label for="percentage"><i class="fas fa-percentage"></i> Percentage:</label>
    <input type="number" step="0.01" min="0" max="100" name="percentage" required />
  </div>
  <div style="width:100%; text-align:center;">
    <button type="submit" name="add_academic"><i class="fas fa-plus"></i> Add Academic Record</button>
  </div>
</form>
</section>

<h2><i class="fas fa-briefcase"></i> Non-Academic Activities</h2>
<!-- Non-Academic Form -->
<section>
<h3><i class="fas fa-users"></i> Add Non-Academic Activity</h3>
<form method="POST">
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
    <label for="activity_type"><i class="fas fa-handshake"></i> Activity Type:</label>
    <select name="activity_type" required>
      <option value="">--Select Type--</option>
      <option value="Volunteer">Volunteer</option>
      <option value="Club Member">Club Member</option>
      <option value="Association Member">Association Member</option>
    </select>
  </div>
  <div class="form-group">
    <label for="organization_name"><i class="fas fa-building"></i> Organization Name:</label>
    <input type="text" name="organization_name" required />
  </div>
  <div class="form-group">
    <label for="role"><i class="fas fa-user-tag"></i> Role:</label>
    <input type="text" name="role" required />
  </div>
  <div class="form-group">
    <label for="duration"><i class="fas fa-clock"></i> Duration:</label>
    <input type="text" name="duration" required />
  </div>
  <div class="form-group">
    <label for="remarks"><i class="fas fa-comment"></i> Remarks:</label>
    <textarea name="remarks" rows="2"></textarea>
  </div>
  <div style="width:100%; text-align:center;">
    <button type="submit" name="add_non_academic"><i class="fas fa-plus"></i> Add Activity</button>
  </div>
</form>
</section>

<!-- Work Experience & Achievements -->
<h2><i class="fas fa-briefcase"></i> Work Experience & Achievements</h2>
<!-- Work Experience Form -->
<section>
<h3><i class="fas fa-briefcase"></i> Add Work Experience</h3>
<form method="POST">
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
    <label for="organization"><i class="fas fa-building"></i> Organization:</label>
    <input type="text" name="organization" required />
  </div>
  <div class="form-group">
    <label for="role"><i class="fas fa-user-tag"></i> Role:</label>
    <input type="text" name="role" required />
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
    <label for="achievements"><i class="fas fa-star"></i> Special Achievements:</label>
    <textarea name="achievements" rows="3"></textarea>
  </div>
  <div style="width:100%; text-align:center;">
    <button type="submit" name="add_work_experience"><i class="fas fa-plus"></i> Add Work Experience</button>
  </div>
</form>
</section>

<!-- Display Work Experience -->
<h3><i class="fas fa-list"></i> Work Experience & Achievements</h3>
<table>
  <thead>
    <tr>
      <th>Staff Member</th>
      <th>Organization</th>
      <th>Role</th>
      <th>Start Date</th>
      <th>End Date</th>
      <th>Duration</th>
      <th>Achievements</th>
    </tr>
  </thead>
  <tbody>
    <?php if($work_experience_records): ?>
      <?php foreach($work_experience_records as $rec): ?>
      <tr>
        <td><?= htmlspecialchars($rec['first_name'].' '.$rec['last_name']) ?></td>
        <td><?= htmlspecialchars($rec['organization']) ?></td>
        <td><?= htmlspecialchars($rec['role']) ?></td>
        <td><?= htmlspecialchars($rec['start_date']) ?></td>
        <td><?= htmlspecialchars($rec['end_date']) ?></td>
        <td>
          <?php
            $start = new DateTime($rec['start_date']);
            $end = new DateTime($rec['end_date']);
            $interval = $start->diff($end);
            echo $interval->format('%y years %m months');
          ?>
        </td>
        <td><?= htmlspecialchars($rec['achievements']) ?></td>
      </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr><td colspan="7">No work experience records.</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<!-- View existing records (staff, academic, non-academic, etc.) -->
<h3><i class="fas fa-list"></i> View Records</h3>

<h4>Staff Members</h4>
<table>
  <thead>
    <tr>
      <th>Picture</th>
      <th>First Name</th><th>Last Name</th><th>Address</th><th>Qualification</th><th>Mobile</th><th>Designation</th><th>Department</th><th>Email</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($staff_list as $s): ?>
    <tr>
      <td>
        <?php if ($s['picture_path']): ?>
          <img src="<?= htmlspecialchars($s['picture_path']) ?>" width="50" alt="Staff Photo" />
        <?php else: ?>
          N/A
        <?php endif; ?>
      </td>
      <td><?= htmlspecialchars($s['first_name']) ?></td>
      <td><?= htmlspecialchars($s['last_name']) ?></td>
      <td><?= htmlspecialchars($s['address']) ?></td>
      <td><?= htmlspecialchars($s['qualification']) ?></td>
      <td><?= htmlspecialchars($s['mobile_number']) ?></td>
      <td><?= htmlspecialchars($s['designation']) ?></td>
      <td><?= htmlspecialchars($s['department']) ?></td>
      <td><?= htmlspecialchars($s['email']) ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<!-- You can similarly add images for other records if needed -->

</div>
</body>
</html>
