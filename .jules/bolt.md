
## 2026-06-17 - Optimization of Analytics KPIs and Monthly Trends
**Learning:** Found multiple redundant aggregate queries in a single dashboard controller. Consolidating these into a single query using conditional aggregation (`CASE WHEN`) significantly reduces database round-trips. Also, using `keyBy()` on collections before looping avoids inner-loop filtering.
**Action:** Always check dashboard/analytics controllers for multiple `count()` or `sum()` calls on the same base query.
