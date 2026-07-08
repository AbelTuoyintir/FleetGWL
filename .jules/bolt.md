## 2025-07-01 - [Optimizing Database Aggregations]
**Learning:** Moving statistical calculations (SUM, AVG, COUNT) from PHP's Eloquent collections to the database layer using `selectRaw` significantly reduces memory overhead and CPU usage. It avoids hydrating multiple model instances just to perform basic math.
**Action:** Always prefer SQL aggregate functions for dashboard statistics or report summaries where the individual models aren't needed for anything else.

## 2025-07-01 - [Handling Zero in Conditional Logic]
**Learning:** In PHP, the expression `if ($model->start_mileage)` will evaluate to `false` if the mileage is `0`. This can lead to silent failures in observers or business logic when `0` is a valid and significant value.
**Action:** Use `!is_null($value)` or strict comparison when checking for the presence of numeric fields that could be zero.

## 2025-07-02 - [Optimizing Fuel Analytics with Database Aggregation]
**Learning:** The `FuelManagementController@analyticsData` method was a bottleneck due to in-memory processing of the entire fuel log collection. Transitioning to database-level aggregations (`selectRaw`, `groupBy`) reduced execution time by ~97% (from ~219ms to ~7ms for 1000 records).
**Action:** Always prefer database-level aggregations for report-heavy endpoints. Ensure to qualify columns in JOIN queries to avoid "ambiguous column" errors (e.g., `fuel_logs.status`).
