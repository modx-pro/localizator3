<template>
  <Dialog
    v-model:visible="visible"
    :header="dialogHeader"
    modal
    :style="{ width: '50rem' }"
    :closable="true"
    maximizable
    appendTo="self"
    @hide="onHide"
  >
    <form v-if="hasFormConfig" @submit.prevent="onSubmit" class="content-form">
      <Tabs v-model:value="activeTab" class="content-form__tabs">
        <TabList>
          <Tab v-for="tab in visibleTabs" :key="tab.id" :value="tab.id">
            {{ tab.caption }}
          </Tab>
        </TabList>
        <TabPanels>
          <TabPanel v-for="tab in visibleTabs" :key="tab.id" :value="tab.id">
            <div class="content-form__grid">
              <FormFieldRenderer
                v-for="field in visibleFields(tab)"
                :key="field.field"
                :field="field"
                v-model="formData[fieldKey(field)]"
                :options="fieldOptions(field)"
                :disabled="isFieldDisabled(field)"
                :empty-message="emptyLanguagesMessage"
              />
            </div>
          </TabPanel>
        </TabPanels>
      </Tabs>
      <div class="content-form__footer">
        <Button type="button" :label="cancelLabel" severity="secondary" @click="onCancel" />
        <Button
          type="submit"
          :label="submitLabel"
          icon="pi pi-check"
          :disabled="submitDisabled"
        />
      </div>
    </form>
    <div v-else class="content-form__loading">
      {{ loadingMessage }}
    </div>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Dialog, Tabs, TabList, Tab, TabPanels, TabPanel, Button } from 'primevue'
import FormFieldRenderer from './shared/FormFieldRenderer.vue'

const props = defineProps({
  modelValue: Boolean,
  formConfig: Object,
  initialData: Object,
  languages: Array,
  isEdit: Boolean,
  lexicon: Object,
  loading: Boolean,
  error: String,
})

const emit = defineEmits(['update:modelValue', 'submit', 'cancel'])

const activeTab = ref(null)
const formData = ref({})

const visible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val),
})

const hasFormConfig = computed(() => !!props.formConfig?.formtabs)

const visibleTabs = computed(() => {
  const tabs = []
  const config = props.formConfig?.formtabs
  if (!config) return tabs
  if (config.document) tabs.push(config.document)
  if (config.tvs?.tabs) tabs.push(...config.tvs.tabs)
  return tabs
})

const dialogHeader = computed(() => {
  return props.isEdit
    ? (props.lexicon?.localizator_item_update || 'Edit')
    : (props.lexicon?.localizator_add || 'Add')
})

const cancelLabel = computed(() => props.lexicon?.localizator_cancel || 'Cancel')
const submitLabel = computed(() => {
  return props.isEdit
    ? (props.lexicon?.localizator_save || 'Save')
    : (props.lexicon?.localizator_item_create || 'Create')
})

const submitDisabled = computed(() => !props.isEdit && !formData.value.key)
const loadingMessage = computed(() => props.lexicon?.localizator_loading || 'Loading...')

const emptyLanguagesMessage = computed(() => {
  if (props.loading) return props.lexicon?.localizator_loading || 'Loading...'
  if (props.error) return props.error
  const total = props.formConfig?.totalActiveLanguages ?? 0
  if (total === 0) {
    return props.lexicon?.localizator_no_languages_configured || 'No languages configured'
  }
  return props.lexicon?.localizator_no_available_languages || 'No available languages'
})

watch(() => props.initialData, (data) => {
  formData.value = { ...(data || {}) }
}, { immediate: true })

watch(visibleTabs, (tabs) => {
  if (tabs.length > 0 && !tabs.some(t => t.id === activeTab.value)) {
    activeTab.value = tabs[0].id
  }
})

function visibleFields(tab) {
  if (!tab?.fields) return []
  return tab.fields.filter(f => f.visible !== false)
}

function fieldKey(field) {
  return field.field === 'tv' ? `tv_${field.tv_id}` : field.field
}

function fieldOptions(field) {
  if (field.field === 'key') return props.languages || []
  return field.options || []
}

function isFieldDisabled(field) {
  return field.field === 'key' && props.isEdit
}

function onSubmit() {
  emit('submit', { ...formData.value })
}

function onCancel() {
  emit('cancel')
  visible.value = false
}

function onHide() {
  emit('cancel')
}
</script>

<style scoped>
.content-form__grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  padding: 0.5rem 0;
}

.content-form__footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 1rem;
}

.content-form__loading {
  padding: 2rem;
  text-align: center;
}

@media (max-width: 480px) {
  .content-form__grid {
    grid-template-columns: 1fr;
  }
}
</style>
