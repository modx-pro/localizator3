<?php

class localizatorContentCreateProcessor extends modObjectCreateProcessor
{
    public $objectType = 'localizatorContent';
    public $classKey = \localizator3\localizatorContent::class;
    public $languageTopics = array('localizator3:default');
    public $beforeSaveEvent = 'OnBeforeSaveLocalization';
    public $afterSaveEvent = 'OnSaveLocalization';
    public $permission = '';

    public function __construct($modx, array $properties = array())
    {
        parent::__construct($modx, $properties);
        $data = $this->getProperties();
        foreach ($data as $key => $value) {
            if (strpos($key, 'tvlocalizator_') !== false) {
                $this->setProperty(substr($key, 14), $value);
                $this->unsetProperty($key);
            }
            if (strpos($key, 'tvbrowserlocalizator_') !== false) {
                $this->unsetProperty($key);
            }
        }
        $this->unsetProperty('action');
    }

    public function checkPermissions()
    {
        if (!$this->modx->getOption('localizator3_check_permissions', null, false, true)) {
            return true;
        }
        $key = trim($this->getProperty('key'));
        $this->permission = "localizatorcontent_save_{$key}";
        return parent::checkPermissions();
    }

    /**
     * @return bool
     */
    public function beforeSet()
    {
        $key = trim($this->getProperty('key'));
        $resource_id = $this->getProperty('resource_id');
        if (empty($key)) {
            return $this->modx->lexicon('localizator_language_err_no_key');
        } elseif ($this->modx->getCount($this->classKey, array('key' => $key, 'resource_id' => $resource_id))) {
            return $this->modx->lexicon('localizator_content_err_ae');
        }

        if (!$this->checkPermissions()) {
            return $this->modx->lexicon('access_denied');
        }

        $localizator = $this->modx->getService(
            'localizator3',
            'localizator',
            $this->modx->getOption('localizator3_core_path', null, $this->modx->getOption('core_path') . 'components/localizator3/') . 'model/localizator3/'
        );
        if ($localizator instanceof localizator && $localizator->isDefaultFromResource()) {
            $defaultKey = $localizator->getDefaultLanguageKey();
            if ($defaultKey !== '' && $key === $defaultKey) {
                return $this->modx->lexicon('localizator_content_err_default_from_resource');
            }
        }

        return parent::beforeSet();
    }
}

return 'localizatorContentCreateProcessor';
