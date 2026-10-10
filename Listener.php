<?php

namespace cv6\Core;

class Listener
{
    public static function templaterSetup(\XF\Container $container, \XF\Template\Templater &$templater)
    {
        $templater->addFunction('cv6icon', function ($templater, &$escape, $str)
        {
            if (is_string($str) && preg_match('/^@([a-z0-9]+)-var-(.*)$/i', $str, $matches))
            {
                $prefix = strtolower($matches[1]) . '-';
                $iconName = $matches[2];
                return $prefix . $iconName;
            }
            return $str;
        });
    }
}