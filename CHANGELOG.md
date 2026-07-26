# Changelog - [DD] Core AddOn

All notable changes to this add-on will be documented in this file.

## [1.4.2] - 2026-07-26

### Fixed
- **Option Validation Alignment**: Option verifiers in `cv6\Core\Option\Check` now validate custom format classes against allowed settings in the `cv6Core` group (`cv6CoreFaStyle`, `cv6CoreFaRotation`, `cv6CoreFaAnimation`), issuing error message `cv6_please_remove_disallowed_icon_classes_x` for disallowed classes.
- **Brand Autocomplete Filtering**: `cv6\Core\Helper\Icon::searchIcons()` filters autocomplete suggestions based on enabled styles in `cv6CoreFaStyle`, excluding Brand icons when `fab` is disabled in ACP options.

## [1.4.1] - 2026-07-25

### Added
- **Brand Variant Auto-Switch & Style Memory**: Automatically activates the `fab` (Brand) variant in the Layout Dropdown when selecting a brand icon (e.g. `fa-facebook`, `fa-github`). Stores the previously active non-brand style (`fal`, `fas`, `far`, `fad`) and restores it when selecting a non-brand icon afterwards.

## [1.4.0] - 2026-07-24

### Added
- **FontAwesome Icon Auto-Completion**: Added interactive `XF.cv6IconAutoComplete` component triggered on `fa-` + 2 characters. Features SVG mask previews inheriting current text color (`currentColor`) and smooth `XF.Animate.fadeDown`/`fadeUp` transitions.
- **Icon Helper Service**: Created `cv6\Core\Helper\Icon` for filesystem indexing & registry caching (`cv6IconList`).
- **Controllers & Routing**: Added public and admin controllers (`cv6\Core\Pub\Controller\Icon` & `cv6\Core\Admin\Controller\Icon`) mapped to prefix `cv6-core`.
- **Option Validators**: Updated `cv6\Core\Option\Check` with `verifyValidIconMandatory` (strict icon validation) and `verifyValidIconOptional` (clears field if only modifiers are entered without an icon name), utilizing native `\XF\Repository\IconRepository` constants.
- **Options Template Integration**: Extended `cv6_options_template_icon` and `cv6_fa_icon` macros with support for `autocomplete=1` in `edit_format_params`.
