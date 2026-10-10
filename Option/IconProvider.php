<?php

declare(strict_types=1);

namespace cv6\Core\Option;

use cv6\Core\Repository\IconProvider as IconProviderRepo;
use XF\Entity\Option;
use XF\Option\AbstractOption;

class IconProvider extends AbstractOption
{
    public static function renderCheckbox(Option $option, array $htmlParams): string
    {
        /** @var IconProviderRepo $providerRepo */
        $providerRepo = \XF::repository(IconProviderRepo::class);
        $providers = $providerRepo->getAllProviders();

        $choices = [];
        $optionValue = (array) ($option->option_value ?: []);

        foreach ($providers as $id => $provider) {
            $label = $provider->getTitle();
            $hint = $id === 'fa' ? (string) \XF::phrase('cv6_core_icon_provider_fa_hint') : '';

            $choices[$id] = [
                'name' => "{$htmlParams['inputName']}[{$id}]",
                'value' => 1,
                'selected' => !empty($optionValue[$id]),
                'label' => $label,
                'hint' => $hint,
            ];
        }

        $rowOptions = static::getRowOptions($option, $htmlParams);

        return static::getTemplater()->formCheckBoxRow(
            [],
            $choices,
            $rowOptions
        );
    }

    public static function verifyOption(&$value, Option $option, string $optionId): bool
    {
        if (!is_array($value)) {
            $value = [];
        }

        $clean = [];
        foreach ($value as $key => $selected) {
            if ($selected) {
                $clean[(string) $key] = true;
            }
        }

        // Font Awesome is the default core fallback; ensure at least one provider is active
        if (empty($clean)) {
            $clean['fa'] = true;
        }

        $value = $clean;

        return true;
    }
}
