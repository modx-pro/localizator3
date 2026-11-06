<?php

/**
 * OnTVFormSave handler — persists the Localizator3 checkbox on TV save.
 *
 * ExtJS `xcheckbox` submits the field only when checked; an unchecked
 * checkbox sends nothing, so without this handler a previously enabled
 * TV could never be disabled again (the processor keeps the old value).
 * The RELATIVE map field also exists, but the processor may strip it
 * before the map merge; reading $_POST directly keeps this robust.
 *
 * @var \MODX\Revolution\modX $modx
 * @var localizator $localizator
 * @var \MODX\Revolution\modTemplateVar $tv
 * @var string $mode
 */

$eventParams = $modx->event->params ?? array();
$tv = $tv ?? ($eventParams['tv'] ?? null);
$mode = $mode ?? ($eventParams['mode'] ?? '');

if (!$tv instanceof \MODX\Revolution\modTemplateVar) {
    return;
}

// Только форма TV: не трогаем программные сохранения без POST.
if (!isset($_POST['name'])) {
    return;
}

// Физическая колонка должна существовать; иначе — выходим тихо,
// чтобы не ломать сохранение TV на старых установках.
$table = $modx->getTableName(\MODX\Revolution\modTemplateVar::class);
$c = $modx->prepare("SHOW COLUMNS IN {$table} LIKE 'localizator3_enabled'");
if (!$c || !$c->execute() || !$c->fetch(PDO::FETCH_ASSOC)) {
    return;
}

// POST без поля = чекбокс снят (ExtJS не шлёт 0).
$submitted = isset($_POST['localizator3_enabled']) ? $_POST['localizator3_enabled'] : null;
$value = ($submitted === '1' || $submitted === 1 || $submitted === true || filter_var($submitted, FILTER_VALIDATE_BOOLEAN)) ? 1 : 0;

if ((int)$tv->get('localizator3_enabled') !== (int)$value) {
    $tv->set('localizator3_enabled', $value);
    $tv->save();
}
