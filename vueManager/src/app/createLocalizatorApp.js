/**
 * createLocalizatorApp — единый bootstrap для Vue-UI Localizator3.
 *
 * Vue-стек (vue, primevue) резолвится через Import Map пакета VueTools ≥1.1.2-pl.
 * Тема PrimeVue (Aura) и локаль берутся из composables VueTools, поэтому сам
 * бандл не тащит @primevue/themes, primeicons и createPinia (stores пока нет).
 *
 * @param {import('vue').Component} RootComponent — корневой компонент (ContentGrid, LanguagesGrid, …)
 * @param {Record<string, any>} [rootProps] — props корневого компонента
 * @param {{ components?: Record<string, import('vue').Component> }} [options]
 *     options.components — PrimeVue-компоненты, нужные конкретному entry (не глобально всё подряд)
 * @returns {import('vue').App}
 */
import { createApp } from 'vue'
// PrimeVue и тема Aura резолвятся через Import Map VueTools: ключ «primevue»
// указывает на сборку, реэкспортирующую и config, и темы (Aura).
import PrimeVue, { Aura } from 'primevue'
import { getPrimeVueLocale } from '@vuetools/usePrimeVueLocale'

export function createLocalizatorApp(RootComponent, rootProps = {}, options = {}) {
  const app = createApp(RootComponent, rootProps)

  app.use(PrimeVue, {
    theme: {
      preset: Aura,
      options: {
        darkModeSelector: false,
        cssLayer: false,
      },
    },
    locale: getPrimeVueLocale(),
    ripple: false,
  })

  if (options.components) {
    for (const [name, component] of Object.entries(options.components)) {
      app.component(name, component)
    }
  }

  return app
}

export default createLocalizatorApp
