<template>
  <div class="content-grid vueApp">
    <Toast />
    <ConfirmDialog />

    <div class="content-grid__toolbar">
      <div class="content-grid__toolbar-actions">
        <Button
          :label="lexicon.localizator_add || 'Add'"
          icon="pi pi-plus"
          @click="openCreateDialog"
        />
        <Button
          :label="lexicon.localizator_translate || 'Translate'"
          icon="pi pi-language"
          :disabled="!hasSelection"
          @click="translateSelected"
        />
      </div>
      <InputText
        v-model="searchQuery"
        :placeholder="lexicon.localizator_grid_search || 'Search'"
        class="content-grid__search"
        @keyup.enter="loadContent"
      />
    </div>

    <DataTable
      :value="items"
      :loading="loading"
      v-model:selection="selectedItems"
      :lazy="true"
      :paginator="true"
      :rows="10"
      :total-records="totalRecords"
      :first="first"
      data-key="id"
      striped-rows
      scrollable
      @page="onPage"
      @sort="onSort"
    >
      <Column selection-mode="multiple" header-style="width: 3rem" />
      <Column field="_key" :header="lexicon.localizator__key || 'Language'" sortable />
      <Column field="pagetitle" :header="lexicon.localizator_pagetitle || 'Title'" sortable />
      <Column field="seotitle" :header="lexicon.localizator_seotitle || 'SEO Title'" sortable />
      <Column field="active" :header="lexicon.localizator_active || 'Active'" sortable>
        <template #body="{ data }">
          <i
            :class="data.active ? 'pi pi-check content-grid__status--active' : 'pi pi-times content-grid__status--inactive'"
            :title="data.active ? lexicon.localizator_item_disable : lexicon.localizator_item_enable"
          />
        </template>
      </Column>
      <Column :header="lexicon.localizator_grid_actions || 'Actions'" style="width: 10rem">
        <template #body="{ data }">
          <div class="content-grid__actions">
            <Button
              icon="pi pi-pencil"
              severity="secondary"
              text
              rounded
              size="small"
              :title="lexicon.localizator_item_update"
              @click="openEditDialog(data)"
            />
            <Button
              v-if="!data.active"
              icon="pi pi-power-off"
              severity="success"
              text
              rounded
              size="small"
              :title="lexicon.localizator_item_enable"
              @click="enableItems([data.id])"
            />
            <Button
              v-else
              icon="pi pi-power-off"
              severity="secondary"
              text
              rounded
              size="small"
              :title="lexicon.localizator_item_disable"
              @click="disableItems([data.id])"
            />
            <Button
              icon="pi pi-trash"
              severity="danger"
              text
              rounded
              size="small"
              :title="lexicon.localizator_item_remove"
              @click="confirmRemove([data.id])"
            />
          </div>
        </template>
      </Column>
    </DataTable>

    <Dialog
      v-model:visible="dialogVisible"
      :header="isEdit ? (lexicon.localizator_item_update || 'Edit') : (lexicon.localizator_add || 'Add')"
      modal
      :style="{ width: '50rem' }"
      :closable="true"
      maximizable
      appendTo="self"
      @hide="resetForm"
    >
      <form v-if="formConfig" @submit.prevent="submitForm" class="content-grid__form">
        <Tabs v-model:value="activeTab" class="content-form-tabs">
          <TabList>
            <Tab v-for="tab in visibleTabs" :key="tab.id" :value="tab.id">
              {{ tab.caption }}
            </Tab>
          </TabList>
          <TabPanels>
            <TabPanel v-for="tab in visibleTabs" :key="tab.id" :value="tab.id">
            <div class="content-grid__form-grid">
              <div
                v-for="field in visibleFields(tab)"
                :key="field.field"
                class="content-grid__field"
                :class="{ 'content-grid__field--full': field.field === 'content' || field.type === 'textarea' || field.type === 'richtext' }"
              >
                <label v-if="field.caption" :for="'field-' + field.field" class="content-grid__label">
                  {{ field.caption }}
                  <span v-if="field.required" class="content-grid__required">*</span>
                </label>
                <InputText
                  v-if="field.type === 'text'"
                  :id="'field-' + field.field"
                  v-model="form[field.field]"
                  :required="field.required"
                  class="content-grid__input"
                />
                <Textarea
                  v-else-if="field.type === 'textarea' || field.type === 'richtext' || field.type === 'tv'"
                  :id="'field-' + field.field"
                  v-model="form[tvFieldName(field)]"
                  :required="field.required"
                  :rows="field.field === 'content' ? 8 : 4"
                  class="content-grid__input"
                />
                <div v-else-if="field.field === 'key'" class="content-grid__select-group">
                  <Select
                    :id="'field-' + field.field"
                    v-model="form[field.field]"
                    :options="languages"
                    option-label="name"
                    option-value="key"
                    :placeholder="field.caption"
                    :disabled="isEdit"
                    :empty-message="emptyLanguagesMessage"
                    class="content-grid__input"
                  />
                  <small v-if="!isEdit && languages.length === 0" class="content-grid__hint">
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
        <div class="content-grid__dialog-footer">
          <Button type="button" :label="lexicon.localizator_cancel || 'Cancel'" severity="secondary" @click="dialogVisible = false" />
          <Button
            type="submit"
            :label="isEdit ? (lexicon.localizator_save || 'Save') : (lexicon.localizator_item_create || 'Create')"
            icon="pi pi-check"
            :disabled="!isEdit && !form.key"
          />
        </div>
      </form>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'

const props = defineProps({
  resourceId: { type: Number, required: true },
  connectorUrl: { type: String, required: true },
  modAuth: { type: String, default: '' },
  lexicon: { type: Object, default: () => ({}) },
})

const loading = ref(false)
const items = ref([])
const selectedItems = ref([])
const totalRecords = ref(0)
const first = ref(0)
const searchQuery = ref('')
const sortField = ref('id')
const sortOrder = ref(-1)

const formConfig = ref(null)
const formConfigLoading = ref(false)
const formConfigError = ref(null)
const form = ref({})
const dialogVisible = ref(false)
const isEdit = ref(false)
const languages = ref([])
const activeTab = ref(null)

const toast = useToast()
const confirmDialog = useConfirm()
const hasSelection = computed(() => selectedItems.value?.length > 0)

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
  return fields.filter((f) => f.visible !== false)
}

function tvFieldName(field) {
  if (field.type === 'tv' && field.inputTV) {
    return 'tvlocalizator_' + field.inputTV
  }
  return field.field
}

function buildParams(extra = {}) {
  const params = { ...extra }
  if (props.modAuth) params.HTTP_MODAUTH = props.modAuth
  return new URLSearchParams(params)
}

function getConnectorUrl() {
  const u = props.connectorUrl
  if (!u || u.startsWith('http') || u.startsWith('/')) return u || ''
  if (typeof window !== 'undefined') return window.location.origin + '/' + u.replace(/^\//, '')
  return u
}

function loadFormConfig() {
  formConfigLoading.value = true
  formConfigError.value = null
  formConfig.value = null
  languages.value = []

  const url = getConnectorUrl()
  return fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: buildParams({
      action: 'mgr/content/getformconfig',
      resource_id: props.resourceId,
    }),
  })
    .then((r) => {
      if (!r.ok) throw new Error(`getformconfig ${r.status}: ${r.statusText}`)
      return r.json()
    })
    .then((data) => {
      if (data.success && data.object) {
        formConfig.value = {
          formtabs: data.object.formtabs,
          customization: data.object.customization,
          totalActiveLanguages: data.object.totalActiveLanguages ?? 0,
          existingCount: data.object.existingCount ?? 0,
        }
        languages.value = data.object.languages || []
      } else {
        const msg = data.message || (props.lexicon?.localizator_unknown_error || 'Unknown error')
        formConfigError.value = msg
        console.error('localizator3 getformconfig:', msg)
        toast.add({
          severity: 'error',
          summary: props.lexicon?.localizator_error || 'Error',
          detail: msg,
          life: 5000,
        })
      }
    })
    .catch((e) => {
      const msg = e.message || String(e)
      formConfigError.value = msg
      console.error('localizator3 getformconfig fetch error:', e)
      toast.add({
        severity: 'error',
        summary: props.lexicon?.localizator_error || 'Error',
        detail: msg,
        life: 5000,
      })
    })
    .finally(() => {
      formConfigLoading.value = false
    })
}

function loadContent() {
  loading.value = true
  const params = buildParams({
    action: 'mgr/content/getlist',
    resource_id: props.resourceId,
    start: first.value,
    limit: 10,
    sort: sortField.value,
    dir: sortOrder.value === 1 ? 'ASC' : 'DESC',
  })
  if (searchQuery.value) params.set('query', searchQuery.value)

  fetch(getConnectorUrl() + '?' + params)
    .then((r) => r.json())
    .then((data) => {
      items.value = data.results || []
      totalRecords.value = data.total || 0
    })
    .finally(() => (loading.value = false))
}

function onPage(e) {
  first.value = e.first
  loadContent()
}

function onSort(e) {
  sortField.value = e.sortField || 'id'
  sortOrder.value = e.sortOrder || -1
  loadContent()
}

function openCreateDialog() {
  isEdit.value = false
  form.value = { resource_id: props.resourceId, key: '', active: 1 }
  if (formConfig.value?.formtabs?.document?.fields) {
    for (const f of formConfig.value.formtabs.document.fields) {
      if (f.visible !== false && f.field !== 'id') {
        form.value[f.field] = form.value[f.field] ?? ''
      }
    }
  }
  dialogVisible.value = true
}

function openEditDialog(row) {
  isEdit.value = true
  form.value = { ...row, resource_id: props.resourceId }
  dialogVisible.value = true
  fetch(getConnectorUrl(), {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: buildParams({ action: 'mgr/content/getformconfig', resource_id: props.resourceId, loc_id: row.id }),
  })
    .then((r) => r.json())
    .then((data) => {
      if (data.success && data.object?.record) {
        form.value = { ...form.value, ...data.object.record }
      }
    })
}

function resetForm() {
  form.value = {}
}

function submitForm() {
  if (!isEdit.value && !form.value.key) {
    return
  }
  const action = isEdit.value ? 'mgr/content/update' : 'mgr/content/create'
  const payload = { action, ...form.value }
  const body = buildParams(payload)

  fetch(getConnectorUrl(), {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body,
  })
    .then((r) => r.json())
    .then((data) => {
      if (data.success) {
        dialogVisible.value = false
        loadContent()
        const lx = props.lexicon || {}
        const detail = isEdit.value
          ? (lx.localizator_content_updated || lx.localizator_language_updated || 'Translation updated')
          : (lx.localizator_content_created || lx.localizator_language_created || 'Translation created')
        toast.add({
          severity: 'success',
          summary: lx.localizator_success || 'Success',
          detail,
          life: 3000,
        })
      } else {
        toast.add({
          severity: 'error',
          summary: props.lexicon?.localizator_error || 'Error',
          detail: data.message || '',
          life: 5000,
        })
      }
    })
}

function enableItems(ids) {
  ids.forEach((id) => runAction('mgr/content/enable', { id }))
}

function disableItems(ids) {
  ids.forEach((id) => runAction('mgr/content/disable', { id }))
}

function runAction(action, extra = {}) {
  const body = buildParams({ action, ...extra })
  fetch(getConnectorUrl(), { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body })
    .then((r) => r.json())
    .then((data) => data.success && loadContent())
}

function confirmRemove(ids) {
  const lx = props.lexicon || {}
  confirmDialog.require({
    message: lx.localizator_items_remove_confirm || 'Are you sure you want to delete?',
    header: lx.localizator_items_remove || 'Delete',
    icon: 'pi pi-exclamation-triangle',
    accept: () => {
      ids.forEach((id) => runAction('mgr/content/remove', { id }))
    },
  })
}

function translateSelected() {
  if (!hasSelection.value) return
  const lx = props.lexicon || {}
  confirmDialog.require({
    message: lx.localizator_translate_confirm || 'Translate selected?',
    header: lx.localizator_translate || 'Translate',
    icon: 'pi pi-language',
    accept: () => {
      fetch(getConnectorUrl(), {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: buildParams({
          action: 'mgr/content/translate',
          resource_id: props.resourceId,
          start: 0,
        }),
      })
        .then((r) => r.json())
        .then((data) => {
          if (data.success) loadContent()
        })
    },
  })
}

onMounted(() => {
  loadFormConfig().then(loadContent)
})

watch(() => props.resourceId, () => {
  loadFormConfig().then(loadContent)
})
</script>

<style scoped>
.content-grid__toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.content-grid__toolbar-actions {
  display: flex;
  gap: 0.5rem;
}

.content-grid__search {
  width: 16rem;
}

.content-grid__form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  padding: 0.5rem 0;
}

.content-grid__field {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.content-grid__field--full {
  grid-column: 1 / -1;
}

.content-grid__label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--p-text-color, #374151);
}

.content-grid__required {
  color: #ef4444;
  margin-left: 2px;
}

.content-grid__dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 1rem;
}

.content-grid__actions {
  display: flex;
  gap: 0.25rem;
}

/* Status icons */
.content-grid__status--active {
  color: #22c55e;
}

.content-grid__status--inactive {
  color: #9ca3af;
}

/* Form layout */
.content-grid__form {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.content-grid__input {
  width: 100%;
}

.content-grid__select-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.content-grid__hint {
  color: var(--p-text-muted-color, #6b7280);
  font-size: 0.875rem;
}

@media (max-width: 480px) {
  .content-grid__form-grid {
    grid-template-columns: 1fr;
  }

  .content-grid__field--full {
    grid-column: 1;
  }
}
</style>
