## 2025-05-15 - [Email Enumeration Prevention]
**Vulnerability:** Distinct error messages for "User not found", "Incorrect password", and "Inactive account" during login allowed attackers to enumerate valid email addresses in the system.
**Learning:** Exposing whether an email exists via specific error messages is a common information leakage vulnerability. This is especially risky in applications that handle sensitive data or have limited registration.
**Prevention:** Always use generic authentication error messages (e.g., "Invalid credentials.") for all failure cases (missing user, wrong password, account disabled). Consolidate checks to ensure consistent response timing and prevent side-channel analysis where possible.

## 2026-06-22 - [Public Maintenance Scripts RCE]
**Vulnerability:** Publicly accessible maintenance scripts (`fix-gwc.php`, `install-all.php`) using `exec()` allowed unauthenticated Remote Code Execution (RCE).
**Learning:** Utilities that bypass application-level auth for "emergency fixes" or "automated setup" create critical security holes when placed in the web server's public root.
**Prevention:** Never place scripts that execute system commands or modify environment files in the `public/` directory. Use Artisan commands or CI/CD pipelines for administrative tasks.

## 2026-06-25 - [Sensitive Data Logging]
**Vulnerability:** Plaintext logging of sensitive user data, including passwords, phone numbers, and license photos, in application logs via `Log::info`.
**Learning:** Development-time "debug" logs often remain in production and can inadvertently leak critical secrets and PII if they log raw request data without filtering.
**Prevention:** Always use `$request->except()` or equivalent filtering to redact sensitive fields before passing user-controlled data to logging functions.
