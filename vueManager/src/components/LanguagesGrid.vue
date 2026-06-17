<template>
  <div class="languages-grid vueApp">
    <Toast />
    <ConfirmDialog />

    <p class="loc-section-bar">
      {{ lexicon.localizator_languages_section_desc || 'Manage site languages, HTTP hosts and culture keys for localization.' }}
    </p>

    <div class="loc-stats">
      <div class="loc-stat-card">
        <div class="loc-stat-card__value">{{ stats.total }}</div>
        <div class="loc-stat-card__label">{{ lexicon.localizator_stats_total || 'Total languages' }}</div>
      </div>
      <div class="loc-stat-card">
        <div class="loc-stat-card__value">{{ stats.active }}</div>
        <div class="loc-stat-card__label">{{ lexicon.localizator_stats_active || 'Active' }}</div>
      </div>
      <div class="loc-stat-card">
        <div class="loc-stat-card__value">{{ stats.inactive }}</div>
        <div class="loc-stat-card__label">{{ lexicon.localizator_stats_inactive || 'Inactive' }}</div>
      </div>
    </div>

    <div class="loc-toolbar">
      <div class="loc-toolbar__field">
        <label for="languages-search" class="loc-toolbar__label">
          {{ lexicon.localizator_grid_search || 'Search' }}
        </label>
        <IconField class="loc-toolbar__input">
          <InputIcon class="pi pi-search" />
          <InputText
            id="languages-search"
            v-model="searchQuery"
            :placeholder="lexicon.localizator_grid_search || 'Search'"
            @keyup.enter="loadLanguages"
          />
        </IconField>
      </div>
      <div class="loc-toolbar__actions">
        <Button
          :label="lexicon.localizator_language_create || 'Add localization'"
          icon="pi pi-plus"
          @click="openCreateDialog"
        />
      </div>
    </div>

    <div class="loc-panel">
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
        <Column field="active" :header="lexicon.localizator_active || 'Active'" sortable style="width: 7rem">
          <template #body="{ data }">
            <Tag
              :value="data.active
                ? (lexicon.localizator_item_enable || 'Active')
                : (lexicon.localizator_item_disable || 'Inactive')"
              :severity="data.active ? 'success' : 'secondary'"
              rounded
            />
          </template>
        </Column>
        <Column :header="lexicon.localizator_grid_actions || 'Actions'" style="width: 10rem">
          <template #body="{ data }">
            <div class="loc-row-actions">
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
                icon="pi pi-power-off"
                :severity="data.active ? 'secondary' : 'success'"
                text
                rounded
                size="small"
                :title="data.active ? lexicon.localizator_item_disable : lexicon.localizator_item_enable"
                @click="data.active ? disableItems([data.id]) : enableItems([data.id])"
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
    </div>

    <Dialog
      v-model:visible="dialogVisible"
      :header="isEdit ? (lexicon.localizator_language_update || 'Update') : (lexicon.localizator_language_create || 'Create')"
      modal
      :style="{ width: 'min(92vw, 40rem)' }"
      :closable="true"
      appendTo="self"
      @hide="resetForm"
    >
      <form @submit.prevent="submitForm" class="loc-form">
        <div class="loc-form-grid">
          <div class="loc-form-field">
            <label for="key" class="loc-form-label">
              {{ lexicon.localizator_language_key || 'Key' }}
              <span class="loc-form-label__required">*</span>
            </label>
            <InputText id="key" v-model="form.key" required :disabled="isEdit" />
          </div>
          <div class="loc-form-field">
            <label for="name" class="loc-form-label">{{ lexicon.localizator_language_name || 'Name' }}</label>
            <InputText id="name" v-model="form.name" />
          </div>
          <div class="loc-form-field loc-form-field--full">
            <label for="http_host" class="loc-form-label">
              {{ lexicon.localizator_language_http_host || 'HTTP Host' }}
              <span class="loc-form-label__required">*</span>
            </label>
            <InputText
              id="http_host"
              v-model="form.http_host"
              required
              :placeholder="lexicon.localizator_language_http_host_placeholder || 'project.test/ru/'"
            />
            <small class="loc-form-hint">
              {{ lexicon.localizator_language_http_host_hint || 'Without http:// or https://, e.g. project.test/ru/' }}
            </small>
          </div>
          <div class="loc-form-field">
            <label for="cultureKey" class="loc-form-label">{{ lexicon.localizator_language_cultureKey || 'Culture' }}</label>
            <InputText id="cultureKey" v-model="form.cultureKey" />
          </div>
          <div class="loc-form-field loc-form-field--full">
            <label for="description" class="loc-form-label">{{ lexicon.localizator_language_description || 'Description' }}</label>
            <Textarea id="description" v-model="form.description" rows="3" />
          </div>
        </div>
        <div class="loc-form-checkbox">
          <Checkbox id="active" v-model="form.active" :binary="true" input-id="active" />
          <label for="active" class="loc-form-checkbox__label">{{ lexicon.localizator_active || 'Active' }}</label>
        </div>
        <div class="loc-form-footer">
          <Button type="button" :label="lexicon.localizator_cancel || 'Cancel'" severity="secondary" @click="dialogVisible = false" />
          <Button
            type="submit"
            :label="isEdit ? (lexicon.localizator_item_update || 'Update') : (lexicon.localizator_item_create || 'Create')"
            icon="pi pi-check"
          />
        </div>
      </form>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useToast, useConfirm } from 'primevue'

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
const stats = ref({ total: 0, active: 0, inactive: 0 })
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

function normalizeHttpHost(value) {
  if (!value) return ''
  return String(value).trim().replace(/^https?:\/\//i, '')
}

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
  if (!data.success) {
    throw new Error(data.message || 'Request failed')
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
      'localizator_languages', 'localizator_languages_section_desc',
      'localizator_stats_total', 'localizator_stats_active', 'localizator_stats_inactive',
      'localizator_language_create', 'localizator_language_update',
      'localizator_key', 'localizator_language_name', 'localizator_language_http_host',
      'localizator_language_http_host_hint', 'localizator_language_http_host_placeholder',
      'localizator_language_err_no_http_host',
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

function updateStatsFromList(list, total) {
  stats.value = {
    total: total ?? list.length,
    active: list.filter((item) => item.active).length,
    inactive: list.filter((item) => !item.active).length,
  }
}

async function loadStats() {
  try {
    const params = { start: 0, limit: 9999, sort: 'rank', dir: 'ASC' }
    if (searchQuery.value) params.query = searchQuery.value
    const data = await api('mgr/language/getlist', params)
    updateStatsFromList(data.results || [], data.total || 0)
  } catch {
    updateStatsFromList(languages.value, totalRecords.value)
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
    await loadStats()
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
  form.http_host = normalizeHttpHost(row.http_host)
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
  const httpHost = normalizeHttpHost(form.http_host)
  if (!httpHost) {
    toast.add({
      severity: 'error',
      summary: lexicon.value.localizator_error || 'Error',
      detail: lexicon.value.localizator_language_err_no_http_host || 'HTTP HOST is required',
      life: 5000,
    })
    return
  }

  form.http_host = httpHost

  try {
    if (isEdit.value) {
      await api('mgr/language/update', {
        id: form.id,
        key: form.key,
        name: form.name,
        http_host: httpHost,
        cultureKey: form.cultureKey,
        description: form.description,
        active: form.active ? 1 : 0,
      })
      toast.add({ severity: 'success', summary: lexicon.value.localizator_success || 'Success', detail: lexicon.value.localizator_language_updated || 'Language updated', life: 3000 })
    } else {
      await api('mgr/language/create', {
        key: form.key,
        name: form.name,
        http_host: httpHost,
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
