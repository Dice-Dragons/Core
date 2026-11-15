<?php

declare(strict_types=1);

namespace cv6\Core\XF;

use cv6\Core\Icon\Provider\IconProviderInterface;
use cv6\Core\Repository\IconProvider as IconProviderRepo;

use function array_keys;
use function htmlspecialchars;
use function implode;
use function in_array;
use function is_array;
use function preg_split;
use function str_starts_with;
use function substr;
use function trim;

class IconRenderer extends XFCP_IconRenderer
{
    public function render(string $iconClasses, array $options = []): string
    {
        /** @var IconProviderRepo $providerRepo */
        $providerRepo = $this->app->repository(IconProviderRepo::class);
        $provider = $providerRepo->getProviderForClasses($iconClasses);

        // If no provider or default FA provider, use standard XenForo rendering
        if ($provider === null || $provider->getId() === 'fa') {
            return parent::render($iconClasses, $options);
        }

        // Check if provider is currently active
        $activeProvider = $providerRepo->getActiveProvider($provider->getId());
        if ($activeProvider === null) {
            // Disabled or unavailable provider fallback: subtle placeholder to prevent layout breakage
            return $this->renderMissingProviderFallback($iconClasses, $options);
        }

        $cleanClasses = $this->cleanProviderClasses($iconClasses, $activeProvider);
        $options = $this->standardizeOptions($cleanClasses, $options);

        // Delegate to custom provider
        return $activeProvider->render($cleanClasses, null, $options);
    }

    protected function cleanProviderClasses(string $classes, IconProviderInterface $provider): string
    {
        $tokens = preg_split('/\s+/', trim($classes));
        if (!is_array($tokens)) {
            return $classes;
        }

        $prefix = $provider->getPrefix();
        $styles = array_keys($provider->getStyles());

        $cleaned = [];
        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '') {
                continue;
            }

            if ($prefix !== '' && str_starts_with($token, 'fa-' . $prefix)) {
                $cleaned[] = substr($token, 3);
            } elseif (str_starts_with($token, 'fa-') && in_array(substr($token, 3), $styles, true)) {
                $cleaned[] = substr($token, 3);
            } else {
                $cleaned[] = $token;
            }
        }

        return implode(' ', $cleaned);
    }

    protected function renderMissingProviderFallback(string $iconClasses, array $options = []): string
    {
        $title = \XF::phrase('cv6_core_icon_provider_missing_explain', ['classes' => $iconClasses]);
        $fallbackClasses = 'fa--xf far fa-question-circle cv6-icon--missing ' . ($options['class'] ?? '');
        $titleAttr = htmlspecialchars((string) $title);
        $classAttr = htmlspecialchars(trim($fallbackClasses));

        return '<i class="' . $classAttr . '" title="' . $titleAttr . '" data-xf-init="tooltip" aria-hidden="true"></i>';
    }
}
