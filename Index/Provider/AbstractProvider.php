<?php

namespace cv6\Core\Index\Provider;

use XF\Mvc\Entity\Finder;
use XF\Phrase;

abstract class AbstractProvider
{
    abstract public function getId(): string;

    abstract public function getTitle(): Phrase|string;

    /**
     * @return string[]
     */
    abstract public function getLetters(): array;

    public function normalizeLetter(string $character): string
    {
        return mb_strtoupper($character, 'UTF-8');
    }

    public function isLetter(string $character): bool
    {
        return in_array($this->normalizeLetter($character), $this->getLetters(), true);
    }

    public function applyLetterFilter(Finder $finder, string $column, string $letter): void
    {
        $escaped = $finder->escapeLike($letter, '?%');
        $finder->whereSql($column . ' LIKE ' . \XF::db()->quote($escaped));
    }

    public function applyNumberFilter(Finder $finder, string $column): void
    {
        $finder->whereSql($column . ' REGEXP "^[0-9]"');
    }

    public function isNumber(string $character): bool
    {
        return is_numeric($character);
    }

    public function isRtl(): bool
    {
        return false;
    }

    abstract public function applyOtherFilter(Finder $finder, string $column): void;
}
