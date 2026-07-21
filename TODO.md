# TODO

## Maintenance vehicle search route fix
- [x] Add route named `maintenance.search-vehicle` in `routes/admin.php`
- [x] Implement controller method in `app/Http/Controllers/MaintenanceController.php` to handle `registration` query and return JSON vehicle search results for the AJAX in `resources/views/vehicle-maintenance/edit.blade.php`

- [x] Verify route name with `php artisan route:list --name=maintenance.search-vehicle`

- [x] Smoke test the page `GET /maintenance/create` to ensure AJAX resolves and renders results


