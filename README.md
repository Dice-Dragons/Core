# [DD] - Core Library
*This documentation is also available in: [Deutsch](README.de.md)*

![Version](https://img.shields.io/badge/version-1.4.3-blue.svg) ![XenForo](https://img.shields.io/badge/XenForo-2.3%2B-orange.svg) ![PHP](https://img.shields.io/badge/PHP-8.2%2B-qt.svg)

**[DD] - Core Library** is the foundational framework required for (nearly) all cv6 / Dice Dragons add-ons. It provides a rich set of shared resources, UI components, and developer tools to ensure stability and reduce code duplication.

---

## Features & Developer Tools

This library includes essential tools for XenForo development:

### UI Components & Templates
* **FontAwesome Icon Auto-Completion**: Interactive auto-completion dropdown (`fa-` + 2 characters) with live SVG mask previews inheriting current text color (`currentColor`), smooth `XF.Animate.fadeDown`/`fadeUp` transitions, and full support for modifier/variant classes (`fas`, `fa-spin`, `fa-rotate-90`). Filters suggested icons based on enabled styles in `cv6CoreFaStyle` (excluding Brand icons when `fab` is disabled). Supports `autocomplete=1` in option format parameters (`edit_format_params`).
* **FontAwesome Icon Chooser**: Ready-to-use template with a menu interface for selecting icons.
* **Asset Uploader**: Standardized upload template including live image preview.
* **Universal Integration**: Specialized templates to use the Icon Chooser and Asset Uploader easily in both **Style Properties** and **Add-on Options**.
* **Tabbed Interfaces**: Traits and templates to quickly create tabbed index pages.

### Backend & Logic
* **Icon Helper Service (`cv6\Core\Helper\Icon`)**: Caches and scans native XenForo FontAwesome SVG icons (`styles/fa/`) into XenForo registry (`cv6IconList`) for fast search lookups.
* **Icon Controllers**: Public (`cv6\Core\Pub\Controller\Icon`) and Admin (`cv6\Core\Admin\Controller\Icon`) controllers mapped to `cv6-core/auto-complete`.
* **Option Validators (`cv6\Core\Option\Check`)**:
    * `verifyValidIconMandatory`: Requires a valid icon class name. Validates format classes against enabled settings in the `cv6Core` group (`cv6CoreFaStyle`, `cv6CoreFaRotation`, `cv6CoreFaAnimation`).
    * `verifyValidIconOptional`: Clears input field if only modifiers are entered without an icon name, while properly validating unknown or disallowed classes using native `XF\Repository\IconRepository` constants.
* **Setup Traits**: Reusable traits to simplify add-on installation, upgrades, and database schema changes.
* **JS Dependency Handler**: Efficient management of JavaScript dependencies.
* **Widget Framework**: Base functionality for creating index widgets.

### Styling & LESS
* **LESS Icon Helpers**: Functionality to easily render FontAwesome icons directly within `.less` files.
* **Global Style Settings**: Centralized opacity settings (standardizing the look across all cv6 add-ons).

---

## Requirements

* **XenForo**: 2.3.0 or higher
* **PHP**: **8.2.0** or higher
* **Dependency**: This add-on is the required base for all cv6 extensions.

---

## Installation

### 1. Upload
Upload the contents to `src/addons/cv6/Core`.

### 2. Install
Install the add-on via the Admin Control Panel or per CLI:
```bash
php cmd.php xf-addon:install cv6/Core
```

---

## Configuration

While this add-on primarily works in the background, it provides central settings:
1. Navigate to **ACP > Options > [DD] Core Settings**.
2. **Font Awesome Integration**: Configure how icons and format styles/rotations/animations are rendered and permitted globally for dependent add-ons.
3. **Central Design Settings**: Adjust global opacity for all cv6 extensions.

---

## Links & Support

* **Developer**: [Hoffi](https://forum.dice-dragons.de/mitglieder/hoffi.1/)
* **Support Thread**: [Get Help Here](https://forum.dice-dragons.de/forum/core/)
* **FAQ**: [View FAQ](https://forum.dice-dragons.de/downloads/core.145/)