<?php
// Database connection parameters
$host = 'localhost'; 
$user = 'root'; 
$pass = ''; 
$db = 'iqac';

// Establish connection
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Set your default intake values for each year (replace with actual values: 60 or 30)
$default_intake_year1 = 60; // e.g., 60
$default_intake_year2 = 30; // e.g., 30
$default_intake_year3 = 60; // e.g., 60

// Fetch total students
$result_total = $conn->query("SELECT COUNT(*) as total FROM students");
if ($result_total) {
    $total_students = $result_total->fetch_assoc()['total'];
} else {
    die("Query failed for total students: " . $conn->error);
}

// Fetch students admitted through counseling (N1)
$result_counseling = $conn->query("SELECT COUNT(*) as total FROM students WHERE counselling_quota='Yes'");
if ($result_counseling) {
    $total_counseling = $result_counseling->fetch_assoc()['total'];
} else {
    die("Query failed for counseling quota: " . $conn->error);
}

// Fetch students admitted through management quota (N2)
$result_management = $conn->query("SELECT COUNT(*) as total FROM students WHERE management_quota='Yes'");
if ($result_management) {
    $total_management = $result_management->fetch_assoc()['total'];
} else {
    die("Query failed for management quota: " . $conn->error);
}

// Fetch students admitted through lateral entry (N3)
$result_lateral = $conn->query("SELECT COUNT(*) as total FROM students WHERE entry_type='Lateral'");
if ($result_lateral) {
    $total_lateral = $result_lateral->fetch_assoc()['total'];
} else {
    die("Query failed for lateral entry: " . $conn->error);
}

// Fetch all distinct batch_years
$batch_years_result = $conn->query("SELECT DISTINCT batch_year FROM students");
$batch_years = [];
if ($batch_years_result) {
    while ($row = $batch_years_result->fetch_assoc()) {
        $batch_years[] = $row['batch_year'];
    }
} else {
    die("Failed to fetch batch_years: " . $conn->error);
}

// Map batch_year to counts
$batch_year_counts = [];
if (!empty($batch_years)) {
    // Initialize counts
    foreach ($batch_years as $by) {
        $batch_year_counts[$by] = 0;
    }

    // Fetch counts per batch_year
    $placeholders = implode(',', array_fill(0, count($batch_years), '?'));
    $stmt = $conn->prepare("SELECT batch_year, COUNT(*) as count FROM students WHERE batch_year IN ($placeholders) GROUP BY batch_year");
    $stmt_params = [];
    foreach ($batch_years as $b) {
        $stmt_params[] = &$b;
    }
    call_user_func_array([$stmt, 'bind_param'], array_merge([str_repeat('s', count($stmt_params))], $stmt_params));
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $batch_year_counts[$row['batch_year']] = $row['count'];
    }
    $stmt->close();
}

// Assign batch_years to Year 1, Year 2, Year 3 based on your logic
// Example: assuming batch_years are ['2023', '2022', '2021']
$year1 = '';
$year2 = '';
$year3 = '';

foreach ($batch_years as $by) {
    if ($by == '2023') {
        $year1 = $by;
    } elseif ($by == '2022') {
        $year2 = $by;
    } elseif ($by == '2021') {
        $year3 = $by;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Students Intake Information</title>
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet" />
<style>
  body {
    font-family: 'Roboto', sans-serif;
    background-color: #f4f4f9;
    margin: 0;
    padding: 30px;
  }

  .container {
    max-width: 900px;
    margin: 0 auto;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    padding: 20px;
  }

  h2 {
    text-align: center;
    margin-bottom: 10px;
    color: #333;
  }

  h3 {
    text-align: center;
    margin-bottom: 20px;
    color: #555;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    font-family: 'Roboto', sans-serif;
  }

  th, td {
    padding: 12px;
    border: 1px solid #ddd;
    text-align: left;
  }

  th {
    background-color: #2196f3;
    color: #fff;
  }

  tr:nth-child(even) {
    background-color: #f2f2f2;
  }

  /* Responsive */
  @media(max-width: 600px){
    body {
      padding: 15px;
    }
    table, th, td {
      font-size: 14px;
    }
  }
</style>
</head>
<body>
<div class="container">
  <h2>C. Admission Process</h2>
  <h3>Table 13: Students Intake Information</h3>
  <table>
    <tr>
      <th>Students Admission Details</th>
      <th>I year</th>
      <th>II year**</th>
      <th>III year**</th>
    </tr>
    <tr>
      <td>Sanctioned intake strength of the program (N)</td>
      <td><?= htmlspecialchars($default_intake_year1) ?></td>
      <td><?= htmlspecialchars($default_intake_year2) ?></td>
      <td><?= htmlspecialchars($default_intake_year3) ?></td>
    </tr>
    <tr>
      <td>Total number of students admitted through counseling (N1)</td>
      <td><?= htmlspecialchars($total_counseling) ?></td>
      <td></td>
      <td></td>
    </tr>
    <tr>
      <td>Number of students admitted through management quota (N2)</td>
      <td><?= htmlspecialchars($total_management) ?></td>
      <td></td>
      <td></td>
    </tr>
    <tr>
      <td>Number of students admitted through lateral entry (N3)*</td>
      <td><?= htmlspecialchars($total_lateral) ?></td>
      <td></td>
      <td></td>
    </tr>
    <tr style="background-color:#000; color:#fff;">
      <td>Total number of students admitted in the Program (N1+N2+N3)</td>
      <td><?= htmlspecialchars($total_counseling + $total_management + $total_lateral) ?></td>
      <td></td>
      <td></td>
    </tr>
    <!-- Display counts per batch_year -->
    <tr>
      <td>Batch Year Counts</td>
      <td><?= isset($batch_year_counts[$year1]) ? htmlspecialchars($batch_year_counts[$year1]) : '0' ?></td>
      <td><?= isset($batch_year_counts[$year2]) ? htmlspecialchars($batch_year_counts[$year2]) : '0' ?></td>
      <td><?= isset($batch_year_counts[$year3]) ? htmlspecialchars($batch_year_counts[$year3]) : '0' ?></td>
    </tr>
  </table>
</div>
</body>
</html>