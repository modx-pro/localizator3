<?php

$localizator = $modx->getService('localizator3', 'localizator', $modx->getOption('localizator3_core_path', null, $modx->getOption('core_path') . 'components/localizator3/') . 'model/localizator3/');

$class = $modx->getOption('class', $scriptProperties, \MODX\Revolution\modResource::class, true);
$localizator_key = $modx->getOption('localizator3_key', $scriptProperties, $modx->getOption('localizator3_key', null), true);
$onlyWithLocalization = filter_var($modx->getOption('onlyWithLocalization', $scriptProperties, false), FILTER_VALIDATE_BOOLEAN);

$elementName = $modx->getOption('snippet', $scriptProperties, 'pdoResources', true);
$elementSet = array();
if (strpos($elementName, '@') !== false) {
    list($elementName, $elementSet) = explode('@', $elementName);
}
if ($elementName == 'msProducts' || $elementName == 'ms3Products') {
    $class = class_exists('\MiniShop3\Model\msProduct')
        ? '\MiniShop3\Model\msProduct'
        : 'msProduct';
    $scriptProperties['class'] = $class;
}

$alias = $modx->getAlias($class);
$useResourceFields = $localizator instanceof localizator
    && $localizator->shouldUseResourceFields($localizator_key !== '' ? $localizator_key : null);

$where = array();
$leftJoin = array();
$innerJoin = array();
$select = array();

if (!$useResourceFields) {
    // Ключ языка в ON, не в WHERE: иначе LEFT JOIN отбрасывает ресурсы без перевода.
    $on = '`localizator`.`resource_id` = `' . $alias . '`.`id`'
        . ' AND `localizator`.`key` = ' . $modx->quote((string)$localizator_key)
        . ' AND `localizator`.`active` = 1';

    $localizatorJoin = array(
        'class' => \localizator3\localizatorContent::class,
        'alias' => 'localizator',
        'on' => $on,
    );

    if ($onlyWithLocalization) {
        $innerJoin = array('localizator' => $localizatorJoin);
    } else {
        $leftJoin = array('localizator' => $localizatorJoin);
    }

    $resourceFields = array_keys($modx->getFields($class));
    $contentFields = array_keys($modx->getFields(\localizator3\localizatorContent::class));
    $overlay = array_values(array_intersect(
        array('pagetitle', 'longtitle', 'menutitle', 'description', 'introtext', 'content'),
        $resourceFields,
        $contentFields
    ));

    $columns = array('`' . $alias . '`.*');
    foreach ($overlay as $field) {
        // CASE без вложенных скобок: pdoTools режет select по запятым и по первой паре скобок.
        $columns[] = 'CASE WHEN `localizator`.`' . $field . '` IS NULL OR `localizator`.`' . $field . "` = ''"
            . ' THEN `' . $alias . '`.`' . $field . '`'
            . ' ELSE `localizator`.`' . $field . '` END AS `' . $field . '`';
    }
    $columns[] = '`' . $alias . '`.`id`';
    $select = array(
        'localizator' => implode(', ', $columns),
    );
}

foreach (array('where', 'leftJoin', 'innerJoin', 'select') as $v) {
    if (!empty($scriptProperties[$v])) {
        $tmp = $scriptProperties[$v];
        if (!is_array($tmp)) {
            $tmp = json_decode($tmp, true);
        }
        if (is_array($tmp)) {
            $$v = array_merge($$v, $tmp);
        }
    }
    unset($scriptProperties[$v]);
}

$localizatorProperties = array(
    'where' => $where,
    'leftJoin' => $leftJoin,
    'innerJoin' => $innerJoin,
    'select' => $select,
    'localizator3_key' => $localizator_key,
);


unset($scriptProperties['snippet']);
/** @var modSnippet $snippet */
if (!empty($elementName) && $element = $modx->getObject(\MODX\Revolution\modSnippet::class, array('name' => $elementName))) {
    $elementProperties = $element->getProperties();
    $elementPropertySet = !empty($elementSet)
        ? $element->getPropertySet($elementSet)
        : array();
    if (!is_array($elementPropertySet)) {
        $elementPropertySet = array();
    }
    $params = array_merge(
        $elementProperties,
        $elementPropertySet,
        $scriptProperties,
        $localizatorProperties
    );
    $element->setCacheable(false);
    return $element->process($params);
} else {
    $modx->log(modX::LOG_LEVEL_ERROR, '[Localizator3] Could not find main snippet with name: "' . $elementName . '"');
    return '';
}
