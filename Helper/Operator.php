<?php

namespace cv6\Core\Helper;

class Operator
{
    // Mathematical operators
    public const OP_EQUALS = '=';
    public const OP_NOT_EQUALS = '!=';
    public const OP_GREATER = '>';
    public const OP_GREATER_EQUALS = '>=';
    public const OP_LESS = '<';
    public const OP_LESS_EQUALS = '<=';
    public const OP_BETWEEN = 'between';
    public const OP_NOT_BETWEEN = 'not_between';

    // Logical / Set / Text operators
    public const OP_IN = 'in';
    public const OP_NOT_IN = 'not_in';
    public const OP_CONTAINS = 'contains';
    public const OP_NOT_CONTAINS = 'not_contains';
    public const OP_EMPTY = 'empty';
    public const OP_NOT_EMPTY = 'not_empty';
    public const OP_STARTS_WITH = 'starts_with';
    public const OP_ENDS_WITH = 'ends_with';

    // Groups
    public const GROUP_MATH = 'math';
    public const GROUP_LOGIC = 'logic';

    /**
     * Returns an array of mathematical comparison operators.
     *
     * @return array<string, \XF\Phrase>
     */
    public static function getMathematicalOperators(): array
    {
        return [
            self::OP_EQUALS => \XF::phrase('cv6_op_equals'),
            self::OP_NOT_EQUALS => \XF::phrase('cv6_op_not_equals'),
            self::OP_GREATER => \XF::phrase('cv6_op_greater'),
            self::OP_GREATER_EQUALS => \XF::phrase('cv6_op_greater_equals'),
            self::OP_LESS => \XF::phrase('cv6_op_less'),
            self::OP_LESS_EQUALS => \XF::phrase('cv6_op_less_equals'),
            self::OP_BETWEEN => \XF::phrase('cv6_op_between'),
            self::OP_NOT_BETWEEN => \XF::phrase('cv6_op_not_between'),
        ];
    }

    /**
     * Returns an array of logical, set and text comparison operators.
     *
     * @return array<string, \XF\Phrase>
     */
    public static function getLogicalOperators(): array
    {
        return [
            self::OP_IN => \XF::phrase('cv6_op_in'),
            self::OP_NOT_IN => \XF::phrase('cv6_op_not_in'),
            self::OP_CONTAINS => \XF::phrase('cv6_op_contains'),
            self::OP_NOT_CONTAINS => \XF::phrase('cv6_op_not_contains'),
            self::OP_EMPTY => \XF::phrase('cv6_op_empty'),
            self::OP_NOT_EMPTY => \XF::phrase('cv6_op_not_empty'),
            self::OP_STARTS_WITH => \XF::phrase('cv6_op_starts_with'),
            self::OP_ENDS_WITH => \XF::phrase('cv6_op_ends_with'),
        ];
    }

    /**
     * Returns all registered comparison operators (flat map).
     *
     * @return array<string, \XF\Phrase>
     */
    public static function getAllOperators(): array
    {
        return array_merge(static::getMathematicalOperators(), static::getLogicalOperators());
    }

    /**
     * Returns operators grouped by category (suitable for optgroups or categorized UI).
     *
     * @return array<string, array{label: \XF\Phrase, operators: array<string, \XF\Phrase>}>
     */
    public static function getGroupedOperators(): array
    {
        return [
            self::GROUP_MATH => [
                'label' => \XF::phrase('cv6_op_group_math'),
                'operators' => static::getMathematicalOperators(),
            ],
            self::GROUP_LOGIC => [
                'label' => \XF::phrase('cv6_op_group_logic'),
                'operators' => static::getLogicalOperators(),
            ],
        ];
    }

    /**
     * Fetches operators filtered by type, whitelist, and blacklist.
     *
     * @param string $type 'all', 'math'/'mathematical', 'logic'/'logical'
     * @param array|null $include Whitelist of operator keys (null or empty array = all)
     * @param array|null $exclude Blacklist of operator keys (null or empty array = none)
     * @return array<string, \XF\Phrase>
     */
    public static function getOperators(string $type = 'all', ?array $include = null, ?array $exclude = null): array
    {
        $operators = match (strtolower($type)) {
            'math', 'mathematical' => static::getMathematicalOperators(),
            'logic', 'logical' => static::getLogicalOperators(),
            default => static::getAllOperators(),
        };

        if (!empty($include)) {
            $includeMap = array_flip($include);
            $operators = array_intersect_key($operators, $includeMap);
        }

        if (!empty($exclude)) {
            $excludeMap = array_flip($exclude);
            $operators = array_diff_key($operators, $excludeMap);
        }

        return $operators;
    }

    /**
     * Check if a given operator string is valid.
     */
    public static function isValidOperator(string $op): bool
    {
        $all = static::getAllOperators();
        return isset($all[$op]);
    }

    /**
     * Get phrase for a specific operator, or null if unknown.
     */
    public static function getOperatorTitle(string $op): ?\XF\Phrase
    {
        $all = static::getAllOperators();
        return $all[$op] ?? null;
    }
}
