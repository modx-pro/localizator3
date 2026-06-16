<?php

namespace localizator3;

use xPDO\Om\xPDOSimpleObject;

class localizatorLanguage extends xPDOSimpleObject
{
    public static $permissions = array('view', 'save');
    protected $_originalKey;

    public function __construct($xpdo)
    {
        parent::__construct($xpdo);
        $this->_originalKey = $this->get('key');
    }

    public function save($cacheFlag = null)
    {
        $result = parent::save($cacheFlag);
        $newKey = $this->get('key');
        if ($result && ($newKey != $this->_originalKey)) {
            if ($template = $this->xpdo->getObject(\MODX\Revolution\modAccessPolicyTemplate::class, array('name' => 'LocalizatorManagerPolicyTemplate'))) {
                foreach (self::$permissions as $tmp) {
                    $permission = null;
                    if (!empty($this->_originalKey)) {
                        $permission = $this->xpdo->getObject(\MODX\Revolution\modAccessPermission::class, array('name' => "localizatorcontent_{$tmp}_{$this->_originalKey}"));
                    }

                    if (!$permission) {
                        $permission = $this->xpdo->newObject(\MODX\Revolution\modAccessPermission::class);
                    }
                    $permission->fromArray(array(
                        'template' => $template->id,
                        'name' => "localizatorcontent_{$tmp}_{$newKey}",
                        'description' => "localizatorcontent_{$tmp}",
                        'value' => 1,
                    ));
                    $permission->save();
                }

                foreach ($template->getMany('Policies') as $policy) {
                    $data = $policy->get('data');
                    foreach (self::$permissions as $tmp) {
                        $value = true;
                        if (!empty($this->_originalKey) && isset($data["localizatorcontent_{$tmp}_{$this->_originalKey}"])) {
                            $value = $data["localizatorcontent_{$tmp}_{$this->_originalKey}"];
                            unset($data["localizatorcontent_{$tmp}_{$this->_originalKey}"]);
                        }
                        $data["localizatorcontent_{$tmp}_{$newKey}"] = $value;
                    }
                    $policy->set('data', $data);
                    $policy->save();
                }
            }
        }

        return $result;
    }

    public function remove(array $ancestors = array())
    {
        $key = $this->get('key');
        $result = parent::remove($ancestors);
        if ($result) {
            if ($template = $this->xpdo->getObject(\MODX\Revolution\modAccessPolicyTemplate::class, array('name' => 'LocalizatorManagerPolicyTemplate'))) {
                $c = $this->xpdo->newQuery(\MODX\Revolution\modAccessPermission::class);
                $c->command('DELETE');
                $c->where(array(
                    'template' => $template->id,
                    'name:IN' => array_map(function ($v) use ($key) {
                        return "localizatorcontent_{$v}_{$key}";
                    }, self::$permissions),
                ));
                $c->prepare();
                $c->stmt->execute();

                foreach ($template->getMany('Policies') as $policy) {
                    $data = $policy->get('data');
                    foreach (self::$permissions as $tmp) {
                        if (isset($data["localizatorcontent_{$tmp}_{$key}"])) {
                            unset($data["localizatorcontent_{$tmp}_{$key}"]);
                        }
                    }
                    $policy->set('data', $data);
                    $policy->save();
                }
            }
        }
        return $result;
    }
}
