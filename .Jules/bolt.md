## 2026-06-19 - [Consolidating Statistics Queries]
**Learning:** Consolidating multiple aggregate queries into a single query using conditional aggregation (`SUM(CASE WHEN...)`) reduces database round-trips and improves consistency. In this case, it reduced total queries from 18 to 9 for the `getVehicleStatistics` endpoint.
**Action:** Identify consecutive `count()` or `exists()` calls on the same model and refactor them into a single `selectRaw` query.

## 2026-06-19 - [Database Schema Constraints in Seeders]
**Learning:** When creating performance test seeders for this codebase, ensure `code` columns are provided for `regions`, `districts`, and `stations` tables as they are non-null and unique.
**Action:** Always check the migration schema before writing seeders to avoid `IntegrityConstraintViolationException`.
