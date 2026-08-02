# Production WebSocket (wss://) Fix - TODO

## Problem
On `https://gwc.wodabre.com`, the browser forces `wss://` (TLS) for WebSocket because the page is HTTPS (mixed content). Pusher-js ignores `forceTLS:false` on HTTPS pages.

**Diagnostics confirmed:**
- ✅ TCP port 8080 is OPEN on `gwc.wodabre.com` (Reverb running)
- ✅ Plain `ws://gwc.wodabre.com:8080/app/...` handshake **succeeds** (State = Open)
- ❌ Browser still tries `wss://gwc.wodabre.com:8080/app/...` → Reverb only speaks plain WS → handshake fails → "WebSocket is closed before the connection is established"

## Fix: Make Reverb speak WSS (TLS) on port 8080

### ✅ Step 1: Enable TLS in Reverb config
- [x] `config/reverb.php` — Added `options.tls.local_cert` / `options.tls.local_pk` driven by env:
  - `REVERB_TLS_CERT` (fullchain certificate for gwc.wodabre.com)
  - `REVERB_TLS_KEY` (private key)
- [x] Falls back to plain WS when env vars are empty (safe for local dev)

### ✅ Step 2: Fix client Echo config
- [x] `resources/js/app.js` — Set `forceTLS` based on page protocol, enable `['ws','wss']` transports, remove hardcoded host, use env vars
- [x] `resources/views/components/webrtc-call-overlay.blade.php` — Same for the inline Echo fallback; uses `config()` (survives config:cache)

### 🔲 Step 3: On the server (cPanel) — REQUIRED MANUAL ACTION
1. Download SSL cert bundle + private key for `gwc.wodabre.com` from cPanel:
   - cPanel → **Security → SSL/TLS → Certificates** (or "Install and manage SSL")
   - Copy the certificate (PEM) and private key (PEM)
2. Create the cert/key files on the server inside the Laravel app, e.g.:
   - `storage/ssl/gwc.wodabre.com.crt`
   - `storage/ssl/gwc.wodabre.com.key`
3. Add to production `.env`:
   ```env
   REVERB_TLS_CERT=/home/USERNAME/your-app/storage/ssl/gwc.wodabre.com.crt
   REVERB_TLS_KEY=/home/USERNAME/your-app/storage/ssl/gwc.wodabre.com.key
   REVERB_SERVER_HOSTNAME=gwc.wodabre.com
   ```
4. Clear & cache config:
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```
5. Restart Reverb. It should now print:
   ```
   INFO Starting secure server on 0.0.0.0:8080 (gwc.wodabre.com).
   ```

### 🔲 Step 4: Rebuild frontend
```bash
npm run build
```
Upload the new `public/build` folder to the server.

### 🔲 Step 5: Verify
1. Hard-refresh the browser (Empty Cache and Hard Reload)
2. Console should show `wss://gwc.wodabre.com:8080/app/...` → **connected**
3. `window.Echo.connector.pusher.connection.state` → `"connected"`
4. Place a test call → receiver gets `IncomingCall`

