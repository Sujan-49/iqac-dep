<?php
require_once __DIR__ . '/include/auth.php';
$currentUser = iqac_require_login(['staff', 'tutor', 'hod', 'iqac', 'admin', 'super_admin']);
mysqli_report(MYSQLI_REPORT_OFF);
// delete.php
$host='localhost'; $user='root'; $pass=''; $db='mou';
$conn=@new mysqli($host,$user,$pass,$db);
if($conn->connect_error) $conn=@new mysqli($host,$user,$pass,'iqac');
if($conn->connect_error) die("DB connection error: " . $conn->connect_error);

if(!isset($_GET['id'])) {
    die("No ID specified");
}
$id=intval($_GET['id']);

$res=$conn->query("SELECT * FROM mou_files WHERE id=$id");
if($res && $res->num_rows>0){
    $record=$res->fetch_assoc();

    // Delete files
    @unlink($record['filepath']);
    if($record['image']) @unlink($record['image']);

    // Delete record
    $stmt=$conn->prepare("DELETE FROM mou_files WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $stmt->close();
    header("Location: upload.php?msg=Record deleted");
    exit;
} else {
    die("Record not found");
}
?>
