## 2026-07-02 - [Critical Authorization Bypass in Admin Routes]
**Vulnerability:** Several administrative route groups in `routes/admin.php` were only protected by `auth` middleware, allowing any authenticated user (including drivers) to access sensitive administrative features like Fuel Management, Driver Management, and system reports.
**Learning:** Route prefixes and grouping can lead to security gaps if middleware is not consistently applied to all groups. Over-reliance on the assumption that `/admin` or similar prefixes are automatically protected can be dangerous.
**Prevention:** Always use a standard security template for routes. Apply role-based middleware to all administrative route groups explicitly, even if they are within a protected prefix, to ensure defense-in-depth.
