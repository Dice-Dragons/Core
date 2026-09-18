# Changelog - [DD] Core AddOn

All notable changes to this add-on will be documented in this file.

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
