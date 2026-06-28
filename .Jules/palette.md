## 2026-06-21 - [AI Chat Bot Accessibility & Interaction]
**Learning:** Adding ARIA labels to icon-only buttons is essential, but synchronizing `aria-expanded` with the component's visual state via JavaScript provides crucial context for screen reader users. Additionally, replacing static "Typing..." indicators with CSS-based animations (`.dot-bounce`) creates a more modern and delightful interaction without adding JS weight.
**Action:** Always ensure `aria-expanded` is updated in toggle handlers and prioritize CSS keyframes for micro-animations to maintain performance and "delight".

## 2026-06-22 - [Standardizing Icon-Only Button Accessibility]
**Learning:** In a dashboard-heavy application, action tables frequently use icon-only buttons for density. Standardizing the use of both `aria-label` (for screen readers) and `title` (for hover tooltips) on these elements ensures a consistent, accessible experience across different user groups. Applying this pattern to dynamically rendered JavaScript table rows is just as critical as static Blade templates.
**Action:** Always pair `aria-label` and `title` on icon-only interactive elements and verify their presence in both static and dynamic HTML generation logic.

## 2026-06-27 - [Semantic Navigation & Focus Management]
**Learning:** Standardizing ARIA attributes (`aria-expanded`, `aria-controls`) across both sidebar and tab navigation ensures a coherent experience for screen reader users. Crucially, managing focus during mobile sidebar transitions (focusing the close button on open and returning focus to the trigger on close) prevents "focus loss," a major barrier in keyboard navigation. Additionally, centralizing this logic in layouts rather than individual views ensures consistency and reduces maintenance overhead.
**Action:** Always implement explicit focus shifts for modal-like transitions and verify ARIA state synchronization in centralized layout handlers.
