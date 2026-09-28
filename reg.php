<?php
// staff_register.php
session_start();
include 'include/dp_connection.php';

// Fetch mou_files data for department dropdown
$mou_files = [];
$result = $conn->query("SELECT id, department FROM mou_files");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $mou_files[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $department_id = $_POST['department_id'];
    $position = $_POST['position'];
    $mou_file_id = $_POST['mou_file_id'];
    $password = $_POST['password'];

    // Hash the password securely
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert into staff table
    $stmt = $conn->prepare("INSERT INTO staff (name, email, department, position, mou_file_id, password) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssis", $name, $email, $department_id, $position, $mou_file_id, $hashed_password);

    if ($stmt->execute()) {
        header("Location: login.php");
        exit();
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Register Staff</title>
<style>
  body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    padding: 0;
  }

  .container {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-top: 30px;
  }

  .form-box {
    background-color: #fff;
    padding: 30px 40px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    max-width: 400px;
    width: 100%;
    margin-bottom: 50px;
  }

  h2 {
    text-align: center;
    margin-bottom: 20px;
    color: #333;
  }

  form {
    display: flex;
    flex-direction: column;
  }

  label {
    margin-bottom: 5px;
    font-weight: bold;
    color: #555;
  }

  input, select {
    padding: 8px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 4px;
  }

  button {
    padding: 10px;
    background-color: #007bff;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
  }

  button:hover {
    background-color: #0056b3;
  }

  /* Error message styling */
  .error {
    color: red;
    text-align: center;
    margin-bottom: 15px;
  }
</style>
</head>
<body>
<div class="container">

  <!-- Registration Form -->
  <div class="form-box">
    <h2>Register Staff</h2>
    <?php if (isset($error_message)): ?>
      <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>
    <form method="POST" action="">
        <label>Name:</label>
        <input type="text" name="name" required />

        <label>Email:</label>
        <input type="email" name="email" required />

        <label>Department:</label>
        <select name="department_id" required>
            <option value="">Select Department</option>
            <?php foreach ($mou_files as $mou): ?>
                <option value="<?php echo $mou['id']; ?>"><?php echo htmlspecialchars($mou['department']); ?></option>
            <?php endforeach; ?>
        </select>

        <label>Position:</label>
        <input type="text" name="position" required />

        <label>MOU File ID:</label>
        <input type="number" name="mou_file_id" required />

        <label>Password:</label>
        <input type="password" name="password" required />

        <button type="submit">Register</button>
    </form>
  </div>

</div>
</body>
</html>