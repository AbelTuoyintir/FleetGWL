# TODO

## Migration fix for foreign key error (vehicles.region_id -> regions.id)
- [x] Inspect migrations for `vehicles` and `regions`.
- [x] Add migration: `2026_06_15_230000_fix_vehicles_region_fk.php` to explicitly change `vehicles.region_id` to `UNSIGNED BIGINT NULL` and recreate the FK with `ON DELETE SET NULL`.
- [ ] Run migration(s) to verify:
  - `php artisan migrate`
  - If schema is inconsistent, use `php artisan migrate:refresh` / `migrate:fresh` as appropriate.
- [ ] If FK still fails, inspect live schema with:
  - `SHOW CREATE TABLE vehicles;`
  - `SHOW CREATE TABLE regions;`
  - and adjust migration accordingly.

