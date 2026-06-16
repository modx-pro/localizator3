<?php

class localizatorLexiconTranslateProcessor extends modProcessor
{
    public function process()
    {
        $this->localizator = $this->modx->getService('localizator3', 'localizator', $this->modx->getOption('localizator3_core_path', null, $this->modx->getOption('core_path') . 'components/localizator3/') . 'model/localizator3/');

        if (!$default_language = $this->modx->getOption('localizator3_default_language')) {
            return $this->failure($this->modx->lexicon('localizator_item_err_default_language'));
        }

        $tranlate_all = $this->modx->getOption('localizator3_translate_translated_fields');

        $languages = [];
        $processed = 0;

        $_languages = $this->modx->getIterator(\localizator3\localizatorLanguage::class);
        foreach ($_languages as $language) {
            $key = $language->cultureKey ?: $language->key;
            if ($key != $default_language) {
                $languages[] = $key;
            }
        }

        $c = $this->modx->newQuery(\MODX\Revolution\modLexiconEntry::class);
        $c->limit(1000000);
        $c->where(array(
            'namespace' => 'localizator3',
            'topic' => 'site',
            'language' => $default_language
        ));

        $total = $this->modx->getCount(\MODX\Revolution\modLexiconEntry::class, $c);
        $entries = $this->modx->getIterator(\MODX\Revolution\modLexiconEntry::class, $c);
        foreach ($entries as $entry) {
            foreach ($languages as $language) {
                $tmp = $this->modx->getObject(\MODX\Revolution\modLexiconEntry::class, array(
                    'namespace' => 'localizator3',
                    'topic' => 'site',
                    'language' => $language,
                    'name' => $entry->name,
                ));

                // если уже есть запись и указано не перезаписывать - прерываем цикл
                if ($tmp && $tmp->get('value') && !$tranlate_all) {
                    continue;
                }

                if (!$tmp) {
                    $tmp = $this->modx->newObject(\MODX\Revolution\modLexiconEntry::class);
                    $tmp->fromArray(array(
                        'namespace' => 'localizator3',
                        'topic' => 'site',
                        'language' => $language,
                        'name' => $entry->name,
                    ));
                }

                $translation = $this->localizator->translate($entry->value, $default_language, $language);
                if (!$translation || is_array($translation)) {
                    continue;
                }

                $tmp->set('value', $translation);
                $tmp->save();
            }

            $processed++;
        }

        return $this->success('', array(
            'total' => $total,
            'processed' => $processed,
        ));
    }
}

return 'localizatorLexiconTranslateProcessor';
