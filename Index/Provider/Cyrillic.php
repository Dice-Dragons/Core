<?php

namespace cv6\Core\Index\Provider;

use XF\Mvc\Entity\Finder;
use XF\Phrase;

class Cyrillic extends AbstractProvider
{
    /** @var array<string, string[]> */
    protected array $variantMap = [
        'Г' => ['Ґ', 'Ғ', 'Ѓ'],
        'Д' => ['Ђ'],
        'Е' => ['Є'],
        'Ж' => ['Ӂ', 'Җ'],
        'З' => ['Ѕ', 'Ҙ'],
        'И' => ['І', 'Ї', 'Ӣ'],
        'К' => ['Қ', 'Ҡ', 'Ҟ', 'Ҝ'],
        'Л' => ['Љ'],
        'Н' => ['Њ', 'Ң', 'Ҥ'],
        'О' => ['Ө'],
        'У' => ['Ў', 'Ү', 'Ӯ', 'Ұ'],
        'Х' => ['Һ', 'Ҳ'],
        'Ц' => ['Ҵ'],
        'Ч' => ['Ҷ', 'Ҹ', 'Џ', 'Ћ'],
    ];

    /** @var array<string, string>|null */
    protected ?array $reverseMap = null;

    public function getId(): string
    {
        return 'cyrillic';
    }

    public function getTitle(): Phrase|string
    {
        return \XF::phrase('cv6_core_alphabet_cyrillic');
    }

    public function getLetters(): array
    {
        return [
            'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й',
            'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф',
            'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я',
        ];
    }

    public function normalizeLetter(string $character): string
    {
        $character = mb_strtoupper($character, 'UTF-8');

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
        $allVariants = [];
        foreach ($this->variantMap as $variants)
        {
            foreach ($variants as $v)
            {
                $allVariants[] = $v;
                $allVariants[] = mb_strtolower($v, 'UTF-8');
            }
        }
        $variantChars = implode('', $allVariants);
        $finder->whereSql($column . ' REGEXP "^[^0-9А-Яа-яЁё' . $variantChars . ']"');
    }
}
