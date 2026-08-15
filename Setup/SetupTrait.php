<?php

namespace cv6\Core\Setup;

use XF\Db\Schema\Alter;

trait SetupTrait
{

    protected array $indexDefinitions = [];

    /**
     * inherit from this for the tables which need to be created.
     */
    public function getTables($tableName = null)
    {
        return [];
    }

    public function getInitialData($tableName = null)
    {
        return [];
    }

    protected function createTables($singleTable = null)
    {
        $sm = $this->schemaManager();
        
        foreach ($this->getTables() as $tableName => $closure)
        {
            if (!$sm->tableExists($tableName) && (!$singleTable || $singleTable === $tableName))
            {
                $sm->createTable($tableName, $closure);
            }      
        }
    }

    protected function dropTables()
    {
        $sm = $this->schemaManager();

        foreach (array_keys($this->getTables()) as $tableName)
        {
            if ($sm->tableExists($tableName)) 
            {
                $sm->dropTable($tableName);
            }
        }
    }    

    protected function indexExists(string $tableName, string $indexName): bool
    {
        $sm = $this->schemaManager();
        if (!$sm->tableExists($tableName))
        {
            return false;
        }

        $indexes = $sm->getTableIndexDefinitions($tableName);
        return isset($indexes[$indexName]);
    }

    protected function addIndex(string $tableName, string $indexName, $columns)
    {
        $sm = $this->schemaManager();

        if ($sm->tableExists($tableName) && !$this->indexExists($tableName, $indexName))
        {
            $sm->alterTable($tableName, function (Alter $table) use ($indexName, $columns) {
                $table->addKey($columns, $indexName);
            });
        }
    }

    protected function insertInitialData($tableName = null) 
    {
        $db = $this->db();

        foreach ($this->getInitialData() as $tableName => $query)
        {
            $db->query($query);
        }
    }

    protected function syncGroupPhrases(string $tableName, string $phrasePrefix, ?string $addonId = null): void
    {
        $db = $this->db();
        $sm = $this->schemaManager();

        if (!$sm->tableExists($tableName))
        {
            return;
        }

        $groups = $db->fetchAll("SELECT group_id, title FROM {$tableName}");
        if (empty($groups))
        {
            return;
        }

        $phraseTitles = [];
        foreach ($groups as $group)
        {
            $phraseTitles[] = $phrasePrefix . '.' . $group['group_id'];
        }

        $existing = $db->fetchPairs("
            SELECT title, phrase_id 
            FROM xf_phrase 
            WHERE title IN (" . $db->quote($phraseTitles) . ") AND language_id = 0
        ");

        $addonId = $addonId ?: ($this->addOn ? $this->addOn->getAddOnId() : '');

        foreach ($groups as $group)
        {
            $phraseTitle = $phrasePrefix . '.' . $group['group_id'];
            if (!isset($existing[$phraseTitle]) && !empty($group['title']))
            {
                /** @var \XF\Entity\Phrase $phrase */
                $phrase = \XF::em()->create('XF:Phrase');
                $phrase->title = $phraseTitle;
                $phrase->phrase_text = (string)$group['title'];
                $phrase->language_id = 0;
                $phrase->addon_id = $addonId;
                $phrase->save(false, false);
            }
        }
    }
}

