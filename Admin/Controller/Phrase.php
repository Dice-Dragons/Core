<?php

namespace cv6\Core\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Entity\Language;
use XF\Entity\Phrase as PhraseEntity;
use XF\Finder\LanguageFinder;
use XF\Finder\PhraseFinder;
use XF\Mvc\ParameterBag;

class Phrase extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params)
    {
        $this->assertAdminPermission('language');
    }

    public function actionQuickEdit()
    {
        $phraseTitle = $this->filter('phrase', 'str');
        if (!$phraseTitle)
        {
            return $this->error(\XF::phrase('requested_phrase_not_found'));
        }

        $multiline = $this->filter('multiline', 'bool');
        $targetId = $this->filter('target_id', 'str');
        $label = $this->filter('label', 'str');

        /** @var Language[]|\XF\Mvc\Entity\ArrayCollection $languages */
        $languages = $this->finder(LanguageFinder::class)->order('title')->fetch();

        /** @var array<int, PhraseEntity> $existingPhrases */
        $existingPhrases = [];
        foreach ($this->finder(PhraseFinder::class)->where('title', $phraseTitle)->fetch() AS $phrase)
        {
            $existingPhrases[$phrase->language_id] = $phrase;
        }

        $masterPhrase = $existingPhrases[0] ?? null;
        $masterText = $masterPhrase ? $masterPhrase->phrase_text : '';

        if ($label !== '')
        {
            $displayTitle = $masterText !== '' ? "{$label} ({$masterText})" : $label;
        }
        else
        {
            $displayTitle = $masterText !== '' ? $masterText : $phraseTitle;
        }

        $canEditMaster = \XF::$developmentMode || !$masterPhrase || empty($masterPhrase->addon_id);

        $languageData = [];
        foreach ($languages AS $language)
        {
            $langId = $language->language_id;
            $customPhrase = $existingPhrases[$langId] ?? null;

            if ($customPhrase)
            {
                $text = $customPhrase->phrase_text;
                $isCustomized = true;
                $inheritedFrom = null;
                $placeholder = '';
            }
            else
            {
                $text = '';
                $isCustomized = false;
                $inheritedFromName = null;
                $placeholder = $this->getInheritedPhraseText($language, $existingPhrases, $masterText, $inheritedFromName);
                $inheritedFrom = $inheritedFromName;
            }

            $languageData[$langId] = [
                'language' => $language,
                'phrase' => $customPhrase,
                'text' => $text,
                'isCustomized' => $isCustomized,
                'placeholder' => $placeholder,
                'inheritedFrom' => $inheritedFrom
            ];
        }

        $viewParams = [
            'phraseTitle' => $phraseTitle,
            'displayTitle' => $displayTitle,
            'label' => $label,
            'multiline' => $multiline,
            'targetId' => $targetId,
            'masterPhrase' => $masterPhrase,
            'masterText' => $masterText,
            'languageData' => $languageData,
            'canEditMaster' => $canEditMaster,
            'currentLanguageId' => \XF::visitor()->language_id ?: 1
        ];

        return $this->view('cv6\Core:Phrase\QuickEdit', 'cv6_core_phrase_quick_edit', $viewParams);
    }

    public function actionQuickSave()
    {
        $this->assertPostOnly();

        $phraseTitle = $this->filter('phrase', 'str');
        if (!$phraseTitle)
        {
            return $this->error(\XF::phrase('requested_phrase_not_found'));
        }

        $targetId = $this->filter('target_id', 'str');
        $multiline = $this->filter('multiline', 'bool');
        $phrasesInput = $this->filter('phrases', 'array');

        /** @var array<int, PhraseEntity> $existingPhrases */
        $existingPhrases = [];
        foreach ($this->finder(PhraseFinder::class)->where('title', $phraseTitle)->fetch() AS $phrase)
        {
            $existingPhrases[$phrase->language_id] = $phrase;
        }

        $masterText = '';
        $canEditMaster = \XF::$developmentMode || empty($existingPhrases[0]->addon_id);
        if (isset($phrasesInput[0]) && $canEditMaster)
        {
            $masterText = (string)$phrasesInput[0];
            $masterPhrase = $existingPhrases[0] ?? null;

            if ($masterText !== '')
            {
                if (!$masterPhrase)
                {
                    $masterPhrase = $this->em()->create(PhraseEntity::class);
                    $masterPhrase->title = $phraseTitle;
                    $masterPhrase->language_id = 0;
                    $masterPhrase->addon_id = '';
                }
                $masterPhrase->phrase_text = $masterText;
                $masterPhrase->addon_id = '';
                $masterPhrase->save();
            }
            else if ($masterPhrase)
            {
                $masterPhrase->delete();
            }
        }
        else if (isset($existingPhrases[0]))
        {
            $masterText = $existingPhrases[0]->phrase_text;
        }

        foreach ($phrasesInput AS $languageId => $text)
        {
            $languageId = (int)$languageId;
            if ($languageId === 0)
            {
                continue; // Handled above
            }

            $text = (string)$text;
            $customPhrase = $existingPhrases[$languageId] ?? null;

            if ($text === '')
            {
                if ($customPhrase)
                {
                    $customPhrase->delete();
                }
            }
            else
            {
                if (!$customPhrase)
                {
                    $customPhrase = $this->em()->create(PhraseEntity::class);
                    $customPhrase->title = $phraseTitle;
                    $customPhrase->language_id = $languageId;
                    $customPhrase->addon_id = '';
                }
                $customPhrase->phrase_text = $text;
                $customPhrase->addon_id = '';
                $customPhrase->save();
            }
        }

        $currentLangId = \XF::visitor()->language_id ?: 1;
        $currentText = $phrasesInput[$currentLangId] ?? null;
        if ($currentText === null || $currentText === '')
        {
            $currentText = $masterText;
        }

        $reply = $this->message(\XF::phrase('your_changes_have_been_saved'));
        $reply->setJsonParam('phraseTitle', $phraseTitle);
        $reply->setJsonParam('targetId', $targetId);
        $reply->setJsonParam('masterText', $masterText);

        return $reply;
    }

    /**
     * @param Language $language
     * @param array<int, PhraseEntity> $existingPhrases
     * @param string $masterText
     * @param string|null $inheritedFromName
     * @return string
     */
    protected function getInheritedPhraseText(Language $language, array $existingPhrases, string $masterText, ?string &$inheritedFromName = null): string
    {
        $current = $language->Parent;
        while ($current)
        {
            if (isset($existingPhrases[$current->language_id]))
            {
                $inheritedFromName = $current->title;
                return $existingPhrases[$current->language_id]->phrase_text;
            }
            $current = $current->Parent;
        }

        if ($masterText !== '')
        {
            $inheritedFromName = (string)\XF::phrase('master_language');
            return $masterText;
        }

        $inheritedFromName = null;
        return '';
    }
}
