<?php

namespace cv6\Core\Index\Provider;

use XF\Mvc\Entity\Finder;
use XF\Phrase;

class Hebrew extends AbstractProvider
{
    /** @var array<string, string[]> */
    protected array $variantMap = [
        'כ' => ['ך'],
        'מ' => ['ם'],
        'נ' => ['ן'],
        'פ' => ['ף'],
        'צ' => ['ץ'],
    ];

    /** @var array<string, string>|null */
    protected ?array $reverseMap = null;

    public function getId(): string
    {
        return 'hebrew';
    }

    public function getTitle(): Phrase|string
    {
        return \XF::phrase('cv6_core_alphabet_hebrew');
    }

    public function getLetters(): array
    {
        return [
            'א', 'ב', 'ג', 'ד', 'ה', 'ו', 'ז', 'ח', 'ט', 'י',
            'כ', 'ל', 'מ', 'נ', 'ס', 'ע', 'פ', 'צ', 'ק', 'ר',
            'ש', 'ת',
        ];
    }

    public function isRtl(): bool
    {
        return true;
    }

    public function normalizeLetter(string $character): string
    {
        $cleaned = preg_replace('/[\x{0591}-\x{05C7}]/u', '', $character);
        if ($cleaned !== null && $cleaned !== '')
        {
            $character = $cleaned;
        }

        $character = mb_substr($character, 0, 1, 'UTF-8');

        if ($this->reverseMap === null)
        {
            $this->reverseMap = [];
            foreach ($this->variantMap as $base => $variants)
            {
                foreach ($variants as $variant)
                {
                    $this->reverseMap[$variant] = $base;
                }
            }
        }

        return $this->reverseMap[$character] ?? $character;
    }

    /**
     * @return string[]
     */
    public function getVariantsForLetter(string $letter): array
    {
        $base = $this->normalizeLetter($letter);
        $variants = $this->variantMap[$base] ?? [];
        return array_merge([$base], $variants);
    }

    public function applyLetterFilter(Finder $finder, string $column, string $letter): void
    {
        $variants = $this->getVariantsForLetter($letter);
        if (count($variants) > 1)
        {
            $db = \XF::db();
            $conditions = [];
            foreach ($variants as $variant)
            {
                $escaped = $finder->escapeLike($variant, '?%');
                $conditions[] = $column . ' LIKE ' . $db->quote($escaped);
            }
            $finder->whereSql('(' . implode(' OR ', $conditions) . ')');
        }
        else
        {
            parent::applyLetterFilter($finder, $column, $letter);
        }
    }

    public function applyOtherFilter(Finder $finder, string $column): void
    {
        $finder->whereSql($column . ' REGEXP "^[^0-9א-ת]"');
    }
}
