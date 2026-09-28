<?php
mysqli_report(MYSQLI_REPORT_OFF);
// Connect to database
$host='localhost'; $user='root'; $pass=''; $db='iqac';
$conn=new mysqli($host,$user,$pass,$db);
if($conn->connect_error) die("DB connection error: " . $conn->connect_error);

$message='';

// Fetch existing records
$gaps = [];
$result=$conn->query("SELECT * FROM curriculum_gaps");
if($result){
    while($row=$result->fetch_assoc()){
        $gaps[]=$row;
    }
}

// Handle form submission to add new record
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['save_gap'])){
    $course_code = $_POST['course_code'];
    $additional_content = $_POST['additional_content'];
    $action_taken = $_POST['action_taken'];
    $date = $_POST['date'];
    $resource_person = $_POST['resource_person'];
    $mode = $_POST['mode'];
    $no_of_students = intval($_POST['no_of_students']);
    $relevance_to_POs_PSOs = $_POST['relevance_to_POs_PSOs'];

    $stmt=$conn->prepare("INSERT INTO curriculum_gaps (course_code, additional_content, action_taken, date, resource_person, mode, no_of_students, relevance_to_POs_PSOs) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssis", $course_code, $additional_content, $action_taken, $date, $resource_person, $mode, $no_of_students, $relevance_to_POs_PSOs);
    
    if($stmt->execute()){
        $message='Record added successfully.';
        // Refresh data
        $gaps=[];
        $result=$conn->query("SELECT * FROM curriculum_gaps");
        if($result){
            while($row=$result->fetch_assoc()){
                $gaps[]=$row;
            }
        }
    } else {
        $message='Error: '.$conn->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Curriculum Gaps</title>
<style>
body {
    font-family: 'Inter', sans-serif; 
    background:#f0f0f0; 
    margin:0; padding:20px;
}
h2 {
    text-align:center; margin-bottom:20px;
}
form {
    background:#fff; padding:20px; border-radius:8px; max-width:1000px; margin:auto; box-shadow:0 0 10px rgba(0,0,0,0.1);
}
form .form-group {
    display:flex; flex-direction:column; margin-bottom:15px;
}
label {
    margin-bottom:5px; font-weight:600;
}
input[type=text], input[type=date], textarea, select, input[type=number] {
    padding:8px; border:1px solid #ccc; border-radius:4px; font-family:'Inter', sans-serif;
}
button {
    padding:10px 20px; background:#007bff; color:#fff; border:none; border-radius:8px; cursor:pointer; font-family:'Inter', sans-serif;
}
button:hover {
    background:#0056b3;
}
table {
    width:100%; border-collapse:collapse; margin-top:30px; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 0 10px rgba(0,0,0,0.1);
}
table th, table td {
    border:1px solid #ccc; padding:8px; text-align:left;
}
table th {
    background:#f2f2f2;
}
</style>
</head>
<body>

<h2>Curriculum Gaps</h2>

<?php if($message): ?>
<div style="max-width:1000px; margin:auto; padding:10px; background:#d4edda; color:#155724; border:1px solid #c3e6cb; border-radius:8px; margin-bottom:20px;">
    <?= $message ?>
</div>
<?php endif; ?>

<!-- Form to add new gap -->
<form method="POST" style="margin:auto; max-width:1000px;">
    <div class="form-group">
        <label for="course_code">Course Code:</label>
        <input type="text" name="course_code" required />
    </div>
    <div class="form-group">
        <label for="additional_content">Additional Content Identified:</label>
        <textarea name="additional_content" rows="3" required></textarea>
    </div>
    <div class="form-group">
        <label for="action_taken">Action Taken:</label>
        <textarea name="action_taken" rows="3" required></textarea>
    </div>
    <div class="form-group">
        <label for="date">Date - Month - Year:</label>
        <input type="date" name="date" required />
    </div>
    <div class="form-group">
        <label for="resource_person">Resource Person with Designation:</label>
        <input type="text" name="resource_person" required />
    </div>
    <div class="form-group">
        <label for="mode">Mode:</label>
        <input type="text" name="mode" required />
    </div>
    <div class="form-group">
        <label for="no_of_students">Number of Students Present:</label>
        <input type="number" name="no_of_students" min="0" required />
    </div>
    <div class="form-group">
        <label for="relevance_to_POs_PSOs">Relevance to POs and PSOs:</label>
        <textarea name="relevance_to_POs_PSOs" rows="3" required></textarea>
    </div>
    <div style="text-align:center;">
        <button type="submit" name="save_gap">Save</button>
    </div>
</form>

<!-- Display existing records -->
<h3 style="text-align:center; margin-top:40px;">Existing Curriculum Gaps</h3>
<table>
    <thead>
        <tr>
            <th>Course Code</th>
            <th>Additional Content</th>
            <th>Action Taken</th>
            <th>Date (Y-M-D)</th>
            <th>Resource Person & Designation</th>
            <th>Mode</th>
            <th>No. of Students Present</th>
            <th>Relevance to POs & PSOs</th>
        </tr>
    </thead>
    <tbody>
        <?php if($gaps): ?>
            <?php foreach($gaps as $g): ?>
            <tr>
                <td><?= htmlspecialchars($g['course_code']) ?></td>
                <td><?= htmlspecialchars($g['additional_content']) ?></td>
                <td><?= htmlspecialchars($g['action_taken']) ?></td>
                <td><?= htmlspecialchars($g['date']) ?></td>
                <td><?= htmlspecialchars($g['resource_person']) ?></td>
                <td><?= htmlspecialchars($g['mode']) ?></td>
                <td><?= htmlspecialchars($g['no_of_students']) ?></td>
                <td><?= htmlspecialchars($g['relevance_to_POs_PSOs']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8" style="text-align:center;">No records found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
