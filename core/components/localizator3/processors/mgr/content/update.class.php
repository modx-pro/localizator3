<?php

class localizatorContentUpdateProcessor extends modObjectUpdateProcessor
{
    public $objectType = 'localizatorContent';
    public $classKey = \localizator3\localizatorContent::class;
    public $languageTopics = array('localizator3:default');
    public $beforeSaveEvent = 'OnBeforeSaveLocalization';
    public $afterSaveEvent = 'OnSaveLocalization';
    public $permission = '';

    function __construct($modx, array $properties = array())
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

    public function initialize()
    {

        $primaryKey = $this->getProperty($this->primaryKeyField, false);
        if (empty($primaryKey)) {
            $this->object = $this->modx->getObject($this->classKey, array(
                'resource_id' => $this->getProperty('resource_id', false),
                'key' => $this->getProperty('key', false),
            ));
            if (!$this->object) {
                return $this->modx->lexicon('localizator_item_err_nf');
            }
            $this->setProperty($this->primaryKeyField, $this->object->get($this->primaryKeyField));
        }

        return parent::initialize();
    }


    /**
     * Vue шлёт boolean как строку "true"/"false". intval("true") равен 0,
     * поэтому сохранение формы выключало уже включённый перевод.
     *
     * @param mixed $value
     * @return int
     */
    public static function normalizeActive($value)
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        if (is_int($value) || is_float($value)) {
            return (int)$value === 1 ? 1 : 0;
        }
        $value = strtolower(trim((string)$value));
        return in_array($value, array('1', 'true', 'on', 'yes'), true) ? 1 : 0;
    }

    public function beforeSet()
    {
        if (array_key_exists('active', $this->properties)) {
            $this->setProperty('active', self::normalizeActive($this->properties['active']));
        }

        $id = (int)$this->getProperty('id');
        if (empty($id)) {
            return $this->modx->lexicon('localizator_error_no_id');
        }

        $key = trim($this->getProperty('key'));
        $resource_id = $this->getProperty('resource_id');
        if (empty($key)) {
            return $this->modx->lexicon('localizator_language_err_no_key');
        } elseif ($this->modx->getCount($this->classKey, array('key' => $key, 'resource_id' => $resource_id, 'id:!=' => $id))) {
            return $this->modx->lexicon('localizator_content_err_ae');
        }

        if (!$this->checkPermissions()) {
            return $this->modx->lexicon('access_denied');
        }

        return parent::beforeSet();
    }
}

return 'localizatorContentUpdateProcessor';
