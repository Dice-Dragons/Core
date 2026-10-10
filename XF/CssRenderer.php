<?php

declare(strict_types=1);

namespace cv6\Core\XF;

class CssRenderer extends XFCP_CssRenderer
{
    protected function processIconVariables(string $contents): string
    {
        $contents = parent::processIconVariables($contents);

        $pattern = '/@(?P<prefix>[a-z0-9]+)-var-((?P<variant>[a-z0-9_-]+)-)?(?P<name>[a-z0-9_-]+)/i';
        $replaceCallback = function (array $matches) {
            $prefix = strtolower($matches['prefix']);
            if ($prefix === 'fa') {
                return $matches[0];
            }

            /** @var \cv6\Core\Repository\IconProvider $providerRepo */
            $providerRepo = $this->app->repository(\cv6\Core\Repository\IconProvider::class);
            $provider = $providerRepo->getProviderByPrefix($prefix . '-');
            if (!$provider) {
                return $matches[0];
            }

            $variant = !empty($matches['variant']) ? $matches['variant'] : null;
            $name = $matches['name'];

            $maskUrl = $provider->getMaskUrl($name, $variant);
            return $maskUrl ?? $matches[0];
        };

        $contents = (string) preg_replace_callback($pattern, $replaceCallback, $contents);

        $interpPattern = '/\"[^\"]*@{(?P<prefix>[a-z0-9]+)-var-((?P<variant>[a-z0-9_-]+)-)?(?P<name>[a-z0-9_-]+)}[^\"]*\"/i';
        $contents = (string) preg_replace_callback($interpPattern, $replaceCallback, $contents);

        return $contents;
    }
}
