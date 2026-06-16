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
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Checkbox from 'primevue/checkbox'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import ConfirmationService from 'primevue/confirmationservice'
import ToastService from 'primevue/toastservice'

import LanguagesGrid from '../components/LanguagesGrid.vue'

const app = createApp(LanguagesGrid)
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
app.component('Dialog', Dialog)
app.component('Textarea', Textarea)
app.component('Checkbox', Checkbox)
app.component('Toast', Toast)
app.component('ConfirmDialog', ConfirmDialog)

const container = document.getElementById('localizator3-languages-app')
if (container) {
  app.mount(container)
}
