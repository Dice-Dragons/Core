<?php

namespace cv6\Core\Entity;

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

    public function fetchLetterCounter()
    {
        $result = $this->db()->fetchPairs("
			SELECT UPPER(SUBSTR(".$this->getIndexColumn().",1,1)) AS letter, COUNT(*) AS c FROM ". $this->getIndexTable()."
			GROUP BY letter;");

        return $result;
    }

    public function indexWhere(Finder &$finder)
    {
    }

    public function indexWith(Finder &$finder)
    {
    }

    public function fetchLetterIndex(int $withCounter = 0, Finder &$finder = null)
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

            $letterIndex = range('A', 'Z');
            $hide = array_flip(array_merge(['0','_'],$letterIndex));
            if ($withCounter == 1) {
                $index = $this->fetchLetterCounter();
                $indexCounter = [];
                foreach ($index as $character => $count) 
                {
                    if (is_numeric($character)) 
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
                    else if (in_array($character, $letterIndex)) 
                    {
                        $indexCounter[$character] = $count;
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

            $letter = strtoupper(\XF::app()->request()->filter('letter', 'str', '-'));

            if (in_array($letter, $letterIndex)) 
            {
                if ($finder !== null)
                {
                    $finder->where($this->getIndexColumn(), 'LIKE', $letter . '%');
                }
            } 
            elseif ($letter == '0-9') 
            {
                if ($finder !== null)
                {
                    $finder->whereSql($this->getIndexColumn().' REGEXP "^[0-9]"');
                }
            } 
            elseif ($letter == '_') 
            {
                if ($finder !== null)
                {
                    $finder->whereSql($this->getIndexColumn() . ' REGEXP "^[^a-zA-Z0-9]"');
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
                'counter' => $indexCounter
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