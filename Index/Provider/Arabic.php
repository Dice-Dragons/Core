<?php

namespace cv6\Core\Index\Provider;

use XF\Mvc\Entity\Finder;
use XF\Phrase;

class Arabic extends AbstractProvider
{
    /** @var array<string, string[]> */
    protected array $variantMap = [
        'ا' => ['أ', 'إ', 'آ', 'ٱ', 'ء'],
        'ب' => ['پ'],
        'ت' => ['ة', 'ٹ'],
        'ج' => ['چ'],
        'د' => ['ڈ'],
        'ر' => ['ڑ'],
        'ز' => ['ژ'],
        'ك' => ['گ'],
        'ن' => ['ں'],
        'ه' => ['ہ', 'ھ'],
        'و' => ['ؤ'],
        'ي' => ['ى', 'ئ', 'ؠ', 'ی', 'ے', 'ۓ'],
    ];

    /** @var array<string, string>|null */
    protected ?array $reverseMap = null;

    public function getId(): string
    {
        return 'arabic';
    }

    public function getTitle(): Phrase|string
    {
        return \XF::phrase('cv6_core_alphabet_arabic');
    }

    public function getLetters(): array
    {
        return [
            'ا', 'ب', 'ت', 'ث', 'ج', 'ح', 'خ', 'د', 'ذ', 'ر',
            'ز', 'س', 'ش', 'ص', 'ض', 'ط', 'ظ', 'ع', 'غ', 'ف',
            'ق', 'ك', 'ل', 'م', 'ن', 'ه', 'و', 'ي',
        ];
    }

    public function isRtl(): bool
    {
        return true;
    }

    public function isNumber(string $character): bool
    {
        return is_numeric($character) || (bool)preg_match('/^[٠-٩۰-۹]/u', $character);
    }

    public function normalizeLetter(string $character): string
    {
        $cleaned = preg_replace('/[\x{064B}-\x{065F}\x{0640}]/u', '', $character);
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

    public function applyNumberFilter(Finder $finder, string $column): void
    {
        $finder->whereSql($column . ' REGEXP "^[0-9٠-٩۰-۹]"');
    }

    public function applyOtherFilter(Finder $finder, string $column): void
    {
        $allVariants = [];
        foreach ($this->variantMap as $variants)
        {
            foreach ($variants as $v)
            {
                $allVariants[] = $v;
            }
        }
        $variantChars = implode('', array_unique($allVariants));
        $finder->whereSql($column . ' REGEXP "^[^0-9٠-٩۰-۹ا-ي' . $variantChars . ']"');
    }
}
