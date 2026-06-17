/**
 * Entry: вкладка локализации ресурса (ContentGrid).
 * Vue-стек предоставляется VueTools через Import Map, поэтому здесь только
 * регистрация нужных PrimeVue-компонентов и монтирование корневого компонента.
 */
import '../base.css'

import { createLocalizatorApp } from '../app/createLocalizatorApp.js'
import ConfirmationService from 'primevue/confirmationservice'
import ToastService from 'primevue/toastservice'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
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

import ContentGrid from '../components/ContentGrid.vue'

const components = {
  Toast,
  ConfirmDialog,
  DataTable,
  Column,
  Button,
  InputText,
  InputNumber,
  Textarea,
  Dialog,
  Select,
  Tabs,
  TabList,
  Tab,
  TabPanels,
  TabPanel,
}

function mountContentApp(container) {
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
}

window.localizatorContentApp = mountContentApp

const container = document.getElementById('localizator3-content-app')
if (container) {
  mountContentApp(container)
} else {
  // Вкладка добавляется в ExtJS-табы асинхронно — ждём появления контейнера.
  const observer = new MutationObserver(() => {
    const el = document.getElementById('localizator3-content-app')
    if (el) {
      mountContentApp(el)
      observer.disconnect()
    }
  })
  observer.observe(document.body, { childList: true, subtree: true })
}
