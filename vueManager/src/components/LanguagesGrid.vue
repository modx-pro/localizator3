<template>
  <div class="languages-grid vueApp">
    <Toast />
    <ConfirmDialog />

    <div class="flex justify-content-between align-items-center mb-3">
      <h2 class="m-0">{{ lexicon.localizator_languages || 'Localization' }}</h2>
      <div class="flex gap-2">
        <InputText
          v-model="searchQuery"
          :placeholder="lexicon.localizator_grid_search || 'Search'"
          class="w-16rem"
          @keyup.enter="loadLanguages"
        />
        <Button
          :label="lexicon.localizator_language_create || 'Add localization'"
          icon="pi pi-plus"
          @click="openCreateDialog"
        />
      </div>
    </div>

    <DataTable
      :value="languages"
      :loading="loading"
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
      <Column field="key" :header="lexicon.localizator_key || 'Key'" sortable />
      <Column field="name" :header="lexicon.localizator_language_name || 'Name'" sortable />
      <Column field="http_host" :header="lexicon.localizator_language_http_host || 'HTTP Host'" sortable />
      <Column field="cultureKey" :header="lexicon.localizator_language_cultureKey || 'Culture'" sortable />
      <Column field="active" :header="lexicon.localizator_active || 'Active'" sortable>
        <template #body="{ data }">
          <i
            :class="data.active ? 'pi pi-check text-green-500' : 'pi pi-times text-gray-400'"
            :title="data.active ? lexicon.localizator_item_enable : lexicon.localizator_item_disable"
          />
        </template>
      </Column>
      <Column :header="lexicon.localizator_grid_actions || 'Actions'" style="width: 10rem">
        <template #body="{ data }">
          <div class="flex gap-1">
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
      :header="isEdit ? (lexicon.localizator_language_update || 'Update') : (lexicon.localizator_language_create || 'Create')"
      modal
      :style="{ width: 'min(90vw, 36rem)' }"
      :closable="true"
      appendTo="self"
      @hide="resetForm"
    >
      <form @submit.prevent="submitForm" class="languages-form flex flex-column gap-3">
        <div class="languages-form-grid">
          <div class="field-group">
            <label for="key" class="field-label">{{ lexicon.localizator_language_key || 'Key' }} *</label>
            <InputText id="key" v-model="form.key" required :disabled="isEdit" class="w-full" />
          </div>
          <div class="field-group">
            <label for="name" class="field-label">{{ lexicon.localizator_language_name || 'Name' }}</label>
            <InputText id="name" v-model="form.name" class="w-full" />
          </div>
          <div class="field-group field-group-full">
            <label for="http_host" class="field-label">{{ lexicon.localizator_language_http_host || 'HTTP Host' }} *</label>
            <InputText id="http_host" v-model="form.http_host" required :disabled="isEdit" class="w-full" />
          </div>
          <div class="field-group">
            <label for="cultureKey" class="field-label">{{ lexicon.localizator_language_cultureKey || 'Culture' }}</label>
            <InputText id="cultureKey" v-model="form.cultureKey" class="w-full" />
          </div>
          <div class="field-group field-group-full">
            <label for="description" class="field-label">{{ lexicon.localizator_language_description || 'Description' }}</label>
            <Textarea id="description" v-model="form.description" rows="3" class="w-full" auto-resize />
          </div>
        </div>
        <div class="flex align-items-center gap-2">
          <Checkbox id="active" v-model="form.active" :binary="true" input-id="active" />
          <label for="active" class="field-label mb-0">{{ lexicon.localizator_active || 'Active' }}</label>
        </div>
        <div class="flex justify-content-end gap-2 pt-2 border-top-1 surface-border">
          <Button type="button" :label="lexicon.localizator_cancel || 'Cancel'" severity="secondary" @click="dialogVisible = false" />
          <Button type="submit" :label="isEdit ? (lexicon.localizator_item_update || 'Update') : (lexicon.localizator_item_create || 'Create')" />
        </div>
      </form>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'

const connectorUrl = typeof localizator !== 'undefined' && localizator?.config?.connector_url
  ? localizator.config.connector_url
  : '/assets/components/localizator3/connector.php'

const modAuth = typeof localizator !== 'undefined' && localizator?.config?.modAuth
  ? localizator.config.modAuth
  : (typeof MODx !== 'undefined' && MODx?.config?.modAuth) || ''

const lexicon = ref({})
const languages = ref([])
const loading = ref(false)
const totalRecords = ref(0)
const first = ref(0)
const searchQuery = ref('')
const lazyParams = ref({
  start: 0,
  limit: 10,
  sort: 'rank',
  dir: 'ASC',
})
const dialogVisible = ref(false)
const isEdit = ref(false)
const form = reactive({
  id: null,
  key: '',
  name: '',
  http_host: '',
  cultureKey: '',
  description: '',
  active: true,
})

const confirm = useConfirm()
const toast = useToast()

async function api(action, params = {}) {
  const bodyParams = { action, ...params }
  if (modAuth) bodyParams.HTTP_MODAUTH = modAuth
  const body = new URLSearchParams(bodyParams)
  const res = await fetch(connectorUrl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: body.toString(),
  })
  const data = await res.json()
  if (!data.success && data.message) {
    throw new Error(data.message)
  }
  return data
}

function loadLexicon() {
  if (typeof localizator !== 'undefined' && localizator.config?.lexicon) {
    lexicon.value = { ...localizator.config.lexicon }
    return
  }
  if (typeof MODx !== 'undefined' && MODx.lexicon) {
    const keys = [
      'localizator_languages', 'localizator_language_create', 'localizator_language_update',
      'localizator_key', 'localizator_language_name', 'localizator_language_http_host',
      'localizator_language_cultureKey', 'localizator_language_description', 'localizator_active',
      'localizator_grid_search', 'localizator_grid_actions', 'localizator_item_update',
      'localizator_item_create', 'localizator_item_enable', 'localizator_item_disable',
      'localizator_item_remove', 'localizator_items_remove', 'localizator_items_remove_confirm',
      'localizator_cancel', 'localizator_success', 'localizator_error', 'localizator_save',
      'localizator_language_key', 'localizator_item_remove_confirm',
      'localizator_language_updated', 'localizator_language_created', 'localizator_deleted',
      'localizator_enabled', 'localizator_disabled',
    ]
    keys.forEach((k) => {
      if (MODx.lexicon[k]) lexicon.value[k] = MODx.lexicon[k]
    })
  }
}

async function loadLanguages() {
  loading.value = true
  try {
    const params = {
      start: lazyParams.value.start,
      limit: lazyParams.value.limit,
      sort: lazyParams.value.sort,
      dir: lazyParams.value.dir,
    }
    if (searchQuery.value) params.query = searchQuery.value

    const data = await api('mgr/language/getlist', params)
    languages.value = data.results || []
    totalRecords.value = data.total || 0
  } catch (e) {
    toast.add({ severity: 'error', summary: lexicon.value.localizator_error || 'Error', detail: e.message, life: 5000 })
  } finally {
    loading.value = false
  }
}

function onPage(event) {
  lazyParams.value = {
    ...lazyParams.value,
    start: event.first,
    limit: event.rows,
  }
  first.value = event.first
  loadLanguages()
}

function onSort(event) {
  lazyParams.value = {
    ...lazyParams.value,
    sort: event.sortField || 'rank',
    dir: event.sortOrder === 1 ? 'ASC' : 'DESC',
  }
  loadLanguages()
}

function openCreateDialog() {
  isEdit.value = false
  resetForm()
  form.active = true
  dialogVisible.value = true
}

function openEditDialog(row) {
  isEdit.value = true
  form.id = row.id
  form.key = row.key
  form.name = row.name
  form.http_host = row.http_host
  form.cultureKey = row.cultureKey || ''
  form.description = row.description || ''
  form.active = !!row.active
  dialogVisible.value = true
}

function resetForm() {
  form.id = null
  form.key = ''
  form.name = ''
  form.http_host = ''
  form.cultureKey = ''
  form.description = ''
  form.active = true
}

async function submitForm() {
  try {
    if (isEdit.value) {
      await api('mgr/language/update', {
        id: form.id,
        key: form.key,
        name: form.name,
        http_host: form.http_host,
        cultureKey: form.cultureKey,
        description: form.description,
        active: form.active ? 1 : 0,
      })
      toast.add({ severity: 'success', summary: lexicon.value.localizator_success || 'Success', detail: lexicon.value.localizator_language_updated || 'Language updated', life: 3000 })
    } else {
      await api('mgr/language/create', {
        key: form.key,
        name: form.name,
        http_host: form.http_host,
        cultureKey: form.cultureKey,
        description: form.description,
        active: form.active ? 1 : 0,
      })
      toast.add({ severity: 'success', summary: lexicon.value.localizator_success || 'Success', detail: lexicon.value.localizator_language_created || 'Language created', life: 3000 })
    }
    dialogVisible.value = false
    loadLanguages()
  } catch (e) {
    toast.add({ severity: 'error', summary: lexicon.value.localizator_error || 'Error', detail: e.message, life: 5000 })
  }
}

function confirmRemove(ids) {
  confirm.require({
    message: ids.length > 1
      ? (lexicon.value.localizator_items_remove_confirm || 'Are you sure you want to delete these entries?')
      : (lexicon.value.localizator_item_remove_confirm || 'Are you sure you want to delete this record?'),
    header: lexicon.value.localizator_items_remove || 'Delete',
    icon: 'pi pi-exclamation-triangle',
    accept: () => removeItems(ids),
  })
}

async function removeItems(ids) {
  try {
    await api('mgr/language/remove', { ids: JSON.stringify(ids) })
    toast.add({ severity: 'success', summary: lexicon.value.localizator_success || 'Success', detail: lexicon.value.localizator_deleted || 'Deleted', life: 3000 })
    loadLanguages()
  } catch (e) {
    toast.add({ severity: 'error', summary: lexicon.value.localizator_error || 'Error', detail: e.message, life: 5000 })
  }
}

async function enableItems(ids) {
  try {
    await api('mgr/language/enable', { ids: JSON.stringify(ids) })
    toast.add({ severity: 'success', summary: lexicon.value.localizator_success || 'Success', detail: lexicon.value.localizator_enabled || 'Enabled', life: 3000 })
    loadLanguages()
  } catch (e) {
    toast.add({ severity: 'error', summary: lexicon.value.localizator_error || 'Error', detail: e.message, life: 5000 })
  }
}

async function disableItems(ids) {
  try {
    await api('mgr/language/disable', { ids: JSON.stringify(ids) })
    toast.add({ severity: 'success', summary: lexicon.value.localizator_success || 'Success', detail: lexicon.value.localizator_disabled || 'Disabled', life: 3000 })
    loadLanguages()
  } catch (e) {
    toast.add({ severity: 'error', summary: lexicon.value.localizator_error || 'Error', detail: e.message, life: 5000 })
  }
}

onMounted(() => {
  loadLexicon()
  loadLanguages()
})
</script>

<style scoped>
.languages-form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem 1.5rem;
}

.field-group {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.field-group-full {
  grid-column: 1 / -1;
}

.field-label {
  font-weight: 500;
  min-width: 0;
}

@media (max-width: 480px) {
  .languages-form-grid {
    grid-template-columns: 1fr;
  }

  .field-group-full {
    grid-column: 1;
  }
}
</style>
