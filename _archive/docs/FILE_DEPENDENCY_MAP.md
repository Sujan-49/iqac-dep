# FILE DEPENDENCY MAP & FUNCTION REFERENCE GUIDE

## File Dependency Graph

### Core Dependencies (Used by Multiple Files)

```
┌─────────────────────────────────────┐
│   DATABASE CONNECTION FILES          │
│  (Must be included first)            │
└──────────┬──────────────────────────┘
           │
           ├─ dp_connection.php (iqac database)
           │  ├─ host: localhost
           │  ├─ user: root
           │  ├─ password: ""
           │  └─ db: iqac
           │
           └─ include/dp_connection.php (mou database)
              ├─ host: localhost
              ├─ user: root
              ├─ password: ""
              └─ db: mou

Used by Files:
├─ Academic modules: academic_perform.php, semester_details.php, successrate.php, curr_gap.php
├─ Student modules: std_upload.php, std_achiev.php, std_higher.php, std_pro.php, std_indus.php, std_sports_details.php
├─ Admin modules: addmission.php, view_acd.php, view_ach.php, view_sem.php, view_std.php
├─ Staff modules: staff_upload.php
└─ MOU modules: upload.php, edit.php, delete.php, gallery.php, departments.php
```

### SESSION DEPENDENCIES

```
┌─────────────────────────────────────┐
│    session_start()                   │
│  (Called in every protected page)    │
└──────────┬──────────────────────────┘
           │
           ├─ Checks: $_SESSION['user_id']
           ├─ Checks: $_SESSION['student_id']
           ├─ Checks: $_SESSION['role']
           ├─ Checks: $_SESSION['username']
           │
           └─ Used by:
              ├─ login.php (sets session)
              ├─ slogin.php (sets session)
              ├─ stlogin.php (sets session)
              ├─ logout.php (destroys session)
              ├─ std_index.php (checks session)
              ├─ staff_index.php (checks session)
              └─ All admin pages (check session)
```

### FRONTEND DEPENDENCIES

```
┌──────────────────────────────────────┐
│    CSS Files                          │
└──────────┬───────────────────────────┘
           │
           ├─ styleabout.css ← about.html
           ├─ stylehome.css ← home.php, home.html
           ├─ styledepartments.css ← departments.php
           ├─ stylegallery.css ← gallery.php, gallery.html
           ├─ styleupload.css ← upload.php
           ├─ styleview.css ← view.php, view_*.php
           │
           └─ Inline styles in many .php files

┌──────────────────────────────────────┐
│    JavaScript Files                   │
└──────────┬───────────────────────────┘
           │
           ├─ scriptdept.js ← departments.php
           │  └─ Handles department filtering
           │
           └─ scriptgallery.js ← gallery.php
              └─ Handles gallery functionality
```

---

## Module-Specific Dependencies

### STUDENT MODULE DEPENDENCIES

```
Student Pages:
├─ std_index.php
│  └─ Depends on: session_start, dp_connection.php
│
├─ std_upload.php
│  ├─ Depends on: dp_connection.php
│  ├─ Queries: students, academic_details, achievement_details, non_academic_details
│  ├─ Uploads to: uploads/students/, uploads/parents/, uploads/certificates/, uploads/photos/
│  └─ Functions: mkdir(), move_uploaded_file(), $conn->prepare()
│
├─ std_achiev.php
│  ├─ Depends on: dp_connection.php
│  ├─ Queries: students, achievement_details
│  ├─ Uploads to: uploads/certificates/, uploads/photos/
│  └─ Functions: file upload validation
│
├─ std_higher.php
│  ├─ Depends on: dp_connection.php
│  ├─ Queries: students, higher_studies
│  └─ Functions: uploadFile(), mkdir()
│
├─ std_pro.php
│  ├─ Depends on: dp_connection.php
│  ├─ Queries: students, student_projects
│  └─ Operations: INSERT student projects
│
├─ std_publication.php
│  ├─ Depends on: dp_connection.php
│  ├─ Auto-creates: student_publications table
│  └─ Operations: INSERT publications
│
├─ std_indus.php
│  ├─ Depends on: dp_connection.php
│  ├─ Queries: students, industry_visits
│  ├─ Uploads to: uploads/
│  └─ Operations: INSERT industry visits
│
├─ std_partici.php
│  ├─ Depends on: dp_connection.php
│  ├─ Queries: student_participation
│  ├─ Uploads to: uploads/
│  └─ Operations: INSERT participation
│
├─ std_sports_details.php
│  ├─ Depends on: dp_connection.php
│  ├─ Queries: students, student_sports
│  ├─ Uploads to: uploads/certificates/
│  └─ File validation: PDF, JPG, PNG
│
├─ std_percent.php
│  ├─ Depends on: dp_connection.php
│  ├─ Queries: students, semester_marks, semester_details
│  └─ Operations: JOIN analysis
│
└─ view_student.php
   ├─ Depends on: dp_connection.php
   ├─ Session check: $_SESSION['student_id']
   ├─ Queries: students, academic_details, achievement_details, non_academic_details
   └─ Operations: Multiple JOIN queries
```

### STAFF MODULE DEPENDENCIES

```
Staff Pages:
├─ staff_index.php
│  ├─ Depends on: dp_connection.php, session_start()
│  ├─ Checks: $_SESSION['user_id'], $_SESSION['role']='staff'
│  ├─ Queries: staff, staff_details
│  └─ Operations: JOIN query
│
├─ staff_upload.php
│  ├─ Depends on: dp_connection.php
│  ├─ Handles: Staff registration, academic details, non-academic, work experience
│  ├─ Uploads to: uploads/staff/
│  ├─ Queries: staff_details, staff_academic_details, staff_non_academic_details, staff_work_experience
│  └─ Operations: Multiple INSERT operations
│
└─ staff_fdp.php
   ├─ Depends on: dp_connection.php (implied)
   ├─ Purpose: Faculty Development Program
   └─ [Details if file was provided]
```

### ACADEMIC MODULE DEPENDENCIES

```
Academic Pages:
├─ academic_perform.php
│  ├─ Depends on: dp_connection.php
│  ├─ Queries: students, academic_details (achievement_mark referenced)
│  ├─ Operations: Complex JOINs with aggregation
│  ├─ Calculations: Mean percentage, API score, Success rate
│  └─ Issues: SQL injection in string concatenation
│
├─ semester_details.php
│  ├─ Depends on: dp_connection.php
│  ├─ Queries: students, semester_marks, semester_details, achievement_mark
│  ├─ Uploads to: uploads/mark_sheets/
│  ├─ Operations: INSERT with 42 parameters
│  ├─ Calculations: Total marks from 9 subjects
│  └─ JSON handling: Possible subject data storage
│
├─ successrate.php
│  ├─ Depends on: dp_connection.php
│  ├─ Queries: students, academic_details, semester_marks
│  ├─ Operations: Complex aggregation and JOINs
│  ├─ Calculations: Success with/without backlog
│  └─ Groups by: batch_year
│
├─ curr_gap.php
│  ├─ Depends on: dp_connection.php
│  ├─ Queries: curriculum_gaps (auto-creates)
│  ├─ Operations: INSERT curriculum gaps
│  ├─ Fields: course_code, action_taken, resource_person, mode, no_of_students
│  └─ Purpose: Track curriculum improvements
│
└─ tech.php
   ├─ Depends on: dp_connection.php
   ├─ Queries: technical_events (auto-creates)
   ├─ Uploads to: uploads/
   ├─ Operations: INSERT technical events
   └─ Purpose: Track technical event participation
```

### ADMIN MODULE DEPENDENCIES

```
Admin Pages:
├─ addmission.php
│  ├─ Depends on: dp_connection.php
│  ├─ Operations: Student registration
│  └─ Queries: students table
│
├─ view_acd.php
│  ├─ Depends on: dp_connection.php
│  ├─ Queries: academic_details, students
│  └─ Operations: JOIN display
│
├─ view_ach.php
│  ├─ Depends on: dp_connection.php
│  ├─ Queries: achievement_details, students
│  └─ Operations: JOIN display
│
├─ view_sem.php
│  ├─ Depends on: dp_connection.php
│  ├─ Queries: semester_marks, students
│  └─ Operations: JOIN display
│
├─ view_std.php
│  ├─ Depends on: dp_connection.php
│  ├─ Queries: students (all columns)
│  └─ Operations: Display all student data
│
├─ view.php
│  ├─ Depends on: data.json (local file)
│  ├─ Purpose: Display uploaded MOUs
│  └─ Operations: JSON parsing
│
└─ delete.php
   ├─ Depends on: include/dp_connection.php (mou database)
   ├─ Queries: mou_files
   ├─ Operations: DELETE record, @unlink files
   └─ Files deleted: filepath, image
```

### MOU MODULE DEPENDENCIES

```
MOU Pages (use include/dp_connection.php):
├─ upload.php
│  ├─ Depends on: include/dp_connection.php (mou database)
│  ├─ Queries: mou_files
│  ├─ Uploads to: uploads/, uploads/images/
│  ├─ Operations: INSERT mou_files with files
│  ├─ Handles: DELETE via $_GET['action']='delete'
│  └─ Redirects to: edit.php via $_GET['action']='edit'
│
├─ edit.php
│  ├─ Depends on: include/dp_connection.php
│  ├─ Queries: mou_files
│  ├─ Operations: UPDATE mou_files
│  ├─ File handling: Replace documents/images
│  ├─ Cleanup: @unlink() old files
│  └─ Redirect: upload.php after save
│
├─ delete.php
│  ├─ Depends on: include/dp_connection.php
│  ├─ Queries: mou_files (by ID)
│  ├─ Operations: DELETE record
│  ├─ File cleanup: @unlink(filepath), @unlink(image)
│  └─ Redirect: upload.php
│
├─ gallery.php
│  ├─ Depends on: include/dp_connection.php
│  ├─ CSS: stylegallery.css
│  ├─ JS: scriptgallery.js
│  ├─ Queries: mou_files (DISTINCT department, company)
│  ├─ Filters: $_GET['department'], $_GET['company']
│  └─ Operations: Display images with dropdowns
│
├─ departments.php
│  ├─ Depends on: include/dp_connection.php
│  ├─ CSS: styledepartments.css
│  ├─ JS: scriptdept.js
│  ├─ Queries: mou_files with filters
│  ├─ Filters: $_GET['department'], $_GET['year']
│  └─ Operations: Display MOUs by department
│
├─ mou_index.php
│  ├─ Depends on: [Connection implied]
│  ├─ Purpose: List MOU files for browsing
│  └─ [Full implementation not reviewed]
│
└─ [MOU viewing pages]
   └─ Dependencies: CSS, JavaScript, Database connection
```

### AUTHENTICATION DEPENDENCIES

```
Login Pages:
├─ login.php
│  ├─ Depends on: db.php ❌ MISSING!
│  ├─ Queries: users table
│  ├─ Session: Sets $_SESSION['user_id'], ['username'], ['role']
│  ├─ Redirect: student_dashboard.php or staff_dashboard.php
│  └─ Issues: db.php not found in workspace
│
├─ slogin.php
│  ├─ Depends on: dp_connection.php (iqac)
│  ├─ Queries: users table
│  ├─ Session: Sets $_SESSION['user_id'], ['username'], ['role']
│  ├─ Redirect: index.php
│  └─ Authentication: Password verification with prepared statement
│
├─ stlogin.php
│  ├─ Depends on: dp_connection.php (iqac)
│  ├─ Queries: students table
│  ├─ Session: Sets $_SESSION['student_id'] ONLY
│  ├─ Redirect: view_student.php
│  ├─ Authentication: ID only (NO PASSWORD) ❌ SECURITY ISSUE
│  └─ Issues: No password required, single session variable
│
├─ logout.php
│  ├─ Depends on: session_start()
│  ├─ Operations: session_destroy()
│  └─ Redirect: login.php
│
└─ register.php
   ├─ Depends on: [Not fully reviewed]
   └─ Purpose: User registration
```

---

## Function Reference Guide

### Session & Authentication Functions

```php
// Start session (in all protected pages)
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) { ... }
if (!isset($_SESSION['student_id'])) { ... }

// Set session variables
$_SESSION['user_id'] = $user_id;
$_SESSION['username'] = $username;
$_SESSION['role'] = $role;
$_SESSION['student_id'] = $student_id;

// Destroy session
session_destroy();

// Verify password
password_verify($input_password, $hashed_password);
```

### Database Functions (MySQLi)

```php
// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Prepare statement (SQL Injection Prevention)
$stmt = $conn->prepare("SELECT * FROM table WHERE id = ?");

// Bind parameters
$stmt->bind_param("i", $id);  // "i" = integer
$stmt->bind_param("s", $name); // "s" = string
$stmt->bind_param("is", $id, $name); // mixed types

// Execute statement
$stmt->execute();

// Get result
$result = $stmt->get_result();
$row = $result->fetch_assoc();  // Single row
while($row = $result->fetch_assoc()) { ... }  // Multiple rows

// Get affected rows
$stmt->affected_rows;

// Close statement
$stmt->close();

// Close connection
$conn->close();

// Real escape string (DEPRECATED - use prepared statements instead)
$conn->real_escape_string($string);
```

### File Upload Functions

```php
// Check if file was uploaded
if (isset($_FILES['field_name']) && $_FILES['field_name']['error'] == UPLOAD_ERR_OK) {

// Get uploaded file info
$file = $_FILES['field_name'];
$file['name'];      // Original filename
$file['tmp_name'];  // Temporary path
$file['error'];     // Error code
$file['size'];      // File size
$file['type'];      // MIME type

// Get file extension
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$basename = basename($file['name']);

// Create upload directory
if (!is_dir('uploads/')) {
    mkdir('uploads/', 0755, true);
}

// Move uploaded file
move_uploaded_file($file['tmp_name'], $target_path);

// Delete file
@unlink($file_path);  // @ suppresses warnings
unlink($file_path);
```

### String & Validation Functions

```php
// Escape HTML special characters
htmlspecialchars($string);

// Trim whitespace
trim($string);

// Convert to lowercase
strtolower($string);

// Get string length
strlen($string);

// Substring
substr($string, 0, 5);

// String replace
str_replace($search, $replace, $string);

// Regex pattern match
preg_match('/pattern/', $string);
preg_replace('/pattern/', $replacement, $string);

// Filter variables
filter_var($email, FILTER_VALIDATE_EMAIL);
filter_var($number, FILTER_VALIDATE_INT);

// Type conversion
intval($variable);      // Convert to integer
floatval($variable);    // Convert to float
strval($variable);      // Convert to string
```

### Array Functions

```php
// Count array elements
count($array);

// Check if key exists
isset($array['key']);
array_key_exists('key', $array);

// Get all keys/values
array_keys($array);
array_values($array);

// Merge arrays
array_merge($array1, $array2);

// Filter array
array_filter($array, 'callback_function');

// Map array
array_map('callback_function', $array);

// Implode (join array to string)
implode(',', $array);

// Explode (split string to array)
explode(',', $string);

// Push to array
$array[] = $value;
array_push($array, $value);
```

### Output Functions

```php
// Echo output
echo "text";

// Print output
print "text";

// Variable dump
var_dump($variable);

// Print readable output
print_r($variable);

// JSON encode/decode
json_encode($array);
json_decode($json_string, true); // Returns array
```

### Redirect Functions

```php
// HTTP header redirect
header('Location: page.php');
exit();

// With parameter
header('Location: page.php?id=' . $id);
exit();

// Back button
header('Location: ' . $_SERVER['HTTP_REFERER']);
```

---

## Dependency Chains

### Critical Dependency Chain 1: Student View

```
view_student.php
├─ Requires: session_start()
├─ Requires: dp_connection.php
│  ├─ Requires: mysqli
│  └─ Database: iqac
├─ Queries:
│  ├─ students (by id from session)
│  ├─ academic_details (JOIN)
│  ├─ achievement_details (JOIN)
│  └─ non_academic_details (JOIN)
└─ Displays: Complete student profile
```

### Critical Dependency Chain 2: Marks Entry

```
semester_details.php
├─ Requires: dp_connection.php
├─ Requires: students selection
├─ Requires: semester selection
├─ Queries:
│  ├─ students
│  ├─ semester_marks
│  └─ semester_details
├─ File upload:
│  └─ uploads/mark_sheets/
├─ Database operations:
│  └─ INSERT into achievement_mark (42 parameters)
└─ Calculations:
   ├─ Total marks
   ├─ Pass/fail status
   └─ Grade calculation
```

### Critical Dependency Chain 3: Academic Performance Report

```
academic_perform.php
├─ Requires: dp_connection.php
├─ Queries:
│  ├─ students
│  ├─ academic_details
│  └─ achievement_mark (implicit)
├─ Calculations:
│  ├─ Students per year
│  ├─ Appeared students count
│  ├─ Passed students count
│  ├─ Mean percentage
│  └─ API (Academic Performance Index)
└─ Output: HTML table with metrics
```

### Critical Dependency Chain 4: MOU Upload

```
upload.php (mou database)
├─ Requires: include/dp_connection.php
├─ Directory creation:
│  ├─ uploads/
│  └─ uploads/images/
├─ File uploads:
│  ├─ Document → uploads/{timestamp}_{name}
│  └─ Image → uploads/images/{timestamp}_{name}
├─ Database operations:
│  └─ INSERT into mou_files
├─ Form fields:
│  ├─ department, year, company
│  ├─ status, staff_id, sign_date
│  ├─ file, image (upload fields)
│  └─ POST validation
└─ Redirection:
   ├─ edit.php (for editing)
   └─ (stays on upload.php for listing)
```

---

## Missing Dependencies

### Critical Issues

1. **login.php References db.php**
   - **File:** login.php (line with include 'db.php')
   - **Status:** FILE MISSING ❌
   - **Impact:** CRITICAL - login.php cannot function
   - **Resolution:** Create db.php or update include path

2. **Staff Dashboard Student Data**
   - **File:** staff_index.php (references student_dashboard.php)
   - **Status:** NOT FOUND
   - **Impact:** Dashboard may not work
   - **Resolution:** Check if file exists or update reference

---

## Circular Dependencies

None detected.

---

## Unused Files

- data.json (referenced in view.php but not found)
- Some screenshot files in Screenshots/ directory

---

## Recommended Refactoring

### Create Central Includes Folder

```
include/
├─ dp_connection_iqac.php
├─ dp_connection_mou.php
├─ auth.php (centralized auth functions)
├─ functions.php (utility functions)
├─ constants.php (system constants)
└─ classes/
   ├─ Student.php
   ├─ Staff.php
   ├─ Achievement.php
   └─ Mou.php
```

### Create Common Functions File

```php
// include/functions.php

function uploadFile($fieldName, $targetDir, $allowedExts = []) { ... }
function sanitizeInput($input) { ... }
function validateEmail($email) { ... }
function redirectTo($page) { ... }
function isValidRole($role) { ... }
function checkSession() { ... }
```

---

END OF FILE DEPENDENCY & FUNCTION REFERENCE DOCUMENTATION
