# PSG PTC ERP Phase 0.3 Account Recovery and Login Validation

Generated: 2026-06-02

## Scope Control

No UI, navigation, dashboards, reports, NBA exports, or ERP features were modified.

Authentication-only changes:

- Removed forced password-change redirect from `login.php`.
- Removed forced password-change guard from `include/auth.php`.
- Kept `password_change.php` available as an optional password change page.
- Kept password validation/security intact.

## 1. User Account Audit

| User ID | Username | Role | First Login | Force Change | Failed Attempts | Locked Until | Active | Hash Present |
| ---: | --- | --- | ---: | ---: | ---: | --- | ---: | --- |
| 1 | admin | admin | 1 | 0 | 0 | NULL | 1 | YES |
| 2 | staff1 | staff | 0 | 0 | 5 | 2026-06-02 14:58:01 | 1 | YES |
| 3 | student1 | student | 0 | 0 | 2 | NULL | 1 | YES |
| 4 | tutor1 | tutor | 1 | 0 | 0 | NULL | 1 | YES |
| 5 | 30di01 | student | 1 | 0 | 0 | NULL | 1 | YES |
| 6 | 30di02 | student | 1 | 0 | 0 | NULL | 1 | YES |
| 7 | 30di03 | student | 1 | 0 | 0 | NULL | 1 | YES |
| 8 | 30di04 | student | 1 | 0 | 0 | NULL | 1 | YES |
| 9 | 30di05 | student | 1 | 0 | 0 | NULL | 1 | YES |
| 10 | 30di06 | student | 1 | 0 | 0 | NULL | 1 | YES |
| 11 | 30di07 | student | 1 | 0 | 0 | NULL | 1 | YES |
| 12 | 30di08 | student | 1 | 0 | 0 | NULL | 1 | YES |
| 13 | 30di09 | student | 1 | 0 | 0 | NULL | 1 | YES |
| 14 | 30di10 | student | 0 | 0 | 0 | NULL | 1 | YES |
| 15 | hod1 | hod | 1 | 0 | 0 | NULL | 1 | YES |
| 16 | iqac1 | iqac | 1 | 0 | 0 | NULL | 1 | YES |
| 17 | superadmin | super_admin | 1 | 0 | 1 | NULL | 1 | YES |

## 2. Locked Account Report

Current lock status after validation:

| Username | Locked Until | Reason | Failed Attempts | Current Status |
| --- | --- | --- | ---: | --- |
| staff1 | 2026-06-02 14:58:01 | Previous failed password attempts | 5 | OPEN, lock time has passed |

No account is currently locked by active `locked_until > NOW()` status.

## 3. Password Hash Audit

All user records have password hashes.
All checked hashes are valid bcrypt hashes.
No corrupted hashes were detected.
No missing hashes were detected.
No legacy/plaintext hashes were detected.

Known-password validation:

| Username | Known/Test Password | Password Status |
| --- | --- | --- |
| admin | admin123 | VALID |
| staff1 | staff123, staff1 | UNKNOWN / DOES NOT MATCH |
| student1 | student123, student1, CS2024001 | UNKNOWN / DOES NOT MATCH |
| tutor1 | tutor1 | VALID |
| superadmin | superadmin, super123, Super@123 | UNKNOWN / DOES NOT MATCH |
| 30di01-30di09 | username as password | VALID |
| 30di10 | changed during previous password-flow validation | Known test password is no longer the username |

## 4. Login Validation Report

Forced password-change policy revision validation:

- `first_login` no longer blocks dashboard access.
- `force_password_change` no longer blocks dashboard access.
- `password_change.php` remains available for optional password changes.

Core login validation:

| Account | Authentication Result | Role | Lock Status | Password Status | Dashboard Route |
| --- | --- | --- | --- | --- | --- |
| admin/admin123 | PASS | admin | OPEN | VALID | `dashboard_admin.php` |
| staff1/staff123 | FAIL | staff | OPEN | DOES NOT MATCH | Not reached |
| student1/student123 | FAIL | student | OPEN | DOES NOT MATCH | Not reached |
| tutor1/tutor1 | PASS | tutor | OPEN | VALID | `dashboard_tutor.php` |
| superadmin/superadmin | FAIL | super_admin | OPEN | DOES NOT MATCH | Not reached |

Observed successful session isolation:

- Admin session: `role=admin`, `student_id=0`, `staff_id=0`, `is_tutor=0`.
- Tutor session: `role=tutor`, `student_id=0`, `staff_id=2`, `is_tutor=1`.
- Failed login sessions reset to `user_id=0`, `role=''`, `student_id=0`, `staff_id=0`, `is_tutor=0`.

## 5. Recovery Plan

No recovery action was executed automatically.

| Username | Problem | Recommended Fix | Risk Level |
| --- | --- | --- | --- |
| staff1 | Password does not match expected `staff123`; failed attempts remain at 5 | Authorized admin reset to known temporary password, clear failed attempts and `locked_until`, then test `dashboard_staff.php` | Low |
| student1 | Password does not match expected `student123` | Authorized admin/tutor reset to known temporary password, then test `dashboard_student.php` | Low |
| superadmin | Tested expected passwords did not match | Super admin recovery via controlled database backup-first reset or verified owner-provided password | Medium |

## Login Flow Report

Updated behavior:

1. User enters username and password.
2. Login validates against `users.password_hash`.
3. Session is isolated and regenerated.
4. User redirects directly to role dashboard.

No forced password-change screen is shown after successful login.

## Dashboard Redirect Report

Configured dashboard routes:

| Role | Dashboard |
| --- | --- |
| student | `dashboard_student.php` |
| staff | `dashboard_staff.php` |
| tutor | `dashboard_tutor.php` |
| admin / hod / iqac | `dashboard_admin.php` |
| super_admin | `dashboard_super.php` |

Validated:

- admin -> Admin Dashboard: PASS
- tutor -> Tutor Dashboard: PASS
- staff -> Staff Dashboard: BLOCKED by password mismatch
- student -> Student Dashboard: BLOCKED by password mismatch
- superadmin -> Super Dashboard: BLOCKED by password mismatch

## Password Settings Report

Password change is now optional.

Kept:

- `password_change.php`
- current password check
- strong password validation
- password hashing
- `password_changed_at` update when password is changed

Removed as blockers:

- Login redirect to `password_change.php`.
- `iqac_require_login()` redirect to `password_change.php`.

## Session Validation Report

Session isolation remains active:

- successful login clears old session data
- failed login clears old session data
- role IDs reset to safe defaults
- student login cannot inherit staff ID
- staff/tutor/admin login cannot inherit student ID

Syntax validation:

- `include/auth.php`: PASS
- `login.php`: PASS
- `password_change.php`: PASS
