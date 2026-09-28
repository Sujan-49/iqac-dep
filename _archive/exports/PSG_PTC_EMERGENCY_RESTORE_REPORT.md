# PSG PTC Emergency Restore Report

Generated: 2026-06-02

## Restore Policy Applied

No files were deleted, moved, renamed, or archived in this restore pass.

No database tables or columns were dropped, renamed, truncated, or removed.

The restore approach was:

- Keep all existing files.
- Keep existing modules visible only through RBAC-filtered menus.
- Restore removed forms/sections inside active pages.
- Preserve page guards for unauthorized access.
- Improve schema safety without disabling forms.

## Restored Navigation

Restored authorized menu entries:

- Gallery Management
- Upload Management
- MOU Module
- Applications

These are restored for admin/HOD/IQAC/super admin menu scope. Students still do not see admin/staff/NBA admin links.

## Restored Dashboard Links

Restored admin dashboard module shortcuts:

- Gallery
- Upload
- MOU
- Applications
- Marks Entry

## Restored Student Forms

### `std_achiev.php`

Restored active upload fields:

- Certificate Upload
- Photo Upload

If `achievement_details` exists, the original certificate/photo columns are used. If the live table is `student_achievements`, uploaded evidence paths are preserved in the description because that table has no certificate/photo columns.

### `std_higher.php`

Restored visible legacy forms:

- Student Registration
- Add Higher Study Details
- Add Placement Details

Save actions are schema-safe. If old legacy tables are absent, the form remains visible and reports the missing table instead of crashing.

### `view_student.php`

Restored profile sections:

- Academic Qualifications
- Non-Academic Activities

## RBAC Correction

Removed the over-restrictive admission approval redirect that blocked pending students from student modules.

Student menu now uses role-based filtering only:

- Dashboard
- Profile
- My Marks
- Semester Marks
- Achievements
- Sports
- Publications
- Industry Visit
- Higher Studies
- Applications
- Notifications
- Change Password
- Logout

## Validation

PHP lint passed:

- `include/auth.php`
- `include/navigation.php`
- `std_achiev.php`
- `std_higher.php`
- `view_student.php`
- `academic_erp.php`

HTTP checks:

- `std_achiev.php` redirects unauthenticated users to `login.php`
- `std_higher.php` redirects unauthenticated users to `login.php`
- `view_student.php` redirects unauthenticated users to `login.php`
- `login.php` returns HTTP 200

## Remaining Restore Watchlist

The following pages still contain older UI/workflows and should be improved without removing content:

- `std_upload.php`
- `staff_upload.php`
- `upload.php`
- `gallery.php`
- `departments.php`
- `view.php`
- `edit.php`
- `delete.php`
- `std_pro.php`
- `semester_details.php`

