# TODO

- [ ] Verify queue is async: set QUEUE_CONNECTION to `database` (or `redis`) in `.env` and not `sync`.
- [ ] Run `php artisan queue:table` and `php artisan migrate` (creates `jobs` table if using database queue).
- [ ] Start a worker: `php artisan queue:work`.
- [ ] Confirm the markdown template exists at `resources/views/emails/google-style-login-alert.blade.php`.
- [ ] If needed, switch `queue()` to `send()` temporarily to isolate whether the mail build/render hangs.

