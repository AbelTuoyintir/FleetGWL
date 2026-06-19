## 2026-06-19 - Accessible Floating UI Components
**Learning:** Floating widgets like AI chat bots are often excluded from standard layout accessibility passes. Icon-only buttons in these components lack descriptive labels, and focus states are frequently suppressed with `focus:outline-none` without providing a `focus-visible` alternative.
**Action:** Always ensure icon-only buttons in floating components have `aria-label` and use Tailwind's `focus-visible:ring` to provide keyboard feedback without affecting mouse users.
