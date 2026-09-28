<?php
// Connect to database
$host='localhost'; $user='root'; $pass=''; $db='iqac';
$conn=new mysqli($host,$user,$pass,$db);
if($conn->connect_error) die("DB connection error: " . $conn->connect_error);

// Initialize variables
$message='';
$students_list = [];
$student_projects = [];

// Fetch students for dropdown
$result_students = $conn->query("SELECT id, first_name, last_name FROM students");
if($result_students){
    while($row=$result_students->fetch_assoc()){
        $students_list[]=$row;
    }
}

// Fetch existing projects
$result_projects=$conn->query("SELECT sp.*, s.first_name, s.last_name FROM student_projects sp JOIN students s ON sp.student_id=s.id");
if($result_projects){
    while($row=$result_projects->fetch_assoc()){
        $student_projects[]=$row;
    }
}

// Handle form submission to add a new project
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['save_project'])){
    $student_id=intval($_POST['project_student_id']);
    $project_title=trim($_POST['project_title']);
    $project_type=$_POST['project_type'];
    $remarks=trim($_POST['remarks']);

    $stmt=$conn->prepare("INSERT INTO student_projects (student_id, project_title, project_type, remarks) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $student_id, $project_title, $project_type, $remarks);
    if($stmt->execute()){
        $message='Project added successfully.';
        // Refresh project list
        $student_projects=[];
        $res_proj=$conn->query("SELECT sp.*, s.first_name, s.last_name FROM student_projects sp JOIN students s ON sp.student_id=s.id");
        if($res_proj){
            while($row=$res_proj->fetch_assoc()){
                $student_projects[]=$row;
            }
        }
    } else {
        $message='Error: ' . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Student Projects</title>
<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<style>
body {
    font-family: 'Inter', sans-serif; 
    background: #f9f9f9; 
    margin: 0; padding: 20px;
}
h2 {
    text-align: center; margin-bottom: 20px;
}
.message {
    padding: 10px; margin: 10px 0; border-radius: 4px;
}
.message.success { background-color: #d4edda; color: #155724; }
.message.error { background-color: #f8d7da; color: #721c24; }
form {
    max-width: 600px; margin: 0 auto 30px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
form .form-group { margin-bottom: 15px; }
label { display: block; margin-bottom: 5px; font-weight: 600; }
input[type=text], select, textarea {
    width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 8px;
}
button {
    background-color: #007bff; color: #fff; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-family: 'Inter', sans-serif;
}
button:hover { background-color: #0056b3; }
table {
    width: 100%; border-collapse: collapse; margin-top: 20px;
}
table, th, td { border: 1px solid #333; }
th, td { padding: 10px; }
th { background-color: #f2f2f2; }
</style>
</head>
<body>

<h2><i class="fas fa-project-diagram"></i> Student Projects</h2>

<?php if($message): ?>
<div class="message success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<!-- Form to add new project -->
<form method="POST">
  <div class="form-group">
    <label for="project_student_id"><i class="fas fa-user"></i> Select Student:</label>
    <select name="project_student_id" required>
      <option value="">--Select Student--</option>
      <?php foreach($students_list as $s): ?>
        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['first_name'].' '.$s['last_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group">
    <label for="project_title"><i class="fas fa-file-alt"></i> Title of the Project:</label>
    <input type="text" name="project_title" required />
  </div>
  <div class="form-group">
    <label for="project_type"><i class="fas fa-layer-group"></i> Type of the Project:</label>
    <select name="project_type" required>
      <option value="">--Select Type--</option>
      <option value="Community">Community</option>
      <option value="Research">Research</option>
      <option value="Industry">Industry</option>
    </select>
  </div>
  <div class="form-group">
    <label for="remarks"><i class="fas fa-comments"></i> Remarks:</label>
    <textarea name="remarks" rows="3"></textarea>
  </div>
  <div style="text-align:center;">
    <button type="submit" name="save_project"><i class="fas fa-plus"></i> Add Project</button>
  </div>
</form>

<!-- Existing projects table -->
<h3>Existing Projects</h3>
<table>
  <thead>
    <tr>
      <th>Student Name</th>
      <th>Title</th>
      <th>Type</th>
      <th>Remarks</th>
      <th>Date Added</th>
    </tr>
  </thead>
  <tbody>
    <?php if($student_projects): ?>
      <?php foreach($student_projects as $proj): ?>
        <tr>
          <td><?= htmlspecialchars($proj['first_name'].' '.$proj['last_name']) ?></td>
          <td><?= htmlspecialchars($proj['project_title']) ?></td>
          <td><?= htmlspecialchars($proj['project_type']) ?></td>
          <td><?= htmlspecialchars($proj['remarks']) ?></td>
          <td><?= $proj['date_created'] ?></td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr><td colspan="5" style="text-align:center;">No projects found.</td></tr>
    <?php endif; ?>
  </tbody>
</table>

</body>
</html>