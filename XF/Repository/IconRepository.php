<?php

namespace cv6\Core\XF\Repository;

use XF\Repository\ForumTypeRepository;

class IconRepository extends XFCP_IconRepository
{

    /**
     * @var string
     */
    public const ICON_CLASS_ANIMATION_REGEX = '^fa-(spin|pulse)$';

    /**
     * @var string
     */
    public const ICON_CLASS_ROTATE_REGEX = '^fa-(rotate-(90|180|270)+|flip-(horizontal|vertical|both))$';


    /**
     * @return list<{name: string, variant: string}>
     */
    public function getUnknownIconData(string $classes): array
    {

        $errors = [];

        $classes = explode(' ', $classes);
        $animationRegex = static::ICON_CLASS_ANIMATION_REGEX;
        $rotateRegex = static::ICON_CLASS_ROTATE_REGEX;
        $validVariants = static::ICON_VARIANTS;

        foreach ($classes as $class) {
            if (preg_match("/{$animationRegex}/i", $class)) {
                continue;
            }

            if (preg_match("/{$rotateRegex}/i", $class)) {
                continue;
            }

            if (array_key_exists($class, $validVariants)) {
                continue;
            }

            if (substr($class, 0, 3) === 'fa-') {
                $class = substr($class, 3);
            }

            // Whats this?
            $errors[] = $class;

        }

        return $errors;
    }
}