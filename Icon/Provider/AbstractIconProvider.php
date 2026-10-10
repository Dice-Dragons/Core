<?php

namespace cv6\Core\Icon\Provider;

abstract class AbstractIconProvider implements IconProviderInterface
{
    public function normalizeName(string $name): string
    {
        $prefix = $this->getPrefix();
        if ($prefix !== '' && str_starts_with($name, $prefix)) {
            return substr($name, strlen($prefix));
        }

        return $name;
    }

    public function matchesClasses(string $classes): bool
    {
        $tokens = preg_split('/\s+/', trim($classes));
        if (!is_array($tokens)) {
            return false;
        }

        $prefix = $this->getPrefix();
        $styles = array_keys($this->getStyles());

        foreach ($tokens as $token) {
            if ($prefix !== '' && str_starts_with($token, $prefix)) {
                return true;
            }
            if (in_array($token, $styles, true)) {
                return true;
            }
        }

        return false;
    }

    public function getDefaultStyle(): string
    {
        $styles = array_keys($this->getStyles());
        return $styles[0] ?? '';
    }

    public function isValidIcon(string $name): bool
    {
        return true;
    }

    public function getMigrationMap(): array
    {
        return [];
    }

    public function getStyleMap(string $targetProviderId): array
    {
        return [];
    }

    public function getIncomingStyleMap(string $sourceProviderId): array
    {
        return [];
    }

    public function getMaskUrl(string $name, ?string $style = null): ?string
    {
        $html = $this->render($name, $style, ['inline' => true]);
        if (preg_match('/<svg\b[^>]*>(.*?)<\/svg>/is', $html, $matches)) {
            $svgTag = $matches[0];
            if (!str_contains($svgTag, 'xmlns=')) {
                $svgTag = str_replace('<svg ', '<svg xmlns="http://www.w3.org/2000/svg" ', $svgTag);
            }
            $svgTag = (string) preg_replace('/<title>.*?<\/title>/is', '', $svgTag);
            $svgTag = str_replace('fill="currentColor"', 'fill="black"', $svgTag);
            $dataUri = 'data:image/svg+xml;utf8,' . rawurlencode($svgTag);
            return "url('{$dataUri}')";
        }

        return null;
    }
}
