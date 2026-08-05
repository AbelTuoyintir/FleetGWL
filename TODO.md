# TODO

## Fix Laravel Echo / Reverb WebSocket initialization error

- [x] Investigate root cause (Echo 1.x CDN doesn't support `reverb` broadcaster)
- [x] Update `initializeEcho()` fallback in `webrtc-call-overlay.blade.php`:
  - [x] Upgrade Laravel Echo CDN from 1.16.1 to 2.4.0
  - [x] Add ripple/reverb broadcaster detection
  - [x] Harden `wsHost` fallback to `window.location.hostname`
  - [x] Fix `forceTLS` to `window.location.protocol === 'https:'`
  - [x] Make success log defensive with optional chaining
- [x] Blade change is server-rendered — no asset rebuild required (takes effect on next page load)
- [x] Confirm no `wsHost` error on next page refresh
