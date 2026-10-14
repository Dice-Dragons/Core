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

    /**
     * Renames a table if the old table exists and the new table does not yet exist.
     */
    protected function renameTableIfExists(string $oldTableName, string $newTableName): bool
    {
        $sm = $this->schemaManager();
        if (!$this->tableExists($oldTableName) || $this->tableExists($newTableName))
        {
            return false;
        }

        $sm->renameTable($oldTableName, $newTableName);
        return true;
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

    /**
     * Drops one or more indexes from a table if the table and the indexes exist.
     */
    protected function dropIndexIfExists(string $tableName, string|array $indexNames): bool
    {
        if (!$this->tableExists($tableName))
        {
            return false;
        }

        $indexNames = (array)$indexNames;
        $toDrop = array_filter($indexNames, fn(string $idx): bool => $this->indexExists($tableName, $idx));

        if (empty($toDrop))
        {
            return false;
        }

        $this->schemaManager()->alterTable($tableName, function (Alter $table) use ($toDrop): void
        {
            $table->dropIndexes($toDrop);
        });

        return true;
    }

    /**
     * Adds a column only if the table exists and the column is still missing.
     * Note: tableExists() and columnExists() are inherited from XF\AddOn\AbstractSetup (InstallHelperTrait).
     *
     * @param callable(Alter): mixed $definition Receives the Alter object and must call addColumn(...)
     */
    protected function addColumnIfMissing(string $tableName, string $columnName, callable $definition): bool
    {
        if (!$this->tableExists($tableName) || $this->columnExists($tableName, $columnName))
        {
            return false;
        }

        $this->schemaManager()->alterTable($tableName, function (Alter $table) use ($definition): void
        {
            $definition($table);
        });

        return true;
    }

    /**
     * Adds multiple columns to a table in a single ALTER TABLE query, skipping columns that already exist.
     *
     * @param array<string, callable(Alter): void> $columns Map of [column_name => callable]
     * @return int Number of columns added
     */
    protected function addColumnsIfMissing(string $tableName, array $columns): int
    {
        if (!$this->tableExists($tableName) || empty($columns))
        {
            return 0;
        }

        $missing = [];
        foreach ($columns as $columnName => $definition)
        {
            if (!$this->columnExists($tableName, $columnName))
            {
                $missing[$columnName] = $definition;
            }
        }

        if (empty($missing))
        {
            return 0;
        }

        $this->schemaManager()->alterTable($tableName, function (Alter $table) use ($missing): void
        {
            foreach ($missing as $definition)
            {
                $definition($table);
            }
        });

        return count($missing);
    }

    /**
     * Drops one or more columns from a table if the table and the columns exist.
     */
    protected function dropColumnIfExists(string $tableName, string|array $columnNames): bool
    {
        if (!$this->tableExists($tableName))
        {
            return false;
        }

        $columnNames = (array)$columnNames;
        $toDrop = array_filter($columnNames, fn(string $col): bool => $this->columnExists($tableName, $col));

        if (empty($toDrop))
        {
            return false;
        }

        $this->schemaManager()->alterTable($tableName, function (Alter $table) use ($toDrop): void
        {
            $table->dropColumns($toDrop);
        });

        return true;
    }

    /**
     * Renames a column if the table and old column exist and the new column does not yet exist.
     *
     * @param ?callable(\XF\Db\Schema\Column, Alter): void $definition Optional callback to adjust definition
     */
    protected function renameColumnIfExists(string $tableName, string $oldColumn, string $newColumn, ?callable $definition = null): bool
    {
        if (!$this->tableExists($tableName) || !$this->columnExists($tableName, $oldColumn) || $this->columnExists($tableName, $newColumn))
        {
            return false;
        }

        $this->schemaManager()->alterTable($tableName, function (Alter $table) use ($oldColumn, $newColumn, $definition): void
        {
            $column = $table->renameColumn($oldColumn, $newColumn);
            if ($definition !== null)
            {
                $definition($column, $table);
            }
        });

        return true;
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

    /**
     * Deletes widgets by their widget keys.
     */
    protected function deleteWidgets(array|string $widgetKeys): void
    {
        $widgetKeys = (array)$widgetKeys;
        if (empty($widgetKeys))
        {
            return;
        }

        $widgets = \XF::finder('XF:Widget')
            ->where('widget_key', $widgetKeys)
            ->fetch();

        foreach ($widgets as $widget)
        {
            $widget->delete();
        }
    }
}

