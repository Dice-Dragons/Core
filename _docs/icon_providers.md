# Icon Provider Architecture & Developer Guide

`cv6/Core` introduces a modular, multi-provider icon system for XenForo 2.3. It decouples the forum framework from Font Awesome, allowing any custom or third-party icon set (Google Material Symbols, Game Icons, Font Awesome 6/7, Lucide, Tabler, etc.) to integrate seamlessly.

---

## 1. How It Works

1. **Provider Discovery**: Registered via XenForo Code Event `cv6_icon_provider`.
2. **Rendering Delegation**: `XF\IconRenderer` is extended to inspect icon classes and delegate rendering to the corresponding active provider based on its registered prefix or style classes. If a provider is disabled or missing, a subtle placeholder icon is rendered to prevent layout breaks.
3. **LESS / CSS Mask Support**: `XF\CssRenderer` is extended to intercept `@<prefix>-var-<name>` variables in stylesheets and generate standard `data:image/svg+xml` CSS mask URIs at runtime.
4. **Autocomplete & Picker**: The `cv6_fa_icon::cv6_inputIcon` macro supports dynamic provider switching, style dropdowns, and live SVG autocomplete via Ajax.
5. **Cross-Provider Migration**: The built-in Migration Wizard (`admin.php?cv6-core/migrate`) allows seamless automated migration of content, options, style properties, and phrases between any two providers.

---

## 2. Registering an Icon Provider in 3 Steps

### Step 1: Create the Event Listener

In your add-on's `Listener.php` (or dedicated listener class):

```php
namespace MyVendor\MyAddon;

use cv6\Core\Icon\Provider\IconProviderInterface;

class Listener
{
    /**
     * @param array<string, IconProviderInterface|class-string<IconProviderInterface>> $providers
     */
    public static function cv6IconProvider(array &$providers): void
    {
        $providers['my_set'] = new \MyVendor\MyAddon\Icon\Provider\MySetProvider();
    }
}
```

### Step 2: Register the Code Event Listener

In XenForo AdminCP (or via `_output/code_event_listeners/`):
- **Event**: `cv6_icon_provider`
- **Execute callback**: `MyVendor\MyAddon\Listener::cv6IconProvider`

### Step 3: Implement the Provider Class

Extend `\cv6\Core\Icon\Provider\AbstractIconProvider` to inherit automatic CSS mask generation, prefix stripping, and style handling:

```php
namespace MyVendor\MyAddon\Icon\Provider;

use cv6\Core\Icon\Provider\AbstractIconProvider;

class MySetProvider extends AbstractIconProvider
{
    public function getId(): string
    {
        return 'my_set';
    }

    public function getTitle(): string
    {
        return 'My Icon Set';
    }

    public function getPrefix(): string
    {
        return 'ms-'; // Icons will be named e.g. ms-settings, ms-home
    }

    public function getBrandIcon(): string
    {
        return 'fas fa-icons'; // FontAwesome icon used in ACP dropdown
    }

    public function getBrandColor(): string
    {
        return '#007acc';
    }

    public function getStyles(): array
    {
        return [
            'mso' => 'Outlined',
            'msf' => 'Filled',
        ];
    }

    public function render(string $name, ?string $style = null, array $options = []): string
    {
        $cleanName = $this->normalizeName($name);
        $style = $style ?: $this->getDefaultStyle();

        // 1. Load your SVG content
        $svgContent = $this->loadSvg($cleanName, $style);
        if (!$svgContent) {
            return '';
        }

        // 2. Wrap or format with standard XenForo classes
        $classes = 'fa--xf ms-icon ' . ($options['class'] ?? '');
        $titleAttr = !empty($options['title']) ? ' title="' . htmlspecialchars($options['title']) . '"' : '';

        return '<span class="' . htmlspecialchars($classes) . '"' . $titleAttr . ' aria-hidden="true">'
            . $svgContent
            . '</span>';
    }

    public function search(string $query, int $limit = 30): array
    {
        // Return matching icons for autocomplete
        return [
            [
                'id' => 'ms-home',
                'text' => 'ms-home',
                'icon' => 'ms-home',
                'name' => 'home',
                'variant' => 'mso',
                'provider' => $this->getId(),
            ],
        ];
    }

    public function isValidIcon(string $name): bool
    {
        $cleanName = $this->normalizeName($name);
        return $this->iconExists($cleanName);
    }

    public function getMigrationMap(): array
    {
        // Map FontAwesome names to your provider's names
        return [
            'fa-cog' => 'ms-settings',
            'fa-home' => 'ms-home',
            'fa-user' => 'ms-account',
        ];
    }

    public function getStyleMap(string $targetProviderId): array
    {
        // Map your styles to other providers if needed
        return [
            'mso' => 'far',
            'msf' => 'fas',
        ];
    }
}
```

---

## 3. Core Features Available Automatically

### 1. Template Usage (`<xf:fa>`)
Once enabled, any template tag automatically supports your provider:
```html
<xf:fa icon="ms-settings" />
<xf:fa icon="mso ms-home" />
```

### 2. LESS / CSS Mask Variables
Your icons can be used in LESS stylesheets as CSS masks via `.m-faIcon()`:
```less
.my-custom-badge:before {
    .m-faIcon();
    content: @ms-var-settings;
}
```
`AbstractIconProvider::getMaskUrl()` automatically extracts the SVG, converts it to a standard black mask, and encodes it into a `data:image/svg+xml` URI.

### 3. Migration Wizard
Administrators can navigate to **AdminCP > Development > Icon Migration** (or **Tools > Icon Migration**) to migrate all content from Font Awesome (or any other provider) to your provider in 4 guided steps.
