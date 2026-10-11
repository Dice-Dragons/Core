# OpenSpec: [DD] Core Library (`cv6/Core`)

## 1. Metadata & Scope
* **Add-On ID**: `cv6/Core`
* **Title**: `[DD] - Core Library`
* **Current Version**: `1.6.2` (Version ID: `1060270`)
* **Vendor**: `cv6` / Hoffi
* **Target Environment**: XenForo 2.3.0+, PHP 8.2.0+
* **Dependencies**: None (Core foundational library for all `cv6/*` add-ons)

---

## 2. Shared Components & UI Subsystems

### A. Multi-Provider Icon Framework & Migration Wizard
* **Interfaces & Core**:
  * `cv6\Core\Icon\Provider\IconProviderInterface`: Contract for all icon providers (`getId()`, `getPrefix()`, `getStyles()`, `render()`, `getMaskUrl()`, `search()`, `isValidIcon()`, `getMigrationMap()`, `getStyleMap()`).
  * `cv6\Core\Icon\Provider\AbstractIconProvider`: Base class providing prefix normalization, automatic Data-URI CSS mask extraction (`fill="black"`), and style mapping defaults.
  * `cv6\Core\Icon\Provider\FontAwesomeProvider`: Native Font Awesome provider (`fa-`).
* **Code Event (`cv6_icon_provider`)**: Official XenForo event enabling third-party add-ons to register custom icon sets.
* **Core Extensions**:
  * `cv6\Core\XF\IconRenderer`: Extends `XF\IconRenderer` to delegate rendering calls dynamically by prefix/class, with fallback placeholder rendering on missing providers.
  * `cv6\Core\XF\CssRenderer`: Extends `XF\CssRenderer` to intercept `@<prefix>-var-<name>` variables in LESS and generate runtime `data:image/svg+xml` masks.
* **Migration Wizard (`cv6\Core\Admin\Controller\Icon::actionMigrate`)**:
  * 4-step wizard to convert icons across Database (options, standard XF tables, dynamic `cv6_icon` tables), Style Properties (`@...-var-...`), and Phrases (`<xf:fa>`), with interactive override modal (`actionMigrateChange`) and automatic cache rebuilds.
* **Autocomplete & Picker**:
  * `cv6_fa_icon::cv6_inputIcon`: Multi-provider selector dropdown with brand icons/colors, dynamic styles, and live Ajax SVG autocomplete (`_files/js/cv6/core/icon.js`).

### B. Asset Upload Field & Reset Subsystem (`cv6_assetupload`)
* **Macro**: `cv6_assetupload::cv6_assetuploadrow`
* **Arguments**:
  * `arg-name` (Required): Form input name.
  * `arg-asset` (Required): Asset type/key for permission mapping (e.g. `cv6publisher`, `cv6coreoptions`).
  * `arg-default` (Optional, Default `""`): Default path/URL to an asset graphic. When non-empty, renders an integrated Reset button.
  * `arg-value` (Optional): Current input value.
  * `arg-label`, `arg-hint`, `arg-explain`, `arg-placeholder`, `arg-rowclass`, `arg-class`, `arg-preselector`.
* **Reset Button Specification**:
  * Tag: `<xf:button>` with classes `inputGroup inputGroup--button button--alt cv6button--reset js-cv6AssetReset`.
  * Tooltip: `title="{{ phrase('reset') }}"` with `data-xf-init="tooltip"`.
  * Click Handler: `data-xf-click="cv6-asset-reset"` and `data-default="{$default}"`.
  * Icon: `<xf:fa icon="fa-undo" />`.
* **LESS & Visual Styling (`cv6_core.less`)**:
  * `.inputGroup.inputGroup--asset`: Styled with `display: flex; flex-wrap: nowrap;`.
  * Seamless Joined Bar: Zeroes the right border-radius of `.inputGroup--joined > :last-child` and the left border-radius of `.cv6button--reset`.
  * Hover Animation: On `.cv6button--reset:hover`, `.fa--xf` rotates **180° counter-clockwise** (`transform: rotate(-180deg)`) using `.m-transition(transform; 0.3s; ease);`.
* **JavaScript Architecture (`asset.js`)**:
  * `XF.cv6AssetImage`: Registered on `cv6--asset-imagepreview`. Manages live background previews, input/change listeners, and exposes `reset()` and `updatePreview(val)`.
  * `XF.cv6AssetReset`: Event handler registered via `XF.Event.register('click', 'cv6-asset-reset', 'XF.cv6AssetReset')`.
* **Option Integration (`cv6_options_template_asset.html`)**:
  * Forwards `arg-default="{$option.default_value}"` to the macro so options using this template gain the reset capability automatically.

### B. Multi-Toggle & Collapsible Rows Subsystem (`cv6-multi-toggle`)
* **JavaScript**: `_files/js/cv6/core/toggle.js` (`XF.cv6MultiToggle`).
* **Registration**: `XF.Event.register('click', 'cv6-multi-toggle', 'XF.cv6MultiToggle')` and `cv6-bundle-toggle`.
* **Features**:
  * Synchronized sliding of native `<tr>` table rows without breaking table display via cell wrapping and `XF.Animate.animate`.
  * Atomic LocalStorage state persistence preventing cross-page key wiping.
  * Synchronous pre-paint execution to prevent Flash of Unstyled Content (FOUC).
* **Templates & Macros**:
  * Macro: `<xf:macro template="cv6_admin_macros" name="toggle_js" />`.
  * Classes: `.dataList-row--childRow`, `.dataList-row--bundleChild`.

### C. FontAwesome Icon Selection & Helper (`cv6_fa_icon`)
* **Components**: `icon.js`, `cv6_fa_icon.html`, `cv6_options_icon.html`, `cv6_style_fa_icon.html`.
* **Helper**: `cv6\Core\Helper\Icon` providing FA6 icon searching, validation, caching, and custom rendering overrides.
* **Option Verifiers**: `cv6\Core\Option\Check::verifyValidIconMandatory` and `verifyValidIconOptional`.
* **AutoComplete**: Live search (`fa-` + 2 characters) with standalone SVG mask previews.

### D. Letter Index Filter Subsystem (`cv6_letterindex`)
* **Components**: `cv6_letterindex.html`, `cv6_letterindex.less`.
* **Controller / Trait**: Provides tabbed alphabetical / numeric filtering for public and admin listings.

### E. ComboSort & Phrase Quick Edit
* **ComboSort (`combosort.js`)**: Enables multi-select reordering and sorting in ACP lists.
* **Phrase Quick Edit (`phrase.js`, `cv6_core_phrase_quick_edit.html`)**: In-place phrase editing without navigating away from the current page.

### F. Centralized Comparison Operators Subsystem (`\cv6\Core\Helper\Operator`)
* **Standardized Operators**: Suite-wide uniform comparison phrases with mathematical symbols (`=`, `!=`, `>`, `>=`, `<`, `<=`) and concise uppercase keywords (`BETWEEN`, `NOT BETWEEN`, `IN`, `NOT IN`, `CONTAINS`, `NOT CONTAINS`, `EMPTY`, `NOT EMPTY`, `STARTS WITH`, `ENDS WITH`).
* **Helper (`\cv6\Core\Helper\Operator`)**: Exposes constants (`OP_EQUALS`, `OP_CONTAINS`, etc.), category groupings (`GROUP_MATH`, `GROUP_LOGIC`), and retrieval/filter methods (`getMathematicalOperators()`, `getLogicalOperators()`, `getAllOperators()`, `getGroupedOperators()`, `getOperators()`).
* **Template Functions**: `{{ cv6_operators() }}` and `{{ cv6_grouped_operators() }}` registered on `XF\Template\Templater`.
* **AdminCP Macros**: `<xf:macro template="cv6_admin_macros" name="operator_select" ... />` and `operator_select_row` supporting optgroup categorization and filtering.

---

## 3. Options & Assets

### AdminCP Options
| Option Key | Data Type | Edit Format | Default Value | Description |
| --- | --- | --- | --- | --- |
| `cv6CoreImageSample` | `string` | `cv6_options_template_asset` | `styles/cv6/core/sample.png` | Sample asset upload field demonstrating default value and reset functionality. |
| `cv6IconSample` | `string` | `cv6_options_icon` | `far fa-smile` | Sample icon option demonstrating FA6 icon picker. |
| `cv6CoreFaStyle` | `string` | `select` | `solid` | Default FontAwesome style variant. |
| `cv6CoreFaAnimation` | `string` | `select` | `none` | Default FontAwesome animation. |
| `cv6CoreFaRotation` | `string` | `select` | `0` | Default FontAwesome rotation degree. |
| `cv6extraFaIcons` | `array` | - | `[]` | Additional custom FA icons registered in the system. |
| `cv6AllowedIconProviders` | `array` | `callback` (`cv6\Core\Option\IconProvider::renderCheckbox`) | `['fa' => true]` | Select active icon providers available in the system. |
| `cv6ShowIconHelper` | `boolean` | `onoff` | `true` | Display helper tools for icon selection in ACP. |

### Build Process & Static Assets (`build.json`)
* **Additional Files**:
  * `js/cv6/core/asset.js`
  * `js/cv6/core/combosort.js`
  * `js/cv6/core/icon.js`
  * `js/cv6/core/phrase.js`
  * `js/cv6/core/toggle.js`
  * `styles/cv6/core` (includes `sample.png` and future static style assets)
* **Minification**: Automatically minifies all JavaScript files in `_files/js/cv6/core/` into the release package.

---

## 4. Acceptance Criteria & Test Scenarios

- [x] **Asset Upload Default & Reset**:
  - `cv6_assetuploadrow` renders Reset button only when `arg-default` is non-empty.
  - Clicking Reset updates the input to the default value, clears temporary values, and updates the background preview.
  - Hovering the reset button rotates the `fa-undo` icon smoothly by 180° counter-clockwise.
- [x] **Sample Asset Delivery**:
  - `styles/cv6/core/sample.png` is packaged by `build.json` and served with HTTP 200 in development.
- [x] **Multi-Toggle Collapsibles**:
  - Smooth animation on `<tr>` elements without layout glitching.
  - State persisted in LocalStorage across navigation.
- [x] **FontAwesome Validation**:
  - Full compatibility with FA6 icon formats and XenForo 2.3 SVG rendering.
- [x] **Centralized Comparison Operators**:
  - `Operator::getAllOperators()` returns all 16 standardized mathematical and logical operators.
  - Template functions `{{ cv6_operators() }}` and macros render formatted dropdowns with English and German translations.
