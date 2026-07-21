# TODO

## Maintenance vehicle search route fix
- [x] Add route named `maintenance.search-vehicle` in `routes/admin.php`
- [x] Implement controller method in `app/Http/Controllers/MaintenanceController.php` to handle `registration` query and return JSON vehicle search results for the AJAX in `resources/views/vehicle-maintenance/edit.blade.php`

- [x] Verify route name with `php artisan route:list --name=maintenance.search-vehicle`

- [x] Smoke test the page `GET /maintenance/create` to ensure AJAX resolves and renders results

## Missing `photo` column in vehicles table
- [x] Create migration `2026_07_21_add_photo_to_vehicles_table.php` to add nullable `photo` string column to `vehicles` table
- [x] Run migration successfully


