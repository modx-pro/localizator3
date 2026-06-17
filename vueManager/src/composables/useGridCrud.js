/**
 * useGridCrud — CRUD operations for grid components
 *
 * Encapsulates create, read, update, delete, enable/disable actions
 * with consistent error handling and feedback.
 */
import { ref } from 'vue'

export function useGridCrud(options = {}) {
  const { connector, lexicon, onSuccess, onError } = options

  const dialogVisible = ref(false)
  const editingItem = ref(null)
  const isEdit = ref(false)
  const formData = ref({})

  /**
   * Open create dialog
   */
  function openCreate(initialData = {}) {
    isEdit.value = false
    editingItem.value = null
    formData.value = { ...initialData }
    dialogVisible.value = true
  }

  /**
   * Open edit dialog
   */
  function openEdit(item) {
    isEdit.value = true
    editingItem.value = item
    formData.value = { ...item }
    dialogVisible.value = true
  }

  /**
   * Close dialog and reset form
   */
  function closeDialog() {
    dialogVisible.value = false
    editingItem.value = null
    formData.value = {}
    isEdit.value = false
  }

  /**
   * Execute action with error handling
   */
  async function executeAction(action, params, options = {}) {
    const { successMessage, errorMessage, suppressToast = false } = options

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

  /**
   * Enable items by IDs
   */
  async function enable(ids) {
    return executeAction(
      'mgr/content/enable',
      { ids: Array.isArray(ids) ? ids.join(',') : ids },
      { successMessage: lexicon?.t?.('localizator_enabled') || 'Enabled' }
    )
  }

  /**
   * Disable items by IDs
   */
  async function disable(ids) {
    return executeAction(
      'mgr/content/disable',
      { ids: Array.isArray(ids) ? ids.join(',') : ids },
      { successMessage: lexicon?.t?.('localizator_disabled') || 'Disabled' }
    )
  }

  /**
   * Remove items by IDs (with confirmation handled separately)
   */
  async function remove(ids) {
    return executeAction(
      'mgr/content/remove',
      { ids: Array.isArray(ids) ? ids.join(',') : ids },
      { successMessage: lexicon?.t?.('localizator_deleted') || 'Deleted' }
    )
  }

  /**
   * Save form (create or update)
   */
  async function save(data, action = null) {
    const isUpdate = isEdit.value || data.id
    const saveAction = action || (isUpdate ? 'mgr/content/update' : 'mgr/content/create')

    const result = await executeAction(
      saveAction,
      data,
      {
        successMessage: isUpdate
          ? (lexicon?.t?.('localizator_content_updated') || 'Updated')
          : (lexicon?.t?.('localizator_content_created') || 'Created'),
      }
    )

    closeDialog()
    return result
  }

  /**
   * Translate selected items
   */
  async function translate(ids) {
    return executeAction(
      'mgr/content/translate',
      { ids: Array.isArray(ids) ? ids.join(',') : ids },
      {
        successMessage: lexicon?.t?.('localizator_translate_processed') || 'Translation completed',
        errorMessage: lexicon?.t?.('localizator_item_err_save') || 'Translation failed',
      }
    )
  }

  return {
    // Dialog state
    dialogVisible,
    editingItem,
    isEdit,
    formData,

    // Dialog actions
    openCreate,
    openEdit,
    closeDialog,

    // CRUD actions
    save,
    enable,
    disable,
    remove,
    translate,
    executeAction,
  }
}
