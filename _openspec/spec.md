# OpenSpec: [DD] Core Library (`cv6/Core`)

## 1. Metadata & Scope
* **Add-On ID**: `cv6/Core`
* **Title**: `[DD] - Core Library`
* **Current Version**: `1.5.1` (Version ID: `1050170`)
* **Vendor**: `cv6` / Hoffi
* **Target Environment**: XenForo 2.3.0+, PHP 8.2.0+
* **Dependencies**: None (Core foundational library for all `cv6/*` add-ons)

---

## 2. Shared Components & UI Subsystems

### A. Asset Upload Field & Reset Subsystem (`cv6_assetupload`)
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
