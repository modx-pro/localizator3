<?php

class localizatorContentGetListProcessor extends modObjectGetListProcessor
{
    public $objectType = 'localizatorContent';
    public $classKey = \localizator3\localizatorContent::class;
    public $defaultSortField = 'id';
    public $defaultSortDirection = 'DESC';
    public $permission = 'localizatorcontent_list';


    /**
     * We do a special check of permissions
     * because our objects is not an instances of modAccessibleObject
     *
     * @return boolean|string
     */
    public function beforeQuery()
    {
        $this->loc_permission = $this->modx->getOption('localizator3_check_permissions', null, false, true);
        if (!$this->loc_permission) {
            return true;
        }

        if (!$this->checkPermissions()) {
            return $this->modx->lexicon('access_denied');
        }

        return true;
    }


    /**
     * @param xPDOQuery $c
     *
     * @return xPDOQuery
     */
    public function prepareQueryBeforeCount(xPDOQuery $c)
    {
        $resource_id = $this->getProperty('resource_id');
        $where = array(
            'resource_id' => $resource_id,
        );

        $debugLog = $this->modx->getOption('localizator3_debug_log', null, false) || (defined('LOCALIZATOR3_DEBUG_LOG') && LOCALIZATOR3_DEBUG_LOG);
        if ($debugLog) {
            $this->modx->log(modX::LOG_LEVEL_INFO, sprintf(
                '[localizator3 getlist] resource_id=%d, loc_permission=%s',
                $resource_id, $this->loc_permission ? 'true' : 'false'
            ));
        }

        if ($this->loc_permission) {
            $q = $this->modx->newQuery(\localizator3\localizatorLanguage::class)
                ->where([
                    'active' => 1,
                ])
                ->select('key');

            if ($q->prepare() && $q->stmt->execute()) {
                while ($key = $q->stmt->fetchColumn()) {
                    if (!$this->modx->hasPermission("localizatorcontent_view_{$key}")) {
                        $where['localizatorContent.key:NOT IN'][] = $key;
                    }
                }
            }
        }

        $c->leftJoin(\localizator3\localizatorLanguage::class, 'localizatorLanguage', 'localizatorLanguage.key = localizatorContent.key');
        $c->where($where);

        $query = trim($this->getProperty('query'));
        if ($query) {
            $c->where(array(
                'pagetitle:LIKE' => "%{$query}%",
                'OR:longtitle:LIKE' => "%{$query}%",
                'OR:menutitle:LIKE' => "%{$query}%",
                'OR:seotitle:LIKE' => "%{$query}%",
                'OR:introtext:LIKE' => "%{$query}%",
                'OR:description:LIKE' => "%{$query}%",
                'OR:keywords:LIKE' => "%{$query}%",
            ));
        }

        return $c;
    }

    /**
     * Восстанавливаем SELECT после getCount (он перезаписывает columns).
     *
     * @param xPDOQuery $c
     * @return xPDOQuery
     */
    public function prepareQueryAfterCount(xPDOQuery $c)
    {
        $c->select(array(
            'localizatorContent.*',
            "CONCAT(COALESCE(localizatorLanguage.name, localizatorContent.key), ' [', COALESCE(localizatorLanguage.key, localizatorContent.key), '] (', COALESCE(localizatorLanguage.http_host, '-'), ')') as `_key`"
        ));
        return $c;
    }

    /**
     * @param array $list
     * @return array
     */
    public function afterIteration(array $list)
    {
        $debugLog = $this->modx->getOption('localizator3_debug_log', null, false) || (defined('LOCALIZATOR3_DEBUG_LOG') && LOCALIZATOR3_DEBUG_LOG);
        if ($debugLog) {
            $this->modx->log(modX::LOG_LEVEL_INFO, sprintf(
                '[localizator3 getlist] resource_id=%d, resultsCount=%d',
                $this->getProperty('resource_id'), count($list)
            ));
        }
        return $list;
    }

    /**
     * @param xPDOObject $object
     *
     * @return array
     */
    public function prepareRow(xPDOObject $object)
    {
        $array = $object->toArray();
        $array['actions'] = array();

        // Edit
        $array['actions'][] = array(
            'cls' => '',
            'icon' => 'icon icon-edit',
            'title' => $this->modx->lexicon('localizator_item_update'),
            //'multiple' => $this->modx->lexicon('localizator_items_update'),
            'action' => 'loadUpdateWin',
            'button' => true,
            'menu' => true,
        );

        if (!$array['active']) {
            $array['actions'][] = array(
                'cls' => '',
                'icon' => 'icon icon-power-off action-green',
                'title' => $this->modx->lexicon('localizator_item_enable'),
                'multiple' => $this->modx->lexicon('localizator_items_enable'),
                'action' => 'enableItem',
                'button' => true,
                'menu' => true,
            );
        } else {
            $array['actions'][] = array(
                'cls' => '',
                'icon' => 'icon icon-power-off action-gray',
                'title' => $this->modx->lexicon('localizator_item_disable'),
                'multiple' => $this->modx->lexicon('localizator_items_disable'),
                'action' => 'disableItem',
                'button' => true,
                'menu' => true,
            );
        }

        // Remove
        $array['actions'][] = array(
            'cls' => '',
            'icon' => 'icon icon-trash-o action-red',
            'title' => $this->modx->lexicon('localizator_item_remove'),
            'multiple' => $this->modx->lexicon('localizator_items_remove'),
            'action' => 'removeItem',
            'button' => true,
            'menu' => true,
        );

        return $array;
    }
}

return 'localizatorContentGetListProcessor';
