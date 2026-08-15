# [DD] - Core Library
*Diese Dokumentation ist auch verfügbar auf: [English](README.md)*

![Version](https://img.shields.io/badge/version-1.4.3-blue.svg) ![XenForo](https://img.shields.io/badge/XenForo-2.3%2B-orange.svg) ![PHP](https://img.shields.io/badge/PHP-8.2%2B-qt.svg)

**[DD] - Core Library** ist das grundlegende Framework für fast alle cv6 / Dice Dragons Add-Ons. Es stellt gemeinsam genutzte Ressourcen, UI-Komponenten und Entwickler-Tools bereit, um Stabilität zu gewährleisten und Code-Duplizierung zu vermeiden.

---

## Funktionen & Entwickler-Tools

### UI-Komponenten & Templates
* **FontAwesome Icon Auto-Vervollständigung**: Interaktives Dropdown-Auswahlmenü bei Eingabe von `fa-` + 2 Zeichen mit SVG-Masken-Vorschau in Vererbung der aktuellen Textfarbe (`currentColor`), flüssigen `XF.Animate.fadeDown`/`fadeUp` Übergängen und Unterstützung für Zusatz- und Varianten-Klassen (`fas`, `fa-spin`, `fa-rotate-90`). Filtert Vorschläge basierend auf aktivierten Styles in `cv6CoreFaStyle` (schließt Brand-Icons aus, wenn `fab` deaktiviert ist). Unterstützt `autocomplete=1` in den Format-Parametern der Optionen (`edit_format_params`).
* **FontAwesome Icon Chooser**: Fertiges Menü-Template zur Auswahl von FontAwesome Icons.
* **Asset Uploader**: Standardisiertes Upload-Template inklusive Bild-Vorschau.
* **Universelle Integration**: Spezialisierte Templates zur einfachen Nutzung von Icon Chooser und Asset Uploader in **Stileigenschaften** und **Add-On-Optionen**.
* **Tab-Schnittstellen**: Traits und Templates zum schnellen Erstellen getabbter Übersichtsseiten.

### Backend & Logik
* **Icon Helper Service (`cv6\Core\Helper\Icon`)**: Indiziert und cacht native XenForo FontAwesome SVG-Icons (`styles/fa/`) in der XenForo Registry (`cv6IconList`) für schnelle Suchanfragen.
* **Icon Controller**: Public (`cv6\Core\Pub\Controller\Icon`) und Admin (`cv6\Core\Admin\Controller\Icon`) Controller gemappt auf die Route `cv6-core/auto-complete`.
* **Option-Verifizierer (`cv6\Core\Option\Check`)**:
    * `verifyValidIconMandatory`: Pflichtfeld-Prüfung für ein gültiges Haupt-Icon. Validiert Format-Klassen gegen aktivierte Einstellungen der `cv6Core`-Gruppe (`cv6CoreFaStyle`, `cv6CoreFaRotation`, `cv6CoreFaAnimation`).
    * `verifyValidIconOptional`: Leert das Eingabefeld automatisch, wenn nur Modifizierer ohne Icon angegeben werden, und prüft ungültige oder nicht erlaubte Klassen anhand der nativen `XF\Repository\IconRepository` Konstanten.
* **Setup Traits**: Wiederverwendbare Traits für Installation, Upgrades und Schema-Änderungen.
* **JS Dependency Handler**: Effiziente Verwaltung von JavaScript-Abhängigkeiten.
* **Widget Framework**: Basisfunktionalität zum Erstellen von Index-Widgets.

### Styling & LESS
* **LESS Icon Helpers**: Funktionen zum direkten Rendern von FontAwesome Icons in `.less`-Dateien.
* **Zentrale Design-Einstellungen**: Zentrale Deckkraft-Einstellungen für einheitliches Design.

---

## Voraussetzungen

* **XenForo**: 2.3.0 oder höher
* **PHP**: **8.2.0** oder höher
* **Abhängigkeit**: Basis-Add-On für alle cv6 Erweiterungen.

---

## Installation

### 1. Upload
Lade den Inhalt hoch nach `src/addons/cv6/Core`.

### 2. Installation
Installiere das Add-On über das ACP oder per CLI:
```bash
php cmd.php xf-addon:install cv6/Core
```

### 3. Deutsches Sprachpaket (Optional)
Für deutsche Übersetzungen kann das Sprachpaket importiert werden:
1. Navigiere im ACP zu **Aussehen -> Sprachen**.
2. Klicke oben rechts auf **Importieren**.
3. Wähle die XML-Datei aus `src/addons/cv6/Core/language/Core-1040370-de.xml`.
4. Wähle bei **Sprache überschreiben** die Sprache **Deutsch [Du]** (oder als Kind-Sprache importieren).
5. Klicke auf **Importieren**.

---

## Einrichtung

1. Gehe im ACP zu **Optionen -> [DD] Core Settings**.
2. **Font Awesome Integration**: Globales Rendering von Icons und erlaubten Format-Styles/Rotationen/Animationen konfigurieren.
3. **Zentrale Design-Einstellungen**: Globale Deckkraft anpassen.

---

## Links & Support

* **Entwickler**: [Hoffi](https://forum.dice-dragons.de/mitglieder/hoffi.1/)
* **Support-Forum**: [Hilfe & Fragen](https://forum.dice-dragons.de/forum/core/)
* **FAQ**: [FAQ ansehen](https://forum.dice-dragons.de/downloads/core.145/)