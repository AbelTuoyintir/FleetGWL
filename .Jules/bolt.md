## 2026-06-23 - [Consolidated Stats and Eager Loading]
**Learning:** The vehicle statistics endpoint was executing 10 separate COUNT queries. Consolidating these into one query using conditional aggregation (SUM CASE) reduced total queries by ~40%. Also identified an N+1 bottleneck in the form data API where serializing driver names triggered a User query for every driver because the 'name' accessor wasn't eager-loaded.
**Action:** Always check for repeated COUNT queries on the same table and use conditional aggregation. Audit API responses that use computed attributes (accessors) to ensure underlying relationships are eager-loaded.
## 2026-06-25 - [SQL Aggregation over Collection Methods]
**Learning:** Replaced in-memory Laravel collection aggregations (sum, avg) with SQL-level `selectRaw` in `FuelManagementController@quickStats`. Database-level aggregation reduced response time by ~84% (from ~167ms to ~27ms for 1,000 records) and significantly lowered memory overhead by avoiding hydration of Eloquent models.
**Action:** Favor database-level aggregation (`SUM`, `AVG`, `COUNT`) over Laravel Collection methods whenever processing large datasets or performance-critical endpoints.
