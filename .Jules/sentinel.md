## 2025-05-15 - [Email Enumeration Prevention]
**Vulnerability:** Distinct error messages for "User not found", "Incorrect password", and "Inactive account" during login allowed attackers to enumerate valid email addresses in the system.
**Learning:** Exposing whether an email exists via specific error messages is a common information leakage vulnerability. This is especially risky in applications that handle sensitive data or have limited registration.
**Prevention:** Always use generic authentication error messages (e.g., "Invalid credentials.") for all failure cases (missing user, wrong password, account disabled). Consolidate checks to ensure consistent response timing and prevent side-channel analysis where possible.

## 2025-06-20 - [Public Remote Code Execution (RCE) Scripts]
**Vulnerability:** Publicly accessible PHP scripts in the `public/` directory (`fix-gwc.php` and `install-all.php`) allowed unauthenticated users to execute arbitrary shell commands via `exec()`.
**Learning:** Development or maintenance scripts left in the public web root are a critical security risk. They often bypass authentication and provide direct access to system commands and environment manipulation.
**Prevention:** Never place maintenance or setup scripts in the `public/` directory. Use Artisan commands, private administrative interfaces with strict authentication, or CI/CD pipelines for deployment and maintenance tasks.
