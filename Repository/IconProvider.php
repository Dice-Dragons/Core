<?php

namespace cv6\Core\Repository;

use cv6\Core\Icon\Provider\FontAwesomeProvider;
use cv6\Core\Icon\Provider\IconProviderInterface;
use XF\Mvc\Entity\Repository;

class IconProvider extends Repository
{
    /** @var array<string, IconProviderInterface>|null */
    protected ?array $providerCache = null;

    /**
     * @return array<string, IconProviderInterface>
     */
    public function getAllProviders(): array
    {
        if ($this->providerCache !== null) {
            return $this->providerCache;
        }

        $providers = [
            'fa' => new FontAwesomeProvider()
        ];

        \XF::fire('cv6_icon_provider', [&$providers]);

        $normalized = [];
        foreach ($providers as $key => $provider) {
            if (is_string($provider) && class_exists($provider)) {
                $provider = new $provider();
            }

            if ($provider instanceof IconProviderInterface) {
                $normalized[$provider->getId()] = $provider;
            }
        }

        $this->providerCache = $normalized;
        return $this->providerCache;
    }

    /**
     * @return array<string, IconProviderInterface>
     */
    public function getActiveProviders(): array
    {
        $all = $this->getAllProviders();
        $allowed = (array) (\XF::options()->cv6AllowedIconProviders ?? ['fa' => 1]);

        $active = [];
        foreach ($all as $id => $provider) {
            if (!empty($allowed[$id])) {
                $active[$id] = $provider;
            }
        }

        if (empty($active) && isset($all['fa'])) {
            $active['fa'] = $all['fa'];
        }

        return $active;
    }

    public function getProvider(string $id): ?IconProviderInterface
    {
        $all = $this->getAllProviders();
        return $all[$id] ?? null;
    }

    public function getActiveProvider(string $id): ?IconProviderInterface
    {
        $active = $this->getActiveProviders();
        return $active[$id] ?? null;
    }

    public function getProviderForClasses(string $classes): ?IconProviderInterface
    {
        $all = $this->getAllProviders();

        // Check non-default providers first (e.g. google with gm-)
        foreach ($all as $id => $provider) {
            if ($id !== 'fa' && $provider->matchesClasses($classes)) {
                return $provider;
            }
        }

        // Fall back to FA if it matches or as universal default
        if (isset($all['fa']) && $all['fa']->matchesClasses($classes)) {
            return $all['fa'];
        }

        return null;
    }

    public function getProviderByPrefix(string $prefix): ?IconProviderInterface
    {
        $prefix = rtrim(strtolower($prefix), '-') . '-';
        $all = $this->getAllProviders();
        foreach ($all as $provider) {
            if (rtrim(strtolower($provider->getPrefix()), '-') . '-' === $prefix) {
                return $provider;
            }
        }

        return null;
    }
}
