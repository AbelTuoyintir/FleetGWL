## 2026-06-26 - [Dashboard Query Consolidation]
**Learning:** Sequential scalar `count()` and `sum()` queries on the same table (e.g., vehicles, documents) significantly inflate query counts and database round-trips. Conditional aggregation (`SUM(CASE WHEN ... THEN 1 ELSE 0 END)`) allows fetching all metrics in a single sweep.
**Action:** Always check for repeated queries on the same table within dashboard controllers and consolidate them into bulk aggregates.
