<?php

declare(strict_types=1);

namespace cv6\Core\XF\Data;

use cv6\Core\Repository\IconProvider as IconProviderRepo;

class Editor extends XFCP_Editor
{
    public function getButtonsFromCustomBbCodes($bbCodes): array
    {
        $buttons = parent::getButtonsFromCustomBbCodes($bbCodes);

        /** @var IconProviderRepo $providerRepo */
        $providerRepo = \XF::repository(IconProviderRepo::class);

        foreach ($bbCodes as $bbCodeId => $bbCode) {
            $key = 'xfCustom_' . $bbCodeId;
            if (!isset($buttons[$key]['fa'])) {
                continue;
            }

            $rawIcon = (string) $bbCode->editor_icon_value;
            if ($rawIcon === '') {
                continue;
            }

            $provider = $providerRepo->getProviderForClasses($rawIcon);
            if ($provider !== null && $provider->getId() !== 'fa') {
                $buttons[$key]['fa'] = $rawIcon;
            }
        }

        return $buttons;
    }
}
