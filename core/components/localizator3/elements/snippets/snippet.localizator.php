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

$where = array(
    'localizator.key' => $localizator_key,
);

$localizatorJoin = array(
    'class' => \localizator3\localizatorContent::class,
    'on' => "`localizator`.`resource_id` = `{$class}`.`id`",
);

$select = array(
    'localizator' => "`{$class}`.*, `localizator`.*, `{$class}`.`id`",
);

if ($onlyWithLocalization) {
    $innerJoin = array('localizator' => $localizatorJoin);
    $leftJoin = array();
} else {
    $leftJoin = array('localizator' => $localizatorJoin);
    $innerJoin = array();
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
