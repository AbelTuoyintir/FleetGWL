## 2025-05-15 - [Email Enumeration Prevention]
**Vulnerability:** Distinct error messages for "User not found", "Incorrect password", and "Inactive account" during login allowed attackers to enumerate valid email addresses in the system.
**Learning:** Exposing whether an email exists via specific error messages is a common information leakage vulnerability. This is especially risky in applications that handle sensitive data or have limited registration.
**Prevention:** Always use generic authentication error messages (e.g., "Invalid credentials.") for all failure cases (missing user, wrong password, account disabled). Consolidate checks to ensure consistent response timing and prevent side-channel analysis where possible.

## 2025-06-20 - [Dangerous Maintenance Scripts in Public Directory]
**Vulnerability:** Publicly accessible PHP scripts (`fix-gwc.php`, `install-all.php`) used `exec()` to run shell commands, posing a critical Remote Code Execution (RCE) risk.
**Learning:** Development or maintenance scripts left in the `public/` directory can be discovered by attackers and used to compromise the server or manipulate the application environment.
**Prevention:** Never include scripts that execute shell commands or perform administrative tasks in the public directory. Use secure CLI tools (like Artisan) or authenticated admin panels for such tasks.
