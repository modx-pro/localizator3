/**
 * PrimeVue theme for Localizator3 manager UI.
 * Always ModxManagerTheme via getActiveTheme('modx') — ignores vuetools.theme / Aura.
 */
import { getPrimeVueLocale } from '@vuetools/usePrimeVueLocale'
import { getActiveTheme } from '@vuetools/useTheme'

function getManagerPrimeVueThemeOptions() {
  const active = getActiveTheme('modx') || {}
  const { theme, ...rest } = active
  return {
    ...rest,
    theme: {
      ...theme,
      options: {
        ...(theme?.options || {}),
        darkModeSelector: '.modx-dark-mode',
        cssLayer: false,
      },
    },
  }
}

export function getManagerPrimeVueConfig() {
  return {
    ...getManagerPrimeVueThemeOptions(),
    locale: getPrimeVueLocale(),
    ripple: false,
  }
}

export function isModxManagerTheme() {
  return true
}

export function getPrimarySaveSeverity() {
  return 'success'
}
