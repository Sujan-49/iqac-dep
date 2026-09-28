<?php
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "iqac";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create table if not exists
$createTableSql = "
CREATE TABLE IF NOT EXISTS technical_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(255) NOT NULL,
    organized_by VARCHAR(255),
    event_date DATE NOT NULL,
    number_of_students_participated INT,
    description TEXT,
    file_upload VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
";

if ($conn->query($createTableSql) === TRUE) {
    // Table created or already exists
} else {
    die("Error creating table: " . $conn->error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect form data
    $event_name = $_POST['event_name'];
    $organized_by = $_POST['organized_by'];
    $event_date = $_POST['event_date'];
    $number_of_students_participated = $_POST['number_of_students_participated'];
    $description = $_POST['description'];
    $file_upload = null;

    // Handle file upload if any
    if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $filename = basename($_FILES['file_upload']['name']);
        $target_path = $upload_dir . time() . "_" . $filename;
        if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $target_path)) {
            $file_upload = $target_path;
        }
    }

    // Prepare SQL statement
    $stmt = $conn->prepare("INSERT INTO technical_events 
        (event_name, organized_by, event_date, number_of_students_participated, description, file_upload) 
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssiis", $event_name, $organized_by, $event_date, $number_of_students_participated, $description, $file_upload);

    if ($stmt->execute()) {
        $message = "Event added successfully!";
    } else {
        $message = "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Technical Event Submission</title>
<style>
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    padding: 20px;
}
h2 {
    text-align: center;
    color: #333;
}
form {
    max-width: 600px;
    margin: 0 auto;
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
label {
    display: block;
    margin-top: 15px;
    font-weight: bold;
}
input[type="text"],
input[type="date"],
input[type="number"],
textarea {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
}
input[type="file"] {
    margin-top: 5px;
}
button {
    margin-top: 20px;
    padding: 10px 20px;
    background-color: #0056b3;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}
button:hover {
    background-color: #004494;
}
.message {
    max-width: 600px;
    margin: 20px auto;
    padding: 10px;
    background-color: #e0ffe0;
    border: 1px solid #b2ffb2;
    border-radius: 4px;
    color: #333;
    text-align: center;
}
</style>
</head>
<body>

<h2>Register a Technical Event</h2>

<?php if (isset($message)) { ?>
<div class="message"><?php echo htmlspecialchars($message); ?></div>
<?php } ?>

<form action="" method="POST" enctype="multipart/form-data">
    <label for="event_name">Event Name:</label>
    <input type="text" id="event_name" name="event_name" required />

    <label for="organized_by">Organized By:</label>
    <input type="text" id="organized_by" name="organized_by" />

    <label for="event_date">Event Date:</label>
    <input type="date" id="event_date" name="event_date" required />

    <label for="number_of_students_participated">Number of Students Participated:</label>
    <input type="number" id="number_of_students_participated" name="number_of_students_participated" min="0" />

    <label for="description">Description:</label>
    <textarea id="description" name="description" rows="4"></textarea>

    <label for="file_upload">Upload File (Optional):</label>
    <input type="file" id="file_upload" name="file_upload" />

    <button type="submit">Submit Event</button>
</form>

</body>
</html>