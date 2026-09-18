<?php

namespace cv6\Core\Index\Provider;

use XF\Mvc\Entity\Finder;
use XF\Phrase;

class Latin extends AbstractProvider
{
    public function getId(): string
    {
        return 'latin';
    }

    public function getTitle(): Phrase|string
    {
        return \XF::phrase('cv6_core_alphabet_latin');
    }

    public function getLetters(): array
    {
        return range('A', 'Z');
    }

    public function applyOtherFilter(Finder $finder, string $column): void
    {
        $finder->whereSql($column . ' REGEXP "^[^a-zA-Z0-9]"');
    }
}
