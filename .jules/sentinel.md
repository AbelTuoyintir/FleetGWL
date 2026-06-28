# Sentinel's Journal - Critical Security Learnings

## 2025-05-15 - Sensitive Data Exposure in Logs
**Vulnerability:** Plaintext passwords were being logged in `DriverController@store` via `\Log::info('...', $request->all())`.
**Learning:** Developers sometimes use `$request->all()` in debug logs during development and forget to sanitize sensitive fields before production.
**Prevention:** Always use `$request->except([...])` or `$request->only([...])` when logging request data, especially on routes that handle credentials or PII.
