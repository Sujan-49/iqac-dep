# PSG PTC ERP Account Recovery Report

Generated: 2026-06-02

## Scope

Only authentication account recovery was performed.

No UI, navigation, reports, NBA exports, dashboards, ERP features, user creation, or role changes were performed.

## 1. Account Recovery Report

Recovered accounts:

| Username | Role | Temporary Password | Failed Attempts | Locked Until | Recovery Result |
| --- | --- | --- | ---: | --- | --- |
| staff1 | staff | staff123 | 0 | NULL | PASS |
| student1 | student | student123 | 0 | NULL | PASS |
| superadmin | super_admin | superadmin123 | 0 | NULL | PASS |

Actions performed:

- Reset `password_hash` for exactly `staff1`, `student1`, and `superadmin`.
- Cleared `failed_login_attempts`.
- Cleared `locked_until`.

Actions not performed:

- No users created.
- No roles changed.
- No UI files changed.
- No dashboard files changed.
- No navigation files changed.
- No report/NBA/ERP feature files changed.

## 2. Login Validation Report

Password verification after recovery:

| Username | Password | Password Verify | Role | Lock Status |
| --- | --- | --- | --- | --- |
| staff1 | staff123 | PASS | staff | OPEN |
| student1 | student123 | PASS | student | OPEN |
| superadmin | superadmin123 | PASS | super_admin | OPEN |

HTTP login validation:

| Login | Result | Redirect |
| --- | --- | --- |
| staff1/staff123 | PASS | `dashboard_staff.php` |
| student1/student123 | PASS | `dashboard_student.php` |
| superadmin/superadmin123 | PASS | `dashboard_super.php` |

## 3. Dashboard Validation Report

Session validation after login:

### staff1

- `user_id = 2`
- `username = staff1`
- `role = staff`
- `student_id = 0`
- `staff_id = 1`
- `is_tutor = 1`
- Redirect: `dashboard_staff.php`

Result: PASS.

### student1

- `user_id = 3`
- `username = student1`
- `role = student`
- `student_id = 1`
- `staff_id = 0`
- `is_tutor = 0`
- Redirect: `dashboard_student.php`

Result: PASS.

### superadmin

- `user_id = 17`
- `username = superadmin`
- `role = super_admin`
- `student_id = 0`
- `staff_id = 0`
- `is_tutor = 0`
- Redirect: `dashboard_super.php`

Result: PASS.

## Final Status

Core account authentication now passes for the recovered accounts.

Required success criteria:

- staff1 -> Staff Dashboard: PASS
- student1 -> Student Dashboard: PASS
- superadmin -> Super Dashboard: PASS
