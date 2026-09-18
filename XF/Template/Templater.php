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

    public function formTextBox(array $controlOptions)
    {
        $phrase = $this->processPhraseAttribute($controlOptions);
        $targetId = $this->ensurePhraseTargetId($controlOptions, $phrase);
        $label = $controlOptions['data-phrase-label'] ?? null;

        $html = parent::formTextBox($controlOptions);

        if ($phrase !== null && $phrase !== '')
        {
            $html = $this->wrapWithPhraseTranslationButton($html, $phrase, false, $targetId, $label);
        }

        return $html;
    }

    public function formTextArea(array $controlOptions)
    {
        $phrase = $this->processPhraseAttribute($controlOptions);
        $targetId = $this->ensurePhraseTargetId($controlOptions, $phrase);
        $label = $controlOptions['data-phrase-label'] ?? null;

        $html = parent::formTextArea($controlOptions);

        if ($phrase !== null && $phrase !== '')
        {
            $html = $this->wrapWithPhraseTranslationButton($html, $phrase, true, $targetId, $label);
        }

        return $html;
    }

    public function formTextBoxRow(array $controlOptions, array $rowOptions)
    {
        $this->passPhraseAttributeFromRow($controlOptions, $rowOptions);
        return parent::formTextBoxRow($controlOptions, $rowOptions);
    }

    public function formTextAreaRow(array $controlOptions, array $rowOptions)
    {
        $this->passPhraseAttributeFromRow($controlOptions, $rowOptions);
        return parent::formTextAreaRow($controlOptions, $rowOptions);
    }

    protected function processPhraseAttribute(array &$controlOptions): ?string
    {
        $phrase = $this->processAttributeToRaw($controlOptions, 'phrase');
        if ($phrase === null || $phrase === '')
        {
            $phrase = $this->processAttributeToRaw($controlOptions, 'cv6-phrase');
        }

        if ($phrase !== null)
        {
            $phrase = trim((string)$phrase);
        }

        return $phrase;
    }

    protected function passPhraseAttributeFromRow(array &$controlOptions, array &$rowOptions): void
    {
        if (isset($rowOptions['phrase']) && !isset($controlOptions['phrase']))
        {
            $controlOptions['phrase'] = $rowOptions['phrase'];
            unset($rowOptions['phrase']);
        }
        if (isset($rowOptions['cv6-phrase']) && !isset($controlOptions['cv6-phrase']))
        {
            $controlOptions['cv6-phrase'] = $rowOptions['cv6-phrase'];
            unset($rowOptions['cv6-phrase']);
        }
        if (isset($rowOptions['label']) && !isset($controlOptions['data-phrase-label']))
        {
            $controlOptions['data-phrase-label'] = (string)$rowOptions['label'];
        }
    }

    protected function canTranslatePhrases(): bool
    {
        $isAdmin = ($this->routerType === 'admin') || (\XF::app() instanceof \XF\Admin\App);
        if (!$isAdmin)
        {
            return false;
        }

        return \XF::visitor()->hasAdminPermission('language');
    }

    protected function ensurePhraseTargetId(array &$controlOptions, ?string $phrase): ?string
    {
        if ($phrase === null || $phrase === '')
        {
            return null;
        }

        if (!$this->canTranslatePhrases())
        {
            return null;
        }

        if (!empty($controlOptions['id']))
        {
            $targetId = (string)$controlOptions['id'];
        }
        else
        {
            $targetId = 'cv6_phrase_target_' . \XF::$time . '_' . mt_rand(1000, 9999);
            $controlOptions['id'] = $targetId;
        }

        $controlOptions['data-phrase-target'] = $targetId;
        return $targetId;
    }

    protected function wrapWithPhraseTranslationButton(
        string $html,
        string $phrase,
        bool $multiline = false,
        ?string $targetId = null,
        ?string $label = null
    ): string
    {
        if (!$this->canTranslatePhrases())
        {
            return $html;
        }

        $this->includeJs(['src' => 'cv6/core/phrase.js', 'min' => 1, 'addon' => 'cv6/Core']);

        $linkParams = ['phrase' => $phrase];
        if ($multiline)
        {
            $linkParams['multiline'] = 1;
        }
        if ($targetId)
        {
            $linkParams['target_id'] = $targetId;
        }
        if ($label !== null && $label !== '')
        {
            $linkParams['label'] = $label;
        }

        $url = \XF::app()->router('admin')->buildLink('cv6-phrases/quick-edit', null, $linkParams);
        $title = htmlspecialchars(\XF::phrase('cv6_core_translate_phrase')->render());
        $icon = $this->fontAwesome('fa-language', ['aria-hidden' => 'true']);

        $targetAttr = $targetId ? ' data-phrase-target="' . htmlspecialchars($targetId) . '"' : '';
        $buttonHtml = "<span class=\"inputGroup-splitter\"></span>" .
            "<a href=\"{$url}\" class=\"button button--link\" data-xf-click=\"overlay\" data-cache=\"false\"{$targetAttr} title=\"{$title}\">" .
            "{$icon}" .
            "</a>";

        if (preg_match('#^<div\s+class="inputGroup[^"]*"[^>]*>.*</div>$#s', $html))
        {
            $lastDivPos = strrpos($html, '</div>');
            if ($lastDivPos !== false)
            {
                return substr($html, 0, $lastDivPos) . $buttonHtml . substr($html, $lastDivPos);
            }
        }

        return "<div class=\"inputGroup\">{$html}{$buttonHtml}</div>";
    }
} 