<?php

namespace cv6\Core\Helper;

class Icon
{
    public static function getIconList(): array
    {
        $registry = \XF::registry();
        $icons = $registry->get('cv6IconList');

        if (!is_array($icons) || empty($icons) || !is_array(reset($icons))) {
            $icons = static::buildIconCache();
            $registry->set('cv6IconList', $icons);
        }

        return $icons;
    }

    public static function buildIconCache(): array
    {
        $faPath = \XF::getRootDirectory() . '/styles/fa/';
        $subDirs = ['brands', 'light', 'solid', 'regular', 'duotone'];
        $iconMap = [];

        foreach ($subDirs as $dir) {
            $fullPath = $faPath . $dir;
            if (is_dir($fullPath)) {
                $files = scandir($fullPath);
                if (is_array($files)) {
                    foreach ($files as $file) {
                        if (str_ends_with($file, '.svg')) {
                            $name = substr($file, 0, -4);
                            if (!isset($iconMap[$name])) {
                                $iconMap[$name] = [
                                    'name' => $name,
                                    'variant' => $dir,
                                    'variants' => [$dir => true]
                                ];
                            } else {
                                $iconMap[$name]['variants'][$dir] = true;
                            }
                        }
                    }
                }
            }
        }

        ksort($iconMap);
        return $iconMap;
    }

    public static function searchIcons(string $query, int $limit = 30): array
    {
        $query = strtolower(trim($query));
        if (str_starts_with($query, 'fa-')) {
            $query = substr($query, 3);
        }

        if (strlen($query) < 2) {
            return [];
        }

        $allowedStyles = (array) (\XF::options()->cv6CoreFaStyle ?? []);
        $variantToStyleMap = [
            'brands' => 'fab',
            'duotone' => 'fad',
            'light' => 'fal',
            'regular' => 'far',
            'solid' => 'fas'
        ];

        $iconMap = static::getIconList();
        $prefixMatches = [];
        $containsMatches = [];

        foreach ($iconMap as $name => $info) {
            if (!is_array($info)) {
                $name = (string) $info;
                $info = ['name' => $name, 'variant' => 'light', 'variants' => []];
            }

            $variants = $info['variants'] ?? [];
            if (!empty($variants)) {
                $hasAllowedVariant = false;
                foreach ($variants as $variantDir => $enabled) {
                    $styleKey = $variantToStyleMap[$variantDir] ?? null;
                    if ($styleKey && !empty($allowedStyles[$styleKey])) {
                        $hasAllowedVariant = true;
                        break;
                    }
                }
                if (!$hasAllowedVariant) {
                    continue;
                }
            }

            $item = [
                'id' => 'fa-' . $name,
                'text' => 'fa-' . $name,
                'icon' => 'fa-' . $name,
                'name' => $name,
                'variant' => $info['variant'] ?? 'light',
                'variants' => array_keys($variants)
            ];

            if (str_starts_with($name, $query)) {
                $prefixMatches[] = $item;
            } elseif (str_contains($name, $query)) {
                $containsMatches[] = $item;
            }

            if (count($prefixMatches) + count($containsMatches) >= $limit * 2) {
                break;
            }
        }

        $results = array_merge($prefixMatches, $containsMatches);
        return array_slice($results, 0, $limit);
    }
}
