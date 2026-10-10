<?php

namespace cv6\Core\Icon\Provider;

use cv6\Core\Helper\Icon as IconHelper;

class FontAwesomeProvider extends AbstractIconProvider
{
    public function getId(): string
    {
        return 'fa';
    }

    public function getTitle(): string
    {
        return 'Font Awesome';
    }

    public function getPrefix(): string
    {
        return 'fa-';
    }

    public function getBrandIcon(): string
    {
        return 'fab fa-font-awesome-flag';
    }

    public function getBrandColor(): string
    {
        return '#2b82e2';
    }

    public function getStyles(): array
    {
        return [
            'fas' => 'Solid',
            'far' => 'Regular',
            'fal' => 'Light',
            'fad' => 'Duotone',
            'fab' => 'Brands'
        ];
    }

    public function render(string $name, ?string $style = null, array $options = []): string
    {
        $fullName = str_starts_with($name, 'fa-') ? $name : 'fa-' . $name;
        $classes = trim(($style ? $style . ' ' : '') . $fullName);

        try {
            $iconRenderer = \XF::app()->iconRenderer();
            return $iconRenderer->render($classes, $options);
        } catch (\Throwable $e) {
            $classAttr = htmlspecialchars($classes . (!empty($options['class']) ? ' ' . $options['class'] : ''));
            return '<i class="fa--xf ' . $classAttr . '" aria-hidden="true"></i>';
        }
    }

    public function search(string $query, int $limit = 30): array
    {
        $results = IconHelper::searchIcons($query, $limit);
        foreach ($results as &$result) {
            $result['provider'] = 'fa';
        }
        unset($result);

        return $results;
    }

    public function isValidIcon(string $name): bool
    {
        $cleanName = $this->normalizeName($name);
        $list = IconHelper::getIconList();
        return isset($list[$cleanName]) || in_array($cleanName, $list, true);
    }

    public function getMaskUrl(string $name, ?string $style = null): ?string
    {
        $cleanName = $this->normalizeName($name);
        $styles = $this->getStyles();
        $variant = $style ? ($styles[$style] ?? $style) : null;
        if ($variant === null) {
            $variant = 'regular';
        }
        $variant = strtolower($variant);
        try {
            $path = \XF::app()->repository(\XF\Repository\IconRepository::class)->getStandaloneIconUrl($variant, $cleanName);
            return "url('{$path}')";
        } catch (\Throwable $e) {
            return null;
        }
    }
}
