<?php
require_once __DIR__ . '/include/auth.php';

$userContext = iqac_require_login(['staff', 'tutor', 'hod', 'iqac', 'admin', 'super_admin']);
$currentRole = iqac_effective_role($userContext);
$canManageStudentStatus = in_array($currentRole, ['staff', 'tutor', 'hod', 'iqac', 'admin', 'super_admin'], true);
$canUseLegacyStudentForms = in_array($currentRole, ['admin', 'super_admin'], true);
$token = iqac_csrf_token();

// Database connection
$host='localhost'; $user='root'; $pass=''; $db='iqac';
$conn=new mysqli($host,$user,$pass,$db);
if($conn->connect_error) die("DB connection error: " . $conn->connect_error);

// Initialize variables
$message='';
$students_list = [];
$status_students = [];
$staffSubjectCodes = [];
$staffClassYears = [];
$statusCounts = ['total' => 0, 'unlocked' => 0, 'locked' => 0];
$scopeLabel = match ($currentRole) {
    'staff', 'tutor' => 'Assigned students and subject classes',
    'hod' => 'Department students',
    'iqac' => 'College quality review',
    'admin' => 'Admin student control',
    'super_admin' => 'Super admin full control',
    default => 'Allowed student control',
};
$institutions = [];
$achievement_records = [];
$academic_records = [];
$non_academic_records = [];

// Fetch existing data with live-schema compatibility.
$students_list = iqac_student_list($conn);

if(iqac_table_exists($conn, 'student_details')){
    if(
        in_array($currentRole, ['staff', 'tutor'], true)
        && (int)($userContext['staff_id'] ?? 0) > 0
        && iqac_table_exists($conn, 'staff_subject_allocation')
    ){
        $staffId = (int)$userContext['staff_id'];
        $stmt = $conn->prepare('SELECT subject_code, class_year FROM staff_subject_allocation WHERE staff_id = ? ORDER BY class_year, subject_code');
        if($stmt){
            $stmt->bind_param('i', $staffId);
            $stmt->execute();
            $subjectResult = $stmt->get_result();
            while($subjectRow = $subjectResult->fetch_assoc()){
                $staffSubjectCodes[] = (string)$subjectRow['subject_code'];
                if((string)$subjectRow['class_year'] !== ''){
                    $staffClassYears[] = (string)$subjectRow['class_year'];
                }
            }
            $stmt->close();
            $staffSubjectCodes = array_values(array_unique($staffSubjectCodes));
            $staffClassYears = array_values(array_unique($staffClassYears));
        }
    }

    $statusSql = "SELECT sd.student_id, sd.user_id, sd.full_name, sd.roll_number, sd.department, sd.batch, sd.section, sd.current_semester, sd.stage1_status, u.is_active
        FROM student_details sd
        JOIN users u ON u.id = sd.user_id";
    $statusTypes = '';
    $statusParams = [];
    $statusWhere = [];

    if(in_array($currentRole, ['staff', 'tutor'], true)){
        if((int)($userContext['staff_id'] ?? 0) > 0 && (int)($userContext['tutor_assigned_students'] ?? 0) > 0){
            $statusWhere[] = 'sd.tutor_staff_id = ?';
            $statusTypes .= 'i';
            $statusParams[] = (int)$userContext['staff_id'];
        }
        if($staffClassYears !== [] && (string)($userContext['department'] ?? '') !== ''){
            $classPlaceholders = implode(',', array_fill(0, count($staffClassYears), '?'));
            $statusWhere[] = "sd.department = ? AND sd.class_year IN ($classPlaceholders)";
            $statusTypes .= 's' . str_repeat('s', count($staffClassYears));
            $statusParams[] = (string)$userContext['department'];
            foreach($staffClassYears as $classYear){
                $statusParams[] = $classYear;
            }
        } elseif((string)($userContext['department'] ?? '') !== ''){
            $statusWhere[] = 'sd.department = ?';
            $statusTypes .= 's';
            $statusParams[] = (string)$userContext['department'];
        }
    } elseif($currentRole === 'hod' && (string)($userContext['department'] ?? '') !== ''){
        $statusWhere[] = 'sd.department = ?';
        $statusTypes .= 's';
        $statusParams[] = (string)$userContext['department'];
    }

    if($statusWhere){
        $statusSql .= ' WHERE ' . implode(' OR ', $statusWhere);
    }
    $statusSql .= " ORDER BY sd.department, sd.batch, sd.section, sd.roll_number";

    $stmt = $conn->prepare($statusSql);
    if($stmt){
        if($statusTypes !== ''){
            $stmt->bind_param($statusTypes, ...$statusParams);
        }
        $stmt->execute();
        $result_status_students = $stmt->get_result();
        while($row = $result_status_students->fetch_assoc()){
            $row['scope_subjects'] = $staffSubjectCodes !== [] ? implode(', ', $staffSubjectCodes) : 'All allowed';
            $status_students[] = $row;
        }
        $stmt->close();
    }
}
$statusCounts['total'] = count($status_students);
foreach($status_students as $statusStudent){
    if((int)$statusStudent['is_active'] === 1){
        $statusCounts['unlocked']++;
    } else {
        $statusCounts['locked']++;
    }
}

if(iqac_table_exists($conn, 'institutions') && ($result_institutions = $conn->query("SELECT * FROM institutions"))){
    while($row = $result_institutions->fetch_assoc()){
        $institutions[] = $row;
    }
}

$studentTable = iqac_student_table($conn);
$studentPk = iqac_student_pk($conn, $studentTable);
$studentName = iqac_student_name_expr($studentTable, 's');
if($studentTable && iqac_table_exists($conn, 'achievement_details') && ($result_achievement=$conn->query("SELECT ach.*, $studentName AS student_name FROM achievement_details ach JOIN `$studentTable` s ON ach.student_id=s.`$studentPk`"))){
    while($row=$result_achievement->fetch_assoc()){
        $parts = explode(' ', trim((string)$row['student_name']), 2);
        $row['first_name'] = $parts[0] ?? '';
        $row['last_name'] = $parts[1] ?? '';
        $achievement_records[]=$row;
    }
}
if($studentTable && iqac_table_exists($conn, 'academic_details') && ($result_academic=$conn->query("SELECT academic_details.*, $studentName AS student_name FROM academic_details JOIN `$studentTable` s ON academic_details.student_id=s.`$studentPk`"))){
    while($row=$result_academic->fetch_assoc()){
        $parts = explode(' ', trim((string)$row['student_name']), 2);
        $row['first_name'] = $parts[0] ?? '';
        $row['last_name'] = $parts[1] ?? '';
        $academic_records[]=$row;
    }
}
if($studentTable && iqac_table_exists($conn, 'non_academic_details') && ($result_non_academic = $conn->query("SELECT nad.*, $studentName AS student_name FROM non_academic_details nad JOIN `$studentTable` s ON nad.student_id=s.`$studentPk`"))){
    while($row=$result_non_academic->fetch_assoc()){
        $parts = explode(' ', trim((string)$row['student_name']), 2);
        $row['first_name'] = $parts[0] ?? '';
        $row['last_name'] = $parts[1] ?? '';
        $non_academic_records[]=$row;
    }
}

$national_levels = ['National Level', 'State Level', 'District Level', 'Local Level'];

// Handle Student Save
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['student_save'])){
    if(!$canUseLegacyStudentForms){
        $message .= 'Only admin users can create student records from this legacy form.<br>';
    } elseif(!iqac_verify_csrf($_POST['csrf_token'] ?? null)){
        $message .= 'Invalid security token. Please try again.<br>';
    } else {
    if(!iqac_table_exists($conn, 'students')){
        $message .= 'Student save is unavailable until this legacy form is mapped to student_details.<br>';
    } else {
    // Collect form data
    $first_name=$_POST['first_name'];
    $last_name=$_POST['last_name'];
    $age=intval($_POST['age']);
    $dob=$_POST['dob'];
    $entry_type=$_POST['entry_type'];
    $management_quota=$_POST['management_quota'];
    $counseling_quota=$_POST['counseling_quota'];
    $address=$_POST['address'];
    $email_id=$_POST['email_id'];
    $father_mobile=$_POST['father_mobile'];
    $admission_date=$_POST['admission_date'];
    $batch_year=$_POST['batch_year'];
    $student_mobile=$_POST['student_mobile'];
    // Parent details
    $father_name=isset($_POST['father_name']) ? $_POST['father_name'] : '';
    $mother_name=isset($_POST['mother_name']) ? $_POST['mother_name'] : '';
    $father_occupation=isset($_POST['father_occupation']) ? $_POST['father_occupation'] : '';
    $mother_occupation=isset($_POST['mother_occupation']) ? $_POST['mother_occupation'] : '';
    $father_income=isset($_POST['father_income']) ? floatval($_POST['father_income']) : 0;

    // Upload student photo
    $student_photo_path='';
    if(isset($_FILES['student_photo']) && $_FILES['student_photo']['error']==UPLOAD_ERR_OK){
        $tmp=$_FILES['student_photo']['tmp_name'];
        $name=basename($_FILES['student_photo']['name']);
        $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
        $new_name=uniqid('student_').'.'.$ext;
        if(!is_dir('uploads/students')){ mkdir('uploads/students',0755,true); }
        $dest='uploads/students/'.$new_name;
        move_uploaded_file($tmp,$dest);
        $student_photo_path=$dest;
    }

    // Upload father photo
    $father_photo_path='';
    if(isset($_FILES['father_photo']) && $_FILES['father_photo']['error']==UPLOAD_ERR_OK){
        $tmp=$_FILES['father_photo']['tmp_name'];
        $name=basename($_FILES['father_photo']['name']);
        $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
        $new_name=uniqid('father_').'.'.$ext;
        if(!is_dir('uploads/parents')){ mkdir('uploads/parents',0755,true); }
        $dest='uploads/parents/'.$new_name;
        move_uploaded_file($tmp,$dest);
        $father_photo_path=$dest;
    }

    // Upload mother photo
    $mother_photo_path='';
    if(isset($_FILES['mother_photo']) && $_FILES['mother_photo']['error']==UPLOAD_ERR_OK){
        $tmp=$_FILES['mother_photo']['tmp_name'];
        $name=basename($_FILES['mother_photo']['name']);
        $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
        $new_name=uniqid('mother_').'.'.$ext;
        if(!is_dir('uploads/parents')){ mkdir('uploads/parents',0755,true); }
        $dest='uploads/parents/'.$new_name;
        move_uploaded_file($tmp,$dest);
        $mother_photo_path=$dest;
    }

    // Insert into students table
    $stmt=$conn->prepare("INSERT INTO students (
        first_name, last_name, age, father_name, mother_name, address,
        father_occupation, mother_occupation, father_income, admission_date,
        batch_year, student_mobile, email_id, father_mobile, student_photo, dob, entry_type, management_quota, counselling_quota, father_photo, mother_photo
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if(!$stmt){
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ssissssdsisssssssss", $first_name, $last_name, $age, $father_name, $mother_name, $address, $father_occupation, $mother_occupation, $father_income, $admission_date, $batch_year, $student_mobile, $email_id, $father_mobile, $student_photo_path, $dob, $entry_type, $management_quota, $counseling_quota, $father_photo_path, $mother_photo_path);
    if($stmt->execute()){
        $message.="Student added.<br>";
        // refresh list
        $students_list=[];
        $res=$conn->query("SELECT id, first_name, last_name FROM students");
        if($res){
            while($row=$res->fetch_assoc()){
                $students_list[]=$row;
            }
        }
    } else {
        $message.='Error: '.$stmt->error.'<br>';
    }
    $stmt->close();
    }
    }
}

// Handle student account lock/unlock for staff, principal-level, and admin users.
if($_SERVER['REQUEST_METHOD']=='POST' && ($_POST['action'] ?? '') === 'student_status_toggle'){
    if(!$canManageStudentStatus){
        $message .= 'You are not allowed to update student status.<br>';
    } elseif(!iqac_verify_csrf($_POST['csrf_token'] ?? null)){
        $message .= 'Invalid security token. Please try again.<br>';
    } elseif(!iqac_table_exists($conn, 'student_details')){
        $message .= 'Student status is unavailable because student_details is missing.<br>';
    } else {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $statusAction = (string)($_POST['status_action'] ?? '');
        $newActive = $statusAction === 'unlock' ? 1 : 0;

        if(!$studentId || !in_array($statusAction, ['lock', 'unlock'], true)){
            $message .= 'Select a student and lock/unlock action.<br>';
        } else {
            $allowedSql = 'SELECT sd.user_id, sd.full_name, sd.roll_number, sd.department FROM student_details sd WHERE sd.student_id = ?';
            $allowedTypes = 'i';
            $allowedParams = [$studentId];
            if(in_array($currentRole, ['staff', 'tutor'], true)){
                $scope = [];
                if((int)($userContext['staff_id'] ?? 0) > 0 && (int)($userContext['tutor_assigned_students'] ?? 0) > 0){
                    $scope[] = 'sd.tutor_staff_id = ?';
                    $allowedTypes .= 'i';
                    $allowedParams[] = (int)$userContext['staff_id'];
                }
                if($staffClassYears !== [] && (string)($userContext['department'] ?? '') !== ''){
                    $classPlaceholders = implode(',', array_fill(0, count($staffClassYears), '?'));
                    $scope[] = "sd.department = ? AND sd.class_year IN ($classPlaceholders)";
                    $allowedTypes .= 's' . str_repeat('s', count($staffClassYears));
                    $allowedParams[] = (string)$userContext['department'];
                    foreach($staffClassYears as $classYear){
                        $allowedParams[] = $classYear;
                    }
                } elseif((string)($userContext['department'] ?? '') !== ''){
                    $scope[] = 'sd.department = ?';
                    $allowedTypes .= 's';
                    $allowedParams[] = (string)$userContext['department'];
                }
                $allowedSql .= $scope ? ' AND (' . implode(' OR ', $scope) . ')' : ' AND 1=0';
            } elseif($currentRole === 'hod' && (string)($userContext['department'] ?? '') !== ''){
                $allowedSql .= ' AND sd.department = ?';
                $allowedTypes .= 's';
                $allowedParams[] = (string)$userContext['department'];
            }
            $allowedSql .= ' LIMIT 1';

            $stmt = $conn->prepare($allowedSql);
            $stmt->bind_param($allowedTypes, ...$allowedParams);
            $stmt->execute();
            $studentRow = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if(!$studentRow){
                $message .= 'Student not found in your allowed scope.<br>';
            } else {
                $conn->begin_transaction();
                try {
                    $userUpdateSql = iqac_column_exists($conn, 'users', 'locked_until')
                        ? 'UPDATE users SET is_active = ?, locked_until = NULL WHERE id = ?'
                        : 'UPDATE users SET is_active = ? WHERE id = ?';
                    $stmt = $conn->prepare($userUpdateSql);
                    $studentUserId = (int)$studentRow['user_id'];
                    $stmt->bind_param('ii', $newActive, $studentUserId);
                    $stmt->execute();
                    $stmt->close();

                    iqac_audit($conn, 'student_' . $statusAction, (int)$userContext['id'], (string)$userContext['role'], $studentRow['roll_number'] . ' - ' . $studentRow['full_name']);
                    $conn->commit();
                    $message .= 'Student ' . htmlspecialchars((string)$studentRow['roll_number']) . ' ' . ($statusAction === 'unlock' ? 'unlocked' : 'locked') . '.<br>';
                    foreach($status_students as &$statusStudent){
                        if((int)$statusStudent['student_id'] === $studentId){
                            $statusStudent['is_active'] = $newActive;
                            break;
                        }
                    }
                    unset($statusStudent);
                } catch(Throwable $e) {
                    $conn->rollback();
                    $message .= 'Status update failed: ' . htmlspecialchars($e->getMessage()) . '<br>';
                }
            }
        }
    }
}

// Handle Academic Details
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['academic_save'])){
    if(!$canUseLegacyStudentForms){
        $message .= 'Only admin users can add legacy academic records.<br>';
    } elseif(!iqac_verify_csrf($_POST['csrf_token'] ?? null)){
        $message .= 'Invalid security token. Please try again.<br>';
    } elseif(!iqac_table_exists($conn, 'academic_details')){
        $message .= 'Academic details save is unavailable because academic_details is not present in the live schema.<br>';
    } else {
    $student_id=intval($_POST['student_id']);
    $institution_type=$_POST['institution_type'];
    $institution_name=$_POST['institution_name'];
    $total_marks=intval($_POST['total_marks']);
    $percentage=floatval($_POST['percentage']);
    $year_completed=$_POST['year_completed'];
    $roll_number=$_POST['roll_number'];

    $stmt=$conn->prepare("INSERT INTO academic_details (student_id, institution_type, institution_name, total_marks, percentage, year_completed, roll_number) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if(!$stmt){
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("issdiss", $student_id, $institution_type, $institution_name, $total_marks, $percentage, $year_completed, $roll_number);
    if($stmt->execute()){
        $message.='Academic record added.<br>';
        $academic_records=[];
        $res=$conn->query("SELECT academic_details.*, s.first_name, s.last_name FROM academic_details JOIN students s ON academic_details.student_id=s.id");
        if($res){
            while($row=$res->fetch_assoc()){
                $academic_records[]=$row;
            }
        }
    }
    $stmt->close();
    }
}

// Handle Achievement Details
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['achievement_save'])){
    if(!$canUseLegacyStudentForms){
        $message .= 'Only admin users can add legacy achievement records.<br>';
    } elseif(!iqac_verify_csrf($_POST['csrf_token'] ?? null)){
        $message .= 'Invalid security token. Please try again.<br>';
    } elseif(!iqac_table_exists($conn, 'achievement_details')){
        $message .= 'Achievement save is unavailable because achievement_details is not present in the live schema.<br>';
    } else {
    $student_id=intval($_POST['student_id']);
    $achievement_title=$_POST['achievement_title'];
    $description=$_POST['description'];
    $date_awarded=$_POST['date_awarded'];
    $achievement_level=$_POST['achievement_level'];

    // Upload certificate
    $certificate_path='';
    if(isset($_FILES['certificate']) && $_FILES['certificate']['error']==UPLOAD_ERR_OK){
        $tmp=$_FILES['certificate']['tmp_name'];
        $name=basename($_FILES['certificate']['name']);
        $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
        $new_name=uniqid('cert_').'.'.$ext;
        if(!is_dir('uploads/certificates')){ mkdir('uploads/certificates',0755,true); }
        $dest='uploads/certificates/'.$new_name;
        move_uploaded_file($tmp,$dest);
        $certificate_path=$dest;
    }

    // Upload photo
    $photo_path='';
    if(isset($_FILES['photo']) && $_FILES['photo']['error']==UPLOAD_ERR_OK){
        $tmp=$_FILES['photo']['tmp_name'];
        $name=basename($_FILES['photo']['name']);
        $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
        $new_name=uniqid('photo_').'.'.$ext;
        if(!is_dir('uploads/photos')){ mkdir('uploads/photos',0755,true); }
        $dest='uploads/photos/'.$new_name;
        move_uploaded_file($tmp,$dest);
        $photo_path=$dest;
    }

    $stmt=$conn->prepare("INSERT INTO achievement_details (student_id, achievement_title, description, date_awarded, certificate_path, photo_path, achievement_level) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if(!$stmt){
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("issssss", $student_id, $achievement_title, $description, $date_awarded, $certificate_path, $photo_path, $achievement_level);
    if($stmt->execute()){
        $message.='Achievement added with files.<br>';
        $achievement_records=[];
        $res=$conn->query("SELECT ach.*, s.first_name, s.last_name FROM achievement_details ach JOIN students s ON ach.student_id=s.id");
        if($res){
            while($row=$res->fetch_assoc()){
                $achievement_records[]=$row;
            }
        }
    }
    $stmt->close();
    }
}

// Handle Non-Academic Details
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['non_academic_save'])){
    if(!$canUseLegacyStudentForms){
        $message .= 'Only admin users can add legacy non-academic records.<br>';
    } elseif(!iqac_verify_csrf($_POST['csrf_token'] ?? null)){
        $message .= 'Invalid security token. Please try again.<br>';
    } elseif(!iqac_table_exists($conn, 'non_academic_details')){
        $message .= 'Non-academic save is unavailable because non_academic_details is not present in the live schema.<br>';
    } else {
    $student_id=intval($_POST['student_id']);
    $activity_type=$_POST['activity_type'];
    $organization_name=$_POST['organization_name'];
    $role=$_POST['role'];
    $duration=$_POST['duration'];
    $remarks=$_POST['remarks'];

    $stmt=$conn->prepare("INSERT INTO non_academic_details (student_id, activity_type, organization_name, role, duration, remarks) VALUES (?, ?, ?, ?, ?, ?)");
    if(!$stmt){
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("isssss", $student_id, $activity_type, $organization_name, $role, $duration, $remarks);
    if($stmt->execute()){
        $message.='Non-academic record added.<br>';
        $non_academic_records=[];
        $res=$conn->query("SELECT nad.*, s.first_name, s.last_name FROM non_academic_details nad JOIN students s ON nad.student_id=s.id");
        if($res){
            while($row=$res->fetch_assoc()){
                $non_academic_records[]=$row;
            }
        }
    }
    $stmt->close();
    }
}
?>

<?php
// ... (your existing PHP code remains unchanged)
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Student Management Portal</title>
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet" />
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<style>
body {
    font-family: 'Inter', sans-serif; 
    background: #f9f9f9; 
    margin: 0;
    padding: 20px;
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
    border-radius: 8px; /* Rounded corners for inputs */
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
    border-radius: 8px; /* Rounded corners for buttons */
    cursor: pointer;
    font-family: 'Inter', sans-serif;
}
button:hover {
    background-color: #0056b3;
}
/* Style for record tables */
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

/* Additional CSS for responsive layout */
.personal-details-container {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
}
form > .form-group {
    display: flex;
    flex-direction: column;
}
.special-width {
    width: 200px; /* to support aligned input widths */
}

/* Student Photo Box */
.student-details-box {
    border: 2px solid #007bff; /* blue border */
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}

.status-workspace {
    border: 1px solid #dbe3ef;
    border-left: 5px solid #d4af37;
    background: #fff;
    box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
}
.status-workspace::before {
    display: none;
}
.status-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 18px;
}
.status-header h2 {
    text-align: left;
    margin: 0 0 8px;
    color: #1e3a8a;
}
.status-header h2::before {
    content: "";
    margin: 0;
}
.status-header p {
    margin: 0;
    color: #64748b;
    line-height: 1.5;
}
.scope-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 34px;
    padding: 7px 11px;
    border-radius: 999px;
    background: #eaf1ff;
    color: #1e3a8a;
    font-size: 12px;
    font-weight: 800;
    white-space: nowrap;
}
.status-metrics {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin: 14px 0 18px;
}
.status-metric {
    border: 1px solid #dbe3ef;
    border-radius: 8px;
    padding: 14px;
    background: #f8fbff;
}
.status-metric span {
    display: block;
    color: #64748b;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
}
.status-metric strong {
    display: block;
    margin-top: 6px;
    color: #1e3a8a;
    font-size: 24px;
}
.status-action-card {
    display: grid;
    grid-template-columns: minmax(260px, 2fr) minmax(190px, 1fr) auto;
    gap: 14px;
    align-items: end;
    border: 1px solid #dbe3ef;
    border-radius: 8px;
    padding: 16px;
    background: #fbfdff;
}
.status-action-card .form-group {
    display: flex;
    flex-direction: column;
    gap: 7px;
}
.status-action-card label,
.status-filter label {
    color: #1e3a8a;
    font-size: 13px;
    font-weight: 800;
}
.status-action-card select,
.status-filter input {
    min-height: 42px;
    border: 1px solid #dbe3ef;
    border-radius: 8px;
    background: #fff;
}
.status-action-card button {
    width: 100%;
    min-height: 42px;
    margin: 0;
    background: #1e3a8a;
    font-weight: 800;
}
.status-help {
    margin: 12px 0 0;
    color: #64748b;
    font-size: 13px;
}
.status-filter {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin: 18px 0 10px;
}
.status-filter input {
    width: min(380px, 100%);
    padding: 9px 12px;
}
.status-table-wrap {
    width: 100%;
    max-height: 420px;
    overflow: auto;
    border: 1px solid #dbe3ef;
    border-radius: 8px;
}
.status-table {
    min-width: 980px;
    margin: 0;
    border: 0;
}
.status-table th,
.status-table td {
    border: 0;
    border-bottom: 1px solid #dbe3ef;
    padding: 12px;
}
.status-table th {
    position: sticky;
    top: 0;
    z-index: 1;
    background: #f8fafc;
    color: #1e3a8a;
    font-size: 12px;
    text-transform: uppercase;
}
.status-table tr:hover {
    background: #f8fbff;
}
.student-roll {
    color: #1e3a8a;
    font-weight: 900;
}
.student-subjects {
    max-width: 240px;
    color: #475569;
    font-size: 12px;
    line-height: 1.4;
}
.login-badge {
    display: inline-flex;
    min-height: 26px;
    align-items: center;
    padding: 4px 9px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 900;
}
.login-badge.unlocked {
    background: #ecfdf5;
    color: #047857;
}
.login-badge.locked {
    background: #fff1f2;
    color: #b91c1c;
}
@media (max-width: 760px) {
    .status-header,
    .status-filter {
        flex-direction: column;
        align-items: stretch;
    }
    .status-metrics,
    .status-action-card {
        grid-template-columns: 1fr;
    }
}

/* Style for Home Button */
.btn-home {
  display: inline-block;
  padding: 8px 16px;
  background-color: #6c757d;
  color: #fff;
  border-radius: 8px;
  text-decoration: none;
  font-family: 'Inter', sans-serif;
  transition: background-color 0.3s;
}
.btn-home:hover {
  background-color: #5a6268;
}
</style>
</head>
<body>

<div class="container">

<?php if($message): ?>
<div class="message success"><?= $message ?></div>
<?php endif; ?>

<!-- Home Button -->
<div style="margin-bottom:20px;">
  <a href="std_index.php" class="btn-home"><i class="fas fa-home"></i> Home</a>
  <!-- Or for a back button, uncomment below -->
  <!-- <button onclick="window.history.back();"><i class="fas fa-arrow-left"></i> Back</button> -->
</div>

<section class="student-details-box status-workspace" style="margin-bottom:24px;">
  <div class="status-header">
    <div>
      <h2><i class="fas fa-lock"></i> Student Lock / Unlock Control</h2>
      <p>Search by roll number, name, department, or batch. Staff can act only inside assigned tutor scope or subject class. HOD, IQAC, admin, and super admin use their approved management scope.</p>
    </div>
    <span class="scope-chip"><i class="fas fa-shield-halved"></i> <?= htmlspecialchars($scopeLabel) ?></span>
  </div>

  <div class="status-metrics">
    <div class="status-metric">
      <span>Allowed Students</span>
      <strong><?= (int)$statusCounts['total'] ?></strong>
    </div>
    <div class="status-metric">
      <span>Unlocked Logins</span>
      <strong><?= (int)$statusCounts['unlocked'] ?></strong>
    </div>
    <div class="status-metric">
      <span>Locked Logins</span>
      <strong><?= (int)$statusCounts['locked'] ?></strong>
    </div>
  </div>

  <form method="POST" class="status-action-card">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
    <input type="hidden" name="action" value="student_status_toggle">
    <div class="form-group">
      <label for="student_id"><i class="fas fa-user-graduate"></i> Student</label>
      <select name="student_id" id="student_id" required>
        <option value="">Select allowed student</option>
        <?php foreach($status_students as $student): ?>
          <option value="<?= (int)$student['student_id'] ?>" data-search="<?= htmlspecialchars(strtolower($student['roll_number'] . ' ' . $student['full_name'] . ' ' . $student['department'] . ' ' . $student['batch'] . ' ' . $student['section'])) ?>">
            <?= htmlspecialchars($student['roll_number'] . ' - ' . $student['full_name'] . ' | ' . $student['department'] . ' | ' . $student['batch'] . ' ' . $student['section'] . ' | Sem ' . $student['current_semester'] . ' | ' . ((int)$student['is_active'] === 1 ? 'Unlocked' : 'Locked')) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="status_action"><i class="fas fa-toggle-on"></i> Login Status</label>
      <select name="status_action" id="status_action" required>
        <option value="unlock">Unlock / Active</option>
        <option value="lock">Lock / Block</option>
      </select>
    </div>
    <div class="form-group">
      <button type="submit" onclick="return confirm('Update selected student login status?')"><i class="fas fa-floppy-disk"></i> Save</button>
    </div>
  </form>
  <p class="status-help">
    <?php if($staffSubjectCodes): ?>
      Your assigned subjects: <strong><?= htmlspecialchars(implode(', ', $staffSubjectCodes)) ?></strong>.
    <?php else: ?>
      Subject scope: <strong><?= in_array($currentRole, ['admin', 'super_admin', 'hod', 'iqac'], true) ? 'All students allowed by role' : 'No subject allocation found' ?></strong>.
    <?php endif; ?>
  </p>

  <div class="status-filter">
    <label for="studentStatusSearch"><i class="fas fa-magnifying-glass"></i> Find student</label>
    <input type="text" id="studentStatusSearch" placeholder="Search roll no, name, department, batch..." value="<?= htmlspecialchars($_GET['student'] ?? '') ?>">
  </div>
  <div id="studentInvalidNotice" class="message error" style="display:none; margin-top:8px;">
    Invalid student for your login rights. This student is not in your allowed department, class, tutor batch, or subject scope.
  </div>

  <div class="status-table-wrap">
    <table class="status-table" id="studentStatusTable">
      <thead>
        <tr><th>Roll No</th><th>Name</th><th>Department</th><th>Batch</th><th>Semester</th><th>Profile</th><th>Subject Scope</th><th>Login</th></tr>
      </thead>
      <tbody>
      <?php if($status_students): ?>
        <?php foreach($status_students as $student): ?>
          <tr data-search="<?= htmlspecialchars(strtolower($student['roll_number'] . ' ' . $student['full_name'] . ' ' . $student['department'] . ' ' . $student['batch'] . ' ' . $student['section'] . ' ' . $student['current_semester'] . ' ' . ($student['scope_subjects'] ?? ''))) ?>">
            <td class="student-roll"><?= htmlspecialchars($student['roll_number']) ?></td>
            <td><?= htmlspecialchars($student['full_name']) ?></td>
            <td><?= htmlspecialchars($student['department']) ?></td>
            <td><?= htmlspecialchars(trim((string)$student['batch'] . ' ' . (string)$student['section'])) ?></td>
            <td><?= htmlspecialchars((string)$student['current_semester']) ?></td>
            <td><?= htmlspecialchars($student['stage1_status']) ?></td>
            <td class="student-subjects"><?= htmlspecialchars($student['scope_subjects'] ?? 'All allowed') ?></td>
            <td><span class="login-badge <?= (int)$student['is_active'] === 1 ? 'unlocked' : 'locked' ?>"><?= (int)$student['is_active'] === 1 ? 'Unlocked' : 'Locked' ?></span></td>
          </tr>
        <?php endforeach; ?>
        <tr id="studentStatusNoMatch" style="display:none;">
          <td colspan="8" style="text-align:center; color:#b91c1c; font-weight:800;">Invalid student for your login rights. Check department, class year, tutor assignment, and approval status.</td>
        </tr>
      <?php else: ?>
        <tr><td colspan="8" style="text-align:center;">No students available in your allowed scope.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<script>
(function () {
  const search = document.getElementById('studentStatusSearch');
  const table = document.getElementById('studentStatusTable');
  const select = document.getElementById('student_id');
  if (!search || !table || !select) return;

  function applyStudentFilter() {
    const term = search.value.trim().toLowerCase();
    let firstMatch = null;
    let visibleCount = 0;
    table.querySelectorAll('tbody tr[data-search]').forEach(function (row) {
      const isMatch = term === '' || row.dataset.search.includes(term);
      row.style.display = isMatch ? '' : 'none';
      if (isMatch) visibleCount += 1;
      if (isMatch && !firstMatch) firstMatch = row.querySelector('.student-roll')?.textContent.trim().toLowerCase();
    });
    const noMatch = document.getElementById('studentStatusNoMatch');
    if (noMatch) noMatch.style.display = visibleCount === 0 ? '' : 'none';
    const invalidNotice = document.getElementById('studentInvalidNotice');
    if (invalidNotice) invalidNotice.style.display = term !== '' && visibleCount === 0 ? '' : 'none';

    if (term !== '' && firstMatch) {
      Array.from(select.options).forEach(function (option) {
        if (!option.value || !option.dataset.search) return;
        if (option.dataset.search.includes(term) || option.textContent.trim().toLowerCase().startsWith(firstMatch)) {
          select.value = option.value;
        }
      });
    }
  }

  search.addEventListener('input', applyStudentFilter);
  applyStudentFilter();
})();
</script>

<?php if($canUseLegacyStudentForms): ?>

<h2><i class="fas fa-user-graduate"></i> Student Personal Details</h2>
<div class="student-details-box">
<form method="POST" enctype="multipart/form-data" style="width:100%;">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
  <!-- Basic Info -->
  <div class="form-group" style="flex:1 1 250px;">
    <label for="first_name"><i class="fas fa-id-badge"></i> First Name:</label>
    <input type="text" name="first_name" required />
  </div>
  <div class="form-group" style="flex:1 1 250px;">
    <label for="last_name"><i class="fas fa-id-badge"></i> Last Name:</label>
    <input type="text" name="last_name" required />
  </div>
  <div class="form-group" style="flex:1 1 150px;">
    <label for="age"><i class="fas fa-user-clock"></i> Age:</label>
    <input type="number" name="age" required />
  </div>
  <div class="form-group" style="flex:1 1 200px;">
    <label for="dob"><i class="fas fa-calendar"></i> Date of Birth:</label>
    <input type="date" name="dob" required />
  </div>
  <div class="form-group" style="flex:1 1 250px;">
    <label for="entry_type"><i class="fas fa-user-tag"></i> Entry Type:</label>
    <select name="entry_type" required>
      <option value="">--Select Entry Type--</option>
      <option value="Regular">Regular</option>
      <option value="Lateral">Lateral</option>
    </select>
  </div>
  <div class="form-group" style="flex:1 1 150px;">
    <label for="management_quota"><i class="fas fa-check-circle"></i> Management Quota:</label>
    <select name="management_quota" required>
      <option value="">--Select--</option>
      <option value="Yes">Yes</option>
      <option value="No">No</option>
    </select>
  </div>
  <div class="form-group" style="flex:1 1 150px;">
    <label for="counseling_quota"><i class="fas fa-check-circle"></i> Counseling Quota:</label>
    <select name="counseling_quota" required>
      <option value="">--Select--</option>
      <option value="Yes">Yes</option>
      <option value="No">No</option>
    </select>
  </div>
  <!-- Address, Email, Father's Mobile -->
  <div style="display:flex; flex-wrap:wrap; gap:15px; margin-top:10px;">
    <div class="form-group" style="flex:2 1 300px;">
      <label for="address"><i class="fas fa-map-marker-alt"></i> Address:</label>
      <textarea name="address" rows="3" required></textarea>
    </div>
    <div class="form-group" style="flex:1 1 200px;">
      <label for="email_id"><i class="fas fa-envelope"></i> Email ID:</label>
      <input type="email" name="email_id" required class="special-width" />
    </div>
    <div class="form-group" style="flex:1 1 200px;">
      <label for="father_mobile"><i class="fas fa-phone"></i> Father Mobile:</label>
      <input type="text" name="father_mobile" required class="special-width" />
    </div>
  </div>
  <!-- Date of Admission, Batch, Student Mobile -->
  <div style="display:flex; flex-wrap:wrap; gap:15px; margin-top:10px;">
    <div class="form-group" style="flex:1 1 200px;">
      <label for="admission_date"><i class="fas fa-calendar-alt"></i> Date of Admission:</label>
      <input type="date" name="admission_date" required />
    </div>
    <div class="form-group" style="flex:1 1 150px;">
      <label for="batch_year"><i class="fas fa-calendar"></i> Batch Year:</label>
      <input type="text" name="batch_year" placeholder="e.g., 2022" required />
    </div>
    <div class="form-group" style="flex:1 1 200px;">
      <label for="student_mobile"><i class="fas fa-phone"></i> Student Mobile Number:</label>
      <input type="text" name="student_mobile" required />
    </div>
  </div>
  <!-- Student Photo -->
  <div class="form-group" style="flex:1 1 250px;">
    <label for="student_photo"><i class="fas fa-camera"></i> Student Photo:</label>
    <input type="file" name="student_photo" accept=".jpg,.jpeg,.png" required />
  </div>
  <!-- Parent Photos -->
  <h3 style="width:100%; margin-top:20px;"><i class="fas fa-user-friends"></i> Parent Photos</h3>
  <div style="display:flex; flex-wrap:wrap; gap:15px;">
    <div class="form-group" style="flex:1 1 250px;">
      <label for="father_photo"><i class="fas fa-camera"></i> Father's Photo:</label>
      <input type="file" name="father_photo" accept=".jpg,.jpeg,.png" />
    </div>
    <div class="form-group" style="flex:1 1 250px;">
      <label for="mother_photo"><i class="fas fa-camera"></i> Mother's Photo:</label>
      <input type="file" name="mother_photo" accept=".jpg,.jpeg,.png" />
    </div>
  </div>
  <!-- Parent Details -->
  <h3 style="width:100%; margin-top:20px;"><i class="fas fa-user-friends"></i> Parent Details</h3>
  <div style="display:flex; flex-wrap:wrap; gap:15px;">
    <div class="form-group" style="flex:1 1 250px;">
      <label for="father_name">Father's Name:</label>
      <input type="text" name="father_name" required />
    </div>
    <div class="form-group" style="flex:1 1 250px;">
      <label for="mother_name">Mother's Name:</label>
      <input type="text" name="mother_name" required />
    </div>
    <div class="form-group" style="flex:1 1 250px;">
      <label for="father_occupation">Father's Occupation:</label>
      <input type="text" name="father_occupation" required />
    </div>
    <div class="form-group" style="flex:1 1 250px;">
      <label for="mother_occupation">Mother's Occupation:</label>
      <input type="text" name="mother_occupation" required />
    </div>
    <div class="form-group" style="flex:1 1 200px;">
      <label for="father_income">Father's Income:</label>
      <input type="number" step="0.01" name="father_income" required />
    </div>
  </div>
  <div style="width:100%; text-align:center; margin-top:15px;">
    <button type="submit" name="student_save"><i class="fas fa-save"></i> Save Student</button>
  </div>
</form>
</div>

<!-- Academic Details Section -->
<section>
<h3><i class="fas fa-school"></i> Academic Details (School/College)</h3>
<form method="POST">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
  <div class="form-group">
    <label for="student_id"><i class="fas fa-user"></i> Select Student:</label>
    <select name="student_id" required>
      <option value="">--Select Student--</option>
      <?php foreach($students_list as $s): ?>
        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['first_name'].' '.$s['last_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group">
    <label for="institution_type"><i class="fas fa-university"></i> Institution Type:</label>
    <select name="institution_type" id="institution_type" required onchange="updateInstitutionPlaceholder()">
      <option value="">--Select Type--</option>
      <option value="school">School</option>
      <option value="college">College</option>
    </select>
  </div>
  <div class="form-group">
    <label for="institution_name"><i class="fas fa-building"></i> Institution Name:</label>
    <input type="text" name="institution_name" id="institution_name" placeholder="Enter institution name" required />
  </div>
  <div class="form-group">
    <label for="total_marks"><i class="fas fa-trophy"></i> Total Marks:</label>
    <input type="number" name="total_marks" required />
  </div>
  <div class="form-group">
    <label for="percentage"><i class="fas fa-percentage"></i> Percentage:</label>
    <input type="number" step="0.01" name="percentage" required />
  </div>
  <div class="form-group">
    <label for="year_completed"><i class="fas fa-calendar-check"></i> Year of Completion:</label>
    <input type="text" name="year_completed" required />
  </div>
  <div class="form-group">
    <label for="roll_number"><i class="fas fa-id-badge"></i> Roll Number:</label>
    <input type="text" name="roll_number" required />
  </div>
  <div style="width:100%; text-align:center;">
    <button type="submit" name="academic_save"><i class="fas fa-plus"></i> Add Academic Record</button>
  </div>
</form>

<!-- View Academic Records Button -->
<div style="margin-top:10px;">
    <button onclick="toggleVisibility('academicRecordsDisplay')">View Academic Records</button>
</div>
<div id="academicRecordsDisplay" style="display:none; margin-top:20px;">
    <h4>Academic Records (Formatted View)</h4>
    <table>
      <thead>
        <tr>
          <th>Institution Name</th>
          <th>Student</th>
          <th>Type</th>
          <th>Total Marks</th>
          <th>Percentage</th>
          <th>Year</th>
          <th>Roll No</th>
        </tr>
      </thead>
      <tbody>
      <?php if($academic_records): ?>
        <?php foreach($academic_records as $rec): ?>
        <tr>
          <td><?= htmlspecialchars($rec['institution_name']) ?></td>
          <td><?= htmlspecialchars($rec['first_name'].' '.$rec['last_name']) ?></td>
          <td><?= htmlspecialchars($rec['institution_type']) ?></td>
          <td><?= htmlspecialchars($rec['total_marks']) ?></td>
          <td><?= htmlspecialchars($rec['percentage']) ?>%</td>
          <td><?= htmlspecialchars($rec['year_completed']) ?></td>
          <td><?= htmlspecialchars($rec['roll_number']) ?></td>
        </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="7" style="text-align:center;">No academic records.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
</div>
<script>
function updateInstitutionPlaceholder() {
  const type = document.getElementById('institution_type').value;
  const input = document.getElementById('institution_name');
  if(type === 'school') {
    input.placeholder = 'Enter school name';
  } else if(type === 'college') {
    input.placeholder = 'Enter college name';
  } else {
    input.placeholder = 'Enter institution name';
  }
}
</script>
</section>

<!-- Achievement Details -->
<section>
<h3><i class="fas fa-trophy"></i> Achievement Details</h3>
<form method="POST" enctype="multipart/form-data">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
  <div class="form-group">
    <label for="student_id"><i class="fas fa-user"></i> Select Student:</label>
    <select name="student_id" required>
      <option value="">--Select Student--</option>
      <?php foreach($students_list as $s): ?>
        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['first_name'].' '.$s['last_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group">
    <label for="achievement_title"><i class="fas fa-trophy"></i> Title:</label>
    <input type="text" name="achievement_title" required />
  </div>
  <div class="form-group">
    <label for="description"><i class="fas fa-align-left"></i> Description:</label>
    <textarea name="description" rows="3" required></textarea>
  </div>
  <div class="form-group">
    <label for="date_awarded"><i class="fas fa-calendar-week"></i> Date Awarded:</label>
    <input type="date" name="date_awarded" required />
  </div>
  <div class="form-group">
    <label for="achievement_level"><i class="fas fa-globe"></i> Level of Achievement:</label>
    <select name="achievement_level" required>
      <option value="">--Select Level--</option>
      <?php foreach($national_levels as $level): ?>
        <option value="<?= htmlspecialchars($level) ?>"><?= htmlspecialchars($level) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <!-- Files Upload -->
  <div class="form-group">
    <label for="certificate"><i class="fas fa-file-upload"></i> Certificate Upload:</label>
    <input type="file" name="certificate" accept=".pdf,.jpg,.jpeg,.png" required />
  </div>
  <div class="form-group">
    <label for="photo"><i class="fas fa-user-circle"></i> Photo Upload:</label>
    <input type="file" name="photo" accept=".jpg,.jpeg,.png" required />
  </div>
  <div style="width:100%; text-align:center;">
    <button type="submit" name="achievement_save"><i class="fas fa-plus"></i> Add Achievement</button>
  </div>
</form>

<!-- View Achievement Records -->
<div style="margin-top:10px;">
    <button onclick="toggleVisibility('achievementRecordsDisplay')">View Achievement Records</button>
</div>
<div id="achievementRecordsDisplay" style="display:none; margin-top:20px;">
    <h4>Achievement Records (Formatted View)</h4>
    <table>
      <thead>
        <tr>
          <th>Title</th>
          <th>Student</th>
          <th>Description</th>
          <th>Date</th>
          <th>Level</th>
          <th>Certificate</th>
          <th>Photo</th>
        </tr>
      </thead>
      <tbody>
      <?php if($achievement_records): ?>
        <?php foreach($achievement_records as $ach): ?>
        <tr>
          <td><?= htmlspecialchars($ach['achievement_title']) ?></td>
          <td><?= htmlspecialchars($ach['first_name'].' '.$ach['last_name']) ?></td>
          <td><?= htmlspecialchars($ach['description']) ?></td>
          <td><?= htmlspecialchars($ach['date_awarded']) ?></td>
          <td><?= htmlspecialchars($ach['achievement_level']) ?></td>
          <td>
            <?php if($ach['certificate_path']): ?>
              <a href="<?= htmlspecialchars($ach['certificate_path']) ?>" target="_blank">View Certificate</a>
            <?php endif; ?>
          </td>
          <td>
            <?php if($ach['photo_path']): ?>
              <img src="<?= htmlspecialchars($ach['photo_path']) ?>" alt="Photo" width="100" />
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="7" style="text-align:center;">No achievement records.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
</div>
<script>
function toggleVisibility(id) {
    var e = document.getElementById(id);
    e.style.display = (e.style.display === 'block') ? 'none' : 'block';
}
</script>
</section>

<!-- Non-Academic Details -->
<section>
<h3><i class="fas fa-users"></i> Non-Academic Details (Volunteer/Club/Association)</h3>
<form method="POST">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
  <div class="form-group">
    <label for="student_id"><i class="fas fa-user"></i> Select Student:</label>
    <select name="student_id" required>
      <option value="">--Select Student--</option>
      <?php foreach($students_list as $s): ?>
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
    <label for="duration"><i class="fas fa-clock"></i> Duration (e.g., 6 months):</label>
    <input type="text" name="duration" required />
  </div>
  <div class="form-group">
    <label for="remarks"><i class="fas fa-comment"></i> Remarks:</label>
    <textarea name="remarks" rows="2"></textarea>
  </div>
  <div style="width:100%; text-align:center;">
    <button type="submit" name="non_academic_save"><i class="fas fa-plus"></i> Add Record</button>
  </div>
</form>

<!-- View Non-Academic Records -->
<div style="margin-top:10px;">
    <button onclick="toggleVisibility('nonAcademicRecordsDisplay')">View Non-Academic Records</button>
</div>
<div id="nonAcademicRecordsDisplay" style="display:none; margin-top:20px;">
    <h4>Non-Academic Records (Formatted View)</h4>
    <table>
      <thead>
        <tr>
          <th>Activity Type</th>
          <th>Student</th>
          <th>Organization</th>
          <th>Role</th>
          <th>Duration</th>
          <th>Remarks</th>
        </tr>
      </thead>
      <tbody>
      <?php if($non_academic_records): ?>
        <?php foreach($non_academic_records as $rec): ?>
        <tr>
          <td><?= htmlspecialchars($rec['activity_type']) ?></td>
          <td><?= htmlspecialchars($rec['first_name'].' '.$rec['last_name']) ?></td>
          <td><?= htmlspecialchars($rec['organization_name']) ?></td>
          <td><?= htmlspecialchars($rec['role']) ?></td>
          <td><?= htmlspecialchars($rec['duration']) ?></td>
          <td><?= htmlspecialchars($rec['remarks']) ?></td>
        </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="6" style="text-align:center;">No non-academic records.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
</div>
</section>

<?php else: ?>
<section class="student-details-box">
  <h2 style="width:100%; margin-top:0;"><i class="fas fa-circle-info"></i> Student Forms Hidden</h2>
  <p style="width:100%; color:#475569;">Student creation and legacy detail-entry forms are available only to admin and super admin users. Use the lock/unlock panel above for student status control.</p>
</section>
<?php endif; ?>

</div> <!-- end container -->
<script>
function toggleVisibility(id) {
    var e = document.getElementById(id);
    e.style.display = (e.style.display === 'block') ? 'none' : 'block';
}
</script>
</body>
</html>
