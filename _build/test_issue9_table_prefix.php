<?php

declare(strict_types=1);

/**
 * CLI-тест для GitHub issue #9 (MODX table_prefix + Phinx).
 *
 * Запуск: php _build/test_issue9_table_prefix.php
 */

if (php_sapi_name() !== 'cli') {
    exit("CLI only\n");
}

require dirname(__FILE__) . '/config.inc.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

$modx = new modX();
$modx->initialize('mgr');
$modx->setLogTarget('ECHO');
$modx->setLogLevel(modX::LOG_LEVEL_ERROR);

$componentPath = MODX_CORE_PATH . 'components/localizator3/';
require_once $componentPath . 'phinx_repair.php';

$prefix = (string)$modx->getOption('table_prefix', null, '');
$failures = 0;

function pass(string $message): void
{
    echo "[PASS] {$message}\n";
}

function fail(string $message): void
{
    global $failures;
    $failures++;
    echo "[FAIL] {$message}\n";
}

function assertTrue(bool $condition, string $message): void
{
    if ($condition) {
        pass($message);
        return;
    }

    fail($message);
}

function tableExists(modX $modx, string $tableName): bool
{
    return localizator3TableExists($modx, $tableName);
}

function renameTable(modX $modx, string $from, string $to): void
{
    $modx->exec(sprintf(
        'RENAME TABLE `%s` TO `%s`',
        str_replace('`', '``', $from),
        str_replace('`', '``', $to),
    ));
}

function runPhinxMigrate(modX $modx, string $componentPath): void
{
    $vendorAutoload = $componentPath . 'vendor/autoload.php';
    $phinxConfig = $componentPath . 'phinx.php';

    if (!file_exists($vendorAutoload)) {
        throw new RuntimeException('Missing vendor/autoload.php. Run composer install in component.');
    }

    require_once $vendorAutoload;
    localizator3RepairPhinxTablePrefixes($modx);

    $configArray = require $phinxConfig;
    $config = new \Phinx\Config\Config($configArray);
    $input = new \Symfony\Component\Console\Input\StringInput('');
    $output = new \Symfony\Component\Console\Output\BufferedOutput();
    $manager = new \Phinx\Migration\Manager($config, $input, $output);
    $manager->migrate('production');
}

function runTest(string $title, callable $test): void
{
    echo "\n=== {$title} ===\n";

    try {
        $test();
    } catch (Throwable $e) {
        global $failures;
        $failures++;
        echo "[FAIL] {$e->getMessage()}\n";
    }
}

echo "MODX table_prefix: '{$prefix}'\n";

runTest('Test 1: healthy install (no-op)', function () use ($modx, $prefix): void {
    localizator3RepairPhinxTablePrefixes($modx);
    assertTrue(tableExists($modx, $prefix . 'localizator3_languages'), 'languages table exists');
    assertTrue(!tableExists($modx, $prefix . $prefix . 'localizator3_languages'), 'no double-prefixed languages table');
    assertTrue(tableExists($modx, $prefix . 'localizator3_migrations'), 'prefixed migration log exists');
    assertTrue(!tableExists($modx, 'localizator3_migrations'), 'unprefixed migration log absent');
});

runTest('Test 2: double-prefixed data table', function () use ($modx, $prefix): void {
    $base = 'localizator3_languages';
    $correct = $prefix . $base;
    $double = $prefix . $prefix . $base;

    if (!tableExists($modx, $correct)) {
        echo "[SKIP] {$correct} missing\n";
        return;
    }

    renameTable($modx, $correct, $double);

    try {
        localizator3RepairPhinxTablePrefixes($modx);
        assertTrue(tableExists($modx, $correct), 'table renamed to correct name');
        assertTrue(!tableExists($modx, $double), 'double-prefixed table removed');
    } catch (Throwable $e) {
        if (tableExists($modx, $double) && !tableExists($modx, $correct)) {
            renameTable($modx, $double, $correct);
        }
        throw $e;
    }
});

runTest('Test 3: unprefixed migration log', function () use ($modx, $prefix): void {
    $correct = $prefix . 'localizator3_migrations';
    $unprefixed = 'localizator3_migrations';

    if (!tableExists($modx, $correct)) {
        echo "[SKIP] {$correct} missing\n";
        return;
    }

    renameTable($modx, $correct, $unprefixed);

    localizator3RepairPhinxTablePrefixes($modx);
    assertTrue(tableExists($modx, $correct), 'migration log uses prefixed name');
    assertTrue(!tableExists($modx, $unprefixed), 'unprefixed migration log removed');
});

runTest('Test 4: phinx migrate after repair', function () use ($modx, $componentPath, $prefix): void {
    runPhinxMigrate($modx, $componentPath);
    assertTrue(tableExists($modx, $prefix . 'localizator3_languages'), 'languages table after migrate');
    assertTrue(!tableExists($modx, $prefix . $prefix . 'localizator3_languages'), 'no double prefix after migrate');
});

runTest('Test 5: xPDO table name resolution', function () use ($modx, $componentPath, $prefix): void {
    $modelPath = $componentPath . 'model/';
    $modx->addPackage('localizator3', $modelPath, null, 'localizator3\\');
    $classDir = $modelPath . 'localizator3/';

    if (class_exists(\xPDO\xPDO::class)) {
        \xPDO\xPDO::getLoader()->addPsr4('localizator3\\', $classDir);
    }

    $expected = [
        \localizator3\localizatorLanguage::class => $prefix . 'localizator3_languages',
        \localizator3\localizatorContent::class => $prefix . 'localizator3_content',
        \localizator3\locOption::class => $prefix . 'localizator3_option',
    ];

    foreach ($expected as $className => $expectedTable) {
        $resolved = trim($modx->getTableName($className), '`');
        assertTrue($resolved === $expectedTable, "{$className} resolves to {$expectedTable}");
        assertTrue(tableExists($modx, $expectedTable), "table {$expectedTable} exists");
    }
});

echo "\n";
if ($failures > 0) {
    echo "FAILED: {$failures} test(s)\n";
    exit(1);
}

echo "All tests passed.\n";
exit(0);
