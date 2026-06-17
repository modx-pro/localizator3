/**
 * Entry: страница «Языки» (LanguagesGrid).
 * Vue-стек предоставляется VueTools через Import Map; здесь только регистрация
 * нужных PrimeVue-компонентов и монтирование корневого компонента.
 */
import '../base.css'

import { createLocalizatorApp } from '../app/createLocalizatorApp.js'
import { ConfirmationService, ToastService } from 'primevue'
import {
  Toast,
  ConfirmDialog,
  DataTable,
  Column,
  Button,
  InputText,
  Textarea,
  Dialog,
  Checkbox,
} from 'primevue'

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
