<?php
session_start();

// Example login check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to login page if not logged in
    header('Location: login.php');
    exit;
}

// Assuming you store user's department after login
$department = isset($_SESSION['department']) ? $_SESSION['department'] : '';

// Fetch departments for dropdown if needed
// Example: $departments = ['Dept1', 'Dept2', 'Dept3'];
// You might get this from database
?>
<!DOCTYPE html> 
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PSG Polytechnic College - MOU Portal</title>
  <!-- ... [Your existing styles and head content] ... -->
</head>
<body>
  <!-- Navigation -->
  <nav>
    <div class="nav-left">
      <img src="uploads/images/logo1.png" alt="PSG Logo" />
      <span class="nav-title">
        PSG Polytechnic - MOU Portal
        <img src="uploads/images/logo2.png" alt="Secondary Logo" />
      </span>
    </div>
    <div class="nav-links">
      <a href="about.html"><i class="fa-solid fa-circle-info"></i> About</a>
      <a href="departments.php"><i class="fa-solid fa-building"></i> Departments</a>
      <a href="upload.php"><i class="fa-solid fa-upload"></i> Upload</a>
      <a href="view.php"><i class="fa-solid fa-eye"></i> View</a>
      <a href="gallery.php"><i class="fa-solid fa-images"></i> Gallery</a>
      <!-- Add logout link -->
      <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
    </div>
  </nav>

  <!-- Main Content -->
  <main>
    <!-- Example: Show user department info -->
    <?php if($department): ?>
      <section class="section-box">
        <h2>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></h2>
        <p>Your Department: <strong><?= htmlspecialchars($department) ?></strong></p>
      </section>
    <?php endif; ?>

    <!-- Your existing sections ... -->
    <section class="section-box">
      <h2>Explore Our Collaborations</h2>
      <p><strong>Welcome to the MOU Information Portal of PSG Polytechnic College!</strong></p>
      <p>We foster strategic partnerships with industry leaders and academic institutions to bridge academic learning with real-world industry standards, creating opportunities for students and faculty alike.</p>
    </section>
    <!-- ... other sections ... -->

  </main>

  <!-- Footer -->
  <footer>
    &copy; 2025 PSG Polytechnic College - MOU Portal. All Rights Reserved.
  </footer>
</body>
</html>