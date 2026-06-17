<?php

class localizatorLanguageUpdateProcessor extends modObjectUpdateProcessor
{
    public $objectType = 'localizatorLanguage';
    public $classKey = \localizator3\localizatorLanguage::class;
    public $languageTopics = array('localizator3:default');
    public $beforeSaveEvent = 'OnBeforeSaveLocalizatorLanguage';
    public $afterSaveEvent = 'OnSaveLocalizatorLanguage';
    //public $permission = 'save';

    protected $old_key = null;

    /**
     * We doing special check of permission
     * because of our objects is not an instances of modAccessibleObject
     *
     * @return bool|string
     */
    public function beforeSave()
    {
        if (!$this->checkPermissions()) {
            return $this->modx->lexicon('access_denied');
        }

        return true;
    }


    /**
     * @return bool
     */
    public function beforeSet()
    {
        $id = (int)$this->getProperty('id');
        if (empty($id)) {
            return $this->modx->lexicon('localizator_item_err_ns');
        }

        $key = trim($this->getProperty('key'));
        if (empty($key)) {
            $this->modx->error->addField('key', $this->modx->lexicon('localizator_language_err_no_key'));
        } elseif ($this->modx->getCount($this->classKey, array('key' => $key, 'id:!=' => $id))) {
            $this->modx->error->addField('key', $this->modx->lexicon('localizator_language_err_key_exist'));
        }

        $http_host = $this->normalizeHttpHost($this->getProperty('http_host'));
        $this->setProperty('http_host', $http_host);
        if (empty($http_host)) {
            $this->modx->error->addField('http_host', $this->modx->lexicon('localizator_language_err_no_http_host'));
        } elseif ($this->modx->getCount($this->classKey, array('http_host' => $http_host, 'id:!=' => $id))) {
            $this->modx->error->addField('http_host', $this->modx->lexicon('localizator_language_err_http_host_exist'));
        }

        $this->old_key = $this->object->get('key');

        return parent::beforeSet();
    }

    public function afterSave()
    {
        if ($this->old_key != $this->object->get('key')) {
            $classes = [
                \localizator3\localizatorContent::class,
                \localizator3\locTemplateVarResource::class,
                \localizator3\locOption::class,
                \localizator3\locProductOption::class,
            ];
            foreach ($classes as $class) {
                if ($upd = $this->modx->prepare("UPDATE " . $this->modx->getTableName($class) . " SET `key` = ? WHERE `key` = ?")) {
                    $upd->execute(array(
                        $this->object->get('key'),
                        $this->old_key
                    ));
                }
            }
        }

        return true;
    }

    /**
     * @param mixed $value
     * @return string
     */
    protected function normalizeHttpHost($value): string
    {
        $http_host = trim((string)$value);
        $http_host = preg_replace('#^https?://#i', '', $http_host);

        return $http_host;
    }
}

return 'localizatorLanguageUpdateProcessor';
