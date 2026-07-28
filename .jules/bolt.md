## 2026-07-03 - [Consolidation of Database Aggregations]
**Learning:** In-memory collection processing (e.g., `$logs->sum(...)` after `$query->get()`) is a significant performance bottleneck for large datasets. Pushing these calculations to the SQL engine using `selectRaw` and conditional aggregation (e.g., `SUM(CASE WHEN ... THEN 1 ELSE 0 END)`) significantly reduces memory usage and execution time.
**Action:** Always prefer database-level aggregation over fetching large datasets for in-memory processing.
