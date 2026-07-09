## 2024-05-15 - Broken Object Level Authorization (BOLA) in Admin Routes
**Vulnerability:** Several administrative route groups (Fuel Management, Mileage Logs, Driver Management, Locations, and Maintenance) were only protected by the `auth` middleware, allowing any authenticated user (e.g., drivers) to access sensitive management interfaces and data.
**Learning:** Route-level middleware must explicitly check for appropriate roles/permissions, even if they are prefixed with 'admin' or placed in an 'admin.php' file, as 'auth' only verifies identity, not authorization.
**Prevention:** Always apply specific role-based middleware (e.g., `role:admin`) to administrative route groups and verify access controls with automated tests that include non-privileged roles.

## 2025-02-12 - Insecure Randomness in 2FA Recovery Codes
**Vulnerability:** Recovery codes for Two-Factor Authentication were generated using `md5(uniqid())`. `uniqid()` is based on system time and is not cryptographically secure, making recovery codes potentially predictable or susceptible to brute-force if the generation time is known.
**Learning:** Cryptographic functions like `md5` combined with time-based seeds like `uniqid()` are insufficient for security-sensitive tokens.
**Prevention:** Always use cryptographically secure pseudo-random number generators (CSPRNG) like Laravel's `Str::random()` or PHP's `random_bytes()` for security-sensitive identifiers and tokens.
