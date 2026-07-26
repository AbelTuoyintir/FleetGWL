# Sentinel's Journal - Critical Security Learnings

This journal records critical security learnings discovered during the protection of the codebase.

## Security Principles
- Security is everyone's responsibility
- Defense in depth - multiple layers of protection
- Fail securely - errors should not expose sensitive data
- Trust nothing, verify everything

## 2025-05-15 - Broken Function Level Authorization in Admin Routes
**Vulnerability:** Several administrative route groups (Fuel Management, Driver Management, Locations, Reports, Maintenance) were only protected by the `auth` middleware, allowing any authenticated user, regardless of role, to access sensitive administrative data and actions.
**Learning:** Inconsistent application of role-based middleware across different administrative modules created security gaps. While some routes were secured, others were left with only basic authentication.
**Prevention:** Use a consistent middleware stack for all administrative routes. Grouping these routes and applying `role:admin,super_admin` ensures that the "Least Privilege" principle is enforced systematically.
