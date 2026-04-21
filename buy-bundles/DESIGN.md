# Design System Document: High-End Editorial Casino Experience

## 1. Overview & Creative North Star: "The Sovereign Vault"

This design system is built to move away from the chaotic, neon-drenched aesthetics of traditional online casinos. Instead, it adopts the persona of **"The Sovereign Vault."** The goal is to make the user feel like they have been granted access to a private, high-stakes lounge in a world-class physical establishment.

To achieve this, the system breaks away from rigid, "template-like" layouts. We prioritize **intentional asymmetry**, where hero elements may overlap containers, and **high-contrast typography** that feels more like a premium fashion magazine than a gaming app. The "VIP" feel is not achieved through clutter, but through "The Luxury of Space"—using generous white space (or "dark space") to signal that the content is so valuable it doesn't need to shout for attention.

---

## 2. Colors & Tonal Depth

The palette is rooted in deep blacks and metallic golds, utilizing the Material Design 3 token logic to create a sophisticated, multi-layered environment.

### The "No-Line" Rule
To maintain an editorial feel, **1px solid borders for sectioning are strictly prohibited.** Boundaries must be defined solely through background color shifts. For example, a `surface_container_low` section sitting on a `surface` background provides enough distinction without the "cheapness" of a structural line.

### Surface Hierarchy & Nesting
Treat the UI as a physical stack of premium materials—obsidian, smoked glass, and brushed gold.
*   **Base Layer:** `surface` (#131313) or `surface_container_lowest` (#0e0e0e).
*   **Secondary Sections:** Use `surface_container_low` to define content areas.
*   **Elevated Elements (Cards):** Use `surface_container_high` or `surface_container_highest` for components that need to "pop."
*   **Nesting:** When placing an element inside a card, use a *lower* tier (e.g., a `surface_container_low` search bar inside a `surface_container_high` card) to create a "recessed" or "carved" look.

### The "Glass & Gradient" Rule
Flat colors are for utilities; gradients are for experiences.
*   **Liquid Gold:** For primary CTAs and high-value indicators, use a linear gradient from `primary` (#ffe5aa) to `primary_container` (#f5c542) at a 135-degree angle.
*   **Signature Textures:** Incorporate Glassmorphism for floating navigation and overlays. Use `surface_variant` at 40% opacity with a `24px` to `32px` backdrop-blur.

---

## 3. Typography: Editorial Authority

The typography system relies on a high-contrast pairing of **Manrope** (for character and authority) and **Inter** (for precision and utility).

*   **Display & Headlines (Manrope):** These should be used sparingly but boldly. `display-lg` and `headline-lg` are your "hook." Use wide tracking (-2% to -4%) to give it a modern, architectural feel.
*   **Title & Body (Inter):** Inter provides a technical, Swiss-style clarity. This contrast between the "expressive" Manrope and "utilitarian" Inter creates a sense of professional curation.
*   **High-Contrast Scale:** Don't be afraid to place a `display-md` headline directly above a `label-sm` sub-header. This extreme difference in scale is a hallmark of high-end editorial design.

---

## 4. Elevation & Depth: Tonal Layering

In this design system, depth is a feeling, not a drop-shadow.

*   **The Layering Principle:** Instead of standard shadows, stack surfaces. A `surface_container_highest` card on a `surface_dim` background creates a natural, soft lift.
*   **Ambient Shadows:** If an element must float (like a modal), use an "Ambient Shadow."
    *   **Blur:** 40px - 60px.
    *   **Opacity:** 8% - 12%.
    *   **Color:** Use a tinted version of `on_surface` (a warm, dark grey) rather than pure black to simulate natural light.
*   **The "Ghost Border" Fallback:** If accessibility requires a border, use the `outline_variant` token at 15% opacity. It should be felt, not seen.
*   **Inner Glows:** For gold buttons, apply a 2px inner-shadow using `on_primary_fixed` at 20% opacity to create a "3D metallic" beveled edge.

---

## 5. Components

### Buttons: The "Gold Bar" Standard
*   **Primary:** Gradient from `primary` to `primary_container`. Text in `on_primary`. Apply a 1px "inner glow" (top-down) for a metallic finish.
*   **Secondary:** `surface_container_highest` background with a `primary` "Ghost Border" (20% opacity).
*   **Tertiary:** Text-only in `primary`, but with `label-md` uppercase styling and 1.5px letter spacing.

### Cards: Glassmorphic Containers
*   **Structure:** No dividers. Use vertical whitespace (refer to the `xl` or `lg` spacing scale) to separate headers from body content.
*   **Visuals:** Use `surface_variant` at 60% opacity with a `20px` backdrop blur. The corner radius must strictly follow the `lg` (0.5rem) or `xl` (0.75rem) tokens for a soft, premium feel.

### Input Fields: Recessed Luxury
*   **State:** Default inputs should use `surface_container_lowest` to look "carved" into the interface.
*   **Focus:** Transition the background to `surface_container_low` and add a `primary` "Ghost Border" at 30% opacity. No heavy outlines.

### VIP Badges & Chips
*   **Visuals:** Small, high-contrast pills. Use `secondary_container` backgrounds with `on_secondary_container` text.
*   **Motion:** These should have a subtle "shimmer" gradient animation (moving from left to right) to denote high-value or "live" status.

### Progress Bars (Jackpot Trackers)
*   Forbid the standard "flat" progress bar. Use a `surface_container_highest` track with a `primary` to `tertiary_container` gradient fill to represent the "rising heat" of a jackpot.

---

## 6. Do's and Don'ts

### Do:
*   **DO** use intentional asymmetry. Place a high-value game asset (e.g., a 3D gold coin or card) so it "breaks" the container of a card.
*   **DO** use "Primary" gold for action-oriented elements and "Tertiary" blues for supportive, informational data (like odds or balance history).
*   **DO** leverage `surface_bright` for hover states on dark backgrounds to create a "glow" effect.

### Don't:
*   **DON'T** use 100% white (#FFFFFF). Use `on_surface` (#e5e2e1) to keep the contrast high but the "glare" low and premium.
*   **DON'T** use standard 1px dividers. Use a 24px - 32px gap of empty space or a subtle change from `surface` to `surface_container_low`.
*   **DON'T** use "bounce" easing for animations. Use "expressive" easing (long durations, slow-in, slow-out) to mimic the smooth movement of high-end mechanical watches or luxury car interfaces.