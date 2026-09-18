# Adding Custom Alphabet Providers in cv6/Core

*This documentation is also available in: [Deutsch](custom_alphabet.de.md)*

The `cv6/Core` add-on provides an extensible multi-script alphabet index provider architecture for XenForo 2.3. Out of the box, `cv6/Core` includes 5 script providers:
* **Latin** (`latin`): Standard English/Western European alphabet (A–Z).
* **Greek** (`greek`): Greek script (Α–Ω) with Tonos vowel normalization.
* **Cyrillic** (`cyrillic`): Universal Cyrillic script (А–Я) supporting extended Slavic and regional variants.
* **Hebrew** (`hebrew`): Hebrew script (א–ת) with Nikkud stripping, Sofit final form mapping, and RTL support.
* **Arabic** (`arabic`): Arabic script (ا–ي) with Tashkil stripping, letter variant normalization, Eastern/Persian digit recognition, and RTL support.

Developers can easily register custom alphabet providers (e.g. Japanese Hiragana/Katakana, Georgian, Armenian, Devanagari, or custom domain taxonomies).

---

## 1. Creating the Provider Class

A custom alphabet provider must extend `cv6\Core\Index\Provider\AbstractProvider`.

Create your provider class in your add-on directory, for example `src/addons/Vendor/Addon/Index/Provider/Georgian.php`:

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
     * Returns an array of uppercase/standard letter symbols for the index bar.
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
     * Normalizes an incoming letter character (e.g. casing, accents, variants).
     */
    public function normalizeLetter(string $character): string
    {
        return mb_strtoupper($character, 'UTF-8');
    }

    /**
     * Determines whether the given character belongs to this alphabet.
     */
    public function isLetter(string $character): bool
    {
        return in_array($this->normalizeLetter($character), $this->getLetters(), true);
    }

    /**
     * Filters the finder for records starting with the selected letter.
     */
    public function applyLetterFilter(Finder $finder, string $column, string $letter): void
    {
        $escaped = $finder->escapeLike($letter, '?%');
        $finder->whereSql($column . ' LIKE ' . \XF::db()->quote($escaped));
    }

    /**
     * Filters for records starting with numbers (0-9).
     */
    public function applyNumberFilter(Finder $finder, string $column): void
    {
        $finder->whereSql($column . ' REGEXP "^[0-9]"');
    }

    /**
     * Filters for non-alphabetic, non-numeric characters ("Other / #").
     */
    public function applyOtherFilter(Finder $finder, string $column): void
    {
        $finder->whereSql($column . ' REGEXP "^[^0-9\x{10D0}-\x{10FA}]"');
    }

    /**
     * Return true if the alphabet requires Right-to-Left (RTL) layout.
     */
    public function isRtl(): bool
    {
        return false;
    }
}
```

---

## 2. Registering the Provider

You have two clean options in XenForo to register your custom provider:

### Option A: Via XenForo Code Event Listener (`cv6_core_index_alphabets`) [Recommended]

1. Create an event listener class in your add-on, for example `src/addons/Vendor/Addon/Listener.php`:

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

2. Register the Code Event Listener in the XenForo AdminCP (**Development > Code event listeners**) or via CLI:
   - **Listen to event**: `cv6_core_index_alphabets`
   - **Execute callback**: `Vendor\Addon\Listener` :: `cv6CoreIndexAlphabets`

### Option B: Via XenForo Class Extension (`XFCP_Alphabet`)

Extend `cv6\Core\Repository\Alphabet` via XenForo's class extension system:

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

## 3. Working with the Alphabet Repository

All alphabet operations in `cv6/Core` are managed through the singleton repository `cv6\Core\Repository\Alphabet`:

```php
/** @var \cv6\Core\Repository\Alphabet $alphabetRepo */
$alphabetRepo = \XF::repository(\cv6\Core\Repository\Alphabet::class);

// Retrieve all active providers:
$providers = $alphabetRepo->getProviders();

// Retrieve a specific provider by ID (falls back to Latin if null/unknown):
$provider = $alphabetRepo->getProvider('georgian');

// Retrieve options array formatted for <xf:selectrow>:
$options = $alphabetRepo->getProviderOptions();
```

In templates using `cv6_letterindex.html`, right-to-left layout is applied automatically when `$provider->isRtl()` returns `true`:

```html
<xf:macro template="cv6_letterindex" name="letters"
    arg-index="{$index}"
    arg-route="forum/view"
    arg-params="{$forum}"
    arg-number="1"
    arg-other="1" />
```
