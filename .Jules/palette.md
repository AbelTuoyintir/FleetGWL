## 2026-06-18 - [Flex Layout and Visibility]
**Learning:** When using `justify-between` in a flex container where some items are hidden on certain breakpoints (e.g., `lg:hidden`), it can cause unexpected layout shifts for the remaining visible items.
**Action:** Use `justify-end` and apply `mr-auto` to the first item that should stay on the left when visible, ensuring it pushes other items to the right regardless of its visibility state.
