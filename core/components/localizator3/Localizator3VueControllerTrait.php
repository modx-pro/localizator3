<?php

/**
 * Localizator3VueControllerTrait
 *
 * Общий helpers для контроллеров/обработчиков, рендерящих Vue-UI Localizator3.
 *
 * Vue-стек (vue, pinia, primevue) и тема предоставляются пакетом VueTools ≥ 1.1.2-pl
 * через ES Modules Import Map, регистрируемый плагином VueTools на OnManagerPageBeforeRender.
 * Здесь только: проверка доступности VueTools, подключение lean-бандлов и вывод lexicon.
 */
trait Localizator3VueControllerTrait
{
    /**
     * Возвращает сервис VueTools или null, если пакет не установлен.
     *
     * @return \VueTools\VueCore|null
     */
    public function getVueTools()
    {
        $modx = $this->modx ?? ($this->localizator->modx ?? null);
        if (!$modx) {
            return null;
        }
        // bootstrap VueTools регистрирует сервис в контейнере (modx-pro/vueTools ≥1.1.x).
        if (isset($modx->services) && $modx->services->has('vuetools')) {
            return $modx->services->get('vuetools');
        }
        return $modx->getService('vuetools', \VueTools\VueCore::class);
    }

    /**
     * Проверяет, что VueTools установлен и Import Map доступен.
     * Гарантирует, что Import Map зарегистрирован на текущей странице
     * (на форме ресурса событие OnManagerPageBeforeRender уже отработало —
     * вызов идемпотентен).
     *
     * @return bool
     */
    public function requireVueTools()
    {
        $vueTools = $this->getVueTools();
        if (!$vueTools) {
            return false;
        }
        if (method_exists($vueTools, 'registerImportMap')) {
            $vueTools->registerImportMap();
        }
        return true;
    }

    /**
     * Подключает lean entry-бандл Vue-UI как ES-модуль.
     * Атрибут data-vue-module — опознавательный маркер модулей localizator3.
     *
     * @param string $src URL модуля (например, result of versionedAsset())
     */
    public function addVueModule($src)
    {
        $modx = $this->modx ?? ($this->localizator->modx ?? null);
        $html = '<script type="module" data-vue-module src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '"></script>';
        // modExtraManagerController использует $this->addHtml(); обработчики плагинов — $modx->controller->addHtml().
        if (method_exists($this, 'addHtml')) {
            $this->addHtml($html);
        } elseif ($modx && $modx->controller) {
            $modx->controller->addHtml($html);
        } elseif ($modx) {
            $modx->regClientStartupScript($html);
        }
    }
}
