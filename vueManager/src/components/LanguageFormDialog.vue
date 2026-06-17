<template>
  <Dialog
    v-model:visible="visible"
    :header="dialogHeader"
    modal
    :style="{ width: 'min(90vw, 36rem)' }"
    :closable="true"
    maximizable
    appendTo="self"
  >
    <form @submit.prevent="onSubmit" class="language-form">
      <div class="language-form__grid">
        <div class="language-form__field language-form__field--full">
          <label for="lang-key" class="language-form__label">
            {{ lexicon?.localizator_key || 'Key' }}
            <span class="language-form__required">*</span>
          </label>
          <InputText
            id="lang-key"
            v-model="form.key"
            required
            :disabled="isEdit"
            class="language-form__input"
          />
        </div>

        <div class="language-form__field">
          <label for="lang-name" class="language-form__label">
            {{ lexicon?.localizator_language_name || 'Name' }}
          </label>
          <InputText
            id="lang-name"
            v-model="form.name"
            class="language-form__input"
          />
        </div>

        <div class="language-form__field">
          <label for="lang-culture" class="language-form__label">
            {{ lexicon?.localizator_language_cultureKey || 'Culture Key' }}
          </label>
          <InputText
            id="lang-culture"
            v-model="form.cultureKey"
            class="language-form__input"
          />
        </div>

        <div class="language-form__field language-form__field--full">
          <label for="lang-host" class="language-form__label">
            {{ lexicon?.localizator_language_http_host || 'HTTP Host' }}
          </label>
          <InputText
            id="lang-host"
            v-model="form.http_host"
            class="language-form__input"
          />
        </div>

        <div class="language-form__field language-form__field--full">
          <label for="lang-desc" class="language-form__label">
            {{ lexicon?.localizator_language_description || 'Description' }}
          </label>
          <Textarea
            id="lang-desc"
            v-model="form.description"
            rows="3"
            class="language-form__input"
          />
        </div>

        <div class="language-form__field language-form__field--full">
          <div class="language-form__checkbox">
            <Checkbox id="lang-active" v-model="form.active" binary />
            <label for="lang-active" class="language-form__checkbox-label">
              {{ lexicon?.localizator_active || 'Active' }}
            </label>
          </div>
        </div>
      </div>

      <div class="language-form__footer">
        <Button type="button" :label="cancelLabel" severity="secondary" @click="onCancel" />
        <Button
          type="submit"
          :label="submitLabel"
          icon="pi pi-check"
        />
      </div>
    </form>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Checkbox from 'primevue/checkbox'
import Button from 'primevue/button'

const props = defineProps({
  modelValue: Boolean,
  initialData: Object,
  isEdit: Boolean,
  lexicon: Object,
})

const emit = defineEmits(['update:modelValue', 'submit', 'cancel'])

const visible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val),
})

const form = ref({
  key: '',
  name: '',
  cultureKey: '',
  http_host: '',
  description: '',
  active: true,
})

const dialogHeader = computed(() => {
  return props.isEdit
    ? (props.lexicon?.localizator_language_update || 'Update')
    : (props.lexicon?.localizator_language_create || 'Create')
})

const cancelLabel = computed(() => props.lexicon?.localizator_cancel || 'Cancel')
const submitLabel = computed(() => {
  return props.isEdit
    ? (props.lexicon?.localizator_save || 'Save')
    : (props.lexicon?.localizator_item_create || 'Create')
})

watch(() => props.initialData, (data) => {
  if (data) {
    form.value = { ...data }
  } else {
    resetForm()
  }
}, { immediate: true })

function resetForm() {
  form.value = {
    key: '',
    name: '',
    cultureKey: '',
    http_host: '',
    description: '',
    active: true,
  }
}

function onSubmit() {
  emit('submit', { ...form.value })
}

function onCancel() {
  emit('cancel')
  visible.value = false
  if (!props.isEdit) {
    resetForm()
  }
}
</script>

<style scoped>
.language-form__grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.language-form__field {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.language-form__field--full {
  grid-column: 1 / -1;
}

.language-form__label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--p-text-color, #374151);
}

.language-form__required {
  color: #ef4444;
  margin-left: 2px;
}

.language-form__input {
  width: 100%;
}

.language-form__checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.language-form__checkbox-label {
  font-size: 0.875rem;
  cursor: pointer;
}

.language-form__footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 1rem;
}

@media (max-width: 480px) {
  .language-form__grid {
    grid-template-columns: 1fr;
  }
}
</style>
