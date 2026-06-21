## 2026-06-21 - [AI Chat Bot Accessibility & Interaction]
**Learning:** Adding ARIA labels to icon-only buttons is essential, but synchronizing `aria-expanded` with the component's visual state via JavaScript provides crucial context for screen reader users. Additionally, replacing static "Typing..." indicators with CSS-based animations (`.dot-bounce`) creates a more modern and delightful interaction without adding JS weight.
**Action:** Always ensure `aria-expanded` is updated in toggle handlers and prioritize CSS keyframes for micro-animations to maintain performance and "delight".
