/**
 * Entry: вкладка локализации ресурса (ContentGrid).
 * Vue-стек предоставляется VueTools через Import Map, поэтому здесь только
 * регистрация нужных PrimeVue-компонентов и монтирование корневого компонента.
 */
import '../base.css'

import { createLocalizatorApp } from '../app/createLocalizatorApp.js'
import { ConfirmationService, ToastService } from 'primevue'
import {
  Toast,
  ConfirmDialog,
  Button,
  InputText,
  InputNumber,
  Textarea,
  Select,
  Tabs,
  TabList,
  Tab,
  TabPanels,
  TabPanel,
  Checkbox,
} from 'primevue'

import ContentGrid from '../components/ContentGrid.vue'

const components = {
  Toast,
  ConfirmDialog,
  Button,
  InputText,
  InputNumber,
  Textarea,
  Select,
  Tabs,
  TabList,
  Tab,
  TabPanels,
  TabPanel,
  Checkbox,
}

function mountContentApp(container) {
  if (!container) {
    return
  }
  if (container.getAttribute('data-mounted') === '1' && container.querySelector('.content-grid')) {
    return
  }
  if (container.getAttribute('data-mounted') === '1') {
    container.removeAttribute('data-mounted')
  }

  const resourceId = parseInt(container.dataset.resourceId || '0', 10)
  const connectorUrl = container.dataset.connectorUrl || ''
  const modAuth = container.dataset.modAuth || (typeof localizator !== 'undefined' && localizator?.config?.modAuth) || (typeof MODx !== 'undefined' && MODx?.config?.modAuth) || ''
  const lexicon = typeof localizator !== 'undefined' && localizator?.config?.lexicon ? localizator.config.lexicon : {}

  const app = createLocalizatorApp(
    ContentGrid,
    { resourceId, connectorUrl, modAuth, lexicon },
    { components }
  )
  app.use(ConfirmationService)
  app.use(ToastService)
  app.mount(container)
  container.setAttribute('data-mounted', '1')
}

window.localizatorContentApp = mountContentApp

const pending = document.getElementById('localizator3-content-app')
if (pending) {
  mountContentApp(pending)
} else {
  const observer = new MutationObserver(() => {
    const el = document.getElementById('localizator3-content-app')
    if (el) {
      mountContentApp(el)
      observer.disconnect()
    }
  })
  observer.observe(document.body, { childList: true, subtree: true })
}
