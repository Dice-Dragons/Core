<?php

namespace cv6\Core;

class Listener
{
    public static function templaterSetup(\XF\Container $container, \XF\Template\Templater &$templater)
    {
        $templater->addFunction('cv6icon', function ($templater, &$escape, $str)
        {
            if (is_string($str) && strpos($str, '@fa-var-') === 0)
            {
                $str = 'fa-' . substr($str, 8);
            }
            return $str;
        });
    }
}