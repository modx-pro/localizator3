/**
 * useGridCrud — CRUD operations for grid components
 *
 * Encapsulates create, read, update, delete, enable/disable actions
 * with consistent error handling and feedback.
 */
import { ref, unref } from 'vue'

export function useGridCrud(options = {}) {
  const {
    connector,
    lexicon,
    onSuccess,
    onError,
    actionPrefix = 'mgr/content',
    idsFormat = actionPrefix.includes('language') ? 'json' : 'csv',
  } = options

  const dialogVisible = ref(false)
  const editingItem = ref(null)
  const isEdit = ref(false)
  const formData = ref({})

  function t(key, fallback = '') {
    const lx = unref(lexicon)
    if (lx && typeof lx.t === 'function') {
      return lx.t(key, fallback)
    }
    if (lx && typeof lx === 'object') {
      return lx[key] || fallback
    }
    return fallback
  }

  function actionPath(name) {
    return `${actionPrefix.replace(/\/$/, '')}/${name}`
  }

  function formatIds(ids) {
    const arr = Array.isArray(ids) ? ids : [ids]
    return idsFormat === 'json' ? JSON.stringify(arr) : arr.join(',')
  }

  function openCreate(initialData = {}) {
    isEdit.value = false
    editingItem.value = null
    formData.value = { ...initialData }
    dialogVisible.value = true
  }

  function openEdit(item) {
    isEdit.value = true
    editingItem.value = item
    formData.value = { ...item }
    dialogVisible.value = true
  }

  function closeDialog() {
    dialogVisible.value = false
    editingItem.value = null
    formData.value = {}
    isEdit.value = false
  }

  async function executeAction(action, params, actionOptions = {}) {
    const { successMessage, errorMessage, suppressToast = false } = actionOptions

    try {
      const result = await connector.post(action, params)

      if (!suppressToast && successMessage && onSuccess) {
        onSuccess(successMessage)
      }

      return result
    } catch (error) {
      const msg = error.message || errorMessage || 'Action failed'

      if (onError) {
        onError(msg)
      }

      throw error
    }
  }

  async function enable(ids) {
    return executeAction(
      actionPath('enable'),
      { ids: formatIds(ids) },
      { successMessage: t('localizator_enabled', 'Enabled') }
    )
  }

  async function disable(ids) {
    return executeAction(
      actionPath('disable'),
      { ids: formatIds(ids) },
      { successMessage: t('localizator_disabled', 'Disabled') }
    )
  }

  async function remove(ids) {
    return executeAction(
      actionPath('remove'),
      { ids: formatIds(ids) },
      { successMessage: t('localizator_deleted', 'Deleted') }
    )
  }

  async function save(data, action = null) {
    const isUpdate = isEdit.value || !!data.id
    const saveAction = action || (isUpdate ? actionPath('update') : actionPath('create'))

    const result = await executeAction(
      saveAction,
      data,
      {
        successMessage: isUpdate
          ? (t('localizator_language_updated', '') || t('localizator_content_updated', 'Updated'))
          : (t('localizator_language_created', '') || t('localizator_content_created', 'Created')),
      }
    )

    closeDialog()
    return result
  }

  async function translate(ids) {
    return executeAction(
      actionPath('translate'),
      { ids: formatIds(ids) },
      {
        successMessage: t('localizator_translate_processed', 'Translation completed'),
        errorMessage: t('localizator_item_err_save', 'Translation failed'),
      }
    )
  }

  return {
    dialogVisible,
    editingItem,
    isEdit,
    formData,
    openCreate,
    openEdit,
    closeDialog,
    save,
    enable,
    disable,
    remove,
    translate,
    executeAction,
    actionPath,
  }
}
