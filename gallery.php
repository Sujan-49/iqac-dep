<?php
require_once __DIR__ . '/include/auth.php';
$currentUser = iqac_require_login(['staff', 'admin', 'super_admin']);

// Connect to database
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli('localhost', 'root', '', 'mou');
if ($conn->connect_error) {
    $conn = @new mysqli('localhost', 'root', '', 'iqac');
}
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch distinct departments
$departments = [];
$result = $conn->query("SELECT DISTINCT department FROM mou_files");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row['department'];
    }
}

// Fetch distinct companies
$companies = [];
$result = $conn->query("SELECT DISTINCT company FROM mou_files");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $companies[] = $row['company'];
    }
}

// Get selected department and company from URL or default
$department = isset($_GET['department']) ? $_GET['department'] : ($departments[0] ?? '');
$company = isset($_GET['company']) ? $_GET['company'] : ($companies[0] ?? '');

// Fetch images and info for selected department and company
$images = [];
$departmentName = '';
$companyName = '';
if ($department && $company) {
    $stmt = $conn->prepare("SELECT image, department, company FROM mou_files WHERE department = ? AND company = ?");
    $stmt->bind_param("ss", $department, $company);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $images[] = $row;
        $departmentName = $row['department'];
        $companyName = $row['company'];
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
<title><?= htmlspecialchars($department) ?> - MOU Images Gallery</title>
<!-- Google Fonts for Inter -->
<link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="stylegallery.css"/>
<script src="scriptgallery.js" defer></script>
<style>
body { 
  font-family: 'Inter', sans-serif; 
  margin: 20px; 
  display: flex; 
  flex-direction: column; 
  align-items: center; /* Center content horizontally */
  background-color: #f9f9f9;
  position: relative;
}

/* Back link styling */
.back-link {
  position: absolute;
  top: 20px;
  left: 20px;
  padding:8px 12px;
  background:#007bff;
  color:#fff;
  border-radius:4px;
  text-decoration:none;
  font-weight: 600;
}
.back-link:hover { background:#0056b3; }

h2 {
  margin-top: 60px;
  margin-bottom: 10px;
  text-align: center;
}

/* Display department and company info */
.info-box {
  margin-bottom: 20px;
  text-align: center;
  font-size: 1rem;
  font-weight: 600;
}

/* Container for dropdowns side by side */
#dropdownContainer {
  display: flex; 
  justify-content: center; 
  align-items: center; 
  margin-bottom: 20px;
}

/* Styles for individual dropdown blocks */
.dropdown-block {
  margin: 0 15px;
}

/* Labels styling */
label {
  margin-right: 10px;
  font-weight: 600;
}

/* Select styling */
select {
  padding:8px; 
  font-size:1rem;
  border: 2px solid #007bff;
  border-radius: 4px;
  cursor: pointer;
  font-family: 'Inter', sans-serif;
}

/* Gallery styles */
.gallery {
  display: flex; 
  flex-wrap: wrap; 
  gap:10px; 
  justify-content: center;
  width: 100%;
  max-width: 1200px;
}
/* Image styles with padding and border */
.gallery img {
  max-width: 150px;
  cursor: pointer;
  border-radius: 8px;
  box-shadow: var(--shadow-light);
  border: 3px solid #ddd; /* border */
  padding: 5px; /* padding inside border */
  box-sizing: border-box; /* include padding in size */
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.gallery img:hover { 
  transform: scale(1.05); 
  box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

/* Zoom overlay styles */
#imageZoomOverlay {
  display: none; /* Hidden by default */
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0, 0, 0, 0.8);
  justify-content: center;
  align-items: center;
  z-index: 9999;
  padding: 20px;
  box-sizing: border-box;
}
#imageZoomOverlay.show {
  display: flex;
}
#zoomedImageContainer {
  position: relative;
  max-width: 90%;
  max-height: 90%;
  background: #fff;
  padding: 10px;
  border-radius: 10px;
  box-shadow: 0 0 20px rgba(0,0,0,0.5);
}
#zoomedImageContainer img {
  width: 100%;
  height: auto;
  display: block;
  border-radius: 8px;
}
/* Close button style */
#closeZoom {
  position: absolute;
  top: -15px;
  right: -15px;
  background: #fff;
  color: #333;
  border-radius: 50%;
  width: 30px;
  height: 30px;
  display: flex;
  justify-content: center;
  align-items: center;
  cursor: pointer;
  font-size: 1.5rem;
  font-weight: bold;
  box-shadow: 0 2px 10px rgba(0,0,0,0.3);
}

/* Navbar styling with dark blue background and white text/logo */
nav {
  background: #001e55; /* Dark blue background */
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
}

/* Responsive adjustments for nav on small screens */
@media(max-width: 768px){
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
  /* Stack links vertically if needed */
  .nav-links a {
    padding: 8px 12px;
    font-size: 0.9em;
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
</style>
</head>
<body>
<!-- Navbar with logo and white text on dark blue background -->
<nav>
    <div class="nav-left">
      <img src="uploads/images/logo1.png" alt="PSG Logo" />
      <span class="nav-title">
        PSG Polytechnic - MOU Portal
        <img src="uploads/images/logo2.png" alt="Secondary Logo" />
      </span>
    </div>
    <div class="nav-links">
	<a href="index.php"><i class="fa-solid fa-circle-info"></i> home</a>
      <a href="about.html"><i class="fa-solid fa-circle-info"></i> About</a>
      <a href="departments.php"><i class="fa-solid fa-building"></i> Departments</a>
      <a href="upload.php"><i class="fa-solid fa-upload"></i> Upload</a>
      
      
    </div>
</nav>

<!-- Back to departments link -->


<!-- Department and Company Info -->
<div class="info-box">
  
</div>

<!-- Dropdowns for Department and Company side by side -->
<div id="dropdownContainer">
  <!-- Department dropdown -->
  <div class="dropdown-block">
    <label for="deptDropdown">Department:</label>
    <select id="deptDropdown" name="department">
      <option value="">--Select Department--</option>
      <?php foreach($departments as $dep): ?>
        <option value="<?= htmlspecialchars($dep) ?>" <?= ($dep==$department)?'selected':'' ?>>
          <?= htmlspecialchars($dep) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
</div>
  <!-- Company dropdown -->
  <div class="dropdown-block">
    <label for="companyDropdown">Company:</label>
    <select id="companyDropdown" name="company">
	
      <?php foreach($companies as $comp): ?>
        <option value="<?= htmlspecialchars($comp) ?>" <?= $comp==$company?'selected':'' ?>>
          <?= htmlspecialchars($comp) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<h2>Images for Department: <?= htmlspecialchars($department) ?> &amp; Company: <?= htmlspecialchars($company) ?></h2>

<?php if($images): ?>
<div class="gallery">
  <?php foreach($images as $img): ?>
    <img src="<?= htmlspecialchars($img['image']) ?>" alt="Image" onclick="openZoom('<?= htmlspecialchars($img['image']) ?>')"/>
  <?php endforeach; ?>
</div>
<?php else: ?>
<p>No images found for this department and company.</p>
<?php endif; ?>

<!-- Zoom overlay -->
<div id="imageZoomOverlay">
  <div id="zoomedImageContainer">
    <div id="closeZoom" onclick="closeZoom()">&times;</div>
    <img src="" alt="Zoomed Image" id="zoomedImage"/>
  </div>
</div>

<script>
  // Handle department change
  document.getElementById('deptDropdown').addEventListener('change', function() {
    const selectedDept = this.value;
    const selectedComp = document.getElementById('companyDropdown').value;
    window.location.href = 'gallery.php?department=' + encodeURIComponent(selectedDept) + '&company=' + encodeURIComponent(selectedComp);
  });
  // Handle company change
  document.getElementById('companyDropdown').addEventListener('change', function() {
    const selectedComp = this.value;
    const selectedDept = document.getElementById('deptDropdown').value;
    window.location.href = 'gallery.php?department=' + encodeURIComponent(selectedDept) + '&company=' + encodeURIComponent(selectedComp);
  });

  // Open zoom modal
  function openZoom(src) {
    document.getElementById('zoomedImage').src = src;
    document.getElementById('imageZoomOverlay').classList.add('show');
  }
  // Close zoom modal
  function closeZoom() {
    document.getElementById('imageZoomOverlay').classList.remove('show');
  }
  // Close when clicking outside the image
  document.getElementById('imageZoomOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
      closeZoom();
    }
  });
</script>

</body>
</html>
