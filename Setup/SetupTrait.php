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
}

