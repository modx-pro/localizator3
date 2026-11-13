<template>
  <div class="languages-grid vueApp">
    <Toast />
    <ConfirmDialog />

    <header class="languages-grid__intro">
      <h1 class="languages-grid__title">
        {{ lx.localizator_languages || 'Languages' }}
      </h1>
      <p class="loc-section-bar">
        {{ lx.localizator_languages_section_desc || 'Manage site languages, HTTP hosts and culture keys for localization.' }}
      </p>
      <dl class="loc-stats">
        <div class="loc-stat-card">
          <dt class="loc-stat-card__label">{{ lx.localizator_stats_total || 'Total languages' }}</dt>
          <dd class="loc-stat-card__value">{{ stats.total }}</dd>
        </div>
        <div class="loc-stat-card">
          <dt class="loc-stat-card__label">{{ lx.localizator_stats_active || 'Active' }}</dt>
          <dd class="loc-stat-card__value">{{ stats.active }}</dd>
        </div>
        <div class="loc-stat-card">
          <dt class="loc-stat-card__label">{{ lx.localizator_stats_inactive || 'Inactive' }}</dt>
          <dd class="loc-stat-card__value">{{ stats.inactive }}</dd>
        </div>
      </dl>
    </header>

    <div class="loc-toolbar languages-grid__toolbar">
      <IconField class="languages-grid__search">
        <InputIcon class="pi pi-search" />
        <InputText
          id="languages-search"
          v-model="searchQuery"
          :placeholder="lx.localizator_grid_search || 'Search'"
          :aria-label="lx.localizator_grid_search || 'Search'"
          @keyup.enter="refresh"
        />
      </IconField>
      <span class="loc-toolbar__spacer" aria-hidden="true" />
      <Button
        :label="lx.localizator_language_create || 'Add localization'"
        icon="pi pi-plus"
        :severity="saveSeverity"
        @click="openCreateDialog"
      />
    </div>

    <div class="loc-panel">
      <DataTable
        :value="items"
        :loading="loading"
        :lazy="true"
        :paginator="true"
        :rows="rows"
        :total-records="totalRecords"
        :first="first"
        data-key="id"
        striped-rows
        scrollable
        @page="onPage"
        @sort="onSort"
      >
        <Column field="key" :header="lx.localizator_key || 'Key'" sortable />
        <Column field="name" :header="lx.localizator_language_name || 'Name'" sortable />
        <Column field="http_host" :header="lx.localizator_language_http_host || 'HTTP Host'" sortable />
        <Column field="cultureKey" :header="lx.localizator_language_cultureKey || 'Culture'" sortable />
        <Column field="active" :header="lx.localizator_active || 'Active'" sortable style="width: 7rem">
          <template #body="{ data }">
            <Tag
              :value="isActive(data)
  ? (lx.localizator_active || 'Active')
  : (lx.localizator_disabled || 'Inactive')"
              :severity="isActive(data) ? 'success' : 'secondary'"
              rounded
            />
          </template>
        </Column>
        <Column :header="lx.localizator_grid_actions || 'Actions'" style="width: 10rem">
          <template #body="{ data }">
            <div class="loc-row-actions">
              <Button
                icon="pi pi-pencil"
                severity="secondary"
                text
                rounded
                size="small"
                :title="lx.localizator_item_update"
                @click="openEditDialog(data)"
              />
              <Button
                icon="pi pi-power-off"
                :severity="isActive(data) ? 'secondary' : 'success'"
                text
                rounded
                size="small"
                :title="isActive(data) ? lx.localizator_item_disable : lx.localizator_item_enable"
                @click="toggleActive(data)"
              />
              <Button
                icon="pi pi-trash"
                severity="danger"
                text
                rounded
                size="small"
                :title="lx.localizator_item_remove"
                @click="confirmRemove([data.id])"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog
      v-model:visible="dialogVisible"
      :header="isEdit ? (lx.localizator_language_update || 'Update') : (lx.localizator_language_create || 'Create')"
      modal
      :style="{ width: 'min(92vw, 40rem)' }"
      :closable="true"
      appendTo="self"
      @hide="closeDialog"
    >
      <form @submit.prevent="submitForm" class="loc-form">
        <div class="loc-form-grid">
          <div class="loc-form-field">
            <label for="key" class="loc-form-label">
              {{ lx.localizator_language_key || 'Key' }}
              <span class="loc-form-label__required">*</span>
            </label>
            <InputText id="key" v-model="formData.key" required :disabled="isEdit" />
          </div>
          <div class="loc-form-field">
            <label for="name" class="loc-form-label">{{ lx.localizator_language_name || 'Name' }}</label>
            <InputText id="name" v-model="formData.name" />
          </div>
          <div class="loc-form-field loc-form-field--full">
            <label for="http_host" class="loc-form-label">
              {{ lx.localizator_language_http_host || 'HTTP Host' }}
              <span class="loc-form-label__required">*</span>
            </label>
            <InputText
              id="http_host"
              v-model="formData.http_host"
              required
              :placeholder="lx.localizator_language_http_host_placeholder || 'project.test/ru/'"
            />
            <small class="loc-form-hint">
              {{ lx.localizator_language_http_host_hint || 'Without http:// or https://, e.g. project.test/ru/' }}
            </small>
          </div>
          <div class="loc-form-field">
            <label for="cultureKey" class="loc-form-label">{{ lx.localizator_language_cultureKey || 'Culture' }}</label>
            <InputText id="cultureKey" v-model="formData.cultureKey" />
          </div>
          <div class="loc-form-field loc-form-field--full">
            <label for="description" class="loc-form-label">{{ lx.localizator_language_description || 'Description' }}</label>
            <Textarea id="description" v-model="formData.description" rows="3" />
          </div>
        </div>
        <div class="loc-form-checkbox">
          <Checkbox id="active" v-model="formData.active" :binary="true" input-id="active" />
          <label for="active" class="loc-form-checkbox__label">{{ lx.localizator_active || 'Active' }}</label>
        </div>
        <div class="loc-form-footer">
          <Button type="button" :label="lx.localizator_cancel || 'Cancel'" severity="secondary" @click="closeDialog" />
          <Button
            type="submit"
            :label="isEdit ? (lx.localizator_item_update || 'Update') : (lx.localizator_item_create || 'Create')"
            icon="pi pi-check"
            :severity="saveSeverity"
          />
        </div>
      </form>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { getPrimarySaveSeverity } from '../app/primevueTheme.js'
import {
  useConnector,
  useLexicon,
  useDataTable,
  useGridCrud,
  useConfirmAction,
} from '../composables/index.js'

const saveSeverity = getPrimarySaveSeverity()

const connectorUrl = typeof localizator !== 'undefined' && localizator?.config?.connector_url
  ? localizator.config.connector_url
  : '/assets/components/localizator3/connector.php'

const modAuth = typeof localizator !== 'undefined' && localizator?.config?.modAuth
  ? localizator.config.modAuth
  : (typeof MODx !== 'undefined' && MODx?.config?.modAuth) || ''

const rawLexicon = ref({})
const { lexicon, t } = useLexicon(rawLexicon)
const lx = computed(() => lexicon.value)

const connector = useConnector(connectorUrl, modAuth)
const { showSuccess, showError, confirmAction } = useConfirmAction(lexicon)

const {
  loading,
  items,
  totalRecords,
  first,
  rows,
  searchQuery,
  params,
  onPage,
  onSort,
  refresh,
  setLoadFn,
} = useDataTable({ defaultRows: 10, defaultSortField: 'rank', defaultSortOrder: 'ASC' })

const {
  dialogVisible,
  isEdit,
  formData,
  openCreate,
  openEdit,
  closeDialog,
  save,
  enable,
  disable,
  remove,
} = useGridCrud({
  connector,
  lexicon: { t },
  actionPrefix: 'mgr/language',
  idsFormat: 'json',
  onSuccess: showSuccess,
  onError: showError,
})

const stats = ref({ total: 0, active: 0, inactive: 0 })

function normalizeHttpHost(value) {
  if (!value) return ''
  return String(value).trim().replace(/^https?:\/\//i, '')
}

function isActive(value) {
  const v = typeof value === 'object' && value !== null ? value.active : value
  return v === true || v === 1 || v === '1'
}

function loadLexicon() {
  if (typeof localizator !== 'undefined' && localizator.config?.lexicon) {
    rawLexicon.value = { ...localizator.config.lexicon }
    return
  }
  if (typeof MODx !== 'undefined' && MODx.lexicon) {
    const next = {}
    for (const [key, value] of Object.entries(MODx.lexicon)) {
      if (key.startsWith('localizator')) {
        next[key] = value
      }
    }
    rawLexicon.value = next
  }
}

function updateStatsFromList(list, total) {
  stats.value = {
    total: total ?? list.length,
    active: list.filter((item) => isActive(item)).length,
    inactive: list.filter((item) => !isActive(item)).length,
  }
}

async function loadStats() {
  try {
    const data = await connector.post('mgr/language/getlist', {
      start: 0,
      limit: 9999,
      sort: 'rank',
      dir: 'ASC',
    })
    updateStatsFromList(data.results || [], data.total || 0)
  } catch {
    updateStatsFromList(items.value, totalRecords.value)
  }
}

async function loadLanguages() {
  loading.value = true
  try {
    const query = { ...params.value }
    if (!query.query) {
      delete query.query
    }
    const data = await connector.post('mgr/language/getlist', query)
    items.value = data.results || []
    totalRecords.value = data.total || 0
    await loadStats()
  } catch (e) {
    showError(e.message)
  } finally {
    loading.value = false
  }
}

setLoadFn(loadLanguages)

function openCreateDialog() {
  openCreate({
    id: null,
    key: '',
    name: '',
    http_host: '',
    cultureKey: '',
    description: '',
    active: true,
  })
}

function openEditDialog(row) {
  openEdit({
    id: row.id,
    key: row.key,
    name: row.name,
    http_host: normalizeHttpHost(row.http_host),
    cultureKey: row.cultureKey || '',
    description: row.description || '',
    active: isActive(row),
  })
}

async function submitForm() {
  const httpHost = normalizeHttpHost(formData.value.http_host)
  if (!httpHost) {
    showError(t('localizator_language_err_no_http_host', 'HTTP HOST is required'))
    return
  }
  formData.value.http_host = httpHost
  try {
    await save({
      id: formData.value.id,
      key: formData.value.key,
      name: formData.value.name,
      http_host: httpHost,
      cultureKey: formData.value.cultureKey,
      description: formData.value.description,
      active: formData.value.active ? 1 : 0,
    })
    await refresh()
  } catch {
  // toast via onError
  }
}

function confirmRemove(ids) {
  confirmAction({
    message: ids.length > 1
      ? t('localizator_items_remove_confirm', 'Are you sure you want to delete these entries?')
      : t('localizator_item_remove_confirm', 'Are you sure you want to delete this record?'),
    header: t('localizator_items_remove', 'Delete'),
    accept: async () => {
      await remove(ids)
      await refresh()
    },
  })
}

async function toggleActive(row) {
  try {
    if (isActive(row)) {
      await disable([row.id])
    } else {
      await enable([row.id])
    }
    await refresh()
  } catch {
  // toast via onError
  }
}

onMounted(() => {
  loadLexicon()
  loadLanguages()
})
</script>
