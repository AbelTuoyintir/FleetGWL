# Signaling System Bug Fixes - TODO

## Priority Issues (In Order)

### ✅ Step 1: Fix Broadcaster Default
- [x] `config/broadcasting.php` — Default changed from `'null'` to `'reverb'`
- [x] Added prominent comment explaining `BROADCAST_CONNECTION=reverb` is required

### ✅ Step 2: Fix Channel Authorization Logging
- [x] `routes/channels.php` — Added `Log::info()` to debug auth decisions for private channel `user.{id}`
- [x] Added detailed logging: user_id, requested_id, authorized status

### ✅ Step 3: Fix Client-Side JS (webrtc-call-overlay.blade.php)
- [x] Added Echo connection state check (`echoInstance.connector.pusher.connection.state`) on page load
- [x] Added `.subscribed()` callback — logs "✅ Subscribed!" to verify subscription
- [x] Added `.error()` callback — logs `❌ Channel subscription error` with diagnostic hints
- [x] Changed `{{ env() }}` to `{{ config(...) }}` for production safety (survives `config:cache`)
- [x] Added fallback warning when Echo is not initialized or user ID is null
- [x] Improved error handling for `/calls/start` — now shows actual server error message instead of generic message

### ✅ Step 4: Add Debug Route
- [x] `routes/web.php` — Added `GET /test-broadcast?receiver_id={id}` to manually trigger IncomingCall broadcasts
- [x] Creates a test call record, broadcasts IncomingCall, logs everything to Laravel log
- [x] Returns success/error message directly in browser response

### ✅ Step 5: Fix Broadcasting Connection Config (ROOT CAUSE FIX)
- [x] `config/broadcasting.php` — Changed defaults to match Reverb server config
- [x] `'host'` default: `null` → `localhost` (falls back to `REVERB_SERVER_HOSTNAME` from reverb.php)
- [x] `'port'` default: `443` → `8080` (falls back to `REVERB_SERVER_PORT` from reverb.php)
- [x] `'scheme'` default: `'https'` → `'http'`
- [x] These fix the **500 Internal Server Error** when PHP's `broadcast()` tries to reach Reverb

## Post-Fix Verification Checklist

After deploying these changes, follow these steps in order:

### Step 1: Check Reverb Connection on Receiver
1. Open DevTools Console on the driver's browser
2. Run: `window.Echo` → should return an Echo object
3. Run: `window.Echo.connector.pusher.connection.state` → should return `"connected"`

### Step 2: Verify Channel Subscription
1. Look for `"✅ Subscribed to private-user.{id} successfully!"` in console
2. If missing, check Network tab → WS (WebSocket) frames for `pusher_internal:subscription_succeeded`

### Step 3: Test Broadcast
1. Visit: `http://localhost:8000/test-broadcast?receiver_id={driverId}`
2. Check receiver console for `"Echo: IncomingCall received:"`

### Step 4: Check for Errors
1. Look for `❌ Channel subscription error` in console
2. Check Laravel log (`storage/logs/laravel.log`) for `[Channels]` entries
3. Check for 403/401 errors in Network tab

### Step 5: Verify Channel Name Match
- Server broadcasts to: `PrivateChannel('user.{id}')` (singular: `user.`)
- routes/channels.php authorizes: `user.{id}` (singular: `user.`)
- Client subscribes to: `echoInstance.private('user.{id}')` (singular: `user.`)
- ✅ All three must match exactly

### Step 6: Verify Event Name Match
- IncomingCall `broadcastAs()` returns: `'IncomingCall'`
- Client listens for: `'.IncomingCall'` (with leading dot for Pusher protocol)
- ✅ Must match

### Step 7: Check .env Configuration
```
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
QUEUE_CONNECTION=sync
```

### Step 8: Ensure Reverb Server is Running ⚠️ ACTUAL ROOT CAUSE
Reverb must run in its own terminal:
```bash
php artisan reverb:start
```

### ✅ Step 7 (FIXED): Reverb Server Not Running — PRIMARY ROOT CAUSE
- [x] **Problem**: `POST /calls/start` returned HTTP 500 with `Pusher error: cURL error 7: Failed to connect to 127.0.0.1 port 8080`
- [x] **Root Cause**: The Reverb WebSocket server was **not running** — `netstat -ano | findstr :8080` showed `SYN_SENT` (connection attempt failing) instead of `LISTENING`
- [x] **Fix**: Started Reverb with `php artisan reverb:start` (PID 16432, listening on `0.0.0.0:8080`, 58MB RAM)
- [x] **Verification**: `netstat -ano | findstr LISTENING | findstr :8080` now shows `TCP 0.0.0.0:8080 LISTENING`

### ✅ Step 8 (FIXED): Browser Ringtone Autoplay Blocked
- [x] **Problem**: "blocked by browser security" console warning when ringtone tries to autoplay without user gesture
- [x] **Fix**: Added `unlockRingtone()` function that registers `click`/`touchstart` event listeners to unlock the audio context on first user interaction
- [x] Added automatic retry mechanism: if the initial play fails, it re-triggers unlock and retries after 100ms
- [x] This ensures the ringtone works once the user has clicked anywhere on the page first

### Step 9: Check Receiver ID Matches
- Before broadcasting, the server logs: `Receiver ID: {id}`
- Confirm the client's subscription uses the same ID

### ✅ Step 6: Fix Reverb Crashes on Startup (ROOT CAUSE #2)
- [x] `config/reverb.php` — Added missing `'ping_interval'` and `'ping_timeout'` to server options
- [x] Reverb was crashing with `Undefined array key "ping_interval"` immediately on startup
- [x] This is why WebSocket connections kept failing with "Invalid frame header" — Reverb was crashing before completing the WebSocket handshake
- [x] After fix: Reverb starts successfully, listening on 0.0.0.0:8080

---

## GPS Real-Time Driver Tracking — New Feature

### ✅ Step 1: Create Controller
- [x] `app/Http/Controllers/DriverTrackingController.php` — Created with `updateLocation()` method
- [x] Validates latitude/longitude/speed/heading
- [x] Finds the driver's assigned vehicle
- [x] Updates `current_latitude`, `current_longitude`, `last_seen_at` on the vehicles table
- [x] Stores a history record in `vehicle_location_histories` table

### ✅ Step 2: Add Route
- [x] `routes/web.php` — Added `POST /driver/location` route
- [x] Protected by auth middleware

### ✅ Step 3: Driver GPS Tracking JS
- [x] `resources/views/layouts/driver.blade.php` — Added `navigator.geolocation.watchPosition()` script
- [x] Sends GPS data every 10 seconds via AJAX to `/driver/location`
- [x] Handles permissions denied / GPS unavailable / timeout errors
- [x] Stops sending after 5 consecutive failures
- [x] Logs everything to browser console for debugging

### ✅ Step 4: Fix Admin Tracking Controller
- [x] `app/Http/Controllers/Admin/VehicleTrackingController.php` — Now checks for REAL GPS data first
- [x] If `last_seen_at` is within last 5 minutes → uses real coordinates from DB
- [x] Only falls back to simulated drift for vehicles with no recent GPS data

### ✅ Step 5: Database Migration
- [x] `database/migrations/2026_07_24_000001_add_speed_heading_to_vehicles_table.php` — Adds `speed` and `heading` columns
- [x] Run `php artisan migrate` to apply

### ✅ Step 6: Vehicle Model
- [x] `app/Models/Vehicle.php` — Added `speed` and `heading` to `$fillable`

