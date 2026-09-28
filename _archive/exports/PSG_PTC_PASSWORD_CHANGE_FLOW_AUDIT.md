# PSG PTC ERP Password Change Flow Audit

Generated: 2026-06-02

## 1. Password Change Flow Audit

Scope:

- No UI redesign.
- No navigation changes.
- No dashboard changes.
- No NBA/report changes.
- No ERP feature changes.

Files changed:

- `password_change.php`

Database change:

- Added `users.password_changed_at DATETIME NULL` using `ADD COLUMN IF NOT EXISTS`.
- No table was dropped.
- No column was removed.
- No existing password was reset directly.

Bug check:

- `include/auth.php` redirects to `password_change.php` only when session values contain `first_login = 1` or `force_password_change = 1`.
- `login.php` redirects to `password_change.php` only when the authenticated database user row contains `first_login = 1` or `force_password_change = 1`.
- `password_change.php` already set `first_login = 0` and `force_password_change = 0`, but did not record `password_changed_at`.

Fix:

- Successful password change now updates:
  - `password_hash`
  - `first_login = 0`
  - `force_password_change = 0`
  - `password_changed_at = CURRENT_TIMESTAMP`

## 2. First Login Validation

Safe validation account used:

- `30di10`

Reason:

- `admin`, `tutor1`, and `superadmin` are real privileged accounts with `first_login = 1`; changing their passwords during validation would alter administrator credentials.
- `staff1/staff123` and `student1/student123` do not match the current stored hashes.

Validation result:

| Step | Result |
| --- | --- |
| First login using `30di10/30di10` | Redirected to `password_change.php` |
| Password changed successfully | Redirected to `dashboard_student.php` |
| Database `first_login` | Updated to `0` |
| Database `force_password_change` | Updated to `0` |
| Database `password_changed_at` | Populated: `2026-06-02 14:57:08` |

Result: PASS.

## 3. Dashboard Redirect Validation

Validated once-only flow:

| Account | First Login Redirect | After Password Change | Second Login |
| --- | --- | --- | --- |
| `30di10` | `password_change.php` | `dashboard_student.php` | `dashboard_student.php` |

Named account status:

| Account | Current State | Dashboard Validation |
| --- | --- | --- |
| `admin/admin123` | Valid password, `first_login = 1` | Will require one password change before Admin Dashboard |
| `staff1/staff123` | Invalid password and currently locked | Blocked until password/lock recovery |
| `tutor1/tutor1` | Valid password, `first_login = 1` | Will require one password change before Tutor Dashboard |
| `student1/student123` | Invalid password | Blocked until password recovery |
| `superadmin` | Known tested passwords did not match, `first_login = 1` | Blocked until password recovery |

## 4. User Experience Report

Correct behavior now:

- New user with `first_login = 1` is forced to `password_change.php`.
- After successful password change, user is redirected to the correct role dashboard.
- Future logins do not return to `password_change.php` unless `first_login` or `force_password_change` is set again.
- No skip/remind-later option was added.

Validation limitation:

- Full named-account validation is blocked until test account passwords are corrected:
  - `staff1/staff123` does not match the current hash.
  - `student1/student123` does not match the current hash.
  - `staff1` is locked by the failed-login policy.

## Syntax Validation

- `include/auth.php`: PASS
- `login.php`: PASS
- `password_change.php`: PASS
