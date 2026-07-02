## 2025-05-15 - [Optimization] Aggregated Statistics for Mileage Logs
**Learning:** Hydrating thousands of Eloquent models only to perform basic arithmetic (SUM/AVG) in PHP memory is a massive waste of resources. Database-level aggregation is significantly more efficient and scales better.
**Action:** Always prefer `selectRaw` with `SUM`, `AVG`, `COUNT` for statistics endpoints.

## 2025-05-15 - [Redundancy] Unused index variables
**Learning:** Older controller methods often fetch full sets of related models (e.g., `$vehicles = Vehicle::all()`) that are later replaced by AJAX search or paginated loaders.
**Action:** Audit `index()` methods when refactoring to AJAX/Livewire to remove unused "fetch all" queries.
