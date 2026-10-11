# Changelog - [DD] Core AddOn

All notable changes to this add-on will be documented in this file.

## [1.6.2] - 2026-10-11

### Added
- **Centralized Comparison Operators Subsystem**: Centralized all comparison operator phrases, symbols, and helpers across `cv6/*` add-ons (such as `cv6/CustomFields` and `cv6/CharEdit`) into `cv6/Core` to ensure uniform terminology, concise phrases, and cross-addon compatibility:
  - **Standardized Phrases**: Concise mathematical symbols (`=`, `!=`, `>`, `>=`, `<`, `<=`) and uppercase keywords (`BETWEEN`, `NOT BETWEEN`, `IN`, `NOT IN`, `CONTAINS`, `NOT CONTAINS`, `EMPTY`, `NOT EMPTY`, `STARTS WITH`, `ENDS WITH`) in both English (`language_id = 0`) and German (`language_id = 6`).
  - **PHP Helper (`\cv6\Core\Helper\Operator`)**: Provides typed constants (`OP_EQUALS`, `OP_CONTAINS`, etc.), category groupings (`GROUP_MATH`, `GROUP_LOGIC`), and retrieval/filter methods (`getMathematicalOperators()`, `getLogicalOperators()`, `getAllOperators()`, `getGroupedOperators()`, `getOperators()`).
  - **Template Functions**: Registered `{{ cv6_operators($type, $include, $exclude) }}` and `{{ cv6_grouped_operators() }}` in `Templater` for direct usage in `<xf:options source="..." />`.
  - **AdminCP Macros**: Added `<xf:macro template="cv6_admin_macros" name="operator_select" ... />` and `operator_select_row` supporting grouped `<xf:optgroup>` rendering and whitelist/blacklist filtering.

## [1.6.1] - 2026-10-10

### Added
- **Release Automation - Resource Description Sync**: Extended the reusable release workflow (`xenforo-release-publisher.yml`) to automatically synchronize the XenForo Resource Manager (XFRM) description with `_docs/readme.de.bbcode` (or fallback `readme.bbcode`) via REST API (`POST /api/resources/{id}/`) on each release.
- **Icon Provider UI Showcase**: Added `_docs/screenshots/icon-provider-select.png` showcasing the icon provider selection dropdown in AdminCP.

## [1.6.0] - 2026-10-10

### Added
- **Multi-Provider Icon System**: Built a modular, provider-based icon subsystem (`cv6\Core\Icon\Provider\IconProviderInterface`, `AbstractIconProvider`, `FontAwesomeProvider`) allowing any custom or third-party icon set (such as Google Material Symbols, Game Icons, or Font Awesome 6/7) to integrate seamlessly into XenForo 2.3.
- **Icon Provider Code Event (`cv6_icon_provider`)**: Official code event allowing external add-ons to register custom icon providers.
- **Provider Management Repository & Options**: Added `cv6\Core\Repository\IconProvider` to resolve and cache active providers. Added `cv6AllowedIconProviders` option to enable/disable specific icon providers in AdminCP.
- **Rendering & Fallback Protection**: Extended `XF\IconRenderer` to dynamically delegate rendering to active providers based on prefixes/classes. Features an automatic fallback placeholder with informative tooltips (`cv6_core_icon_provider_missing_explain`) when an icon belongs to an inactive or uninstalled provider to prevent layout breakage.
- **Runtime CSS Mask Generation**: Extended `XF\CssRenderer` to intercept `@<prefix>-var-<name>` variables in LESS stylesheets and dynamically generate standard `data:image/svg+xml;utf8,...` CSS masks with `fill="black"` for non-FA icons used in `.m-faIcon()` masks.
- **4-Step Icon Migration Wizard (`admin.php?cv6-core/migrate`)**: Added a comprehensive ACP migration tool to convert existing icons between any two registered providers:
  - **Step 1 (Database)**: Scans and updates options (`xf_option`), standard XenForo content tables, and dynamically discovered tables with `cv6_icon` columns (skipping internal options like `extraFaIcons`).
  - **Step 2 (Style Properties)**: Converts icon classes and `@...-var-...` LESS variables in `xf_style_property`.
  - **Step 3 (Phrases)**: Scans and updates phrases containing `<xf:fa ... icon="..." ...>` tags with automatic phrase recompilation.
  - **Step 4 (Summary & Rebuild)**: Displays migration statistics and automatically rebuilds all affected XenForo caches.
  - **Interactive Override Overlay (`cv6-core/migrate-change`)**: Allows reviewing and customizing target icons in a modal before running the migration.
- **Multi-Provider Icon Input & Live Autocomplete**: Enhanced `cv6_fa_icon::cv6_inputIcon` macro and `cv6/core/icon.js` to support provider switching, style dropdowns, and live SVG autocomplete previews via Ajax.
- **Developer Documentation**: Added `_docs/icon_providers.md` documenting architecture, registration, and implementation guidelines for custom icon providers.

### Changed
- **Option Validation**: Updated `cv6\Core\Option\Check` to dynamically validate icon format and modifier classes against all active providers and styles instead of strictly Font Awesome.
- **Phrase Translation Clean Title**: Streamlined title display in `Phrase::actionTranslate()` to avoid redundant parenthesized titles.

## [1.5.2] - 2026-09-25

### Added
- **Asset Upload Field Default & Reset Subsystem**: Enhanced the `cv6_assetupload::cv6_assetuploadrow` macro with an optional `arg-default=""` parameter. When set, an integrated Reset button (`fa-undo`) is rendered alongside the file upload button to restore the default asset path and refresh the preview in a single click.
- **Counter-Clockwise Icon Animation**: Added a smooth 180° counter-clockwise rotation animation (`transform: rotate(-180deg)`) on hover for the asset reset button using XenForo's `.m-transition` mixin.
- **Option Template Default Propagation**: Updated `cv6_options_template_asset.html` to automatically pass `arg-default="{$option.default_value}"`, enabling the reset button on all options configured with this template that define a default value.
- **Sample Dragon Graphic Asset**: Added `styles/cv6/core/sample.png` to `_files/` and registered `styles/cv6/core` in `build.json`, setting it as default value for the debug option `cv6CoreImageSample`.
- **Dedicated OpenSpec Specification**: Added `_openspec/spec.md` documenting architecture, components, options, and standards for `cv6/Core`.

## [1.5.1] - 2026-09-23

### Fixed
- **Multi-Toggle Cross-Page State Persistence**: Implemented atomic read-modify-write synchronization directly on `window.localStorage` in `_files/js/cv6/core/toggle.js`. Fixes an issue where navigating between different admin views with collapsible items (e.g. Dice Sets and Wiresets) caused stale in-memory caches to overwrite or erase previously saved toggle states.
- **Toggle Initialization Timing & FOUC Prevention**: Re-architected `initStoredToggles` lifecycle to run synchronously as soon as the DOM body is present, eliminating the brief visible flash of expanded content on page reload before initial browser paint.
- **Immediate Inline Display State Toggle**: Child rows now immediately receive `style.display = 'none'` when collapsed (and clear inline display when expanded) to prevent CSS specificity conflicts and layout shifts.
- **Safe Cookie Prefix Resolution**: Added fallback to `<html data-cookie-prefix="...">` when accessing `localStorage` before XenForo's deferred `XF.config.cookie` object is initialized.
- **Container Isolation Support**: Added `getStorageContainer()` method to support custom `data-storage-container` attributes on toggle triggers while defaulting cleanly to `'toggle'`.

## [1.5.0] - 2026-09-18

### Added
- **Multi-Alphabet Index Provider Repository (`cv6\Core\Repository\Alphabet`)**: Extensible repository providing multi-script alphabet index management. Out of the box supports 5 scripts: Latin (`latin`), Greek (`greek` with Tonos normalization), Cyrillic (`cyrillic` with Slavic/regional variant mappings and composite OR-LIKE filtering), Hebrew (`hebrew` with Nikkud stripping, Sofit final-form mapping, and RTL layout), and Arabic (`arabic` with Tashkil stripping, letter variant normalization, Eastern/Persian digit support, and RTL layout).
- **Code Event (`cv6_core_index_alphabets`)**: Official code event allowing third-party add-ons to easily register or override alphabet providers without touching core files.
- **Inline Phrase Quick-Edit**: Added an inline translation modal (`fa-language` button) to AdminCP text inputs (`<xf:textbox>`, `<xf:textarea>`) with `phrase="..."` or `cv6-phrase="..."`. Allows administrators with `language` permission to translate dynamic content phrases across all installed languages directly from the edit view without leaving the form.
- **RTL Support for Letter Index**: Updated `cv6_letterindex.html` template and `IndexTrait` to automatically detect RTL providers (`$provider->isRtl()`) and apply `dir="rtl"` to the letter bar.
- **Developer Documentation**: Added comprehensive guides (`_docs/custom_alphabet.md`, `custom_alphabet.de.md`, and BBCode versions) detailing how to build and register custom alphabet providers.

### Changed
- **IndexTrait Modernization**: Updated `IndexTrait.php` to fetch providers directly from the `Alphabet` repository (`$this->repository(Alphabet::class)->getProvider(...)`), use multibyte-safe string manipulation (`mb_strtoupper`, `mb_substr`), and prepare for PHP 8.4 compatibility.
- **Strict Master Phrase Single Source of Truth**: Form quick-edits strictly keep `xf_phrase` as single source of truth without falling back to temporary form input values. Master phrases remain intact when saving individual language translations.

## [1.4.3] - 2026-08-15

### Added
- **Setup Helper**: Added `syncGroupPhrases` helper method to `SetupTrait.php` to automate sync/import of group titles as master phrases.

### Changed
- **XenForo 2.3 Asset Resolution**: Moved core JavaScript files into the `_files/` directory, updating the build system and admin templates (`cv6_assetupload.html`, `cv6_fa_icon.html`, `cv6_style_fa_icon.html`) to resolve JS assets natively.

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
