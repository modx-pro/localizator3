/**
 * createLocalizatorApp — единый bootstrap для Vue-UI Localizator3.
 *
 * Vue-стек (vue, primevue) резолвится через Import Map пакета VueTools ≥1.1.2-pl.
 * Тема PrimeVue — всегда ModxManagerTheme (@vuetools/useTheme, getActiveTheme('modx')).
 *
 * @param {import('vue').Component} RootComponent — корневой компонент (ContentGrid, LanguagesGrid, …)
 * @param {Record<string, any>} [rootProps] — props корневого компонента
 * @param {{ components?: Record<string, import('vue').Component> }} [options]
 *     options.components — PrimeVue-компоненты, нужные конкретному entry (не глобально всё подряд)
 * @returns {import('vue').App}
 */
import { createApp } from 'vue'
import { PrimeVue } from 'primevue'
import { getManagerPrimeVueConfig } from './primevueTheme.js'

export function createLocalizatorApp(RootComponent, rootProps = {}, options = {}) {
  const app = createApp(RootComponent, rootProps)

  app.use(PrimeVue, getManagerPrimeVueConfig())

  if (options.components) {
    for (const [name, component] of Object.entries(options.components)) {
      app.component(name, component)
    }
  }

  return app
}

export default createLocalizatorApp
