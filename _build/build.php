<?php

use MODX\Revolution\modAccessPermission;
use MODX\Revolution\modAccessPolicy;
use MODX\Revolution\modAccessPolicyTemplate;
use MODX\Revolution\modCategory;
use MODX\Revolution\modChunk;
use MODX\Revolution\modEvent;
use MODX\Revolution\modMenu;
use MODX\Revolution\modPlugin;
use MODX\Revolution\modPluginEvent;
use MODX\Revolution\modSnippet;
use MODX\Revolution\modSystemSetting;
use MODX\Revolution\modX;
use MODX\Revolution\Transport\modPackageBuilder;
use MODX\Revolution\Transport\modTransportPackage;
use xPDO\Transport\xPDOTransport;

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type:text/html;charset=utf-8');

if (!file_exists(__DIR__ . '/config.inc.php')) {
    exit('Could not load config. Please specify correct MODX_CORE_PATH in config.inc.php!');
}

$config = require __DIR__ . '/config.inc.php';

require_once MODX_CORE_PATH . 'vendor/autoload.php';
require_once MODX_CORE_PATH . 'include/deprecated.php';
require_once __DIR__ . '/includes/functions.php';

// Constants for data files compatibility
define('PKG_NAME', $config['name']);
define('PKG_NAME_LOWER', $config['name_lower']);
define('BUILD_SETTING_UPDATE', $config['update']['settings']);
define('BUILD_MENU_UPDATE', $config['update']['menus']);
define('BUILD_CHUNK_UPDATE', $config['update']['chunks']);
define('BUILD_SNIPPET_UPDATE', $config['update']['snippets']);
define('BUILD_PLUGIN_UPDATE', $config['update']['plugins']);
define('BUILD_EVENT_UPDATE', $config['update']['events']);
define('BUILD_POLICY_UPDATE', $config['update']['policies']);
define('BUILD_POLICY_TEMPLATE_UPDATE', $config['update']['policy_templates']);
define('BUILD_PERMISSION_UPDATE', $config['update']['permission']);
define('BUILD_CHUNK_STATIC', $config['static']['chunks']);
define('BUILD_SNIPPET_STATIC', $config['static']['snippets']);
define('BUILD_PLUGIN_STATIC', $config['static']['plugins']);

$modx = \MODX\Revolution\modX::getInstance(null, [\xPDO\xPDO::OPT_CONN_INIT => [\xPDO\xPDO::OPT_CONN_MUTABLE => true]]);
$modx->initialize('mgr');
$modx->setLogLevel($config['log_level']);
$modx->setLogTarget($config['log_target']);
$modx->getService('error', 'error.modError');

if (!defined('XPDO_CLI_MODE') || !XPDO_CLI_MODE) {
    echo '<pre>';
}

$install = new Localizator3Package($modx, $config);
$builder = $install->process();

if (!empty($config['download'])) {
    $name = $builder->getSignature() . '.transport.zip';
    $filepath = MODX_CORE_PATH . 'packages/' . $name;
    if (file_exists($filepath) && ($content = file_get_contents($filepath))) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $name);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($content));
        exit($content);
    }
}

if (!XPDO_CLI_MODE) {
    echo '</pre>';
}

class Localizator3Package
{
    private readonly modX $modx;
    private array $config = [];
    private modCategory $category;
    private array $category_attributes = [];

    public modPackageBuilder $builder;

    public function __construct(modX $modx, array $config = [])
    {
        $this->modx = $modx;

        $root = dirname(__DIR__) . '/';
        $core = $root . 'core/components/' . $config['name_lower'] . '/';
        $assets = $root . 'assets/components/' . $config['name_lower'] . '/';

        $this->config = array_merge([
            'log_level' => modX::LOG_LEVEL_INFO,
            'log_target' => php_sapi_name() === 'cli' ? 'ECHO' : 'HTML',
            'root' => $root,
            'build' => $root . '_build/',
            'data' => $root . '_build/data/',
            'resolvers_path' => $root . '_build/resolvers/',
            'core' => $core,
            'assets' => $assets,
            'source_core' => $core,
            'source_assets' => $assets,
            'pdotools_core' => dirname($core) . '/pdotools/model/pdotools/pdofetchlocalizator3.class.php',
        ], $config);

        $this->initialize();
    }

    public function process(): modPackageBuilder
    {
        if (file_exists($this->config['build'] . 'build.model.php')) {
            require_once $this->config['build'] . 'build.model.php';
        }

        $this->runComposerInstall();

        $this->settings();
        $this->events();
        $this->policies();
        $this->policyTemplates();
        $this->menus();

        $this->snippets();
        $this->chunks();
        $this->plugins();

        $vehicle = $this->builder->createVehicle($this->category, $this->category_attributes);

        $vehicle->resolve('file', [
            'source' => $this->config['core'],
            'target' => "return MODX_CORE_PATH . 'components/';",
        ]);
        $vehicle->resolve('file', [
            'source' => $this->config['assets'],
            'target' => "return MODX_ASSETS_PATH . 'components/';",
        ]);
        if (file_exists($this->config['pdotools_core'])) {
            $vehicle->resolve('file', [
                'source' => $this->config['pdotools_core'],
                'target' => "return MODX_CORE_PATH . 'components/pdotools/model/pdotools/';",
            ]);
        }

        foreach ($this->config['resolvers'] as $resolver) {
            $file = $this->config['resolvers_path'] . 'resolve.' . $resolver . '.php';
            if (file_exists($file) && $vehicle->resolve('php', ['source' => $file])) {
                $this->modx->log(modX::LOG_LEVEL_INFO, 'Added resolver ' . $resolver);
            }
        }

        $this->builder->putVehicle($vehicle);

        $this->builder->setPackageAttributes([
            'changelog' => $this->readDocFile('changelog.txt'),
            'license' => $this->readDocFile('license.txt'),
            'readme' => $this->readDocFile('readme.txt'),
            'chunks' => $this->config['chunks'],
            'setup-options' => [
                'source' => $this->config['build'] . 'setup.options.php',
            ],
        ]);
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Added package attributes and setup options.');

        $this->modx->log(modX::LOG_LEVEL_INFO, 'Packing up transport package zip...');
        $this->builder->pack();

        if (!empty($this->config['install'])) {
            $this->install();
        }

        return $this->builder;
    }

    private function runComposerInstall(): void
    {
        $componentPath = $this->config['core'];
        $composerJson = $componentPath . 'composer.json';
        $vendorPath = $componentPath . 'vendor/autoload.php';

        if (!file_exists($composerJson)) {
            return;
        }

        if (file_exists($vendorPath)) {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Composer vendor already exists, skipping install.');
            return;
        }

        $this->modx->log(modX::LOG_LEVEL_INFO, 'Running composer install in ' . $componentPath . '...');
        $cwd = getcwd();
        chdir($componentPath);
        passthru('composer install --no-dev --optimize-autoloader 2>&1', $exitCode);
        chdir($cwd);

        if ($exitCode !== 0) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Composer install failed. Run manually: cd ' . $componentPath . ' && composer install');
        } else {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Composer install completed.');
        }
    }

    private function initialize(): void
    {
        $this->builder = new modPackageBuilder($this->modx);
        $this->builder->createPackage(
            $this->config['name_lower'],
            $this->config['version'],
            $this->config['release']
        );
        $this->builder->registerNamespace(
            $this->config['name_lower'],
            false,
            true,
            '{core_path}components/' . $this->config['name_lower'] . '/'
        );
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Created Transport Package and Namespace.');

        $this->category = $this->modx->newObject(modCategory::class);
        $this->category->set('category', $this->config['name']);
        $this->category_attributes = [
            xPDOTransport::UNIQUE_KEY => 'category',
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [],
        ];
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Created main Category.');
    }

    private function loadData(string $file)
    {
        $modx = $this->modx;
        $sources = [
            'root' => $this->config['root'],
            'build' => $this->config['build'],
            'data' => $this->config['data'],
            'source_core' => $this->config['source_core'],
            'source_assets' => $this->config['source_assets'],
        ];
        return include $this->config['data'] . $file;
    }

    private function settings(): void
    {
        $settings = $this->loadData('transport.settings.php');
        if (!is_array($settings)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not package in System Settings');
            return;
        }
        $attributes = [
            xPDOTransport::UNIQUE_KEY => 'key',
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => $this->config['update']['settings'],
            xPDOTransport::RELATED_OBJECTS => false,
        ];
        foreach ($settings as $setting) {
            $vehicle = $this->builder->createVehicle($setting, $attributes);
            $this->builder->putVehicle($vehicle);
        }
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaged in ' . count($settings) . ' System Settings.');
    }

    private function events(): void
    {
        $events = $this->loadData('transport.events.php');
        if (!is_array($events)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not package in Events');
            return;
        }
        $attributes = [
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => $this->config['update']['events'],
        ];
        foreach ($events as $event) {
            $vehicle = $this->builder->createVehicle($event, $attributes);
            $this->builder->putVehicle($vehicle);
        }
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaged in ' . count($events) . ' Events.');
    }

    private function policies(): void
    {
        $policies = $this->loadData('transport.policies.php');
        if (!is_array($policies)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not package in Access Policies');
            return;
        }
        $attributes = [
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UNIQUE_KEY => ['name'],
            xPDOTransport::UPDATE_OBJECT => $this->config['update']['policies'],
        ];
        foreach ($policies as $policy) {
            $vehicle = $this->builder->createVehicle($policy, $attributes);
            $this->builder->putVehicle($vehicle);
        }
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaged in ' . count($policies) . ' Access Policies.');
    }

    private function policyTemplates(): void
    {
        $templates = $this->loadData('transport.policytemplates.php');
        if (!is_array($templates)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not package in Policy Templates');
            return;
        }
        $attributes = [
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UNIQUE_KEY => ['name'],
            xPDOTransport::UPDATE_OBJECT => $this->config['update']['policy_templates'],
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
                'Permissions' => [
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => $this->config['update']['permission'],
                    xPDOTransport::UNIQUE_KEY => ['template', 'name'],
                ],
            ],
        ];
        foreach ($templates as $template) {
            $vehicle = $this->builder->createVehicle($template, $attributes);
            $this->builder->putVehicle($vehicle);
        }
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaged in ' . count($templates) . ' Policy Templates.');
    }

    private function menus(): void
    {
        $menus = $this->loadData('transport.menu.php');
        if (!is_array($menus)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not package in Menus');
            return;
        }
        $attributes = [
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => $this->config['update']['menus'],
            xPDOTransport::UNIQUE_KEY => 'text',
            xPDOTransport::RELATED_OBJECTS => true,
        ];
        foreach ($menus as $menu) {
            $vehicle = $this->builder->createVehicle($menu, $attributes);
            $this->builder->putVehicle($vehicle);
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaged in menu "' . $menu->get('text') . '".');
        }
    }

    private function snippets(): void
    {
        $this->category_attributes[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['Snippets'] = [
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => $this->config['update']['snippets'],
            xPDOTransport::UNIQUE_KEY => 'name',
        ];
        $snippets = $this->loadData('transport.snippets.php');
        if (!is_array($snippets)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not package in Snippets');
            return;
        }
        $this->category->addMany($snippets);
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaged in ' . count($snippets) . ' Snippets.');
    }

    private function chunks(): void
    {
        $this->category_attributes[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['Chunks'] = [
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => $this->config['update']['chunks'],
            xPDOTransport::UNIQUE_KEY => 'name',
        ];
        $chunks = $this->loadData('transport.chunks.php');
        if (!is_array($chunks)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not package in Chunks');
            return;
        }
        $this->category->addMany($chunks);
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaged in ' . count($chunks) . ' Chunks.');
    }

    private function plugins(): void
    {
        $this->category_attributes[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['Plugins'] = [
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => $this->config['update']['plugins'],
            xPDOTransport::UNIQUE_KEY => 'name',
        ];
        $this->category_attributes[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['PluginEvents'] = [
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => $this->config['update']['plugins'],
            xPDOTransport::UNIQUE_KEY => ['pluginid', 'event'],
        ];
        $plugins = $this->loadData('transport.plugins.php');
        if (!is_array($plugins)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not package in Plugins');
            return;
        }
        $this->category->addMany($plugins);
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaged in ' . count($plugins) . ' Plugins.');
    }

    private function readDocFile(string $filename): string
    {
        $filepath = $this->config['core'] . 'docs/' . $filename;
        if (!file_exists($filepath)) {
            $this->modx->log(modX::LOG_LEVEL_WARN, 'Documentation file not found: ' . $filepath);
            return '';
        }
        $content = file_get_contents($filepath);
        return $content !== false ? $content : '';
    }

    private function install(): void
    {
        $signature = $this->builder->getSignature();
        $sig = explode('-', $signature);
        $versionSignature = explode('.', $sig[1]);

        /** @var modTransportPackage|null $package */
        $package = $this->modx->getObject(modTransportPackage::class, ['signature' => $signature]);
        if (!$package) {
            $package = $this->modx->newObject(modTransportPackage::class);
            $package->set('signature', $signature);
            $package->fromArray([
                'created' => date('Y-m-d h:i:s'),
                'updated' => null,
                'state' => 1,
                'workspace' => 1,
                'provider' => 0,
                'source' => $signature . '.transport.zip',
                'package_name' => $this->config['name'],
                'version_major' => $versionSignature[0],
                'version_minor' => $versionSignature[1] ?? 0,
                'version_patch' => $versionSignature[2] ?? 0,
            ]);
            if (!empty($sig[2])) {
                $r = preg_split('#([0-9]+)#', $sig[2], -1, PREG_SPLIT_DELIM_CAPTURE);
                if (is_array($r) && !empty($r)) {
                    $package->set('release', $r[0]);
                    $package->set('release_index', $r[1] ?? '0');
                } else {
                    $package->set('release', $sig[2]);
                }
            }
            $package->save();
        }

        if ($package->install()) {
            $this->modx->runProcessor('System/ClearCache');
            $this->modx->log(modX::LOG_LEVEL_INFO, '[Localizator3] Cache cleared');
            $this->modx->log(modX::LOG_LEVEL_INFO, '✅ Успешно установлен пакет ' . $signature);
        }
    }
}
