<?php

class localizatorContentRemoveProcessor extends modObjectRemoveProcessor
{
    public $objectType = 'localizatorContent';
    public $classKey = \localizator3\localizatorContent::class;
    public $languageTopics = array('localizator3:default');
    public $permission = '';

    public $beforeRemoveEvent = 'OnBeforeRemoveLocalization';
    public $afterRemoveEvent = 'OnRemoveLocalization';

    /**
     * @return bool|null|string
     */
    public function initialize()
    {
        if ($this->modx->getOption('localizator3_check_permissions', null, false, true)) {
            $key = trim((string)$this->getProperty('key'));
            if ($key === '') {
                $id = (int)$this->getProperty('id');
                if ($id > 0) {
                    $object = $this->modx->getObject($this->classKey, $id);
                    if ($object) {
                        $key = trim((string)$object->get('key'));
                    }
                }
            }
            if ($key === '') {
                return $this->modx->lexicon('localizator_language_err_no_key');
            }
            $this->permission = "localizatorcontent_save_{$key}";
            if (!$this->modx->hasPermission($this->permission)) {
                return $this->modx->lexicon('access_denied');
            }
        }

        return parent::initialize();
    }
}

return 'localizatorContentRemoveProcessor';
