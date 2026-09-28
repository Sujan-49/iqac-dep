# PSG PTC ERP Architecture Correction Report

Generated: 2026-06-02

## Session Flow Report

Session state now includes role routing essentials:

- `user_id`
- `role`
- `staff_id`
- `student_id`
- `department`
- `semester`
- `batch`
- `is_tutor`
- `login_time`
- `approval_status`
- `admission_approved`

Student approval status is read from `student_details.stage1_status`. Approved values are `approved` and `active`.

## Navigation Report

Sidebar navigation now changes by admission status:

- Pending student: Profile Completion, Application Status, Change Password, Logout.
- Approved student: full student ERP module list.
- Staff: staff dashboard and assigned-work menu only.
- Tutor: tutor dashboard and assigned-batch monitoring menu.
- Admin/HOD/IQAC/Super Admin: admin dashboard and management/report menu.

## Redirect Report

Role home routing now uses dashboard-specific entry points:

- Student: `dashboard_student.php`
- Staff: `dashboard_staff.php`
- Tutor: `dashboard_tutor.php`
- Admin/HOD/IQAC: `dashboard_admin.php`
- Super Admin: `dashboard_super.php`

Unauthenticated dashboard URLs redirect to `login.php`.

## Admission Approval Workflow Report

Before approval, students are blocked from ERP modules and redirected to `std_index.php?approval_required=1`.

Allowed before approval:

- `std_index.php`
- `admission_status.php`
- `password_change.php`
- `logout.php`

`std_index.php` now includes an admission profile completion form using existing `student_details` columns and keeps the student in `pending` status until verification/approval.

## Real-Time Update Report

Added AJAX notification polling:

- Endpoint: `api/notifications_poll.php`
- Sidebar badge updates every 15 seconds.
- Uses the existing `notifications` table.

## Notification Report

Student submissions now create notification rows for tutor/admin visibility in these modules:

- Achievements
- Sports
- Publications
- Industry Visit
- Participation
- Higher Studies
- Applications

## Permission Audit

Dashboard aliases have explicit role checks:

- `dashboard_staff.php`: staff only
- `dashboard_tutor.php`: tutor only
- `dashboard_admin.php`: HOD/IQAC/admin only
- `dashboard_super.php`: super admin only

## URL Security Audit

Unauthenticated checks passed:

- `dashboard_student.php` -> `login.php`
- `dashboard_staff.php` -> `login.php`
- `dashboard_tutor.php` -> `login.php`
- `dashboard_admin.php` -> `login.php`
- `dashboard_super.php` -> `login.php`

## Remaining Work

Not implemented in this pass:

- Full document-upload schema for every requested admission document.
- Tutor/staff/admin approval action screens for admission approval.
- True PDF generation engine for NBA exports.
- Full Server-Sent Events/WebSocket real-time layer.
- Separate CA/Practical/Semester mark module split.

