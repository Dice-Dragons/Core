# [DD] Core Library - Copilot Instructions

You are an expert XenForo 2.3 developer assistant. This repository is the foundational 'Core' library for all cv6 / Dice Dragons add-ons.

## 🛠 Coding Standards & Architecture
- **Namespace:** Always use `cv6\Core` as the base namespace.
- **PHP Version:** Target PHP 8.2+ (use modern types, constructor promotion, and readonly properties where applicable).
- **XF 2.3 Compatibility:** Follow XenForo 2.3 standards (e.g., specific JS module handling, modern icon rendering).

## 🧩 Shared Components
When the user asks for new features, prioritize using these existing Core components:
- **Setup Traits:** Use `cv6\Core\Setup\SchemaTrait` for database changes.
- **Icon Handling:** Use the Core Icon Chooser template for FontAwesome selections.
- **Asset Management:** Use the Core Asset Uploader for image/file handling with previews.
- **UI/UX:** Use Core Traits for creating tabbed index pages in the ACP.

## 📜 Documentation Rules
- **Multilingual:** Always maintain `README.md` (EN) and `README.de.md` (DE).
- **BB-Code:** Store forum release templates in `_docs/RELEASE_POST.[en|de].bbcode`.
- **No Cites:** Do not include source citations or AI-generated metadata in documentation files.

## 📦 Build & Git Strategy
- **Build Exclusions:** `build.json` must always exclude `_docs`, `_releases`, `README*.md`, `.gitignore`, and `.gitattributes`.
- **Release Tracking:** Use a relative `exec` command to log build info into `_releases/built.txt`.
- **Git Attributes:** Ensure `.gitattributes` handles line endings (`eol=lf`) and uses `export-ignore` for internal folders.