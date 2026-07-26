# OpenSpec Specification: cv6/Core

## Metadata
- **Addon ID**: `cv6/Core`
- **Title**: [DD] - Core Library
- **Version**: 1.4.2 (ID: 1040270)
- **Namespace**: `cv6\Core`
- **Requirements**: XenForo 2.3.0+, PHP 8.2.0+

---

## Overview
`cv6/Core` is the foundational base library for the cv6 / Dice Dragons add-on suite. It provides shared UI components, asset uploader templates, tabbed interface traits, setup traits, LESS icon helpers, option verifiers, and FontAwesome icon search and auto-completion.

---

## Key Architecture & Features

### 1. FontAwesome Icon Auto-Completion & UI Components
- **Component**: `XF.cv6IconAutoComplete` registered in `js/cv6/core/icon.js`.
- **Trigger**: Activated on inputs with `data-xf-init="cv6-icon-auto-complete"` when typing `fa-` + $\ge 2$ characters.
- **Rendering**: Uses SVG masks (`mask: url('...')`) with `background-color: currentColor` for theme text color inheritance. Standalone SVG fallback via `XF.Icon.getStandaloneIconUrl(variant, name)` ensures non-preloaded icons render reliably.
- **Transitions**: Smooth native `XF.Animate.fadeDown` and `XF.Animate.fadeUp` dropdown transitions.
- **Multi-Class Support**: Preserves existing modifier and variant classes (`fas`, `fa-rotate-90`, etc.) when selecting an icon suggestion. Filters autocomplete suggestions by enabled styles in `cv6CoreFaStyle` (excluding Brand icons when `fab` is disabled).

### 2. Backend Helper Service & Controllers
- **Helper Service**: `cv6\Core\Helper\Icon` scans `styles/fa/` directories (`solid`, `regular`, `brands`, `light`, `duotone`) and caches icon lists in `\XF::registry('cv6IconList')`.
- **Controllers**:
  - `cv6\Core\Pub\Controller\Icon::actionAutoComplete`
  - `cv6\Core\Admin\Controller\Icon::actionAutoComplete`
- **Routes**: Mapped prefix `cv6-core` -> action `auto-complete`.

### 3. Option Validators (`cv6\Core\Option\Check`)
- **`verifyValidIconMandatory`**: Strict validation requiring a valid icon class name. Validates format classes against enabled settings in the `cv6Core` group (`cv6CoreFaStyle`, `cv6CoreFaRotation`, `cv6CoreFaAnimation`).
- **`verifyValidIconOptional`**: Clears the input field if valid modifiers are present without an icon name, while verifying unknown or disallowed classes using `\XF\Repository\IconRepository` constants (`ICON_VARIANTS`, `ICON_CLASS_BLOCKLIST_REGEX`, `ICON_CLASS_REGEX`).

---

## Acceptance Criteria
1. Typing `fa-` + 2 characters in an icon input with autocomplete enabled opens a styled dropdown menu.
2. Icon previews in the dropdown render with the current theme text color (`currentColor`) and display non-preloaded icons.
3. Choosing an item from the dropdown replaces the active icon word while preserving variant and rotation modifier classes.
4. Saving ACP options with invalid or disallowed icon classes triggers option error messages (`cv6_please_remove_disallowed_icon_classes_x`), while saving valid modifiers without an icon clears the field cleanly.
5. Deactivating `fab` (Brands) in ACP options excludes Brand icons from autocomplete suggestions and rejects `fab` format classes during option validation.
