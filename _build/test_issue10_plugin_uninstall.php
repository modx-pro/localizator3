<?php

declare(strict_types=1);

/**
 * CLI-тест удаления плагина Localizator3 (#10).
 *
 * Запуск: php _build/test_issue10_plugin_uninstall.php
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
$workspaceUninstall = dirname(__FILE__, 2) . '/core/components/localizator3/uninstall_elements.php';
$installedUninstall = $componentPath . 'uninstall_elements.php';

if (file_exists($workspaceUninstall)) {
    copy($workspaceUninstall, $installedUninstall);
}

require_once $installedUninstall;

$pluginClass = class_exists(\MODX\Revolution\modPlugin::class)
    ? \MODX\Revolution\modPlugin::class
    : 'modPlugin';
$eventClass = class_exists(\MODX\Revolution\modPluginEvent::class)
    ? \MODX\Revolution\modPluginEvent::class
    : 'modPluginEvent';

$pluginName = 'localizator3';
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

function getPlugin(modX $modx, string $pluginClass, string $name): ?object
{
    return $modx->getObject($pluginClass, ['name' => $name]);
}

function countPluginEvents(modX $modx, string $eventClass, int $pluginId): int
{
    return (int)$modx->getCount($eventClass, ['pluginid' => $pluginId]);
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

function restorePluginViaBuild(): bool
{
    $buildScript = dirname(__FILE__) . '/build.php';
    if (!file_exists($buildScript)) {
        return false;
    }

    exec('php ' . escapeshellarg($buildScript) . ' 2>&1', $output, $exitCode);

    return $exitCode === 0;
}

echo "Plugin under test: {$pluginName}\n";

runTest('Test 1: plugin installed with events', function () use ($modx, $pluginClass, $eventClass, $pluginName): void {
    $plugin = getPlugin($modx, $pluginClass, $pluginName);
    if (!$plugin) {
        echo "[SKIP] Plugin {$pluginName} not installed\n";
        return;
    }

    $eventCount = countPluginEvents($modx, $eventClass, (int)$plugin->get('id'));
    assertTrue($eventCount > 0, "plugin has {$eventCount} events");
});

runTest('Test 2: resolver and plugins config', function () use ($pluginName): void {
    $pluginsConfig = include dirname(__FILE__) . '/elements/plugins.php';
    assertTrue(isset($pluginsConfig[$pluginName]), 'plugins.php defines localizator3');

    $resolver = dirname(__FILE__) . '/resolvers/resolver_13_uninstall_elements.php';
    assertTrue(file_exists($resolver), 'resolver_13_uninstall_elements.php exists');
});

runTest('Test 3: uninstall helper removes plugin and events', function () use ($modx, $pluginClass, $eventClass, $pluginName): void {
    $plugin = getPlugin($modx, $pluginClass, $pluginName);
    if (!$plugin) {
        echo "[SKIP] Plugin {$pluginName} not installed\n";
        return;
    }

    $pluginId = (int)$plugin->get('id');
    localizator3RemovePlugins($modx);

    assertTrue(getPlugin($modx, $pluginClass, $pluginName) === null, 'plugin removed from database');
    assertTrue(countPluginEvents($modx, $eventClass, $pluginId) === 0, 'plugin events removed');
});

runTest('Test 4: uninstall resolver delegates to helper', function () use ($modx, $pluginClass, $pluginName): void {
    $signature = 'localizator3-1.0.9-beta';
    $packagePath = $modx->getOption('core_path') . 'packages/' . $signature . '/';
    if (!is_dir($packagePath)) {
        $packagePath = $modx->getOption('core_path') . 'packages/';
    }

    $transport = new xPDOTransport($modx, $signature, $packagePath);
    $options = [xPDOTransport::PACKAGE_ACTION => xPDOTransport::ACTION_UNINSTALL];

    $result = include dirname(__FILE__) . '/resolvers/resolver_13_uninstall_elements.php';
    assertTrue($result === true, 'resolver returns true on uninstall');
    assertTrue(getPlugin($modx, $pluginClass, $pluginName) === null, 'plugin absent after resolver');
});

runTest('Test 5: restore plugin via build', function () use ($modx, $pluginClass, $pluginName): void {
    if (getPlugin($modx, $pluginClass, $pluginName) !== null) {
        pass('plugin already present, skip build restore');
        return;
    }

    assertTrue(restorePluginViaBuild(), 'php _build/build.php completed');
    assertTrue(getPlugin($modx, $pluginClass, $pluginName) !== null, 'plugin restored after build');
});

echo "\n";
if ($failures > 0) {
    echo "FAILED: {$failures} test(s)\n";
    exit(1);
}

echo "All tests passed.\n";
exit(0);
