import '../base.css'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config'
import Aura from '@primevue/themes/aura'
import 'primeicons/primeicons.css'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import ConfirmationService from 'primevue/confirmationservice'
import ToastService from 'primevue/toastservice'

import ContentGrid from '../components/ContentGrid.vue'

function mountContentApp(container) {
  const resourceId = parseInt(container.dataset.resourceId || '0', 10)
  const connectorUrl = container.dataset.connectorUrl || ''
  const modAuth = container.dataset.modAuth || (typeof localizator !== 'undefined' && localizator?.config?.modAuth) || (typeof MODx !== 'undefined' && MODx?.config?.modAuth) || ''
  const lexicon = typeof localizator !== 'undefined' && localizator?.config?.lexicon ? localizator.config.lexicon : {}

  const app = createApp(ContentGrid, {
    resourceId,
    connectorUrl,
    modAuth,
    lexicon,
  })
  app.use(createPinia())
  app.use(PrimeVue, {
    theme: {
      preset: Aura,
      options: {
        darkModeSelector: false,
        cssLayer: false,
      },
    },
    ripple: false,
  })

  app.use(ConfirmationService)
  app.use(ToastService)

  app.component('DataTable', DataTable)
  app.component('Column', Column)
  app.component('Button', Button)
  app.component('InputText', InputText)
  app.component('InputNumber', InputNumber)
  app.component('Textarea', Textarea)
  app.component('Dialog', Dialog)
  app.component('Select', Select)
  app.component('Tabs', Tabs)
  app.component('TabList', TabList)
  app.component('Tab', Tab)
  app.component('TabPanels', TabPanels)
  app.component('TabPanel', TabPanel)
  app.component('Toast', Toast)
  app.component('ConfirmDialog', ConfirmDialog)

  app.mount(container)
}

window.localizatorContentApp = mountContentApp

const container = document.getElementById('localizator3-content-app')
if (container) {
  mountContentApp(container)
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
