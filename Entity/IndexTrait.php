<?php

namespace cv6\Core\Entity;

use cv6\Core\Index\Provider\AbstractProvider;
use cv6\Core\Repository\Alphabet;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property bool cv6_indexable
 */
trait IndexTrait
{
    private $indexColumn = null;

    private $createdIndex = [
        0 => null,
        1 => null
    ];


    abstract function getIndexColumn();

    public function getIndexTable() {
        return $this->getStructure()->table;
    }

    public function displayIndex()
    {
        return (bool) ($this->cv6_display_index && $this->hasIndex());
    }

    public function hasIndex()
    {
        return (bool) $this->cv6_indexable;
    }

    public function getAlphabetProvider(): AbstractProvider
    {
        $alphabetId = $this->getIndexAlphabetId();
        return $this->repository(Alphabet::class)->getProvider($alphabetId);
    }

    public function getIndexAlphabetId(): ?string
    {
        if (isset($this->cv6_index_alphabet))
        {
            return (string)$this->cv6_index_alphabet;
        }

        if (isset($this->cv6_alphabet_id))
        {
            return (string)$this->cv6_alphabet_id;
        }

        return null;
    }

    public function hasNumberTab(): bool
    {
        if (isset($this->cv6_index_number_tab))
        {
            return (bool)$this->cv6_index_number_tab;
        }

        $option = \XF::options()->cv6ShowNumberTab;
        return !empty($option['enabled']);
    }

    public function fetchLetterCounter()
    {
        $result = $this->db()->fetchPairs("
			SELECT UPPER(SUBSTRING(".$this->getIndexColumn().",1,1)) AS letter, COUNT(*) AS c FROM ". $this->getIndexTable()."
			GROUP BY letter;");

        return $result;
    }

    public function indexWhere(Finder &$finder)
    {
    }

    public function indexWith(Finder &$finder)
    {
    }

    public function fetchLetterIndex(int $withCounter = 0, ?Finder &$finder = null)
    {
        $withCounter = ($withCounter == 1) ? 1 : 0;
        if ($this->createdIndex[$withCounter] === null)
        {

            if (!$this->hasIndex()) 
            {
                return [
                    'show' => false,
                    'list' => [],
                    'letter' => false,
                    'counter' => false
                ];

            }

            $provider = $this->getAlphabetProvider();
            $letterIndex = $provider->getLetters();
            $hide = array_flip(array_merge(['0','_'],$letterIndex));

            if ($withCounter == 1) {
                $index = $this->fetchLetterCounter();
                $indexCounter = [];
                foreach ($index as $character => $count) 
                {
                    $character = $provider->normalizeLetter(mb_substr((string)$character, 0, 1, 'UTF-8'));
                    if ($provider->isNumber($character)) 
                    {
                        if (!array_key_exists('0', $indexCounter)) 
                        {
                            $indexCounter['0'] = $count;
                        } else 
                        {
                            $indexCounter['0'] += $count;
                        }
                        unset($hide["0"]);
                    } 
                    else if ($provider->isLetter($character)) 
                    {
                        if (!array_key_exists($character, $indexCounter))
                        {
                            $indexCounter[$character] = $count;
                        }
                        else
                        {
                            $indexCounter[$character] += $count;
                        }
                        unset($hide[$character]);
                    } 
                    else 
                    {
                        if (!array_key_exists('_', $indexCounter)) 
                        {
                            $indexCounter['_'] = $count;
                        } else 
                        {
                            $indexCounter['_'] += $count;
                        }
                        unset($hide['_']);
                    }
                }
                unset($index);
            } else 
            {
                $indexCounter = false;
            }

            $letter = \XF::app()->request()->filter('letter', 'str', '-');
            $letter = $provider->normalizeLetter($letter);

            if ($provider->isLetter($letter)) 
            {
                if ($finder !== null)
                {
                    $provider->applyLetterFilter($finder, $this->getIndexColumn(), $letter);
                }
            } 
            elseif ($letter === '0-9') 
            {
                if ($finder !== null)
                {
                    $provider->applyNumberFilter($finder, $this->getIndexColumn());
                }
            } 
            elseif ($letter === '_') 
            {
                if ($finder !== null)
                {
                    $provider->applyOtherFilter($finder, $this->getIndexColumn());
                }
            } 
            else 
            {
                $letter = false;
            }
            $showIndex = true;

            $this->createdIndex[$withCounter] = [
                'show' => $showIndex,
                'list' => $letterIndex,
                'hide' => array_flip($hide),
                'letter' => $letter,
                'counter' => $indexCounter,
                'provider' => $provider,
                'hasNumberTab' => $this->hasNumberTab(),
                'is_rtl' => $provider->isRtl(),
            ];
        }
        return $this->createdIndex[$withCounter];
    }

    public static function addIndexableStructureElements(Structure $structure)
    {
        $structure->columns['cv6_indexable'] = ['type' => Entity::BOOL, 'default' => false];
        $structure->columns['cv6_display_index'] = ['type' => Entity::BOOL, 'default' => false];
    }

}