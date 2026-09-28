# PSG PTC Login And Password Security Report

Generated: 2026-06-02

## 1. Login Security Report

- Login page now has only username/register number and password fields.
- Role selection was removed completely.
- Role is read only from `users.role` after password verification.
- Account lockout is enforced after 5 failed login attempts.
- Lock duration is 15 minutes.
- Failed login attempts are audited.
- Account lock events notify admin and super admin.

## 2. Password Reset Report

- Self-service password reset has been removed from the login flow.
- `Forgot Password` now opens `forgot_password.php`.
- `forgot_password.php` creates a reset request only.
- Students, staff, tutors, HOD, IQAC, admins, and super admins cannot reset passwords from the public forgot-password page.
- Reset requests are stored in `password_reset_requests`.
- Authorized reset review is handled in `password_resets.php`.
- Approved resets generate a temporary password and force password change on next login.

Authorized reset scope:

- Student: request only.
- Staff: change own password with current password; request reset if needed.
- Tutor: can reset only assigned-batch students.
- HOD: can reset users in own department, excluding admin/super admin.
- Admin/IQAC: can reset students, staff, and tutors, excluding admin/super admin.
- Super Admin: full reset control.

## 3. Session Security Report

- Session ID regenerates after login.
- Logout destroys session.
- Protected pages send no-store/no-cache headers.
- Session stores `user_id`, `username`, `role`, `department`, `batch`, `semester`, `is_tutor`, `staff_id`, and `student_id`.
- First login and forced-password-change users are redirected to `password_change.php`.

## 4. Role Detection Report

- Login query selects `users.role`.
- No submitted role is accepted from the browser.
- No `role_hint` field remains in `login.php`.

## 5. Navigation Security Report

- Unauthorized menu links are hidden by `include/navigation.php`.
- Password reset console appears only for tutor, HOD, IQAC, admin, and super admin.
- Students and ordinary staff do not see password reset administration.

## 6. RBAC Verification Report

Validated:

- `login.php` has no role dropdown.
- `forgot_password.php` returns HTTP 200 as request-only page.
- `password_resets.php` redirects unauthenticated users to login.
- Additive database migration applied:
  - `users.failed_login_attempts`
  - `users.locked_until`
  - `password_reset_requests`

## Files Changed

- `login.php`
- `password_change.php`
- `forgot_password.php`
- `password_resets.php`
- `include/auth.php`
- `include/navigation.php`
- `logout.php`
- `academic_erp.php`
- `database/password_security_migration.sql`

## Validation Commands Passed

- PHP lint passed for changed PHP files.
- Login page returns HTTP 200.
- Forgot password page returns HTTP 200.
- Protected password reset console redirects unauthenticated access.
- Password complexity helper rejects weak password and accepts `Strong@123`.

