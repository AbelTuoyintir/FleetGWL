## 2026-06-28 - Throttling for Sensitive and Resource-Intensive Endpoints
**Vulnerability:** Lack of rate limiting on password reset and AI support endpoints.
**Learning:** Guest-accessible endpoints (like AI support) and sensitive auth routes (forgot password) are high-risk targets for automated abuse (email spamming, API credit drain). Even if authentication is required, internal DoS via expensive API calls remains a risk.
**Prevention:** Always apply the `throttle` middleware to routes that trigger external services or sensitive authentication flows to provide defense-in-depth against automated attacks.
