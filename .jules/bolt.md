## 2025-07-01 - [Optimizing Database Aggregations]
**Learning:** Moving statistical calculations (SUM, AVG, COUNT) from PHP's Eloquent collections to the database layer using `selectRaw` significantly reduces memory overhead and CPU usage. It avoids hydrating multiple model instances just to perform basic math.
**Action:** Always prefer SQL aggregate functions for dashboard statistics or report summaries where the individual models aren't needed for anything else.

## 2025-07-01 - [Handling Zero in Conditional Logic]
**Learning:** In PHP, the expression `if ($model->start_mileage)` will evaluate to `false` if the mileage is `0`. This can lead to silent failures in observers or business logic when `0` is a valid and significant value.
**Action:** Use `!is_null($value)` or strict comparison when checking for the presence of numeric fields that could be zero.
