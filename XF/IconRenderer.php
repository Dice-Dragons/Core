<?php

declare(strict_types=1);

namespace cv6\Core\XF;

use cv6\Core\Repository\IconProvider as IconProviderRepo;

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

        $options = $this->standardizeOptions($iconClasses, $options);

        // Check if provider is currently active
        $activeProvider = $providerRepo->getActiveProvider($provider->getId());
        if ($activeProvider === null) {
            // Disabled or unavailable provider fallback: subtle placeholder to prevent layout breakage
            return $this->renderMissingProviderFallback($iconClasses, $options);
        }

        // Delegate to custom provider
        return $activeProvider->render($iconClasses, null, $options);
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
