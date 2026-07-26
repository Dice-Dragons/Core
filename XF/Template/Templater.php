<?php
namespace cv6\Core\XF\Template;

class Templater extends XFCP_Templater
{

    public function addDefaultHandlers()
    {
        parent::addDefaultHandlers();
        $this->addFunction('iconize', 'fnIconize');
        $this->addFunction('ddcopyright', 'fnDiceDragonsCopyright');
    }

    /** @return string for less Icons @fa-var-regular-circle */
    public function fnIconize($templater, &$escape, $icon)
    {
        return str_replace('fa-', '@fa-var-', $icon);
    }
    
    protected function cv6DependentJs($addOn) 
    {
        $dependencies = [
            'cv6/Core' => [
                'cv6/core/asset.js' => [
                    ['dev' => 'xf/form.js', 'prod' => 'xf/form.min.js']
                ]
            ]
        ];

        \XF::app()->fire('cv6_add_js_dependencies', [&$dependencies]);

        if (array_key_exists($addOn, $dependencies))
            return $dependencies[$addOn];
        
        return [];
    } 

    public function includeJs(array $options)
    {
        $tmpOptions = array_replace([
            'src'   => null,
            'defer' => true,
            'addon' => null,
            'min'   => null,
            'dev'   => null,
            'prod'  => null,
            'root'  => false,
        ], $options);

        $dependentJS = $this->cv6DependentJs($tmpOptions['addon']);

        $developmentConfig = $this->app->config('development');
        $productionMode = empty($developmentConfig['fullJs']);

        $src = $tmpOptions['src'];
        if (!empty($dependentJS) && $src && isset($dependentJS[$src]))
        {
            $deps = $dependentJS[$src];
            if (is_array($deps))
            {
                foreach ($deps as $dep)
                {
                    if (is_array($dep))
                    {
                        $depFile = $productionMode ? ($dep['prod'] ?? $dep['dev'] ?? null) : ($dep['dev'] ?? $dep['prod'] ?? null);
                        if ($depFile)
                        {
                            parent::includeJs(['src' => $depFile]);
                        }
                    }
                }
            }
        }

        parent::includeJs($options);
    }

    public function fnDiceDragonsCopyright($templater, &$escape)
    {
        $escape = false;
        $copyrightList = [  ];
        $phrasedList = [];

        \XF::app()->fire('cv6_add_copyright', [&$copyrightList]);

        $html = '';
        if (!empty($copyrightList))
        {
            foreach($copyrightList as $addonId => $copyright)
            {
                $phrasedList[$addonId] = \XF::phrase('cv6_copyright_'.$copyright);
            }
            $html = \XF::phrase('cv6_copyright_base') . ' ' . implode(" | ", $phrasedList);
        }
        return $html;
    }
} 