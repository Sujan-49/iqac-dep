# Project Cleanup Report

## Cleanup Decision

No PHP files were deleted in this pass. Several old-looking pages are still reachable from IQAC, MOU, staff, student, and report workflows, so removing them without a full click-path audit would risk deleting working functionality.

## Broken / Unsafe Items Found

- `gallery.php` had no unified auth guard and used raw `session_start()`.
- `upload.php` allowed tutor/HOD/IQAC upload access even though the current rule says only staff/admin.
- `successrate.php` used old `students` / `academic_details` assumptions and an outdated print table.
- `academic_perform.php` used old `students` / `achievement_mark` assumptions and an outdated print table.
- `std_sports_details.php` used old `students` and `sports_certificate` assumptions. Fixed in the previous pass.
- `std_partici.php` had no unified auth guard. Fixed in the previous pass.

## Removed Files Report

No files removed.

## Removed Navigation Report

The sidebar was rebuilt from a strict role map. Student, staff, tutor, and admin roles now receive only the menu items defined for their role. Duplicate old menu labels such as mixed admin/staff analytics links were removed from student/staff/tutor menus.

## Permission Matrix

- Student: profile, own marks, achievements, sports, publications, industry visit, higher studies, applications, notifications.
- Staff: assigned subjects/batches, marks verification, student reports, applications, gallery upload, department uploads, exports, notifications.
- Tutor: assigned batch, student monitoring, applications, attendance reports, performance reports, notifications.
- Admin/HOD/IQAC/Super Admin: management, assignment, promotion, reports, NBA reports, analytics, gallery/upload management, applications, notifications, audit/system settings.

## NBA Format Export Report

- `successrate.php` now uses NBA tabular format with PSG Polytechnic College header, department, academic year, semester, batch, prepared by, verified by, approved by, generated date.
- `academic_perform.php` now uses NBA tabular format with year-wise appeared/passed/failed/pass/fail/average/highest/lowest.
- Both reports support print/PDF through browser print, CSV, and Excel exports.

## Bulk Student Import Report

- `admin_controls.php` now supports CSV bulk student creation.
- Existing roll numbers are skipped.
- New users are created with username = roll number and password = roll number hash, with first-login password change enabled.
- No existing student records are overwritten.

## Tutor Assignment Report

- `admin_controls.php` continues to assign tutors through `student_details.tutor_staff_id`.
- Tutor dashboard and tutor-scoped reports read from the same live mapping.

## Staff Subject Assignment Report

- `admin_controls.php` continues to assign staff subjects through `staff_subject_allocation`.
- Staff-scoped report pages filter by assigned subject allocations.

## Security Report

- Unified auth remains in `include/auth.php`.
- CSRF is used on patched forms.
- Gallery/upload management is restricted to staff/admin/super admin.
- NBA reports are restricted to staff/tutor/HOD/IQAC/admin/super admin.

## Remaining Non-Destructive Cleanup Candidates

These pages still need a careful UI/RBAC modernization pass before any deletion decisions:

- `std_achiev.php`
- `std_higher.php`
- `std_upload.php`
- `staff_upload.php`
- `staff_fdp.php`
- `semester_details.php`
- `view_acd.php`
- `view_ach.php`
- `view_sem.php`
- `view_std.php`

No existing functionality was intentionally removed.

## Critical RBAC Fix Report

### Fixed

- `student` role home changed from `academic_erp.php` to `std_index.php`.
- Student sidebar dashboard now points to `std_index.php`.
- Student notification link no longer points into `academic_erp.php`.
- `academic_erp.php` now blocks students with `403`.
- `password_change.php` now refreshes the session after password update and redirects to the current role home.

### Direct URL Test Results

| User | URL | Expected | Result |
| --- | --- | --- | --- |
| student1 | `academic_erp.php` | Block | `403` PASS |
| student1 | `successrate.php` | Block | `403` PASS |
| student1 | `academic_perform.php` | Block | `403` PASS |
| student1 | `admin_controls.php` | Block | `403` PASS |
| student1 | `nba_report.php` | Block | `403` PASS |
| student1 | `std_index.php` | Allow | `200` PASS |
| staff1 | `admin_controls.php` | Block | `403` PASS |
| staff1 | `successrate.php` | Allow | `200` PASS |
| tutor1 | `marks_entry.php` | Allow monitoring | `200` PASS |
| tutor1 | `admin_controls.php` | Block | `403` PASS |
| admin | `academic_erp.php` | Allow | `200` PASS |
| admin | `admin_controls.php` | Allow | `200` PASS |

### Navigation Visibility Test

- Student menu does not contain NBA Analytics, Criteria Dashboards, Teacher Mapping, Batch Analytics, Department Reports, or System Settings: PASS.
- Staff menu does not contain Admin Controls or System Settings: PASS.
- Tutor menu does not contain Marks Verification: PASS.
- Admin menu contains Student Management and System Settings: PASS.

### Unused Files / Dead Routes / Duplicate Pages

No files were removed. The following are candidates only and require approval before deletion:

- `home.html` and `home.php`: possible duplicate landing/home routes.
- `gallery.html` and `gallery.php`: duplicate static/dynamic gallery routes.
- `about.html` and `styleabout.css`: static legacy about route.
- `staff_index.php` and `staff_upload.php`: overlapping staff profile/upload surfaces.
- `view_acd.php`, `view_ach.php`, `view_sem.php`, `view_std.php`: legacy view routes still need link tracing before removal.
- `new.sql`, `mou.sql`, and database dumps under `database/`: archive candidates, not runtime PHP routes.

### Broken Link Notes

- Student sidebar no longer links to `academic_erp.php`.
- Student direct access to NBA/report/admin routes is blocked at page guard level.
- Report routes remain available to staff, tutor, HOD, IQAC, admin, and super admin only.
