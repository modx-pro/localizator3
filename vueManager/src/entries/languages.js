/**
 * Entry: страница «Языки» (LanguagesGrid).
 * Vue-стек предоставляется VueTools через Import Map; здесь только регистрация
 * нужных PrimeVue-компонентов и монтирование корневого компонента.
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
import Textarea from 'primevue/textarea'
import Dialog from 'primevue/dialog'
import Checkbox from 'primevue/checkbox'

import LanguagesGrid from '../components/LanguagesGrid.vue'

const components = {
  Toast,
  ConfirmDialog,
  DataTable,
  Column,
  Button,
  InputText,
  Textarea,
  Dialog,
  Checkbox,
}

const container = document.getElementById('localizator3-languages-app')
if (container) {
  const app = createLocalizatorApp(LanguagesGrid, {}, { components })
  app.use(ConfirmationService)
  app.use(ToastService)
  app.mount(container)
}
