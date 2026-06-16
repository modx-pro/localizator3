<?php

namespace localizator3;

use xPDO\Om\xPDOSimpleObject;

class localizatorContent extends xPDOSimpleObject
{
    protected $tvs = null;
    protected $TVKeys = null;

    public $_originalFieldMeta;

    public function __construct($xpdo)
    {
        parent::__construct($xpdo);
        $this->_originalFieldMeta = $this->_fieldMeta;
    }

    public static function getTemplateVarCollection(self $content)
    {
        $xpdo = $content->xpdo;
        $debugLog = $xpdo->getOption('localizator3_debug_log', null, false) || (defined('LOCALIZATOR3_DEBUG_LOG') && LOCALIZATOR3_DEBUG_LOG);
        if ($debugLog) {
            $xpdo->log(\modX::LOG_LEVEL_DEBUG, '[localizator3 localizatorContent] getTemplateVarCollection ENTER resource_id=' . $content->get('resource_id') . ', key=' . $content->get('key'));
        }
        $c = self::prepareTVListCriteria($content);

        $c->query['distinct'] = 'DISTINCT';
        $c->select($content->xpdo->getSelectColumns(\MODX\Revolution\modTemplateVar::class, 'modTemplateVar'));
        $c->select($content->xpdo->getSelectColumns(\MODX\Revolution\modTemplateVarTemplate::class, 'tvtpl', '', array('rank')));
        if ($content->isNew()) {
            $c->select(array(
                'modTemplateVar.default_text AS value',
                '0 AS resourceId'
            ));
        } else {
            $c->select(array(
                'IF(ISNULL(tvc.value),modTemplateVar.default_text,tvc.value) AS value',
                $content->get('resource_id') . ' AS resourceId'
            ));
        }
        if (!$content->isNew()) {
            $c->leftJoin(\localizator3\locTemplateVarResource::class, 'tvc', array(
                'tvc.tmplvarid = modTemplateVar.id',
                'tvc.contentid' => $content->get('resource_id'),
                'tvc.key' => $content->get('key'),
            ));
        }
        $c->sortby('tvtpl.rank', 'ASC');
        $c->sortby('modTemplateVar.rank', 'ASC');
        $c->leftJoin(\MODX\Revolution\modCategory::class, 'Category', 'Category.id=modTemplateVar.category');
        $c->select(array(
            'IF(ISNULL(Category.id),0,Category.id) AS category_id, Category.category AS category_name',
        ));
        if ($debugLog) {
            $xpdo->log(\modX::LOG_LEVEL_DEBUG, '[localizator3 localizatorContent] getTemplateVarCollection getCollection(modTemplateVar)');
        }
        return $content->xpdo->getCollection(\MODX\Revolution\modTemplateVar::class, $c);
    }

    public function getTemplateVars()
    {
        return self::getTemplateVarCollection($this);
    }

    public static function prepareTVListCriteria(self $content)
    {
        $resource = $content->getOne('Resource');
        $debugLog = $content->xpdo->getOption('localizator3_debug_log', null, false) || (defined('LOCALIZATOR3_DEBUG_LOG') && LOCALIZATOR3_DEBUG_LOG);
        if ($debugLog) {
            $content->xpdo->log(\modX::LOG_LEVEL_DEBUG, '[localizator3 localizatorContent] prepareTVListCriteria resource=' . ($resource ? get_class($resource) . '(id=' . $resource->get('id') . ')' : 'null'));
        }
        if (!$resource) {
            $c = $content->xpdo->newQuery(\MODX\Revolution\modTemplateVar::class);
            $c->where('1=0');
            return $c;
        }
        $c = $content->xpdo->newQuery(\MODX\Revolution\modTemplateVar::class);
        $c->innerJoin(\MODX\Revolution\modTemplateVarTemplate::class, 'tvtpl', array(
            'tvtpl.tmplvarid = modTemplateVar.id',
            'tvtpl.templateid' => $resource->get('template'),
        ));
        $c->groupby('modTemplateVar.id');
        $columns = $content->xpdo->getFields(\MODX\Revolution\modTemplateVar::class);
        if (isset($columns['localizator3_enabled'])) {
            $c->where(array(
                'modTemplateVar.localizator3_enabled' => 1,
            ));
        }

        return $c;
    }

    public static function _loadTVs(self $content)
    {
        $c = self::prepareTVListCriteria($content);
        $c->query['distinct'] = 'DISTINCT';
        $c->select($content->xpdo->getSelectColumns(\MODX\Revolution\modTemplateVar::class, 'modTemplateVar'));
        $c->select($content->xpdo->getSelectColumns(\MODX\Revolution\modTemplateVarTemplate::class, 'tvtpl', '', array('rank')));
        if ($content->isNew()) {
            $c->select(array(
                'modTemplateVar.default_text AS value',
            ));
        } else {
            $c->select(array(
                'IF(ISNULL(tvc.value),modTemplateVar.default_text,tvc.value) AS value',
            ));
        }
        if (!$content->isNew()) {
            $c->leftJoin(\localizator3\locTemplateVarResource::class, 'tvc', array(
                'tvc.tmplvarid = modTemplateVar.id',
                'tvc.contentid' => $content->get('resource_id'),
                'tvc.key' => $content->get('key'),
            ));
        }
        $c->sortby('tvtpl.rank', 'ASC');
        $c->sortby('modTemplateVar.rank', 'ASC');

        $data = array();
        if ($c->prepare() && $c->stmt->execute()) {
            while ($tv = $c->stmt->fetch(\PDO::FETCH_ASSOC)) {
                $data[$tv['name']] = $tv['value'];
            }
        }

        return $data;
    }

    public function loadTVs()
    {
        if ($this->tvs === null) {
            $this->tvs = self::_loadTVs($this);
        }
        return $this->tvs;
    }

    public function getTVKeys($force = false)
    {
        if ($this->TVKeys === null || $force) {
            $c = self::prepareTVListCriteria($this);
            $this->TVKeys = array();
            if (!$c) {
                return $this->TVKeys;
            }
            $c->select('modTemplateVar.id,modTemplateVar.name');
            if ($c->prepare() && $c->stmt->execute()) {
                while ($tv = $c->stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $this->TVKeys[$tv['id']] = $tv['name'];
                }
            }
        }

        return $this->TVKeys;
    }

    public function get($k, $format = null, $formatTemplate = null)
    {
        if (is_array($k)) {
            $array = array();
            foreach ($k as $v) {
                $array[$v] = isset($this->_fieldMeta[$v])
                    ? parent::get($v, $format, $formatTemplate)
                    : $this->get($v, $format, $formatTemplate);
            }
            return $array;
        } elseif (isset($this->_fieldMeta[$k])) {
            return parent::get($k, $format, $formatTemplate);
        } elseif (in_array($k, $this->getTVKeys())) {
            if (isset($this->$k)) {
                return $this->$k;
            }
            $this->loadTVs();
            return $this->tvs[$k] ?? null;
        } else {
            return parent::get($k, $format, $formatTemplate);
        }
    }

    public function save($cacheFlag = null)
    {
        $save = parent::save($cacheFlag);
        $this->saveTVs();
        return $save;
    }

    public function toArray($keyPrefix = '', $rawValues = false, $excludeLazy = false, $includeRelated = false)
    {
        $original = parent::toArray($keyPrefix, $rawValues, $excludeLazy, $includeRelated);
        $additional = $this->loadTVs() ?: [];
        $intersect = array_keys(array_intersect_key($original, $additional));
        foreach ($intersect as $key) {
            unset($additional[$key]);
        }
        return array_merge($original, $additional);
    }

    protected function saveTVs()
    {
        $tvs = self::getTemplateVarCollection($this);

        $tvids = [];
        foreach ($tvs as $tv) {
            $tvids[] = $tv->get('id');
            if (!$tv->checkResourceGroupAccess()) {
                continue;
            }

            $value = $this->get($tv->get('name'));

            if ($tv->get('type') != 'checkbox') {
                $value = $value !== null ? $value : $tv->get('default_text');
            } else {
                $value = $value ? $value : '';
            }

            switch ($tv->get('type')) {
                case 'url':
                    $value = str_replace(array('ftp://', 'http://'), '', $value);
                    break;
                case 'date':
                    $value = empty($value) ? '' : strftime('%Y-%m-%d %H:%M:%S', strtotime($value));
                    break;
                case 'tag':
                case 'autotag':
                    $tags = explode(',', $value);
                    $newTags = array();
                    foreach ($tags as $tag) {
                        $newTags[] = trim($tag);
                    }
                    $value = implode(',', $newTags);
                    break;
                default:
                    if (is_array($value)) {
                        $featureInsert = array();
                        foreach ($value as $featureValue => $featureItem) {
                            if (isset($featureItem) && $featureItem === '') {
                                continue;
                            }
                            $featureInsert[count($featureInsert)] = $featureItem;
                        }
                        $value = implode('||', $featureInsert);
                    }
                    break;
            }

            $default = $tv->processBindings($tv->get('default_text'), $this->get('resource_id'));
            if (strcmp($value, $default) != 0) {
                $tvc = $this->xpdo->getObject(\localizator3\locTemplateVarResource::class, array(
                    'key' => $this->get('key'),
                    'tmplvarid' => $tv->get('id'),
                    'contentid' => $this->get('resource_id'),
                ));
                if ($tvc == null) {
                    $tvc = $this->xpdo->newObject(\localizator3\locTemplateVarResource::class);
                    $tvc->set('key', $this->get('key'));
                    $tvc->set('tmplvarid', $tv->get('id'));
                    $tvc->set('contentid', $this->get('resource_id'));
                }
                $tvc->set('value', $value);
                $tvc->save();
            } else {
                $tvc = $this->xpdo->getObject(\localizator3\locTemplateVarResource::class, array(
                    'key' => $this->get('key'),
                    'tmplvarid' => $tv->get('id'),
                    'contentid' => $this->get('resource_id'),
                ));
                if (!empty($tvc)) {
                    $tvc->remove();
                }
            }
        }

        if (!empty($tvids)) {
            $this->xpdo->removeCollection(\localizator3\locTemplateVarResource::class, array(
                'key' => $this->get('key'),
                'tmplvarid:NOT IN' => $tvids,
                'contentid' => $this->get('resource_id'),
            ));
        }
    }

    public static function renderTVOutput($xpdo, $tv, $value = '', $resourceId = 0)
    {
        if (!localizator_is_mod_template_var($tv)) {
            $byName = !is_numeric($tv);
            $tv = $xpdo->getObject(\MODX\Revolution\modTemplateVar::class, $byName ? array('name' => $tv) : $tv);
            if ($tv == null) {
                return $value;
            }
        }

        $value = $tv->processBindings($value, $resourceId);

        $params = array();
        if ($paramstring = $tv->get('display_params')) {
            $cp = explode("&", $paramstring);
            foreach ($cp as $p => $v) {
                $ar = explode("=", $v);
                if (is_array($ar) && count($ar) == 2) {
                    $params[$ar[0]] = $tv->decodeParamValue($ar[1]);
                }
            }
        }
        $outputProperties = $tv->get('output_properties');
        if (!empty($outputProperties) && is_array($outputProperties)) {
            $params = array_merge($params, $outputProperties);
        }

        $value = $tv->prepareOutput($value, $resourceId);

        $outputRenderPaths = $tv->getRenderDirectories('OnTVOutputRenderList', 'output');
        return $tv->getRender($params, $value, $outputRenderPaths, 'output', $resourceId, $tv->get('display'));
    }

    public function remove(array $ancestors = array())
    {
        $tvs = $this->xpdo->getIterator(\localizator3\locTemplateVarResource::class, array(
            'key' => $this->get('key'),
            'contentid' => $this->get('resource_id'),
        ));
        foreach ($tvs as $tv) {
            $tv->remove();
        }

        return parent::remove($ancestors);
    }
}
