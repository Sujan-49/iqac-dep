<?php
require_once __DIR__ . '/include/auth.php';

iqac_start_session();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize inputs
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = iqac_normalize_role($_POST['role']); // Role from dropdown

    // Basic validation
    if (empty($username) || empty($password) || empty($role)) {
        $error = "Please fill in all fields.";
    } else {
        if (!in_array($role, iqac_allowed_roles(), true)) {
            $error = "Invalid role selected.";
        } else {
            // Check if username exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $error = "Username already exists. Please choose another.";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                // Insert new user with role
                $stmt = $conn->prepare("INSERT INTO users (role, username, password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $role, $username, $hashed_password);
                if ($stmt->execute()) {
                    $success = "Registration successful. You can sign in from the login page.";
                } else {
                    $error = "Error: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register with Role</title>
</head>
<body>
<h2>Register</h2>

<?php
if ($error) {
    echo "<p style='color:red;'>" . htmlspecialchars($error) . "</p>";
}
if ($success) {
    echo "<p style='color:green;'>" . htmlspecialchars($success) . "</p>";
}
?>

<form action="register.php" method="POST">
    <label>Username:</label>
    <input type="text" name="username" required><br><br>
    <label>Password:</label>
    <input type="password" name="password" required><br><br>
    <label>Role:</label>
    <select name="role" required>
        <option value="">Select Role</option>
        <option value="student">Student</option>
        <option value="staff">Subject Staff</option>
        <option value="tutor">Tutor</option>
        <option value="hod">HOD</option>
        <option value="iqac">IQAC Coordinator</option>
        <option value="admin">Admin</option>
        <option value="super_admin">Super Admin</option>
    </select><br><br>
    <button type="submit">Register</button>
</form>
</body>
</html>
