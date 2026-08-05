# TODO: Fix `TypeError: Echo is not a constructor`

## Root Cause
The `echo.iife.js` v2.4.0 CDN build exposes `Echo` as a **module namespace object** where the actual constructor is `Echo.default` (NOT `Echo` itself). The fallback code in `webrtc-call-overlay.blade.php` calls `new Echo({...})`, which throws `TypeError: Echo is not a constructor`.

There is also a conflicting inline fallback in `layouts/app.blade.php` that can assign a non-constructor value to `window.Echo`.

## Steps
- [x] 1. Fix `resources/views/components/webrtc-call-overlay.blade.php`:
      - Resolve constructor via `Echo.default || Echo`.
      - Set `window.Echo` to the **instance** (not constructor).
      - Guard against conflicting with an existing working instance.
- [x] 2. Remove/fix the flawed inline IIFE fallback in `resources/views/layouts/app.blade.php`:
      - It never loads Echo but may assign a non-constructor to `window.Echo`.
      - Remove it since `app.js` (Vite) properly owns `window.Echo`.
- [x] 3. Fix inverted `forceTLS` in `resources/js/app.js` (`=== 'http:'` → `=== 'https:'`).
- [x] 4. Rebuild frontend assets (`npm run build`).
- [x] 5. Verify the page loads without the error.

## Summary of Changes
1. **`resources/views/components/webrtc-call-overlay.blade.php`**: `initializeEcho()` now:
   - Only returns an existing `window.Echo` if it's a real Echo **instance** (`typeof window.Echo.private === 'function'`).
   - Properly resolves the Echo constructor from the CDN IIFE namespace (`Echo.default || Echo`).
   - Creates the instance with `new EchoCtor(...)` and assigns it to `window.Echo`.

2. **`resources/views/layouts/app.blade.php`**: Removed the broken inline IIFE fallback that could assign a non-constructor namespace object to `window.Echo`.

3. **`resources/js/app.js`**: Fixed the inverted `forceTLS` logic (`=== 'http:'` → `=== 'https:'`).

4. **Frontend built**: `npm run build` succeeded → new bundle `app-DfZ4YnOn.js`.
