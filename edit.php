<?php
require_once __DIR__ . '/include/auth.php';
$currentUser = iqac_require_login(['staff', 'tutor', 'hod', 'iqac', 'admin', 'super_admin']);
mysqli_report(MYSQLI_REPORT_OFF);
// edit.php
$host='localhost'; $user='root'; $pass=''; $db='mou';
$conn=@new mysqli($host,$user,$pass,$db);
if($conn->connect_error) $conn=@new mysqli($host,$user,$pass,'iqac');
if($conn->connect_error) die("DB connection error: " . $conn->connect_error);

if (!isset($_GET['id'])) {
    header("Location: upload.php?msg=Select a record to edit");
    exit;
}
$id=intval($_GET['id']);

if($_SERVER['REQUEST_METHOD']=='POST'){
    // Get submitted data
    $department=$_POST['department'];
    $year=$_POST['year'];
    $company=$_POST['company'];
    $status=$_POST['status'];

    // Fetch existing record
    $res=$conn->query("SELECT * FROM mou_files WHERE id=$id");
    if($res && $res->num_rows>0){
        $record=$res->fetch_assoc();

        // Handle file upload if new file selected
        $filename=$record['filename'];
        $filepath=$record['filepath'];
        if(isset($_FILES['file']) && $_FILES['file']['error']==UPLOAD_ERR_OK){
            $file=$_FILES['file'];
            $name=basename(preg_replace('/[^a-zA-Z0-9._-]/','',$file['name']));
            $targetDir='uploads/';
            $newPath=$targetDir.time().'_'.$name;
            if(move_uploaded_file($file['tmp_name'],$newPath)){
                @unlink($filepath);
                $filename=$name;
                $filepath=$newPath;
            }
        }

        // Handle image upload if new image
        $imagePath=$record['image'];
        if(isset($_FILES['image']) && $_FILES['image']['error']==UPLOAD_ERR_OK){
            $img=$_FILES['image'];
            $allowed=['image/jpeg','image/png','image/jpg'];
            if(in_array($img['type'],$allowed)){
                $imgName=basename(preg_replace('/[^a-zA-Z0-9._-]/','',$img['name']));
                $newImagePath='uploads/images/'.time().'_'.$imgName;
                if(move_uploaded_file($img['tmp_name'],$newImagePath)){
                    @unlink($record['image']);
                    $imagePath=$newImagePath;
                }
            }
        }

        // Update record
        $stmt=$conn->prepare("UPDATE mou_files SET department=?, year=?, company=?, status=?, filename=?, filepath=?, image=? WHERE id=?");
        $stmt->bind_param("sssssssi", $department, $year, $company, $status, $filename, $filepath, $imagePath, $id);
        $stmt->execute();
        $stmt->close();
        header("Location: upload.php?msg=Record updated");
        exit;
    }else{
        die("Record not found");
    }
}

// Fetch current data
$res=$conn->query("SELECT * FROM mou_files WHERE id=$id");
if($res && $res->num_rows>0){
    $record=$res->fetch_assoc();
}else{
    die("Record not found");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Edit Record - MOU</title>
<style>
body {
  font-family: 'Inter', sans-serif;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background-color: #f4f4f4;
  margin: 0;
}
.container {
  width: 90%;
  max-width: 800px;
  background: #fff;
  padding: 30px;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
h2 {
  text-align: center;
  margin-bottom: 20px;
}
table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 20px;
}
td {
  padding: 12px;
  vertical-align: top;
}
th {
  text-align: left;
  padding: 12px;
  background-color: #eee;
}
input[type="text"], select {
  width: 100%;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 4px;
}
input[type="file"] {
  padding: 4px;
}
button {
  display: block;
  width: 100%;
  padding: 12px;
  background-color: #007bff;
  color: #fff;
  border: none;
  border-radius: 4px;
  font-size: 16px;
  cursor: pointer;
}
button:hover {
  background-color: #0056b3;
}
.current-file, .current-image {
  font-size: 0.9em;
  color: #555;
}
img {
  border: 1px solid #ccc;
  max-width: 150px;
  height: auto;
  display: block;
  margin-top: 8px;
}
</style>
</head>
<body>
<div class="container">
<h2>Edit Record</h2>
<form method="POST" enctype="multipart/form-data">
<table>
<tr>
  <th>Department</th>
  <td><input type="text" name="department" value="<?= htmlspecialchars($record['department']) ?>" required></td>
</tr>
<tr>
  <th>Year</th>
  <td><input type="text" name="year" value="<?= htmlspecialchars($record['year']) ?>" required></td>
</tr>
<tr>
  <th>Company</th>
  <td><input type="text" name="company" value="<?= htmlspecialchars($record['company']) ?>" required></td>
</tr>
<tr>
  <th>Status</th>
  <td><input type="text" name="status" value="<?= htmlspecialchars($record['status']) ?>" required></td>
</tr>
<tr>
  <th>Document File<br><small>Upload new to replace</small></th>
  <td>
    <input type="file" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
    <div class="current-file">Current: <?= htmlspecialchars($record['filename']) ?></div>
  </td>
</tr>
<tr>
  <th>Image<br><small>Upload new to replace</small></th>
  <td>
    <input type="file" name="image" accept=".jpg,.jpeg,.png">
    <div class="current-image">
      <?php if($record['image']): ?>
        <img src="<?= htmlspecialchars($record['image']) ?>" alt="Current Image" />
      <?php else: ?>
        N/A
      <?php endif; ?>
    </div>
  </td>
</tr>
<tr>
  <td colspan="2" style="text-align:center;">
    <button type="submit">Save Changes</button>
  </td>
</tr>
</table>
</form>
</div>
</body>
</html>
