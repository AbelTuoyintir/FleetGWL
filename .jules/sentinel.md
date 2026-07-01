# Sentinel Security Journal

## 2025-05-14 - Initial Scan
**Vulnerability:** Missing role-based authorization on 'locations' and 'fuel-management' routes.
**Learning:** Routes in `routes/admin.php` were not all wrapped in `role:admin` middleware, allowing any authenticated user (e.g., drivers) to access and modify administrative data.
**Prevention:** Always group administrative routes under a common middleware that enforces role checks, or explicitly apply the middleware to each group.
