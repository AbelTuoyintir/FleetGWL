# Fix: Admin-to-Driver Call Connection Not Working

## Root Cause

The `BROADCAST_CONNECTION=log` in `.env` caused all WebRTC signaling events (IncomingCall, OfferCreated, AnswerCreated, IceCandidate) to be written to log files instead of being broadcast through a WebSocket server. The driver never received the incoming call notification because there was no real-time WebSocket connection.

Additionally, the `config/broadcasting.php` and `config/reverb.php` configuration files were missing entirely, and the `.env` lacked the required Reverb environment variables.

## Steps Completed

### Configuration
- [x] Created `config/broadcasting.php` with Reverb driver configuration
- [x] Created `config/reverb.php` with server settings (key, secret, app_id, host, port)
- [x] Changed `BROADCAST_CONNECTION=log` → `BROADCAST_CONNECTION=reverb` in `.env`
- [x] Added `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET` to `.env`
- [x] Added `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME` to `.env`
- [x] Added `REVERB_SERVER_HOST`, `REVERB_SERVER_PORT`, `REVERB_SERVER_HOSTNAME` to `.env`
- [x] Added `VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, `VITE_REVERB_SCHEME` to `.env`
- [x] Fixed BOM character corruption in `.env` file
- [x] Fixed server key name mismatch in `config/reverb.php` (`main` → `reverb`)
- [x] Cleared stale config cache (was causing `Driver [main] not supported` error)

### Build
- [x] Installed npm dependencies (`laravel-echo`, `pusher-js`)
- [x] Built frontend assets with `npx vite build`

### Services to Start (Manual Steps Needed)
- [ ] Start Reverb WebSocket server: `php artisan reverb:start`
- [ ] Start queue worker: `php artisan queue:listen`
- [ ] (Optional) Start Vite dev: `npm run dev`

## Files Created/Modified
- `config/broadcasting.php` — **CREATED** - Broadcasting config with reverb driver
- `config/reverb.php` — **CREATED** - Reverb server configuration
- `.env` — **MODIFIED** - Updated broadcast connection + reverb variables + fixed BOM
- `setup_reverb.php` — **DELETED** - Cleaned up helper script

