# PSG PTC ERP Phase 0.1 Session Isolation Fix Report

Generated: 2026-06-02

## 1. Session Isolation Report

Files changed:

- `include/auth.php`
- `login.php`

No UI files were changed.
No dashboard files were changed.
No navigation files were changed.
No database schema was changed.

Root cause fixed:

- `iqac_login_user()` previously regenerated the session ID but did not clear existing `$_SESSION` values.
- Old role data such as `student_id`, `staff_id`, `is_tutor`, `department`, `batch`, `semester`, and `approval_status` could survive into the next login.

Implemented fix:

- Added `iqac_reset_auth_session()` in `include/auth.php`.
- Successful login now clears `$_SESSION` before writing new user values.
- Successful login regenerates session ID.
- Successful login creates a fresh CSRF token.
- Role-related values are reset to safe defaults before role-specific mapping:
  - `student_id = 0`
  - `staff_id = 0`
  - `is_tutor = 0`
  - `department = ''`
  - `batch = ''`
  - `semester = ''`
  - `section = ''`
  - `approval_status = ''`

Failed login fix:

- Invalid password, locked account, and inactive account branches now call `iqac_reset_auth_session()`.
- A failed login can no longer preserve an earlier authenticated user.

Validation:

- Valid tutor login followed by invalid staff login in the same browser session cleared all authenticated values.
- After failed login, session contained:
  - `user_id = 0`
  - `username = ''`
  - `role = ''`
  - `logged_in = false`
  - `student_id = 0`
  - `staff_id = 0`
  - `is_tutor = 0`

Result: PASS.

## 2. Authentication Validation Report

Tested accounts:

| Account | Password Tested | Result | Notes |
| --- | --- | --- | --- |
| `admin` | `admin123` | PASS | Redirects to password change because `first_login = 1` |
| `staff1` | `staff123` | FAIL | Stored password hash does not match; account lockout was triggered by repeated validation attempts |
| `tutor1` | `tutor1` | PASS | Redirects to password change because `first_login = 1` |
| `student1` | `student123` | FAIL | Stored password hash does not match |

Current validation blocker:

- `staff1/staff123` is not a valid credential pair in the live database.
- `student1/student123` is not a valid credential pair in the live database.
- `staff1` is now locked by the existing failed-login policy until `2026-06-02 14:58:01`.

No password hashes were changed.
No user records were edited manually.

## 3. Dashboard Routing Report

Configured role routing remains unchanged:

| Role | Route |
| --- | --- |
| Student | `dashboard_student.php` |
| Staff | `dashboard_staff.php` |
| Tutor | `dashboard_tutor.php` |
| HOD/Admin/IQAC | `dashboard_admin.php` |
| Super Admin | `dashboard_super.php` |

Observed routing:

- `admin/admin123` authenticated as `admin`; redirected to `password_change.php` because `first_login = 1`.
- `tutor1/tutor1` authenticated as `tutor`; redirected to `password_change.php` because `first_login = 1`.
- `staff1/staff123` could not route because authentication failed.
- `student1/student123` could not route because authentication failed.

Dashboard route code is correct, but full dashboard validation for staff/student is blocked by invalid test passwords.

## 4. Role Detection Report

Role source:

- Role is still read from `users.role`.
- No role dropdown exists.
- No hardcoded student fallback was added.

Observed successful sessions:

Admin session:

- `user_id = 1`
- `username = admin`
- `role = admin`
- `student_id = 0`
- `staff_id = 0`
- `is_tutor = 0`

Tutor session:

- `user_id = 4`
- `username = tutor1`
- `role = tutor`
- `student_id = 0`
- `staff_id = 2`
- `is_tutor = 1`

Result: PASS for successful credential pairs.

## 5. Session Security Report

Logout validation:

- Login created an active PHP session file.
- `logout.php` destroyed the session.
- The session file was removed after logout.
- Existing no-cache headers remain active through `iqac_no_cache_headers()`.

Session protection status:

- Session ID regenerated on successful login.
- Session data cleared before every successful login.
- Session data cleared after failed login.
- Fresh CSRF token generated after session reset.
- Role-specific IDs reset before mapping.
- Student login sets only `student_id`.
- Staff/tutor/admin-like login never inherits `student_id`.
- Tutor login sets `staff_id` and `is_tutor`.

Validation result:

- Session isolation: PASS.
- Logout destruction: PASS.
- Cross-user session leakage after failed login: PASS.
- Full account login validation: BLOCKED for `staff1/staff123` and `student1/student123` because stored hashes do not match those supplied passwords.
