<?php

class localizatorLanguageGetProcessor extends modObjectGetProcessor
{
    public $objectType = 'localizatorLanguage';
    public $classKey = \localizator3\localizatorLanguage::class;
    public $languageTopics = array('localizator3:default');
    //public $permission = 'view';


    /**
     * We doing special check of permission
     * because of our objects is not an instances of modAccessibleObject
     *
     * @return mixed
     */
    public function process()
    {
        if (!$this->checkPermissions()) {
            return $this->failure($this->modx->lexicon('access_denied'));
        }

        return parent::process();
    }
}

return 'localizatorLanguageGetProcessor';
