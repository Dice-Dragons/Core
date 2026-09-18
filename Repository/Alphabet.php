<?php

declare(strict_types=1);

namespace cv6\Core\Repository;

use cv6\Core\Index\Provider\AbstractProvider;
use cv6\Core\Index\Provider\Arabic;
use cv6\Core\Index\Provider\Cyrillic;
use cv6\Core\Index\Provider\Greek;
use cv6\Core\Index\Provider\Hebrew;
use cv6\Core\Index\Provider\Latin;
use XF\Mvc\Entity\Repository;
use XF\Phrase;

class Alphabet extends Repository
{
    /** @var array<string, AbstractProvider>|null */
    protected ?array $providers = null;

    /**
     * @return array<string, class-string<AbstractProvider>>
     */
    public function getDefaultProviders(): array
    {
        return [
            'latin' => Latin::class,
            'greek' => Greek::class,
            'cyrillic' => Cyrillic::class,
            'hebrew' => Hebrew::class,
            'arabic' => Arabic::class,
        ];
    }

    /**
     * @return array<string, AbstractProvider>
     */
    public function getProviders(): array
    {
        if ($this->providers === null)
        {
            $this->providers = [];
            $classes = $this->getDefaultProviders();

            \XF::fire('cv6_core_index_alphabets', [&$classes]);

            foreach ($classes as $id => $class)
            {
                if (is_string($class) && class_exists($class) && is_subclass_of($class, AbstractProvider::class))
                {
                    $this->providers[$id] = new $class();
                }
                elseif ($class instanceof AbstractProvider)
                {
                    $this->providers[$id] = $class;
                }
            }
        }

        return $this->providers;
    }

    public function getProvider(?string $id = null): AbstractProvider
    {
        $providers = $this->getProviders();

        if ($id !== null && isset($providers[$id]))
        {
            return $providers[$id];
        }

        return $providers['latin'] ?? new Latin();
    }

    /**
     * @return array<string, string|Phrase>
     */
    public function getProviderOptions(): array
    {
        $options = [];
        foreach ($this->getProviders() as $id => $provider)
        {
            $options[$id] = $provider->getTitle();
        }
        return $options;
    }

    public function reset(): void
    {
        $this->providers = null;
    }
}
