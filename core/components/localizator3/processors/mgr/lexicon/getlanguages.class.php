<?php

class localizatorLexiconGetLanguagesProcessor extends modProcessor
{
    public function process()
    {
        $list = array();
        $languages = $this->modx->getIterator(\localizator3\localizatorLanguage::class);
        foreach ($languages as $language) {
            $list[] = array(
                'name' => ($language->cultureKey ?: $language->key),
            );
        }

        return $this->outputArray($list, count($list));
    }
}

return 'localizatorLexiconGetLanguagesProcessor';
