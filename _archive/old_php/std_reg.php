<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once __DIR__ . '/include/auth.php';

    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = iqac_normalize_role($_POST['role']);
    if (!in_array($role, iqac_allowed_roles(), true)) {
        die('Invalid role selected.');
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Prepare and execute insert statement
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashed_password, $role);

    if ($stmt->execute()) {
        // Redirect to login page after successful registration
        header('Location: login.php');
        exit();
    } else {
        echo "<p class='error'>Error: " . $conn->error . "</p>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            padding: 20px;
        }
        form {
            background-color: #fff;
            padding: 20px;
            max-width: 300px;
            margin: auto;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        input[type="text"], input[type="password"], select {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .success {
            color: green;
            text-align: center;
        }
        .error {
            color: red;
            text-align: center;
        }
    </style>
</head>
<body>

<h2 style="text-align:center;">Register</h2>

<form method="POST" action="">
    <input type="text" name="username" placeholder="Username" required /><br>
    <input type="password" name="password" placeholder="Password" required /><br>
    <select name="role" required>
        <option value="">Select Role</option>
        <option value="student">Student</option>
        <option value="staff">Subject Staff</option>
        <option value="tutor">Tutor</option>
        <option value="hod">HOD</option>
        <option value="iqac">IQAC Coordinator</option>
        <option value="admin">Admin</option>
        <option value="super_admin">Super Admin</option>
    </select><br>
    <button type="submit">Register</button>
</form>

</body>
</html>
