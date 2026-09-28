<?php
// index.php - Main Home Page of PSG Polytechnic College - MOU Portal
?>
<!DOCTYPE html> 
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PSG Polytechnic College - Academic Portal</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

  <!-- Embed your CSS styles here, including responsiveness and font scaling -->
  <style>
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

    /* Layered animated background for visual depth */
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

    /* Navbar styling with responsiveness and toggle button */
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
    }

    /* Style for the second logo at top right corner of nav (adjusted below) */
    .nav-top-right-logo {
      position: absolute;
      top: 10px;
      right: 20px;
      z-index: 3;
      display: flex;
      align-items: center;
    }
    .nav-top-right-logo img {
      height: 50px;
      width: auto;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
      transition: transform 0.3s ease;
    }
    .nav-top-right-logo img:hover {
      transform: scale(1.05);
    }

    /* Navigation left logo/image styling */
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

    /* Title with animated underline on hover, responsive font size */
    .nav-title {
      font-size: clamp(1rem, 2.5vw, 1.8rem);
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
      flex-wrap: wrap;
      position: relative;
    }
    /* Style for normal links, responsive font size */
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
      font-size: clamp(0.8rem, 1.8vw, 1.2rem);
    }

    /* Dropdown styles */
    .dropdown {
      position: relative;
      display: inline-block;
    }
    .dropbtn {
      display: flex;
      align-items: center;
      cursor: pointer;
      padding: 12px 20px;
      border-radius: 25px;
      background: transparent;
      transition: all 0.3s ease;
      font-weight: 600;
      color: var(--light-text);
    }
    .dropbtn i {
      margin-right: 8px;
    }
    .dropdown-content {
      display: none;
      position: absolute;
      right: 0;
      top: 100%;
      background-color: #fff;
      min-width: 160px;
      border-radius: var(--border-radius);
      box-shadow: var(--shadow-medium);
      z-index: 999;
      flex-direction: column;
    }
    .dropdown-content a {
      color: var(--dark-text);
      padding: 12px 16px;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: background 0.3s ease;
    }
    .dropdown-content a:hover {
      background-color: rgba(0, 44, 119, 0.1);
    }
    .dropdown:hover .dropdown-content {
      display: flex;
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
      background: rgba(255,255,255,0.85);
      backdrop-filter: blur(25px) saturate(180%);
      border-radius: var(--border-radius);
      padding: var(--section-padding);
      border-left: 6px solid var(--psg-blue);
      box-shadow: var(--shadow-light),
                  inset 0 1px 0 rgba(255,255,255,0.4),
                  0 0 0 1px rgba(255,255,255,0.1);
      transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      margin-bottom: 25px;
      overflow: hidden;
    }
    /* Variants for variety */
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

    /* Hover effects for sections */
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
    /* Hover scale and glow for sections */
    .section-box:hover {
      border-left-color: var(--psg-yellow);
      transform: translateY(-12px) scale(1.02);
      box-shadow: var(--shadow-heavy), inset 0 1px 0 rgba(255,255,255,0.6), 0 0 0 2px rgba(244,194,13,0.2), var(--shadow-glow);
      background: rgba(255,255,255,0.95);
    }

    /* Headings with gradient text and animated underline, font size responsive */
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
      font-size: clamp(2rem, 2.5vw, 2.5rem);
      padding-bottom: 12px;
    }
    h2 {
      font-size: clamp(1.5rem, 2.2vw, 2rem);
      padding-bottom: 8px;
      margin-bottom: 20px;
      text-shadow: 0 0 20px rgba(0,44,119,0.3);
    }
    h3 {
      font-size: clamp(1.2rem, 1.8vw, 1.5rem);
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

    /* Buttons with gradient, glow, hover effects, and animations */
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
    .btn:hover,
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
    /* Specific message styles */
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

    /* Hamburger menu toggle button for small screens */
    .menu-toggle {
      display: none; /* Hidden on larger screens by default */
      font-size: 1.5rem;
      cursor: pointer;
      color: var(--light-text);
    }

    /* Responsive adjustments for small screens */
    @media(max-width: 768px){
      nav {
        flex-direction: column;
        align-items: flex-start;
        padding: 0.5rem 1rem;
      }
      /* Hide nav links initially, show when toggled */
      .nav-links {
        display: none;
        width: 100%;
        flex-direction: column;
        gap: 10px;
        margin-top: 10px;
      }
      /* Show nav links when toggled */
      .nav-links.show {
        display: flex;
      }
      /* Position second logo at top center for small screens */
      .nav-top-right-logo {
        position: static;
        margin-top: 10px;
        align-self: center;
      }
      /* Show hamburger menu button */
      .menu-toggle {
        display: block;
        align-self: flex-end;
        margin: 10px 0;
      }
    }
    @media(min-width: 769px){
      nav {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 2rem;
      }
      /* Hide toggle button on larger screens */
      .menu-toggle {
        display: none;
      }
    }
  </style>
</head>
<body>

  <!-- Navigation -->
<nav>
  <div class="nav-left">
    <img src="uploads/images/logo1.png" alt="PSG Logo" />
    <span class="nav-title">
      PSG Polytechnic - Academic Portal
    </span>
  </div>
  <!-- Hamburger menu icon for small screens -->
  <div class="menu-toggle" id="menu-toggle">
    <i class="fa fa-bars"></i>
  </div>
  <!-- Second logo positioned at top right (adjusted in media query) -->
  <div class="nav-top-right-logo">
    <img src="uploads/images/logo2.png" alt="Second Logo" />
  </div>
  <!-- Navigation links -->
  <div class="nav-links" id="nav-links">
    <div class="dropdown">
      <a class="dropbtn"><i class="fa-solid fa-user"></i> Account <i class="fa-solid fa-caret-down"></i></a>
      <div class="dropdown-content">
        <a href="login.php"><i class="fa-solid fa-sign-in-alt"></i> Login</a>
        <a href="reg.php"><i class="fa-solid fa-user-plus"></i> Register</a>
		<a href="slogin.php"><i class="fa-solid fa-sign-in-alt"></i> student Login</a>
      </div>
    </div>
    <a href="departments.php"><i class="fa-solid fa-building"></i> Departments</a>
    <a href="upload.php"><i class="fa-solid fa-upload"></i> Upload</a>
    <a href="view.php"><i class="fa-solid fa-eye"></i> View</a>
    <a href="gallery.php"><i class="fa-solid fa-images"></i> Gallery</a>
    <div class="dropdown">
      <a class="dropbtn"><i class="fa-solid fa-user-graduate"></i> Staff <i class="fa-solid fa-caret-down"></i></a>
      <div class="dropdown-content">
        <a href="staff_upload.php"><i class="fa-solid fa-info-circle"></i> Staff Details</a>
        <a href="staff_fdp.php"><i class="fa-solid fa-list"></i> staff FDP</a>
        <a href="view_acd.php"><i class="fa-solid fa-book-open"></i> View Academic</a>
        <a href="successrate.php"><i class="fa-solid fa-trophy"></i> View Success Rate</a>
        <a href="curr_gap.php"><i class="fa-solid fa-trophy"></i> View curriculum gaps</a>
		<a href="academic_perform.php"><i class="fa-solid fa-trophy"></i> View academic perform</a>
		<a href="addmission.php"><i class="fa-solid fa-trophy"></i> View admission</a>
		<a href="std_partici.php"><i class="fa-solid fa-calendar-alt"></i> View participation event</a>
      </div>
    </div>
    <div class="dropdown">
      <a class="dropbtn"><i class="fa-solid fa-user-graduate"></i> Students <i class="fa-solid fa-caret-down"></i></a>
      <div class="dropdown-content">
        <a href="std_upload.php"><i class="fa-solid fa-info-circle"></i> Student Details</a>
		<a href="view_student.php"><i class="fa-solid fa-info-circle"></i> View student</a>
        <a href="std_sports_details.php"><i class="fa-solid fa-list"></i> Sports Details</a>
        <a href="std_indus.php"><i class="fa-solid fa-book-open"></i> Add Industry</a>
        <a href="std_achiev.php"><i class="fa-solid fa-trophy"></i> View Achievements</a>
        <a href="semester_details.php"><i class="fa-solid fa-plus-circle"></i> Add Semester</a>
        <a href="view_sem.php"><i class="fa-solid fa-calendar-alt"></i> View Semester</a>
        <a href="std_higher.php"><i class="fa-solid fa-calendar-alt"></i> View placement/higher study</a>
        <a href="std_indus.php"><i class="fa-solid fa-calendar-alt"></i> View industry</a>
        <a href="std_pro.php"><i class="fa-solid fa-calendar-alt"></i> View projects</a>
        <a href="std_publication.php"><i class="fa-solid fa-calendar-alt"></i> View publication</a>
		<a href="tech.php"><i class="fa-solid fa-calendar-alt"></i> View technical event</a>
		
      </div>
    </div>
    <!-- Dropdown for department details -->
    <div class="dropdown">
      <a class="dropbtn"><i class="fa-solid fa-user"></i> department details <i class="fa-solid fa-caret-down"></i></a>
      <div class="dropdown-content">
        <a href="login.php"><i class="fa-solid fa-sign-in-alt"></i> about</a>
        <a href="reg.php"><i class="fa-solid fa-user-plus"></i> view</a>
      </div>
    </div>
  </div>
</nav>

<!-- Main Content -->
<main>
  <!-- Existing Sections -->
  <section class="section-box">
    <h2>Explore Our Collaborations</h2>
    <p><strong>Welcome to the MOU Information Portal of PSG Polytechnic College!</strong></p>
    <p>We foster strategic partnerships with industry leaders and academic institutions to bridge academic learning with real-world industry standards, creating opportunities for students and faculty alike.</p>
  </section>

  <section class="section-box">
    <h2>What This Portal Offers</h2>
    <ul>
      <li><strong>Internships & Training:</strong> Practical industry experience through structured programs.</li>
      <li><strong>Industrial Visits:</strong> Exposure to the latest technologies and practices.</li>
      <li><strong>Faculty Development:</strong> Workshops and collaborative training initiatives.</li>
      <li><strong>Skill Development:</strong> Certifications and industry-relevant skills for students.</li>
      <li><strong>Knowledge Sharing:</strong> Guest lectures, webinars, and joint research projects.</li>
    </ul>
  </section>

  <section class="section-box">
    <h2>Our Mission</h2>
    <ul>
      <li>Empowering students with practical skills and knowledge.</li>
      <li>Fostering innovation through collaborations.</li>
      <li>Engaging faculty and students with industry trends.</li>
    </ul>
  </section>

  <section class="section-box">
    <h2>Explore Opportunities</h2>
    <p>Our partnerships enable growth, innovation, and excellence. Stay updated with latest news, success stories, and initiatives through this portal, as we build a brighter future together.</p>
  </section>

  <!-- Additional Sections: Staff, Students, MOU, IQAC -->
  <section class="section-box" id="staff-details">
    <h2>Staff Details</h2>
    <p>Our esteemed faculty and staff members are dedicated to fostering academic excellence and industry collaboration. <a href="staff_upload.php">View Staff Profiles</a></p>
  </section>

  <section class="section-box" id="student-details">
    <h2>Student Details</h2>
    <p>Learn more about our student community, their achievements, and opportunities available. <a href="view_student.php">View Student Profiles</a></p>
  </section>

  <section class="section-box" id="mou-details">
    <h2>MOU Details</h2>
    <p>Explore our Memorandums of Understanding with industry partners and academic institutions. <a href="mou_index.php">View MOU Agreements</a></p>
  </section>

  <section class="section-box" id="iqac-details">
    <h2>IQAC Details</h2>
    <p>Get insights into our Internal Quality Assurance Cell (IQAC) initiatives, quality policies, and accreditation reports. <a href="nba_report.php?report_type=iqac">View IQAC Reports</a></p>
  </section>
</main>

<!-- Footer -->
<footer>
  &copy; 2025 PSG Polytechnic College - MOU Portal. All Rights Reserved.
</footer>

<!-- JavaScript for menu toggle on mobile -->
<script>
  document.getElementById('menu-toggle').addEventListener('click', function() {
    document.getElementById('nav-links').classList.toggle('show');
  });
</script>

</body>
</html>
