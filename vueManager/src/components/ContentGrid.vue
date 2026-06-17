<template>
  <div class="content-grid vueApp">
    <Toast />
    <ConfirmDialog />

    <p class="loc-section-bar">
      {{ lexicon.localizator_content_section_desc || 'Manage resource field translations, TVs and auto-translation.' }}
    </p>

    <div class="loc-toolbar content-grid__locale-bar">
      <div class="loc-toolbar__field content-grid__locale-select">
        <label for="content-locale" class="loc-toolbar__label">
          {{ lexicon.localizator_language || 'Language' }}
        </label>
        <Select
          id="content-locale"
          v-model="selectedLocaleKey"
          :options="localeOptions"
          option-label="label"
          option-value="value"
          :placeholder="lexicon.localizator_language || 'Language'"
          :loading="loading"
          :disabled="loading || formConfigLoading || localeOptions.length === 0"
          fluid
          class="content-grid__locale-dropdown"
        />
      </div>
      <div class="loc-toolbar__actions">
        <Button
          :label="lexicon.localizator_save || 'Save'"
          icon="pi pi-check"
          :loading="saving"
          :disabled="!formReady || saving || (!isEdit && !form.key)"
          @click="submitForm"
        />
        <Button
          :label="lexicon.localizator_translate || 'Translate'"
          icon="pi pi-language"
          severity="secondary"
          :disabled="!isEdit || !form.key || saving"
          @click="translateCurrent"
        />
        <template v-if="isEdit && form.id">
          <Button
            v-if="!form.active"
            icon="pi pi-power-off"
            severity="success"
            text
            rounded
            :title="lexicon.localizator_item_enable"
            @click="toggleActive(true)"
          />
          <Button
            v-else
            icon="pi pi-power-off"
            severity="secondary"
            text
            rounded
            :title="lexicon.localizator_item_disable"
            @click="toggleActive(false)"
          />
          <Button
            icon="pi pi-trash"
            severity="danger"
            text
            rounded
            :title="lexicon.localizator_item_remove"
            @click="confirmRemoveCurrent"
          />
        </template>
      </div>
    </div>

    <div v-if="formConfigLoading || loading" class="content-grid__loading">
      {{ lexicon.localizator_loading || 'Loading...' }}
    </div>
    <div v-else-if="formConfigError" class="content-grid__empty">
      {{ formConfigError }}
    </div>
    <div v-else-if="!formReady && activeLanguages.length === 0" class="content-grid__empty">
      {{ emptyLanguagesMessage }}
    </div>
    <form v-else-if="formReady" class="loc-panel loc-form content-grid__form" @submit.prevent="submitForm">
      <div v-if="!isEdit" class="content-grid__active-row">
        <Checkbox v-model="form.active" :binary="true" input-id="content-active" />
        <label for="content-active" class="loc-form-checkbox__label">
          {{ lexicon.localizator_active || 'Active' }}
        </label>
      </div>

      <Tabs v-model:value="activeTab" class="content-form-tabs">
        <TabList>
          <Tab v-for="tab in visibleTabs" :key="tab.id" :value="tab.id">
            {{ tab.caption }}
          </Tab>
        </TabList>
        <TabPanels>
          <TabPanel v-for="tab in visibleTabs" :key="tab.id" :value="tab.id">
            <div class="loc-form-grid">
              <div
                v-for="field in visibleFields(tab)"
                :key="field.field"
                class="loc-form-field"
                :class="{ 'loc-form-field--full': field.field === 'content' || field.type === 'textarea' || field.type === 'richtext' }"
              >
                <label v-if="field.caption" :for="'field-' + field.field" class="loc-form-label">
                  {{ field.caption }}
                  <span v-if="field.required" class="loc-form-label__required">*</span>
                </label>
                <InputText
                  v-if="field.type === 'text'"
                  :id="'field-' + field.field"
                  v-model="form[field.field]"
                  :required="field.required"
                />
                <Textarea
                  v-else-if="field.type === 'textarea' || field.type === 'richtext' || field.type === 'tv'"
                  :id="'field-' + field.field"
                  v-model="form[tvFieldName(field)]"
                  :required="field.required"
                  :rows="field.field === 'content' ? 12 : 4"
                />
                <div v-else-if="field.field === 'key'" class="content-grid__select-group">
                  <Select
                    :id="'field-' + field.field"
                    v-model="form[field.field]"
                    :options="languages"
                    option-label="name"
                    option-value="key"
                    :placeholder="field.caption"
                    :empty-message="emptyLanguagesMessage"
                  />
                  <small v-if="languages.length === 0" class="loc-form-hint">
                    {{ emptyLanguagesMessage }}
                  </small>
                </div>
                <InputNumber
                  v-else-if="field.type === 'number'"
                  :input-id="'field-' + field.field"
                  v-model="form[field.field]"
                  fluid
                />
              </div>
            </div>
          </TabPanel>
        </TabPanels>
      </Tabs>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useToast, useConfirm } from 'primevue'

const props = defineProps({
  resourceId: { type: Number, required: true },
  connectorUrl: { type: String, required: true },
  modAuth: { type: String, default: '' },
  lexicon: { type: Object, default: () => ({}) },
})

const loading = ref(false)
const saving = ref(false)
const items = ref([])
const selectedLocaleKey = ref('')
const skipLocaleWatch = ref(false)
const activeLanguages = ref([])

const formConfig = ref(null)
const formConfigLoading = ref(false)
const formConfigError = ref(null)
const form = ref({})
const isEdit = ref(false)
const languages = ref([])
const activeTab = ref(null)

const toast = useToast()
const confirmDialog = useConfirm()

const itemsByKey = computed(() => {
  const map = {}
  for (const item of items.value) {
    if (item.key) {
      map[item.key] = item
    }
  }
  return map
})

const localeOptions = computed(() => {
  const lx = props.lexicon || {}
  const newSuffix = lx.localizator_item_create ? ` — ${lx.localizator_item_create}` : ''
  return activeLanguages.value.map((lang) => {
    const hasTranslation = !!itemsByKey.value[lang.key]
    return {
      label: hasTranslation ? lang.label : `${lang.label}${newSuffix}`,
      value: lang.key,
    }
  })
})

const formReady = computed(() => !!formConfig.value?.formtabs && !!selectedLocaleKey.value)

const emptyLanguagesMessage = computed(() => {
  const lx = props.lexicon || {}
  if (formConfigLoading.value) {
    return lx.localizator_loading || 'Loading...'
  }
  if (formConfigError.value) {
    return formConfigError.value
  }
  const total = formConfig.value?.totalActiveLanguages ?? 0
  if (total === 0) {
    return lx.localizator_no_languages_configured || lx.localizator_add_languages_hint || 'Add languages in Localizator3 → Languages'
  }
  return lx.localizator_no_available_languages || 'No available languages. All localizations for this resource have been created.'
})

const visibleTabs = computed(() => {
  if (!formConfig.value?.formtabs) return []
  const tabs = []
  if (formConfig.value.formtabs.document) {
    tabs.push(formConfig.value.formtabs.document)
  }
  if (formConfig.value.formtabs.tvs?.tabs) {
    tabs.push(...formConfig.value.formtabs.tvs.tabs)
  }
  return tabs
})

watch(visibleTabs, (tabs) => {
  if (tabs.length > 0 && !tabs.some((t) => t.id === activeTab.value)) {
    activeTab.value = tabs[0].id
  }
}, { immediate: true })

function visibleFields(tab) {
  const fields = tab.fields || []
  return fields.filter((f) => {
    if (f.visible === false || f.field === 'id') return false
    if (isEdit.value && f.field === 'key') return false
    return true
  })
}

function tvFieldName(field) {
  if (field.type === 'tv' && field.inputTV) {
    return 'tvlocalizator_' + field.inputTV
  }
  return field.field
}

function buildParams(extra = {}) {
  const params = { ...extra }
  if (props.modAuth) {
    params.HTTP_MODAUTH = props.modAuth
    params.modAuth = props.modAuth
  }
  return new URLSearchParams(params)
}

function getConnectorUrl() {
  const u = props.connectorUrl
  if (!u || u.startsWith('http') || u.startsWith('/')) return u || ''
  if (typeof window !== 'undefined') return window.location.origin + '/' + u.replace(/^\//, '')
  return u
}

function showError(error) {
  toast.add({
    severity: 'error',
    summary: props.lexicon?.localizator_error || 'Error',
    detail: error?.message || String(error),
    life: 5000,
  })
}

async function postConnector(params) {
  const response = await fetch(getConnectorUrl(), {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: buildParams(params).toString(),
  })
  if (!response.ok) {
    throw new Error(`HTTP ${response.status}: ${response.statusText}`)
  }
  const data = await response.json()
  if (!data.success) {
    throw new Error(data.message || props.lexicon?.localizator_error || 'Request failed')
  }
  return data
}

function applyFormConfigObject(object, includeRecord = false) {
  formConfig.value = {
    formtabs: object.formtabs,
    customization: object.customization,
    totalActiveLanguages: object.totalActiveLanguages ?? 0,
    existingCount: object.existingCount ?? 0,
  }
  languages.value = object.languages || []
  if (object.activeLanguages?.length) {
    activeLanguages.value = object.activeLanguages
  }
  if (includeRecord && object.record) {
    form.value = { ...object.record, resource_id: props.resourceId }
    isEdit.value = true
    selectedLocaleKey.value = object.record.key || selectedLocaleKey.value
  }
}

async function fetchFormConfig(locId = 0) {
  const params = {
    action: 'mgr/content/getformconfig',
    resource_id: props.resourceId,
  }
  if (locId) {
    params.loc_id = locId
  }
  const response = await fetch(getConnectorUrl(), {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: buildParams(params).toString(),
  })
  if (!response.ok) {
    throw new Error(`getformconfig ${response.status}: ${response.statusText}`)
  }
  const data = await response.json()
  if (!data.success || !data.object) {
    throw new Error(data.message || props.lexicon?.localizator_unknown_error || 'Unknown error')
  }
  return data.object
}

async function loadFormConfig(locId = 0) {
  formConfigLoading.value = true
  formConfigError.value = null
  try {
    const object = await fetchFormConfig(locId)
    applyFormConfigObject(object, !!locId)
    return object
  } catch (e) {
    formConfigError.value = e.message || String(e)
    showError(e)
    return null
  } finally {
    formConfigLoading.value = false
  }
}

async function loadAllItems() {
  loading.value = true
  try {
    const data = await postConnector({
      action: 'mgr/content/getlist',
      resource_id: props.resourceId,
      start: 0,
      limit: 999,
      sort: 'id',
      dir: 'ASC',
    })
    items.value = data.results || []
  } catch (e) {
    showError(e)
  } finally {
    loading.value = false
  }
}

function initEmptyForm() {
  form.value = { resource_id: props.resourceId, key: '', active: 1 }
  if (formConfig.value?.formtabs?.document?.fields) {
    for (const f of formConfig.value.formtabs.document.fields) {
      if (f.visible !== false && f.field !== 'id' && f.field !== 'key') {
        form.value[f.field] = form.value[f.field] ?? ''
      }
    }
  }
  isEdit.value = false
}

async function enterCreateModeForKey(key) {
  if (!key) {
    return
  }
  skipLocaleWatch.value = true
  selectedLocaleKey.value = key
  skipLocaleWatch.value = false
  await loadFormConfig(0)
  initEmptyForm()
  form.value.key = key
}

async function loadLocalization(locId) {
  if (!locId) {
    return
  }
  loading.value = true
  try {
    const object = await fetchFormConfig(locId)
    applyFormConfigObject(object, true)
  } catch (e) {
    showError(e)
  } finally {
    loading.value = false
  }
}

function findItemByKey(key) {
  return items.value.find((item) => item.key === key)
}

async function selectLocaleKey(key) {
  if (!key) {
    return
  }
  const item = findItemByKey(key)
  if (item) {
    skipLocaleWatch.value = true
    selectedLocaleKey.value = key
    skipLocaleWatch.value = false
    await loadLocalization(item.id)
    return
  }
  await enterCreateModeForKey(key)
}

watch(selectedLocaleKey, (value, oldValue) => {
  if (skipLocaleWatch.value || !value || value === oldValue) {
    return
  }
  if (isEdit.value && form.value.key === value) {
    return
  }
  selectLocaleKey(value)
})

async function refreshAll() {
  const currentKey = selectedLocaleKey.value || form.value.key
  await loadAllItems()
  await loadFormConfig(0)
  if (currentKey) {
    await selectLocaleKey(currentKey)
    return
  }
  if (activeLanguages.value.length > 0) {
    await selectLocaleKey(activeLanguages.value[0].key)
    return
  }
  selectedLocaleKey.value = ''
  form.value = {}
  isEdit.value = false
}

async function submitForm() {
  if (!isEdit.value && !form.value.key) {
    return
  }
  saving.value = true
  const action = isEdit.value ? 'mgr/content/update' : 'mgr/content/create'
  try {
    await postConnector({ action, ...form.value })
    const lx = props.lexicon || {}
    toast.add({
      severity: 'success',
      summary: lx.localizator_success || 'Success',
      detail: isEdit.value
        ? (lx.localizator_content_updated || lx.localizator_save || 'Saved')
        : (lx.localizator_content_created || lx.localizator_item_create || 'Created'),
      life: 3000,
    })
    await loadAllItems()
    const savedKey = form.value.key
    if (isEdit.value && form.value.id) {
      await selectLocaleKey(savedKey || selectedLocaleKey.value)
    } else if (savedKey) {
      await selectLocaleKey(savedKey)
    } else {
      await refreshAll()
    }
  } catch (e) {
    showError(e)
  } finally {
    saving.value = false
  }
}

async function runAction(action, extra = {}) {
  await postConnector({ action, ...extra })
  await refreshAll()
  const lx = props.lexicon || {}
  toast.add({
    severity: 'success',
    summary: lx.localizator_success || 'Success',
    life: 3000,
  })
}

function toggleActive(enable) {
  if (!form.value.id) return
  const action = enable ? 'mgr/content/enable' : 'mgr/content/disable'
  runAction(action, { id: form.value.id, key: form.value.key || '' }).catch(showError)
}

function confirmRemoveCurrent() {
  if (!form.value.id) return
  const lx = props.lexicon || {}
  confirmDialog.require({
    message: lx.localizator_items_remove_confirm || 'Are you sure you want to delete?',
    header: lx.localizator_items_remove || 'Delete',
    icon: 'pi pi-exclamation-triangle',
    accept: () => {
      runAction('mgr/content/remove', { id: form.value.id, key: form.value.key || '' }).catch(showError)
    },
  })
}

function translateCurrent() {
  if (!form.value.key) return
  const lx = props.lexicon || {}
  confirmDialog.require({
    message: lx.localizator_translate_confirm || 'Translate this localization?',
    header: lx.localizator_translate || 'Translate',
    icon: 'pi pi-language',
    accept: () => {
      postConnector({
        action: 'mgr/content/translate',
        resource_id: props.resourceId,
        keys: JSON.stringify([form.value.key]),
      })
        .then(async () => {
          await loadLocalization(form.value.id)
          toast.add({
            severity: 'success',
            summary: lx.localizator_success || 'Success',
            detail: lx.localizator_translate || 'Translate',
            life: 3000,
          })
        })
        .catch(showError)
    },
  })
}

async function bootstrap() {
  formConfigError.value = null
  await loadFormConfig(0)
  await loadAllItems()
  if (activeLanguages.value.length === 0) {
    return
  }
  const preferredKey = items.value[0]?.key || activeLanguages.value[0].key
  await selectLocaleKey(preferredKey)
}

onMounted(() => {
  bootstrap()
})

watch(() => props.resourceId, () => {
  selectedLocaleKey.value = ''
  bootstrap()
})
</script>

<style scoped>
.content-grid {
  width: 100%;
  max-width: 100%;
  min-width: 0;
  box-sizing: border-box;
}

.content-grid__locale-bar {
  align-items: flex-end;
  max-width: 100%;
}

.content-grid__locale-select {
  flex: 1 1 auto;
  min-width: 0;
  max-width: 100%;
}

.content-grid__locale-dropdown {
  width: 100%;
  max-width: 100%;
}

.content-grid__select-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.content-grid__active-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.content-grid__form {
  padding: 0;
}

.content-grid__loading,
.content-grid__empty {
  padding: 2rem 1rem;
  text-align: center;
  color: var(--loc-text-muted, #64748b);
}

.content-form-tabs {
  margin-bottom: 0.25rem;
}
</style>
