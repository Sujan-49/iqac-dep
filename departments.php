<?php
require_once __DIR__ . '/include/auth.php';
$currentUser = iqac_require_login(['student', 'staff', 'tutor', 'hod', 'iqac', 'admin', 'super_admin']);
mysqli_report(MYSQLI_REPORT_OFF);
// Database connection details
$host = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'mou';

// Connect to database
$conn = @new mysqli($host, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    $conn = @new mysqli($host, $dbUser, $dbPass, 'iqac');
}
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Get selected department and year from URL
$selectedDepartment = isset($_GET['department']) ? $_GET['department'] : '';
$selectedYear = isset($_GET['year']) ? $_GET['year'] : '';

$mous = [];
if ($selectedDepartment && $selectedYear) {
    // Fetch MOUs matching department and year
    $stmt = $conn->prepare("SELECT * FROM mou_files WHERE department = ? AND year = ? ORDER BY upload_date DESC");
    $stmt->bind_param("ss", $selectedDepartment, $selectedYear);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $mous[] = $row;
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Departments - MOU Portal</title>
<!-- Google Fonts for Inter -->
<link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet" />
<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<style>
  /* CSS Variables for colors and gradients */
  :root {
    --psg-blue: #002c77;
    --psg-yellow: #f4c20d;
    --light-bg: #f5f7fa;
    --dark-text: #1a1a1a;
    --light-text: #ffffff;
    --gradient-primary: linear-gradient(135deg, #002c77, #004499, #0066cc);
    --gradient-accent: linear-gradient(135deg, #f4c20d, #ffdd44, #ffeaa7);
    --border-radius: 20px;
    --section-padding: 20px 20px;
    --shadow-light: 0 4px 6px rgba(0,0,0,0.05);
    --shadow-medium: 0 8px 20px rgba(0,0,0,0.1);
    --shadow-heavy: 0 12px 30px rgba(0,0,0,0.2);
    --shadow-glow: 0 0 20px rgba(244, 194, 13, 0.4);
  }

  /* Reset */
  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }

  /* Body with animated background layers for depth and movement */
  body {
    font-family: 'Inter', sans-serif; 
    margin: 0; 
    padding: 0; 
    background: linear-gradient(135deg, #f9f9f9, #e0e0e0);
    color: var(--dark-text);
    line-height: 1.6;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    overflow-x: hidden;
  }

  /* Layered animated background for a dynamic visual depth */
  body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 200%;
    height: 200%;
    background: 
      radial-gradient(circle at 20% 20%, rgba(0,44,119,0.05) 0%, transparent 50%),
      radial-gradient(circle at 80% 80%, rgba(244,194,13,0.05) 0%, transparent 50%),
      radial-gradient(circle at 40% 40%, rgba(0,102,204,0.03) 0%, transparent 50%);
    z-index: -2;
    animation: float 25s ease-in-out infinite alternate;
  }

  body::after {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23002c77' fill-opacity='0.02'%3E%3Ccircle cx='30' cy='30' r='1'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    z-index: -1;
    animation: drift 30s linear infinite;
  }

  @keyframes float {
    0% { transform: translate(0, 0) rotate(0deg) scale(1); }
    50% { transform: translate(-20px, -15px) rotate(180deg) scale(1.05); }
    100% { transform: translate(-10px, -10px) rotate(360deg) scale(0.95); }
  }

  @keyframes drift {
    0% { transform: translateX(0); }
    100% { transform: translateX(-60px); }
  }

  /* Particles floating for lively background */
  .particles {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
    overflow: hidden;
  }
  .particle {
    position: absolute;
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: linear-gradient(45deg, var(--psg-blue), var(--psg-yellow));
    animation: particles 15s linear infinite;
    opacity: 0.6;
  }
  @keyframes particles {
    0% {
      transform: translateY(100vh) rotate(0deg);
      opacity: 0;
    }
    10% { opacity: 0.6; }
    90% { opacity: 0.6; }
    100% {
      transform: translateY(-100px) rotate(360deg);
      opacity: 0;
    }
  }

  /* Navbar styling with gradient, shadows, hover effects, and responsiveness */
  nav {
    background: var(--gradient-primary);
    padding: 1rem 2rem;
    display: flex;
    flex-wrap: wrap; /* allow wrapping on small screens */
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: var(--shadow-medium), inset 0 1px 0 rgba(255,255,255,0.1);
    border-bottom: 1px solid rgba(255,255,255,0.1);
    border-radius: 0 0 var(--border-radius) var(--border-radius);
    margin: 0 10px;
    overflow: hidden;
    transition: all 0.3s ease;
  }

  /* Responsive adjustments for nav on small screens */
  @media(max-width: 768px) {
    nav {
      padding: 0.5rem 1rem;
    }
    .nav-left {
      flex: 1 1 auto;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .nav-left img {
      height: 40px;
    }
    .nav-title {
      font-size: 1.2rem;
    }
    .nav-links {
      flex: 1 1 auto;
      display: flex;
      justify-content: flex-end;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 10px;
    }
  }

  /* Navigation left logo/image styling with hover effect */
  .nav-left {
    display: flex;
    align-items: center;
    gap: 15px;
    z-index: 2;
  }
  .nav-left img {
    height: 50px;
    border-radius: 12px;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3), 0 0 0 2px rgba(255,255,255,0.1);
    filter: brightness(1.1) contrast(1.05);
  }
  .nav-left img:hover {
    transform: scale(1.1) rotate(5deg);
    box-shadow: 
      0 15px 35px rgba(0, 0, 0, 0.4),
      0 0 0 3px rgba(244, 194, 13, 0.5),
      var(--shadow-glow);
    filter: brightness(1.2) contrast(1.1);
  }

  /* Title with underline animation on hover */
  .nav-title {
    font-size: 1.6rem;
    font-weight: 700;
    color: var(--light-text);
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
  }
  .nav-title::before {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 0;
    width: 0;
    height: 3px;
    background: var(--gradient-accent);
    border-radius: 2px;
    transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  }
  .nav-title:hover::before {
    width: 100%;
  }

  /* Navigation links with animated background and glow effects */
  .nav-links {
    display: flex;
    align-items: center;
    gap: 20px;
    z-index: 2;
  }
  .nav-links a {
    margin-left: 20px;
    padding: 12px 20px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    color: var(--light-text);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    box-shadow: var(--shadow-light);
    overflow: hidden;
    text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
  }
  .nav-links a::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: var(--gradient-accent);
    transition: left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: -1;
    border-radius: 25px;
  }
  .nav-links a:hover::before {
    left: 0;
  }
  .nav-links a:hover {
    color: var(--psg-yellow);
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 8px 25px rgba(244, 194, 13, 0.4);
    text-shadow: none;
  }

  /* Main content grid layout */
  main {
    max-width: 1100px;
    margin: 40px auto;
    padding: 0 20px;
    display: grid;
    gap: 25px;
  }

  /* Section boxes with gradient backgrounds, shadows, and hover effects */
  .section-box {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(25px) saturate(180%);
    border-radius: var(--border-radius);
    padding: var(--section-padding);
    border-left: 6px solid var(--psg-blue);
    box-shadow: var(--shadow-light), inset 0 1px 0 rgba(255,255,255,0.4), 0 0 0 1px rgba(255,255,255,0.1);
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    margin-bottom: 25px;
    overflow: hidden;
  }
  /* Variant backgrounds for variety */
  .section-box:nth-child(1) {
    background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(240,248,255,0.9) 100%);
    border-left-color: var(--psg-blue);
  }
  .section-box:nth-child(2) {
    background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(255,251,235,0.9) 100%);
    border-left-color: var(--psg-yellow);
  }
  .section-box:nth-child(3) {
    background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(248,250,255,0.9) 100%);
    border-left-color: #4a90e2;
  }
  .section-box:nth-child(4) {
    background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(250,255,248,0.9) 100%);
    border-left-color: #28a745;
  }

  /* Hover effects with glow, scale, and animated border */
  .section-box:nth-child(odd) {
    border-top: 2px solid rgba(0,44,119,0.1);
    box-shadow: var(--shadow-light), inset 0 2px 0 rgba(0,44,119,0.05), 0 0 0 1px rgba(255,255,255,0.2);
  }
  .section-box:nth-child(even) {
    border-top: 2px solid rgba(244,194,13,0.2);
    box-shadow: var(--shadow-light), inset 0 2px 0 rgba(244,194,13,0.08), 0 0 0 1px rgba(255,255,255,0.2);
  }
  .section-box::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: conic-gradient(from 0deg, transparent, rgba(244,194,13,0.1), transparent);
    animation: rotate 4s linear infinite;
    z-index: -1;
    opacity: 0;
    transition: opacity 0.5s ease;
  }
  .section-box:hover::before {
    opacity: 1;
  }
  .section-box::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: var(--gradient-accent);
    transform: scaleX(0);
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    transform-origin: left;
    border-radius: 0 0 10px 10px;
    box-shadow: 0 3px 10px rgba(244,194,13,0.3);
  }
  .section-box:hover::after {
    transform: scaleX(1);
  }
  /* Hover scale and glow */
  .section-box:hover {
    border-left-color: var(--psg-yellow);
    transform: translateY(-12px) scale(1.02);
    box-shadow: var(--shadow-heavy), inset 0 1px 0 rgba(255,255,255,0.6), 0 0 0 2px rgba(244,194,13,0.2), var(--shadow-glow);
    background: rgba(255,255,255,0.95);
  }

  /* Headings with gradient text and animated underline */
  h1, h2, h3 {
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-weight: 800;
    margin-bottom: 20px;
    position: relative;
  }
  h1 {
    font-size: 2.5rem;
    padding-bottom: 12px;
  }
  h2 {
    font-size: 1.75rem;
    padding-bottom: 8px;
    margin-bottom: 20px;
    text-shadow: 0 0 20px rgba(0,44,119,0.3);
  }
  h3 {
    font-size: 1.3rem;
    padding-bottom: 6px;
  }
  h1::after, h2::after, h3::after {
    content: "";
    display: block;
    width: 80px;
    height: 5px;
    background: var(--gradient-accent);
    margin-top: 12px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(244,194,13,0.4);
    animation: shimmer 3s ease-in-out infinite;
  }
  h3::after {
    width: 60px;
    height: 3px;
  }

  /* Form groups with consistent bottom margin */
  .form-group {
    margin-bottom: 18px;
    position: relative;
  }
  label {
    display: block;
    font-weight: 600;
    color: var(--dark-text);
    margin-bottom: 8px;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
  }

  /* Input container with hover highlight effect */
  .input-container {
    position: relative;
    overflow: hidden;
    border-radius: 14px;
  }
  .input-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(0,44,119,0.1), transparent);
    transition: left 0.6s ease;
    z-index: 1;
  }
  .input-container:hover::before {
    left: 100%;
  }

  input[type="text"],
  input[type="email"],
  input[type="password"],
  input[type="file"],
  select,
  textarea {
    width: 100%;
    padding: 12px 16px;
    font-size: 15px;
    border-radius: 12px;
    border: 2px solid transparent;
    background: rgba(255,255,255,0.9);
    backdrop-filter: blur(10px);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 15px rgba(0,44,119,0.05), inset 0 1px 0 rgba(255,255,255,0.6);
    margin-bottom: 8px;
    position: relative;
    z-index: 2;
  }
  textarea {
    resize: vertical;
    min-height: 100px;
  }
  input[type="text"]:focus,
  input[type="email"]:focus,
  input[type="password"]:focus,
  input[type="file"]:focus,
  select:focus,
  textarea:focus {
    outline: none;
    border: 2px solid var(--psg-blue);
    background: rgba(255,255,255,1);
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 8px 25px rgba(0,44,119,0.15), 0 0 0 4px rgba(0,44,119,0.1), var(--shadow-glow);
  }

  /* Placeholder styling */
  input::placeholder,
  textarea::placeholder {
    color: rgba(26,26,26,0.5);
    font-style: italic;
  }

  /* Buttons with gradient, hover glow, and animations */
  button,
  .btn,
  button.btn-upload {
    background: var(--gradient-primary);
    color: #fff;
    border: none;
    padding: 10px 24px;
    font-size: 16px;
    font-weight: 700;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: var(--shadow-light), inset 0 1px 0 rgba(255,255,255,0.2);
    position: relative;
    overflow: hidden;
    text-transform: uppercase;
    letter-spacing: 1px;
    backdrop-filter: blur(10px);
    margin-top: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
  }
  button::before,
  .btn::before,
  button.btn-upload::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.6s ease;
  }
  button:hover::before,
  .btn:hover::before,
  button.btn-upload:hover::before {
    left: 100%;
  }
  button::after,
  .btn::after,
  button.btn-upload::after {
    content: '';
    position: inset;
    inset: 2px;
    border-radius: 10px;
    background: var(--gradient-primary);
    z-index: -1;
  }
  button:hover,
  .btn:hover,
  button.btn-upload:hover {
    background: linear-gradient(135deg, #001e55, #003377, #0055aa);
    transform: translateY(-4px) scale(1.05);
    box-shadow: var(--shadow-heavy), 0 0 30px rgba(244, 194, 13, 0.4), inset 0 1px 0 rgba(255,255,255,0.3);
  }
  button:active,
  .btn:active,
  button.btn-upload:active {
    transform: translateY(-2px) scale(1.02);
  }

  /* Secondary button style */
  .btn-secondary {
    background: var(--gradient-accent);
    color: var(--psg-blue);
  }
  .btn-secondary::after {
    background: var(--gradient-accent);
  }
  .btn-secondary:hover {
    background: linear-gradient(135deg, #e6b800, #f4c20d, #ffdd44);
    color: var(--psg-blue);
    box-shadow: var(--shadow-heavy), 0 0 30px rgba(0,44,119,0.4);
  }

  /* Message boxes for success, error, warning, info with animations */
  .message {
    padding: 15px 20px;
    border-radius: 12px;
    text-align: center;
    font-weight: 600;
    margin-bottom: 25px;
    border-left: 5px solid;
    position: relative;
    backdrop-filter: blur(15px);
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    animation: slideIn 0.6s cubic-bezier(0.4, 0, 0.2, 1);
  }
  @keyframes slideIn {
    0% {
      opacity: 0;
      transform: translateY(-20px);
    }
    100% {
      opacity: 1;
      transform: translateY(0);
    }
  }
  /* Specific styles for different message types */
  .success {
    background: linear-gradient(135deg, rgba(212,237,218,0.95), rgba(195,230,203,0.95));
    color: #155724;
    border-left-color: #28a745;
    box-shadow: 0 8px 25px rgba(40,167,69,0.2), inset 0 1px 0 rgba(255,255,255,0.5);
  }
  .error {
    background: linear-gradient(135deg, rgba(248,215,218,0.95), rgba(245,198,203,0.95));
    color: #721c24;
    border-left-color: #dc3545;
    box-shadow: 0 8px 25px rgba(220,53,69,0.2), inset 0 1px 0 rgba(255,255,255,0.5);
  }
  .warning {
    background: linear-gradient(135deg, rgba(255,243,205,0.95), rgba(254,235,200,0.95));
    color: #856404;
    border-left-color: #ffc107;
    box-shadow: 0 8px 25px rgba(255,193,7,0.2), inset 0 1px 0 rgba(255,255,255,0.5);
  }
  .info {
    background: linear-gradient(135deg, rgba(204,229,255,0.95), rgba(180,216,255,0.95));
    color: #004085;
    border-left-color: #0066cc;
    box-shadow: 0 8px 25px rgba(0,102,204,0.2), inset 0 1px 0 rgba(255,255,255,0.5);
  }

  /* Files table with styled header, rows, hover effects */
  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--shadow-light);
    background: rgba(255,255,255,0.95);
  }
  thead {
    background: var(--psg-blue);
    color: var(--light-text);
  }
  th, td {
    padding: 12px;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    text-align: left;
  }
  tbody tr:nth-child(even) {
    background-color: #f9f9f9;
  }
  tbody tr:hover {
    background: linear-gradient(90deg, rgba(0,44,119,0.08), rgba(244,194,13,0.05));
    transform: scale(1.01);
    box-shadow: 0 4px 15px rgba(0,44,119,0.15), inset 0 1px 0 rgba(255,255,255,0.4);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  /* Footer styling with animated underline and glow effects */
  footer {
    background: #333;
    color: #fff;
    text-align: center;
    padding: 20px;
    font-size: 0.9rem;
    margin-top: auto;
    position: relative;
  }
  footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: var(--gradient-accent);
    box-shadow: 0 0 15px rgba(244, 194, 13, 0.5);
  }
  footer::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
    animation: footerShine 4s ease-in-out infinite;
  }
  @keyframes footerShine {
    0% { left: -100%; }
    50% { left: 100%; }
    100% { left: -100%; }
  }

  /* Responsive styles for mobile */
  @media(max-width:600px){
    /* Stack filters vertically */
    .filters { flex-direction: column; align-items: center; }
    /* Reduce font sizes for headings */
    h2 { font-size: 1.5em; }
    /* Adjust table font size */
    table { font-size: 14px; }
  }
</style>
</head>
<body>

<!-- Navbar -->
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
    </div>
</nav>

<!-- Back link -->
<a href="departments.php" class="back-link" style="margin:20px; display:inline-block;">🔙 Back to Departments</a>

<!-- Wrap department and year selection in a form -->
<form method="GET" action="">
<div class="filters" style="margin:20px; display:flex; gap:20px; flex-wrap:wrap;">
  <!-- Department Dropdown -->
  <div class="filter-group">
    <label for="department">Department</label>
    <select id="department" name="department" onchange="this.form.submit()">
      <option value="">--Select Department--</option>
      <option value="Information Technology" <?= ($selectedDepartment=='Information Technology')?'selected':'' ?>>Information Technology</option>
      <option value="Computer Networking" <?= ($selectedDepartment=='Computer Networking')?'selected':'' ?>>Computer Networking</option>
      <option value="Electronics and Communication" <?= ($selectedDepartment=='Electronics and Communication')?'selected':'' ?>>Electronics and Communication</option>
      <option value="Mechanical Engineering" <?= ($selectedDepartment=='Mechanical Engineering')?'selected':'' ?>>Mechanical Engineering</option>
    </select>
  </div>
  <!-- Year Dropdown -->
  <div class="filter-group">
    <label for="year">Year</label>
    <select id="year" name="year" onchange="this.form.submit()" <?= empty($selectedDepartment)?'disabled':'' ?>>
      <option value="">--Select Year--</option>
      <option value="2020-21" <?= ($selectedYear=='2020-21')?'selected':'' ?>>2020-21</option>
      <option value="2021-22" <?= ($selectedYear=='2021-22')?'selected':'' ?>>2021-22</option>
      <option value="2022-23" <?= ($selectedYear=='2022-23')?'selected':'' ?>>2022-23</option>
      <option value="2023-24" <?= ($selectedYear=='2023-24')?'selected':'' ?>>2023-24</option>
      <option value="2024-25" <?= ($selectedYear=='2024-25')?'selected':'' ?>>2024-25</option>
    </select>
  </div>
</div>
</form>

<?php if ($selectedDepartment && $selectedYear): ?>
  <div class="selected-info" style="margin:20px; font-weight:600;">
    Showing MOUs for <strong><?= htmlspecialchars($selectedDepartment) ?></strong> - <strong><?= htmlspecialchars($selectedYear) ?></strong>
  </div>
  
  <div class="mou-table" style="padding:20px;">
    <table>
      <thead>
        <tr>
          <th>Company</th>
          <th>Date</th>
          <th>Status</th>
          <th>Document</th>
          <th>Image</th> <!-- New header -->
        </tr>
      </thead>
      <tbody>
        <?php if($mous): ?>
          <?php foreach($mous as $mou): ?>
            <tr>
              <td><?= htmlspecialchars($mou['company']) ?></td>
              <td><?= htmlspecialchars($mou['upload_date']) ?></td>
              <td><?= htmlspecialchars($mou['status']) ?></td>
              <td><a href="<?= htmlspecialchars($mou['filepath']) ?>" target="_blank">View Document</a></td>
              <td>
                <?php if (!empty($mou['image'])): ?>
                  <img src="<?= htmlspecialchars($mou['image']) ?>" alt="Thumbnail" style="width:50px; height:auto; border-radius:4px; cursor:pointer;" onclick="openZoom('<?= htmlspecialchars($mou['image']) ?>')"/>
                <?php else: ?>
                  N/A
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" style="text-align:center;">No MOUs found for this selection.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<!-- Image Zoom Overlay -->
<div id="imageZoomOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); justify-content:center; align-items:center; z-index:9999;">
  <div style="position:relative; max-width:90%; max-height:90%; background:#fff; padding:10px; border-radius:10px;">
    <div style="position:absolute; top:-15px; right:-15px; background:#fff; color:#333; border-radius:50%; width:30px; height:30px; display:flex; justify-content:center; align-items:center; cursor:pointer; font-size:1.5rem; font-weight:bold;" onclick="closeZoom()">&times;</div>
    <img src="" alt="Zoomed Image" id="zoomedImage" style="width:100%; height:auto; border-radius:8px;"/>
  </div>
</div>

<script>
  // Handle department and year change
  document.getElementById('department').addEventListener('change', function() {
    this.form.submit();
  });
  document.getElementById('year').addEventListener('change', function() {
    this.form.submit();
  });

  // Zoom functions
  function openZoom(src) {
    document.getElementById('zoomedImage').src = src;
    document.getElementById('imageZoomOverlay').style.display = 'flex';
  }
  function closeZoom() {
    document.getElementById('imageZoomOverlay').style.display = 'none';
  }
  document.getElementById('imageZoomOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeZoom();
  });
</script>

</body>
</html>
