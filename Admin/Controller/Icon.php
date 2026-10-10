<?php

declare(strict_types=1);

namespace cv6\Core\Admin\Controller;

use cv6\Core\Helper\Icon as IconHelper;
use cv6\Core\Repository\IconProvider as IconProviderRepo;
use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\AbstractReply;

class Icon extends AbstractController
{
    public function actionAutoComplete(): AbstractReply
    {
        $q = $this->filter('q', 'str');
        $providerId = $this->filter('provider', 'str');

        /** @var IconProviderRepo $providerRepo */
        $providerRepo = $this->repository(IconProviderRepo::class);

        if ($providerId !== '') {
            $provider = $providerRepo->getActiveProvider($providerId);
            $results = $provider ? $provider->search($q) : [];
        } else {
            $provider = $providerRepo->getProviderForClasses($q);
            if ($provider) {
                $results = $provider->search($q);
            } else {
                $results = IconHelper::searchIcons($q);
                foreach ($results as &$result) {
                    $result['provider'] = 'fa';
                }
                unset($result);
            }
        }

        // Decorate results with rendered HTML preview if not yet present
        foreach ($results as &$result) {
            if (empty($result['html'])) {
                $resProviderId = $result['provider'] ?? ($provider ? $provider->getId() : 'fa');
                $resProvider = $providerRepo->getProvider($resProviderId);
                if ($resProvider) {
                    $iconName = $result['icon'] ?? $result['name'] ?? '';
                    $result['html'] = $resProvider->render($iconName, $result['variant'] ?? null, ['inline' => true]);
                }
            }
        }
        unset($result);

        $viewParams = [
            'results' => $results
        ];

        $reply = $this->view('cv6\Core:Icon\AutoComplete', '', $viewParams);
        $reply->setJsonParam('results', $results);
        return $reply;
    }

    public function actionPreviewIcon(): AbstractReply
    {
        $classes = $this->filter('classes', 'str');
        $providerId = $this->filter('provider', 'str');

        /** @var IconProviderRepo $providerRepo */
        $providerRepo = $this->repository(IconProviderRepo::class);

        if ($providerId !== '') {
            $provider = $providerRepo->getActiveProvider($providerId);
        } else {
            $provider = $providerRepo->getProviderForClasses($classes);
        }

        if ($provider) {
            $html = $provider->render($classes, null, ['inline' => true]);
        } else {
            $html = \XF::app()->iconRenderer()->render($classes, ['inline' => true]);
        }

        $reply = $this->view('cv6\Core:Icon\Preview', '');
        $reply->setJsonParam('html', $html);
        $reply->setJsonParam('iconHtml', $html);
        return $reply;
    }

    public function actionMigrateChange(): AbstractReply
    {
        $target = $this->filter('target', 'str');
        $key = $this->filter('key', 'str');
        $icon = $this->filter('icon', 'str');

        /** @var IconProviderRepo $providerRepo */
        $providerRepo = $this->repository(IconProviderRepo::class);
        $targetProvider = $providerRepo->getActiveProvider($target);
        if (!$targetProvider) {
            return $this->error(\XF::phrase('cv6_core_invalid_target_provider'));
        }

        if ($icon !== '') {
            if (preg_match('/^@([a-z0-9]+)-var-(.*)$/i', $icon, $matches)) {
                $prefix = strtolower($matches[1]) . '-';
                $icon = $prefix . $matches[2];
            }

            $tokens = preg_split('/\s+/', trim($icon));
            $cleanTokens = [];
            foreach ($tokens as $token) {
                if ($this->isStyleClass($token) && !$this->isStyleClass($token, $targetProvider)) {
                    continue;
                }
                $cleanTokens[] = $token;
            }
            $icon = implode(' ', $cleanTokens);
        }

        $viewParams = [
            'target' => $target,
            'targetProvider' => $targetProvider,
            'key' => $key,
            'icon' => $icon,
        ];

        return $this->view('cv6\Core:Icon\MigrateChange', 'cv6_core_icon_migrate_change', $viewParams);
    }

    public function actionMigrate(): AbstractReply
    {
        /** @var IconProviderRepo $providerRepo */
        $providerRepo = $this->repository(IconProviderRepo::class);
        $activeProviders = $providerRepo->getActiveProviders();

        $source = $this->filter('source', 'str');
        $target = $this->filter('target', 'str');
        $step = $this->filter('step', 'str') ?: 'database';
        $actionType = $this->filter('action_type', 'str');
        $selectedKeys = $this->filter('migrate', 'array-str');
        $customNew = $this->filter('custom_new', 'array-str');
        $counts = $this->filter('counts', 'array-uint');

        $sourceProvider = $source ? $providerRepo->getProvider($source) : null;
        $targetProvider = $target ? $providerRepo->getProvider($target) : null;

        if (!$sourceProvider || !$targetProvider || $source === $target) {
            $viewParams = [
                'activeProviders' => $activeProviders,
                'source' => $source,
                'target' => $target,
                'step' => 'start',
                'counts' => [],
            ];
            return $this->view('cv6\Core:Icon\Migrate', 'cv6_core_icon_migrate', $viewParams);
        }

        $map = $targetProvider->getMigrationMap();
        if (empty($map) && $sourceProvider) {
            $inv = $sourceProvider->getMigrationMap();
            $map = array_flip($inv);
        }

        if ($this->isPost()) {
            if ($actionType === 'apply') {
                switch ($step) {
                    case 'database':
                        $applied = $this->findAndMigrateDatabaseIcons($sourceProvider, $targetProvider, $map, true, $selectedKeys, $customNew);
                        $counts['database'] = count($applied);
                        if (!empty($applied)) {
                            $this->rebuildMigrationCaches($applied);
                        }
                        $step = 'styles';
                        break;

                    case 'styles':
                        $applied = $this->findAndMigrateStylePropertyIcons($sourceProvider, $targetProvider, $map, true, $selectedKeys, $customNew);
                        $counts['styles'] = count($applied);
                        $step = 'phrases';
                        break;

                    case 'phrases':
                        $applied = $this->findAndMigratePhraseIcons($sourceProvider, $targetProvider, $map, true, $selectedKeys, $customNew);
                        $counts['phrases'] = count($applied);
                        $step = 'complete';
                        break;
                }
            } elseif ($actionType === 'skip') {
                switch ($step) {
                    case 'database':
                        $counts['database'] = 0;
                        $step = 'styles';
                        break;

                    case 'styles':
                        $counts['styles'] = 0;
                        $step = 'phrases';
                        break;

                    case 'phrases':
                        $counts['phrases'] = 0;
                        $step = 'complete';
                        break;
                }
            }
        }

        $results = [];
        if ($step === 'database') {
            $results = $this->findAndMigrateDatabaseIcons($sourceProvider, $targetProvider, $map, false);
        } elseif ($step === 'styles') {
            $results = $this->findAndMigrateStylePropertyIcons($sourceProvider, $targetProvider, $map, false);
        } elseif ($step === 'phrases') {
            $results = $this->findAndMigratePhraseIcons($sourceProvider, $targetProvider, $map, false);
        }

        $groupedResults = [];
        foreach ($results as $item) {
            $groupKey = $item['type'];
            if (!isset($groupedResults[$groupKey])) {
                $groupedResults[$groupKey] = [
                    'key' => preg_replace('/[^a-zA-Z0-9_-]/', '_', $groupKey),
                    'label' => $groupKey,
                    'items' => [],
                ];
            }
            $groupedResults[$groupKey]['items'][] = $item;
        }

        $viewParams = [
            'activeProviders' => $activeProviders,
            'source' => $source,
            'target' => $target,
            'sourceProvider' => $sourceProvider,
            'targetProvider' => $targetProvider,
            'step' => $step,
            'counts' => $counts,
            'results' => $results,
            'groupedResults' => $groupedResults,
        ];

        return $this->view('cv6\Core:Icon\Migrate', 'cv6_core_icon_migrate', $viewParams);
    }

    protected function findAndMigrateDatabaseIcons(
        \cv6\Core\Icon\Provider\IconProviderInterface $source,
        \cv6\Core\Icon\Provider\IconProviderInterface $target,
        array $map,
        bool $apply,
        ?array $selectedKeys = null,
        ?array $customNew = null
    ): array {
        $db = \XF::db();
        $changes = [];

        // 1. Check xf_option records
        $options = $this->finder(\XF\Finder\Option::class)->fetch();
        foreach ($options as $option) {
            if ($option->option_id === 'extraFaIcons') {
                continue;
            }
            $val = $option->option_value;
            if (is_string($val) && $source->matchesClasses($val)) {
                $newVal = $this->replaceIconClasses($val, $source, $target, $map);
                if ($newVal !== $val) {
                    $itemKey = 'xf_option|option_value|option_id|' . $option->option_id;
                    if ($apply && $selectedKeys !== null && !in_array($itemKey, $selectedKeys, true)) {
                        continue;
                    }

                    $finalNew = ($apply && isset($customNew[$itemKey]) && $customNew[$itemKey] !== '')
                        ? $customNew[$itemKey]
                        : $newVal;

                    $changes[] = [
                        'key' => $itemKey,
                        'type' => 'Option',
                        'name' => $option->option_id,
                        'old' => $val,
                        'new' => $newVal,
                        'oldIcon' => $val,
                        'newIcon' => $newVal,
                    ];
                    if ($apply) {
                        $option->option_value = $finalNew;
                        $option->save(false, false);
                    }
                }
            }
        }

        // 2. Predefined XenForo & cv6 tables
        $tables = [
            // Standard XenForo
            ['table' => 'xf_admin_navigation', 'col' => 'icon', 'pk' => 'navigation_id', 'title' => 'navigation_id', 'label' => 'Admin Navigation'],
            ['table' => 'xf_option_group', 'col' => 'icon', 'pk' => 'group_id', 'title' => 'group_id', 'label' => 'Option Group'],
            ['table' => 'xf_bb_code', 'col' => 'editor_icon_value', 'pk' => 'bb_code_id', 'title' => 'bb_code_id', 'label' => 'BBCode', 'where' => "editor_icon_type = 'fa'"],
            ['table' => 'xf_editor_dropdown', 'col' => 'icon', 'pk' => 'cmd', 'title' => 'cmd', 'label' => 'Editor Dropdown'],

            // cv6 Columns & Extensions
            ['table' => 'xf_node', 'col' => 'cv6_icon', 'pk' => 'node_id', 'title' => 'title', 'label' => 'Forum / Node'],
            ['table' => 'xf_thread_prefix', 'col' => 'cv6_icon', 'pk' => 'prefix_id', 'title' => 'prefix_id', 'label' => 'Thread Prefix'],
            ['table' => 'xf_rm_resource_prefix', 'col' => 'cv6_icon', 'pk' => 'prefix_id', 'title' => 'prefix_id', 'label' => 'Resource Prefix'],
            ['table' => 'xf_user_group', 'col' => 'cv6_icon', 'pk' => 'user_group_id', 'title' => 'title', 'label' => 'User Group'],
            ['table' => 'xf_language', 'col' => 'cv6_icon', 'pk' => 'language_id', 'title' => 'title', 'label' => 'Language'],
            ['table' => 'xf_help_page', 'col' => 'cv6_icon', 'pk' => 'page_id', 'title' => 'page_id', 'label' => 'Help Page'],
            ['table' => 'xf_navigation', 'col' => 'cv6_icon', 'pk' => 'navigation_id', 'title' => 'navigation_id', 'label' => 'Public Navigation'],
            ['table' => 'xf_notice', 'col' => 'cv6_countdown_icon', 'pk' => 'notice_id', 'title' => 'title', 'label' => 'Notice Countdown'],
            ['table' => 'xf_rm_category', 'col' => 'cv6_gallery_icon', 'pk' => 'resource_category_id', 'title' => 'title', 'label' => 'Resource Category Gallery'],
            ['table' => 'xf_cv6_setting_edition', 'col' => 'cv6_icon', 'pk' => 'edition_id', 'title' => 'edition_id', 'label' => 'Setting Edition'],
            ['table' => 'xf_cv6_custom_field_group', 'col' => 'icon', 'pk' => 'group_id', 'title' => 'title', 'label' => 'Custom Field Group'],
            ['table' => 'xf_cv6_shop_category', 'col' => 'icon_class', 'pk' => 'category_id', 'title' => 'title', 'label' => 'Shop Category'],
            ['table' => 'xf_cv6_shop_property', 'col' => 'icon_class', 'pk' => 'property_id', 'title' => 'title', 'label' => 'Shop Property'],
            ['table' => 'xf_cv6_shop_property_group', 'col' => 'icon_class', 'pk' => 'property_group_id', 'title' => 'title', 'label' => 'Shop Property Group'],
            ['table' => 'xf_cv6_spell_school', 'col' => 'icon', 'pk' => 'spell_school_id', 'title' => 'title', 'label' => 'Spell School'],
        ];

        $schemaMap = [];
        try {
            $cols = $db->fetchAll("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()");
            foreach ($cols as $c) {
                $schemaMap[$c['TABLE_NAME']][$c['COLUMN_NAME']] = true;
            }
        } catch (\Throwable $e) {
        }

        // 3. Dynamically discover any other table with cv6_icon
        try {
            $existingTableNames = array_column($tables, 'table');
            foreach ($schemaMap as $tableName => $cols) {
                if (isset($cols['cv6_icon']) && !in_array($tableName, $existingTableNames, true)) {
                    $pkCol = $db->fetchOne(
                        "SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'PRIMARY' AND TABLE_NAME = ?",
                        [$tableName]
                    ) ?: 'id';

                    $hasTitle = isset($cols['title']);

                    $tables[] = [
                        'table' => $tableName,
                        'col' => 'cv6_icon',
                        'pk' => $pkCol,
                        'title' => $hasTitle ? 'title' : $pkCol,
                        'label' => ucwords(str_replace(['xf_cv6_', 'xf_'], '', $tableName)),
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Ignore dynamic discovery failure
        }

        // 4. Scan each table
        foreach ($tables as $t) {
            $tableChanges = $this->scanTableForIcons(
                $t['table'],
                $t['col'],
                $t['pk'],
                $t['title'],
                $t['label'],
                $source,
                $target,
                $map,
                $apply,
                $schemaMap,
                $t['where'] ?? null,
                $selectedKeys,
                $customNew
            );
            if (!empty($tableChanges)) {
                $changes = array_merge($changes, $tableChanges);
            }
        }

        return $changes;
    }

    protected function findAndMigrateStylePropertyIcons(
        \cv6\Core\Icon\Provider\IconProviderInterface $source,
        \cv6\Core\Icon\Provider\IconProviderInterface $target,
        array $map,
        bool $apply,
        ?array $selectedKeys = null,
        ?array $customNew = null
    ): array {
        $db = \XF::db();
        $changes = [];

        $sql = "SELECT p.*, s.title AS style_title
                FROM xf_style_property p
                LEFT JOIN xf_style s ON (p.style_id = s.style_id)
                WHERE p.value_parameters LIKE '%template=cv6_style%icon%'
                   OR p.property_name LIKE '%icon%'
                   OR p.property_name LIKE '%Icon%'";

        $properties = $db->fetchAll($sql);

        foreach ($properties as $prop) {
            $rawVal = $prop['property_value'];
            $val = json_decode($rawVal, true);
            if (!is_string($val)) {
                $val = (string) $rawVal;
            }

            if ($val === '') {
                continue;
            }

            $sourcePrefix = $source->getPrefix();
            $targetPrefix = $target->getPrefix();
            $sourceCleanPrefix = rtrim($sourcePrefix, '-');
            $targetCleanPrefix = rtrim($targetPrefix, '-');
            $sourceVarPrefix = '@' . $sourceCleanPrefix . '-var-';
            $targetVarPrefix = '@' . $targetCleanPrefix . '-var-';

            $isStyleVar = str_starts_with($val, $sourceVarPrefix);
            $checkVal = $isStyleVar ? ($sourcePrefix . substr($val, strlen($sourceVarPrefix))) : $val;

            if ($source->matchesClasses($checkVal)) {
                $newVal = $this->replaceIconClasses($checkVal, $source, $target, $map);

                if ($isStyleVar) {
                    if (str_starts_with($newVal, $targetPrefix)) {
                        $newVal = $targetVarPrefix . substr($newVal, strlen($targetPrefix));
                    } else {
                        $newVal = $targetVarPrefix . $newVal;
                    }
                }

                if ($newVal !== $val && $newVal !== $checkVal) {
                    $itemKey = 'xf_style_property|property_value|property_id|' . $prop['property_id'];
                    if ($apply && $selectedKeys !== null && !in_array($itemKey, $selectedKeys, true)) {
                        continue;
                    }

                    $styleName = $prop['style_id'] == 0 ? 'Master Style' : ($prop['style_title'] ?: 'Style ' . $prop['style_id']);
                    $displayTitle = $prop['property_name'] . ($prop['title'] ? ' (' . $prop['title'] . ')' : '');

                    $finalNew = ($apply && isset($customNew[$itemKey]) && $customNew[$itemKey] !== '')
                        ? $customNew[$itemKey]
                        : $newVal;

                    $changes[] = [
                        'key' => $itemKey,
                        'type' => 'Style: ' . $styleName,
                        'name' => $displayTitle,
                        'old' => $val,
                        'new' => $newVal,
                        'oldIcon' => $isStyleVar ? $checkVal : $val,
                        'newIcon' => $isStyleVar ? ($targetPrefix . substr($newVal, strlen($targetVarPrefix))) : $newVal,
                    ];

                    if ($apply) {
                        $saveVal = json_encode($finalNew);
                        $db->update('xf_style_property', ['property_value' => $saveVal], 'property_id = ?', [$prop['property_id']]);
                    }
                }
            }
        }

        if ($apply && !empty($changes)) {
            try {
                \XF::app()->jobManager()->enqueueUnique('stylePropertyRebuild', \XF\Job\StylePropertyRebuild::class);
            } catch (\Throwable $e) {
            }
        }

        return $changes;
    }

    protected function findAndMigratePhraseIcons(
        \cv6\Core\Icon\Provider\IconProviderInterface $source,
        \cv6\Core\Icon\Provider\IconProviderInterface $target,
        array $map,
        bool $apply,
        ?array $selectedKeys = null,
        ?array $customNew = null
    ): array {
        $db = \XF::db();
        $changes = [];

        $sql = "SELECT p.*, l.title AS language_title
                FROM xf_phrase p
                LEFT JOIN xf_language l ON (p.language_id = l.language_id)
                WHERE p.phrase_text LIKE '%<xf:fa%'
                ORDER BY p.language_id ASC, p.title ASC";

        $phrases = $db->fetchAll($sql);

        foreach ($phrases as $phrase) {
            $text = $phrase['phrase_text'];
            $firstOldIcon = null;
            $firstNewIcon = null;

            // Replace strictly within <xf:fa ... icon="..." ...>
            $newText = preg_replace_callback('/<xf:fa\b([^>]*?)\bicon="([^"]+)"([^>]*)>/i', function ($matches) use ($source, $target, $map, &$firstOldIcon, &$firstNewIcon) {
                $pre = $matches[1];
                $classes = $matches[2];
                $post = $matches[3];
                if ($source->matchesClasses($classes)) {
                    $newClasses = $this->replaceIconClasses($classes, $source, $target, $map);
                    if ($firstOldIcon === null) {
                        $firstOldIcon = $classes;
                        $firstNewIcon = $newClasses;
                    }
                    return '<xf:fa' . $pre . 'icon="' . $newClasses . '"' . $post . '>';
                }
                return $matches[0];
            }, $text);

            if ($newText !== $text) {
                $itemKey = 'xf_phrase|phrase_text|phrase_id|' . $phrase['phrase_id'];
                if ($apply && $selectedKeys !== null && !in_array($itemKey, $selectedKeys, true)) {
                    continue;
                }

                $langName = $phrase['language_id'] == 0 ? 'Master Language' : ($phrase['language_title'] ?: 'Lang ' . $phrase['language_id']);
                $finalNew = ($apply && isset($customNew[$itemKey]) && $customNew[$itemKey] !== '')
                    ? $customNew[$itemKey]
                    : $newText;

                $changes[] = [
                    'key' => $itemKey,
                    'type' => 'Phrase: ' . $langName,
                    'name' => $phrase['title'],
                    'old' => $text,
                    'new' => $newText,
                    'oldIcon' => $firstOldIcon,
                    'newIcon' => $firstNewIcon,
                ];

                if ($apply) {
                    /** @var \XF\Entity\Phrase|null $phraseEntity */
                    $phraseEntity = \XF::em()->find('XF:Phrase', $phrase['phrase_id']);
                    if ($phraseEntity) {
                        $phraseEntity->phrase_text = $finalNew;
                        $phraseEntity->save(false, false);
                    }
                }
            }
        }

        if ($apply && !empty($changes)) {
            try {
                $this->repository(\XF\Repository\PhraseRepository::class)->rebuildPhraseCache();
            } catch (\Throwable $e) {
            }
        }

        return $changes;
    }

    protected function scanTableForIcons(
        string $table,
        string $column,
        string $pk,
        string $titleCol,
        string $label,
        \cv6\Core\Icon\Provider\IconProviderInterface $source,
        \cv6\Core\Icon\Provider\IconProviderInterface $target,
        array $map,
        bool $apply,
        array $schemaMap,
        ?string $where = null,
        ?array $selectedKeys = null,
        ?array $customNew = null
    ): array {
        $db = \XF::db();
        $changes = [];

        try {
            if (empty($schemaMap[$table][$column])) {
                return [];
            }

            $hasTitleCol = !empty($schemaMap[$table][$titleCol]);
            $selectCols = "`{$pk}`, `{$column}`" . ($hasTitleCol && $titleCol !== $pk ? ", `{$titleCol}`" : '');

            $sql = "SELECT {$selectCols} FROM `{$table}` WHERE `{$column}` IS NOT NULL AND `{$column}` != ''";
            if ($where) {
                $sql .= " AND ({$where})";
            }

            $rows = $db->fetchAll($sql);
            foreach ($rows as $row) {
                $val = (string) $row[$column];
                if ($source->matchesClasses($val)) {
                    $newVal = $this->replaceIconClasses($val, $source, $target, $map);
                    if ($newVal !== $val) {
                        $itemKey = $table . '|' . $column . '|' . $pk . '|' . $row[$pk];
                        if ($apply && $selectedKeys !== null && !in_array($itemKey, $selectedKeys, true)) {
                            continue;
                        }

                        $displayTitle = !empty($row[$titleCol]) ? (string) $row[$titleCol] : (string) $row[$pk];
                        $finalNew = ($apply && isset($customNew[$itemKey]) && $customNew[$itemKey] !== '')
                            ? $customNew[$itemKey]
                            : $newVal;

                        $changes[] = [
                            'key' => $itemKey,
                            'type' => $label,
                            'name' => $displayTitle,
                            'old' => $val,
                            'new' => $newVal,
                            'oldIcon' => $val,
                            'newIcon' => $newVal,
                        ];

                        if ($apply) {
                            $db->update($table, [$column => $finalNew], "`{$pk}` = ?", [$row[$pk]]);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore individual table query failure
        }

        return $changes;
    }

    protected function isStyleClass(string $token, ?\cv6\Core\Icon\Provider\IconProviderInterface $provider = null): bool
    {
        $tokenLower = strtolower($token);

        if ($provider !== null) {
            $styles = array_map('strtolower', array_keys($provider->getStyles()));
            if (in_array($tokenLower, $styles, true)) {
                return true;
            }
            if ($provider->getId() === 'fa') {
                return in_array($tokenLower, ['fas', 'far', 'fal', 'fad', 'fab', 'fa', 'fa-solid', 'fa-regular', 'fa-light', 'fa-duotone', 'fa-brands'], true);
            }
            return false;
        }

        /** @var \cv6\Core\Repository\IconProvider $providerRepo */
        $providerRepo = $this->repository(\cv6\Core\Repository\IconProvider::class);
        foreach ($providerRepo->getAllProviders() as $p) {
            if ($this->isStyleClass($token, $p)) {
                return true;
            }
        }

        return false;
    }

    protected function replaceIconClasses(
        string $classes,
        \cv6\Core\Icon\Provider\IconProviderInterface $source,
        \cv6\Core\Icon\Provider\IconProviderInterface $target,
        array $map
    ): string {
        $tokens = preg_split('/\s+/', trim($classes));
        if (!is_array($tokens)) {
            return $classes;
        }

        $styleMap = $source->getStyleMap($target->getId());
        if (empty($styleMap)) {
            $styleMap = $target->getIncomingStyleMap($source->getId());
        }

        $newTokens = [];
        $sourcePrefix = $source->getPrefix();
        $targetPrefix = $target->getPrefix();

        $preservedModifiers = [
            'fa-rotate-90', 'fa-rotate-180', 'fa-rotate-270',
            'fa-flip-vertical', 'fa-flip-horizontal',
            'fa-spin', 'fa-pulse', 'fa-fw',
        ];

        foreach ($tokens as $token) {
            if ($token === '') {
                continue;
            }

            // 1. Preserve universal XenForo modifier classes (rotation, spin, flip, fw)
            if (in_array($token, $preservedModifiers, true)) {
                $newTokens[] = $token;
                continue;
            }

            // 2. Check if explicit style mapping exists
            if (isset($styleMap[$token])) {
                if ($styleMap[$token] !== '') {
                    $newTokens[] = $styleMap[$token];
                }
                // Style was mapped (or mapped to empty string to drop)
                continue;
            }

            // 3. Remove source style classes and any non-target provider styles (fad, fas, far, fal, fab, gmo, gmr, gms, gmf, gm_*, etc.)
            if ($this->isStyleClass($token, $source) || ($this->isStyleClass($token) && !$this->isStyleClass($token, $target))) {
                continue;
            }

            // 4. Direct map lookup
            if (isset($map[$token])) {
                $mapped = $map[$token];
                $newTokens[] = str_starts_with($mapped, $targetPrefix) ? $mapped : $targetPrefix . $mapped;
                continue;
            }

            // 5. Stripped prefix lookup
            $stripped = $source->normalizeName($token);
            if (isset($map[$stripped])) {
                $mapped = $map[$stripped];
                $newTokens[] = str_starts_with($mapped, $targetPrefix) ? $mapped : $targetPrefix . $mapped;
                continue;
            }

            // 6. Fallback prefix swap if matches source prefix and target provider recognizes it
            if ($sourcePrefix !== '' && str_starts_with($token, $sourcePrefix)) {
                $bareName = substr($token, strlen($sourcePrefix));
                $candidate = $targetPrefix . $bareName;
                if ($target->isValidIcon($candidate)) {
                    $newTokens[] = $candidate;
                    continue;
                }
            }

            // 7. If token is already formatted with the target prefix, keep it
            if ($targetPrefix !== '' && str_starts_with($token, $targetPrefix)) {
                $newTokens[] = $token;
                continue;
            }

            // 8. If token matches a valid target style, keep it
            if ($this->isStyleClass($token, $target)) {
                $newTokens[] = $token;
                continue;
            }

            // 9. If token had source prefix but no map match, swap prefix to target prefix
            if ($sourcePrefix !== '' && str_starts_with($token, $sourcePrefix)) {
                $bareName = substr($token, strlen($sourcePrefix));
                $newTokens[] = $targetPrefix . $bareName;
                continue;
            }

            $newTokens[] = $token;
        }

        // Deduplicate tokens while preserving order
        $newTokens = array_values(array_unique($newTokens));

        if ($newTokens === $tokens) {
            return $classes;
        }

        return implode(' ', $newTokens);
    }

    protected function rebuildMigrationCaches(array $results): void
    {
        $affectedTables = [];
        foreach ($results as $item) {
            $parts = explode('|', $item['key'] ?? '');
            if (!empty($parts[0])) {
                $affectedTables[$parts[0]] = true;
            }
        }

        if (isset($affectedTables['xf_admin_navigation'])) {
            try {
                $this->repository(\XF\Repository\AdminNavigationRepository::class)->rebuildNavigationCache();
            } catch (\Throwable $e) {
            }
        }

        if (isset($affectedTables['xf_navigation'])) {
            try {
                /** @var \XF\Repository\NavigationRepository $navRepo */
                $navRepo = $this->repository(\XF\Repository\NavigationRepository::class);
                $navRepo->rebuildNavigationEntries();
                $navRepo->rebuildNavigationCache();
            } catch (\Throwable $e) {
            }
        }

        if (isset($affectedTables['xf_option']) || isset($affectedTables['xf_option_group'])) {
            try {
                $this->repository(\XF\Repository\OptionRepository::class)->rebuildOptionCache();
            } catch (\Throwable $e) {
            }
        }

        if (isset($affectedTables['xf_bb_code'])) {
            try {
                $this->repository(\XF\Repository\BbCodeRepository::class)->rebuildBbCodeCache();
            } catch (\Throwable $e) {
            }
        }

        if (isset($affectedTables['xf_editor_dropdown'])) {
            try {
                $this->repository(\XF\Repository\EditorRepository::class)->rebuildEditorDropdownCache();
            } catch (\Throwable $e) {
            }
        }

        if (isset($affectedTables['xf_notice'])) {
            try {
                $this->repository(\XF\Repository\NoticeRepository::class)->rebuildNoticeCache();
            } catch (\Throwable $e) {
            }
        }

        if (isset($affectedTables['xf_language'])) {
            try {
                $this->repository(\XF\Repository\LanguageRepository::class)->rebuildLanguageCache();
            } catch (\Throwable $e) {
            }
        }

        if (isset($affectedTables['xf_thread_prefix'])) {
            try {
                $this->repository(\XF\Repository\ThreadPrefixRepository::class)->rebuildPrefixCache();
            } catch (\Throwable $e) {
            }
        }

        if (isset($affectedTables['xf_rm_resource_prefix'])) {
            try {
                if (class_exists(\XFRM\Repository\ResourcePrefixRepository::class)) {
                    $this->repository(\XFRM\Repository\ResourcePrefixRepository::class)->rebuildPrefixCache();
                }
            } catch (\Throwable $e) {
            }
        }

        if (isset($affectedTables['xf_node'])) {
            try {
                $this->repository(\XF\Repository\NodeRepository::class)->createNodeTree();
            } catch (\Throwable $e) {
            }
        }

        if (isset($affectedTables['xf_user_group'])) {
            try {
                $this->repository(\XF\Repository\UserGroupRepository::class)->rebuildDisplayStyleCache();
            } catch (\Throwable $e) {
            }
        }
    }
}
