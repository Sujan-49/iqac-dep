# ROLE PERMISSION MATRIX & ACCESS CONTROL DOCUMENTATION

## Role-Based Access Control Matrix

### Legend
- ✅ = Full Access
- ⚠️ = Conditional Access (Own records only)
- ❌ = No Access
- 📝 = Read-Only

---

## Permission Matrix by Role

```
┌────────────────────────────────────────────────────────────────────────────┐
│                    STUDENT ROLE PERMISSIONS                               │
├────────────────────────────────────────────────────────────────────────────┤

PROFILE & DATA MANAGEMENT:
├─ View Own Profile (view_student.php)              ✅ Full Access
├─ Update Own Profile (std_upload.php)              ✅ Full Access  
├─ Upload Own Photos                                ✅ Full Access
├─ View Own Academic History                        ✅ Full Access
├─ View Other Student Data                          ❌ No Access
├─ Modify Other Student Data                        ❌ No Access

ACHIEVEMENT TRACKING:
├─ Record Own Achievements (std_achiev.php)         ✅ Full Access
├─ Upload Achievement Certificates                  ✅ Full Access
├─ View Own Achievements                            ✅ Full Access
├─ View Other Students' Achievements                ❌ No Access
├─ Delete Own Achievements                          ⚠️ Own records only
├─ Delete Other Students' Achievements              ❌ No Access

ACADEMIC RECORDS:
├─ Submit Academic Details (std_upload.php)         ✅ Full Access
├─ View Own Academic Details (view_acd.php)         ✅ Full Access
├─ Edit Own Academic Details                        ⚠️ Own records only
├─ View Marks/Grades (semester_details.php)         📝 Read-Only
├─ Edit Marks/Grades                                ❌ No Access
├─ View Performance Analysis (std_percent.php)      ✅ Own data only

ACTIVITIES & APPLICATIONS:
├─ Record Higher Studies (std_higher.php)           ✅ Full Access
├─ Record Projects (std_pro.php)                    ✅ Full Access
├─ Submit Publications (std_publication.php)        ✅ Full Access
├─ Log Industry Visits (std_indus.php)              ✅ Full Access
├─ Record Event Participation (std_partici.php)     ✅ Full Access
├─ Record Sports Activities (std_sports_details)    ✅ Full Access
├─ Upload Evidence/Documents                        ✅ Full Access

REPORTING & VIEWING:
├─ View MOU Gallery (gallery.php)                   ✅ Full Access
├─ View Departments (departments.php)               ✅ Full Access
├─ View Home Page (home.php, index.php)             ✅ Full Access
├─ View About Page (about.html)                     ✅ Full Access

ADMINISTRATIVE FUNCTIONS:
├─ Add Students                                     ❌ No Access
├─ Delete Students                                  ❌ No Access
├─ Enter Marks                                      ❌ No Access
├─ Generate Reports                                 ❌ No Access
├─ Manage Staff                                     ❌ No Access
├─ Upload MOU Files                                 ❌ No Access
├─ Edit MOU Files                                   ❌ No Access

└────────────────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────────────────┐
│                    STAFF ROLE PERMISSIONS                                  │
├────────────────────────────────────────────────────────────────────────────┤

PROFILE & DATA MANAGEMENT:
├─ View Own Profile (staff_index.php)               ✅ Full Access
├─ Update Own Profile (staff_upload.php)            ✅ Full Access
├─ Upload Own Photo                                 ✅ Full Access
├─ View Other Staff Data                            📝 Read-Only
├─ Modify Other Staff Data                          ❌ No Access

QUALIFICATIONS & EXPERIENCE:
├─ Add Own Academic Details (staff_upload.php)      ✅ Full Access
├─ Add Own Non-Academic Activities                  ✅ Full Access
├─ Add Own Work Experience                          ✅ Full Access
├─ Add Own Achievements                             ✅ Full Access
├─ View Own Qualifications                          ✅ Full Access
├─ Modify Own Data                                  ⚠️ Own records only
├─ Modify Other Staff Data                          ❌ No Access

FDP (FACULTY DEVELOPMENT):
├─ Record FDP Participation (staff_fdp.php)         ✅ Full Access
├─ Upload FDP Certificates                          ✅ Full Access
├─ View Own FDP Records                             ✅ Full Access
├─ View Other Staff FDP                             📝 Read-Only
├─ Manage FDP Programs                              ❌ No Access

MOU MANAGEMENT:
├─ View MOUs (mou_index.php)                        ✅ Full Access
├─ View MOU Gallery (gallery.php)                   ✅ Full Access
├─ View Department MOUs (departments.php)           ✅ Full Access
├─ Upload MOU Files (upload.php)                    ✅ With Approval
├─ Edit Own MOU Uploads (edit.php)                  ⚠️ Own MOUs only
├─ Delete Own MOU Uploads (delete.php)              ⚠️ Own MOUs only
├─ Edit Other Staff MOUs                            ❌ No Access
├─ Approve/Reject MOUs                              ❌ No Access

STUDENT MANAGEMENT:
├─ View Student Data                                📝 Read-Only
├─ View Student Achievements                        📝 Read-Only
├─ Add Student Achievement                          ⚠️ Own dept only
├─ View Student Marks                               📝 Read-Only
├─ Enter Student Marks                              ❌ No Access
├─ Delete Student Data                              ❌ No Access

ACADEMIC FUNCTIONS:
├─ View Academic Performance Reports                📝 Read-Only
├─ View Success Rate Analysis                       📝 Read-Only
├─ View Curriculum Gap Reports                      📝 Read-Only
├─ Enter Curriculum Gaps                            ❌ No Access
├─ Approve/Manage Gaps                              ❌ No Access

TECHNICAL EVENTS:
├─ View Technical Events (tech.php)                 ✅ Full Access
├─ Add Technical Events                             ⚠️ Dept coordinator
├─ Upload Event Files                               ⚠️ Dept coordinator

ADMINISTRATIVE FUNCTIONS:
├─ Add Staff                                        ❌ No Access
├─ Delete Staff                                     ❌ No Access
├─ Manage Users                                     ❌ No Access
├─ Generate System Reports                          ❌ No Access
├─ View System Logs                                 ❌ No Access

└────────────────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────────────────┐
│              ADMIN/FACULTY COORDINATOR PERMISSIONS                         │
├────────────────────────────────────────────────────────────────────────────┤

STUDENT MANAGEMENT:
├─ Add Students (addmission.php)                    ✅ Full Access
├─ View All Students                                ✅ Full Access
├─ Update Student Data (std_upload.php)             ✅ Full Access
├─ Delete Students (delete.php)                     ✅ Full Access
├─ View Student Profiles                            ✅ Full Access
├─ Upload Student Photos/Documents                  ✅ Full Access
├─ View Student Academic History                    ✅ Full Access
├─ View Student Achievements (view_ach.php)         ✅ Full Access

ACHIEVEMENT MANAGEMENT:
├─ Add Achievements (std_achiev.php)                ✅ Full Access
├─ View All Achievements                            ✅ Full Access
├─ Categorize Achievements                          ✅ Full Access
├─ Upload Certificates                              ✅ Full Access
├─ Modify Achievements                              ✅ Full Access
├─ Delete Achievements                              ✅ Full Access

ACADEMIC MANAGEMENT:
├─ Enter Semester Marks (semester_details.php)      ✅ Full Access
├─ Edit Marks                                       ✅ Full Access
├─ Upload Mark Sheets                               ✅ Full Access
├─ View Academic Details (view_acd.php)             ✅ Full Access
├─ View All Semester Data (view_sem.php)            ✅ Full Access
├─ Enter Multiple Semesters                         ✅ Full Access
├─ Calculate Totals/Averages                        ✅ Full Access

REPORTING & ANALYSIS:
├─ Generate Academic Performance Report             ✅ Full Access
├─ Generate Success Rate Analysis                   ✅ Full Access
├─ Generate Student Performance Analysis            ✅ Full Access
├─ Export Performance Data                          ✅ Full Access
├─ View Curriculum Gap Reports (curr_gap.php)       ✅ Full Access
├─ Add Curriculum Gaps                              ✅ Full Access
├─ Manage Gap Tracking                              ✅ Full Access

TECHNICAL EVENTS:
├─ Add Technical Events (tech.php)                  ✅ Full Access
├─ View Events                                      ✅ Full Access
├─ Update Event Details                             ✅ Full Access
├─ Upload Event Files                               ✅ Full Access
├─ Delete Events                                    ✅ Full Access
├─ Track Student Participation                      ✅ Full Access

MOU MANAGEMENT:
├─ Upload MOU Files (upload.php)                    ✅ Full Access
├─ Edit All MOU Records (edit.php)                  ✅ Full Access
├─ Delete MOU Records (delete.php)                  ✅ Full Access
├─ View All MOUs                                    ✅ Full Access
├─ View MOU Gallery (gallery.php)                   ✅ Full Access
├─ Filter by Department (departments.php)           ✅ Full Access
├─ Update MOU Status                                ✅ Full Access
├─ Manage Company Records                           ✅ Full Access

STAFF MANAGEMENT:
├─ Add Staff (staff_upload.php)                     ✅ Full Access
├─ View Staff Profiles                              ✅ Full Access
├─ Edit Staff Data                                  ✅ Full Access
├─ Add Staff Qualifications                         ✅ Full Access
├─ Add Staff Activities                             ✅ Full Access
├─ Add Work Experience                              ✅ Full Access
├─ Upload Staff Photos                              ✅ Full Access
├─ Manage FDP Records                               ✅ Full Access

ADMINISTRATIVE FUNCTIONS:
├─ View System Logs                                 ✅ Full Access
├─ Manage User Accounts                             ✅ Full Access
├─ Create New Users                                 ✅ Full Access
├─ Reset Passwords                                  ⚠️ Limited
├─ Manage Roles                                     ⚠️ Limited
├─ View Audit Trails                                ✅ Full Access
├─ Generate System Reports                          ✅ Full Access
├─ Configure System Settings                        ⚠️ Limited

VIEWING & NAVIGATION:
├─ All Pages                                        ✅ Full Access
├─ Home Page (index.php)                            ✅ Full Access
├─ About Page (about.html)                          ✅ Full Access
├─ Gallery Pages                                    ✅ Full Access

└────────────────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────────────────┐
│       UNIMPLEMENTED ROLES (Specified but not in code)                     │
├────────────────────────────────────────────────────────────────────────────┤

TUTOR ROLE (PROPOSED):
├─ View Assigned Students                           ⚠️ Proposed
├─ Enter Student Marks                              ⚠️ Proposed
├─ View Student Attendance                          ⚠️ Proposed
├─ Provide Feedback on Assignments                  ⚠️ Proposed
├─ Track Student Progress                           ⚠️ Proposed
├─ Cannot Delete Records                            ❌ Proposed

CLASS INCHARGE ROLE (PROPOSED):
├─ Manage Class Roster                              ⚠️ Proposed
├─ View Class Performance                           ⚠️ Proposed
├─ Generate Class Reports                           ⚠️ Proposed
├─ Coordinate with HOD                              ⚠️ Proposed
├─ Cannot Modify Marks                              ❌ Proposed

HOD ROLE (PROPOSED):
├─ View Department Dashboard                        ⚠️ Proposed
├─ View All Department Students                     ⚠️ Proposed
├─ Approve Curriculum Changes                       ⚠️ Proposed
├─ Manage Department MOUs                           ⚠️ Proposed
├─ Approve/Reject Requests                          ⚠️ Proposed
├─ Generate Department Reports                      ⚠️ Proposed
├─ Manage Staff in Department                       ⚠️ Proposed

SUPER ADMIN ROLE (PROPOSED):
├─ All Admin Functions                              ⚠️ Proposed
├─ Manage All Users                                 ⚠️ Proposed
├─ System Configuration                             ⚠️ Proposed
├─ View All Logs                                    ⚠️ Proposed
├─ System Maintenance                               ⚠️ Proposed
├─ Backup/Recovery                                  ⚠️ Proposed

└────────────────────────────────────────────────────────────────────────────┘
```

---

## Access Control by Page

### Authentication Pages
| Page | Public | Student | Staff | Admin | Notes |
|------|--------|---------|-------|-------|-------|
| login.php | ✅ | ✅ | ✅ | ✅ | Generic auth |
| slogin.php | ✅ | ❌ | ✅ | ✅ | Staff login |
| stlogin.php | ✅ | ✅ | ❌ | ❌ | Student ID only |
| logout.php | ✅ | ✅ | ✅ | ✅ | Destroys session |
| register.php | ✅ | ❌ | ❌ | ⚠️ | Admin only (implied) |

### Student Pages
| Page | Student | Staff | Admin | Notes |
|------|---------|-------|-------|-------|
| std_index.php | ✅ | ❌ | ✅ | Dashboard |
| std_upload.php | ✅ | ❌ | ✅ | Data entry |
| std_achiev.php | ✅ | ⚠️ | ✅ | View/manage |
| std_higher.php | ✅ | ❌ | ✅ | Own data |
| std_pro.php | ✅ | ❌ | ✅ | Projects |
| std_publication.php | ✅ | ❌ | ✅ | Publications |
| std_indus.php | ✅ | ❌ | ✅ | Industry visits |
| std_partici.php | ✅ | ❌ | ✅ | Participation |
| std_sports_details.php | ✅ | ❌ | ✅ | Sports |
| std_percent.php | ✅ | ❌ | ✅ | Performance |
| view_student.php | ✅ | ⚠️ | ✅ | Own data |

### Staff Pages
| Page | Student | Staff | Admin | Notes |
|------|---------|-------|-------|-------|
| staff_index.php | ❌ | ✅ | ✅ | Dashboard |
| staff_upload.php | ❌ | ✅ | ✅ | Data entry |
| staff_fdp.php | ❌ | ✅ | ✅ | FDP tracking |

### Academic Pages
| Page | Student | Staff | Admin | Notes |
|------|---------|-------|-------|-------|
| academic_perform.php | 📝 | 📝 | ✅ | Reports |
| semester_details.php | 📝 | ❌ | ✅ | Marks entry |
| successrate.php | 📝 | 📝 | ✅ | Analysis |
| curr_gap.php | 📝 | 📝 | ✅ | Gap tracking |
| tech.php | 📝 | ⚠️ | ✅ | Events |

### Admin Pages
| Page | Student | Staff | Admin | Notes |
|------|---------|-------|-------|-------|
| addmission.php | ❌ | ❌ | ✅ | Student creation |
| view_acd.php | 📝 | 📝 | ✅ | Academic view |
| view_ach.php | 📝 | 📝 | ✅ | Achievement view |
| view_sem.php | 📝 | 📝 | ✅ | Semester view |
| view_std.php | 📝 | 📝 | ✅ | Student view |
| view.php | 📝 | 📝 | ✅ | General view |
| delete.php | ❌ | ❌ | ✅ | Delete records |

### MOU Pages
| Page | Public | Student | Staff | Admin | Notes |
|------|--------|---------|-------|-------|-------|
| mou_index.php | ✅ | ✅ | ✅ | ✅ | Browse MOUs |
| upload.php | ❌ | ❌ | ✅ | ✅ | Upload files |
| edit.php | ❌ | ❌ | ⚠️ | ✅ | Own MOUs |
| delete.php | ❌ | ❌ | ⚠️ | ✅ | Delete files |
| departments.php | ✅ | ✅ | ✅ | ✅ | Filter view |
| gallery.php | ✅ | ✅ | ✅ | ✅ | Image gallery |

### General Pages
| Page | Public | Student | Staff | Admin | Notes |
|------|--------|---------|-------|-------|-------|
| index.php | ✅ | ✅ | ✅ | ✅ | Home |
| home.php | ✅ | ✅ | ✅ | ✅ | Home |
| home.html | ✅ | ✅ | ✅ | ✅ | Home |
| about.html | ✅ | ✅ | ✅ | ✅ | About |
| gallery.html | ✅ | ✅ | ✅ | ✅ | Gallery |

---

## Session-Based Access Control

### Session Variable Checks

**For Students:**
```php
// stlogin.php sets only:
$_SESSION['student_id']

// Check on student pages:
if (!isset($_SESSION['student_id'])) {
    header('Location: stlogin.php');
    exit();
}
```

**For Staff/Admin:**
```php
// slogin.php or login.php sets:
$_SESSION['user_id']
$_SESSION['username']
$_SESSION['role']  // 'student', 'staff', 'admin'

// Check on admin pages:
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
```

**ISSUE:** Student pages only check for `$_SESSION['user_id']` and `$_SESSION['role']`, but `stlogin.php` only sets `$_SESSION['student_id']`. This is a MISMATCH.

---

## Recommended Access Control Implementation

### Centralized Authorization

Create `auth.php`:
```php
<?php
function requireRole($required_roles = []) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header('Location: login.php');
        exit();
    }
    
    if (!in_array($_SESSION['role'], $required_roles)) {
        header('Location: unauthorized.php');
        exit();
    }
}

function isStudent() {
    return isset($_SESSION['student_id']);
}

function isStaff() {
    return isset($_SESSION['user_id']) && $_SESSION['role'] === 'staff';
}

function isAdmin() {
    return isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin';
}

function getStudentId() {
    return $_SESSION['student_id'] ?? null;
}

function getStaffId() {
    return $_SESSION['user_id'] ?? null;
}
?>
```

### Usage Examples
```php
// Student page
require 'auth.php';
requireRole(['student']);
$student_id = getStudentId();

// Admin page
require 'auth.php';
requireRole(['admin']);

// Staff page
require 'auth.php';
requireRole(['staff']);
```

---

## DATA OWNERSHIP & ISOLATION

### Student Owns:
- Own profile data
- Own academic records
- Own achievements
- Own activities
- Own project/publication records

### Staff Owns:
- Own profile data
- Own qualifications
- Own activity records
- Own MOU uploads

### Admin Can Access:
- All student data
- All staff data
- All system data
- All MOUs

---

## Future Role Implementation

### Implementation Priority
1. **HOD Role** (Head of Department)
   - Manage department MOUs
   - View department dashboard
   - Approve/reject requests

2. **Tutor Role**
   - View assigned students
   - Enter marks
   - Track progress

3. **Class Incharge Role**
   - Manage class roster
   - View class performance

4. **Super Admin Role**
   - System administration
   - User management
   - Configuration

---

END OF ROLE PERMISSION DOCUMENTATION
