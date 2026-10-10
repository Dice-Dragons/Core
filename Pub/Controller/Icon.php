<?php

declare(strict_types=1);

namespace cv6\Core\Pub\Controller;

use cv6\Core\Helper\Icon as IconHelper;
use cv6\Core\Repository\IconProvider as IconProviderRepo;
use XF\Mvc\Reply\AbstractReply;
use XF\Pub\Controller\AbstractController;

class Icon extends AbstractController
{
    public function actionAutoComplete(): AbstractReply
    {
        $q = $this->filter('q', 'str');
        $providerId = $this->filter('provider', 'str');

        /** @var IconProviderRepo $providerRepo */
        $providerRepo = $this->repository(IconProviderRepo::class);

        if ($providerId !== '') {
            $provider = $providerRepo->getActiveProvider($providerId);
            $results = $provider ? $provider->search($q) : [];
        } else {
            $provider = $providerRepo->getProviderForClasses($q);
            if ($provider) {
                $results = $provider->search($q);
            } else {
                $results = IconHelper::searchIcons($q);
                foreach ($results as &$result) {
                    $result['provider'] = 'fa';
                }
                unset($result);
            }
        }

        // Decorate results with rendered HTML preview if not yet present
        foreach ($results as &$result) {
            if (empty($result['html'])) {
                $resProviderId = $result['provider'] ?? ($provider ? $provider->getId() : 'fa');
                $resProvider = $providerRepo->getProvider($resProviderId);
                if ($resProvider) {
                    $iconName = $result['icon'] ?? $result['name'] ?? '';
                    $result['html'] = $resProvider->render($iconName, $result['variant'] ?? null, ['inline' => true]);
                }
            }
        }
        unset($result);

        $viewParams = [
            'results' => $results
        ];

        $reply = $this->view('cv6\Core:Icon\AutoComplete', '', $viewParams);
        $reply->setJsonParam('results', $results);
        return $reply;
    }

    public function actionPreviewIcon(): AbstractReply
    {
        $classes = $this->filter('classes', 'str');
        $providerId = $this->filter('provider', 'str');

        /** @var IconProviderRepo $providerRepo */
        $providerRepo = $this->repository(IconProviderRepo::class);

        if ($providerId !== '') {
            $provider = $providerRepo->getActiveProvider($providerId);
        } else {
            $provider = $providerRepo->getProviderForClasses($classes);
        }

        if ($provider) {
            $html = $provider->render($classes, null, ['inline' => true]);
        } else {
            $html = \XF::app()->iconRenderer()->render($classes, ['inline' => true]);
        }

        $reply = $this->view('cv6\Core:Icon\Preview', '');
        $reply->setJsonParam('html', $html);
        $reply->setJsonParam('iconHtml', $html);
        return $reply;
    }
}
