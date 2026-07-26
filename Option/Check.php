<?php

namespace cv6\Core\Option;

use XF\Option\AbstractOption;
use XF\Entity\Option;
use XF\Repository\IconRepository;
use cv6\Core\Helper\Icon as IconHelper;

class Check extends AbstractOption
{
    /**
     * @param string $value
     *
     * @return bool
     */
    public static function verifyDefault(&$value, Option $option)
    {
        if ($option->isInsert())
        {
            // always allow a new value to be submitted so we don't blow up installation
            return true;
        }

        $displayStyles = array_keys(\XF::app()->options()->cv6EnableSettingDisplay);

        if (!empty($displayStyles) && !in_array($value, $displayStyles))
        {
            $value=$displayStyles[0];
        }
        return true;
    }

    /**
     * @param array $values
     *
     * @return bool
     */
    public static function verifyActive(&$value, Option $option)
    {
        if ($option->isInsert())
        {
            // always allow a new value to be submitted so we don't blow up installation
            return true;
        }
        
        return !empty($value);
    }

    public static function defaultWhenEmpty(&$value, Option $option)
    {
        if ($option->isInsert())
        {
            // always allow a new value to be submitted so we don't blow up installation
            return true;
        }

        if (empty($value)) {
            $value = $option['default_value'];
        }
        return true;
    }

    public static function parseIconString(string $value): array
    {
        $parts = array_filter(explode(' ', trim($value)));
        
        $validIcons = IconHelper::getIconList();
        
        $validVariants = array_keys(IconRepository::ICON_VARIANTS);
        $blocklistRegex = IconRepository::ICON_CLASS_BLOCKLIST_REGEX;
        $classRegex = IconRepository::ICON_CLASS_REGEX;
        
        $options = \XF::options();
        $allowedStyles = (array) ($options->cv6CoreFaStyle ?? []);
        $allowedRotations = (array) ($options->cv6CoreFaRotation ?? []);
        $allowedAnimations = (array) ($options->cv6CoreFaAnimation ?? []);

        $rotationClasses = ['fa-rotate-90', 'fa-rotate-180', 'fa-rotate-270', 'fa-flip-vertical', 'fa-flip-horizontal'];
        $animationClasses = ['fa-spin', 'fa-pulse'];

        $foundIcons = [];
        $foundModifiers = [];
        $unknownClasses = [];
        $disallowedClasses = [];
        
        foreach ($parts as $part) {
            $partLower = strtolower($part);
            
            if (in_array($partLower, $validVariants, true)) {
                if (empty($allowedStyles[$partLower])) {
                    $disallowedClasses[] = $part;
                } else {
                    $foundModifiers[] = $partLower;
                }
                continue;
            }

            if (in_array($partLower, $rotationClasses, true)) {
                if (empty($allowedRotations[$partLower])) {
                    $disallowedClasses[] = $part;
                } else {
                    $foundModifiers[] = $partLower;
                }
                continue;
            }

            if (in_array($partLower, $animationClasses, true)) {
                if (empty($allowedAnimations[$partLower])) {
                    $disallowedClasses[] = $part;
                } else {
                    $foundModifiers[] = $partLower;
                }
                continue;
            }
            
            if (preg_match("/{$blocklistRegex}/i", $partLower)) {
                $foundModifiers[] = $partLower;
                continue;
            }
            
            if (preg_match("/{$classRegex}/i", $partLower, $matches)) {
                $name = $matches['name'];
                if (isset($validIcons[$name]) || in_array($name, $validIcons, true)) {
                    $foundIcons[] = $partLower;
                    continue;
                }
            }
            
            $unknownClasses[] = $part;
        }
        
        return [
            'icons' => $foundIcons,
            'modifiers' => $foundModifiers,
            'unknown' => $unknownClasses,
            'disallowed' => $disallowedClasses,
        ];
    }

    public static function verifyValidIconMandatory(&$value, Option $option, $option_id)
    {
        if ($option->isInsert())
        {
            return true;
        }

        $parsed = static::parseIconString($value);

        if (!empty($parsed['disallowed']))
        {
            $option->error(\XF::phrase('cv6_please_remove_disallowed_icon_classes_x', [
                'items' => implode(', ', $parsed['disallowed'])
            ]), $option->option_id);
            return false;
        }

        if (!empty($parsed['unknown']))
        {
            $option->error(\XF::phrase('cv6_please_remove_unknown_icon_classes_x', [
                'items' => implode(', ', $parsed['unknown'])
            ]), $option->option_id);
            return false;
        }

        if (empty($parsed['icons']))
        {
            $option->error(\XF::phrase('cv6_please_enter_valid_icon_classes'), $option->option_id);
            return false;
        }

        if (count($parsed['icons']) > 1)
        {
            $option->error(\XF::phrase('cv6_please_enter_only_one_icon_class'), $option->option_id);
            return false;
        }

        return true;
    }

    public static function verifyValidIconOptional(&$value, Option $option, $option_id)
    {
        if ($option->isInsert())
        {
            return true;
        }

        if (trim($value) === '')
        {
            return true;
        }

        $parsed = static::parseIconString($value);

        if (!empty($parsed['disallowed']))
        {
            $option->error(\XF::phrase('cv6_please_remove_disallowed_icon_classes_x', [
                'items' => implode(', ', $parsed['disallowed'])
            ]), $option->option_id);
            return false;
        }

        if (!empty($parsed['unknown']))
        {
            $option->error(\XF::phrase('cv6_please_remove_unknown_icon_classes_x', [
                'items' => implode(', ', $parsed['unknown'])
            ]), $option->option_id);
            return false;
        }

        if (empty($parsed['icons']))
        {
            // Valid modifiers specified but no icon -> clear value
            $value = '';
            return true;
        }

        if (count($parsed['icons']) > 1)
        {
            $option->error(\XF::phrase('cv6_please_enter_only_one_icon_class'), $option->option_id);
            return false;
        }

        return true;
    }

    public static function verifyValidIcon(&$value, Option $option, $option_id, $mandatory = false)
    {
        if ($mandatory)
        {
            return static::verifyValidIconMandatory($value, $option, $option_id);
        }
        else
        {
            return static::verifyValidIconOptional($value, $option, $option_id);
        }
    }
}