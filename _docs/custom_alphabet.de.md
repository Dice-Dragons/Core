# Eigene Alphabet-Provider in cv6/Core hinzufügen

*Diese Dokumentation ist auch verfügbar auf: [English](custom_alphabet.md)*

Das Add-On `cv6/Core` bietet eine erweiterbare Multi-Skript-Architektur für Buchstaben-Indexleisten in XenForo 2.3. Standardmäßig bringt `cv6/Core` 5 Skript-Provider mit:
* **Lateinisch** (`latin`): Standard-Alphabet für Deutsch, Englisch und westeuropäische Sprachen (A–Z).
* **Griechisch** (`greek`): Griechische Schrift (Α–Ω) mit Tonos-Akzent-Normalisierung.
* **Kyrillisch** (`cyrillic`): Universelle kyrillische Schrift (А–Я) mit Unterstützung für slawische und regionale Sonderbuchstaben.
* **Hebräisch** (`hebrew`): Hebräische Schrift (א–ת) mit Entfernung von Nikkud-Vokalzeichen, Normalisierung von Sofit-Endformen und RTL-Unterstützung.
* **Arabisch** (`arabic`): Arabische Schrift (ا–ي) mit Tashkil-Entfernung, Alif/Ya-Varianten-Mapping, Erkennung von östlich-arabischen/persischen Ziffern und RTL-Unterstützung.

Entwickler können problemlos eigene Alphabet-Provider registrieren (z. B. für Japanisch Hiragana/Katakana, Georgisch, Armenisch, Devanagari oder eigene Kategorienschemata).

---

## 1. Erstellen der Provider-Klasse

Ein eigener Alphabet-Provider muss von `cv6\Core\Index\Provider\AbstractProvider` erben.

Erstelle die Provider-Klasse in deinem Add-On, z. B. unter `src/addons/Vendor/Addon/Index/Provider/Georgian.php`:

```php
<?php

namespace Vendor\Addon\Index\Provider;

use cv6\Core\Index\Provider\AbstractProvider;
use XF\Mvc\Entity\Finder;
use XF\Phrase;

class Georgian extends AbstractProvider
{
    public function getId(): string
    {
        return 'georgian';
    }

    public function getTitle(): Phrase|string
    {
        return \XF::phrase('vendor_addon_alphabet_georgian');
    }

    /**
     * Gibt das Array der Buchstaben-Symbole für die Indexleiste zurück.
     *
     * @return string[]
     */
    public function getLetters(): array
    {
        return [
            'ა', 'ბ', 'გ', 'დ', 'ე', 'ვ', 'ზ', 'თ', 'ი', 'კ', 'ლ', 'მ', 'ნ',
            'ო', 'პ', 'ჟ', 'რ', 'ს', 'ტ', 'უ', 'ფ', 'ქ', 'ღ', 'ყ', 'შ', 'ჩ',
            'ც', 'ძ', 'წ', 'ჭ', 'ხ', 'ჯ', 'ჰ',
        ];
    }

    /**
     * Normalisiert eingehende Zeichen (Groß-/Kleinschreibung, Akzente, Varianten).
     */
    public function normalizeLetter(string $character): string
    {
        return mb_strtoupper($character, 'UTF-8');
    }

    /**
     * Prüft, ob das Zeichen zu diesem Alphabet gehört.
     */
    public function isLetter(string $character): bool
    {
        return in_array($this->normalizeLetter($character), $this->getLetters(), true);
    }

    /**
     * Filtert den Finder nach Einträgen, die mit dem gewählten Buchstaben beginnen.
     */
    public function applyLetterFilter(Finder $finder, string $column, string $letter): void
    {
        $escaped = $finder->escapeLike($letter, '?%');
        $finder->whereSql($column . ' LIKE ' . \XF::db()->quote($escaped));
    }

    /**
     * Filtert nach Einträgen, die mit Ziffern beginnen (0-9).
     */
    public function applyNumberFilter(Finder $finder, string $column): void
    {
        $finder->whereSql($column . ' REGEXP "^[0-9]"');
    }

    /**
     * Filtert nach Einträgen mit Sonderzeichen ("Andere / #").
     */
    public function applyOtherFilter(Finder $finder, string $column): void
    {
        $finder->whereSql($column . ' REGEXP "^[^0-9\x{10D0}-\x{10FA}]"');
    }

    /**
     * Gibt true zurück, wenn das Alphabet von rechts nach links (RTL) dargestellt wird.
     */
    public function isRtl(): bool
    {
        return false;
    }
}
```

---

## 2. Registrieren des Providers

Es gibt zwei saubere Wege in XenForo, um einen eigenen Provider zu registrieren:

### Option A: Über den Code Event Listener (`cv6_core_index_alphabets`) [Empfohlen]

1. Erstelle eine Listener-Klasse in deinem Add-On, z. B. `src/addons/Vendor/Addon/Listener.php`:

```php
<?php

namespace Vendor\Addon;

class Listener
{
    public static function cv6CoreIndexAlphabets(array &$classes): void
    {
        $classes['georgian'] = \Vendor\Addon\Index\Provider\Georgian::class;
    }
}
```

2. Registriere den Code Event Listener im AdminCP (**Entwicklung > Code-Event-Listener**) oder per CLI:
   - **Auf Event hören**: `cv6_core_index_alphabets`
   - **Callback ausführen**: `Vendor\Addon\Listener` :: `cv6CoreIndexAlphabets`

### Option B: Über eine XenForo-Klassenerweiterung (`XFCP_Alphabet`)

Erweitere `cv6\Core\Repository\Alphabet` über das XenForo-Class-Extension-System:

```php
<?php

namespace Vendor\Addon\Repository;

use cv6\Core\Repository\XFCP_Alphabet;

class Alphabet extends XFCP_Alphabet
{
    public function getDefaultProviders(): array
    {
        $providers = parent::getDefaultProviders();
        $providers['georgian'] = \Vendor\Addon\Index\Provider\Georgian::class;
        return $providers;
    }
}
```

---

## 3. Verwendung im Alphabet-Repository

Alle Alphabet-Abfragen in `cv6/Core` laufen über das Singleton-Repository `cv6\Core\Repository\Alphabet`:

```php
/** @var \cv6\Core\Repository\Alphabet $alphabetRepo */
$alphabetRepo = \XF::repository(\cv6\Core\Repository\Alphabet::class);

// Alle aktiven Provider abfragen:
$providers = $alphabetRepo->getProviders();

// Bestimmten Provider per ID holen (Fallback auf Lateinisch bei ungültiger ID):
$provider = $alphabetRepo->getProvider('georgian');

// Options-Array für Auswahlmenüs (<xf:selectrow>):
$options = $alphabetRepo->getProviderOptions();
```

In Templates mit `cv6_letterindex.html` wird die RTL-Richtung bei `$provider->isRtl() === true` automatisch angewendet:

```html
<xf:macro template="cv6_letterindex" name="letters"
    arg-index="{$index}"
    arg-route="forum/view"
    arg-params="{$forum}"
    arg-number="1"
    arg-other="1" />
```
