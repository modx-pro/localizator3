/**
 * useConfirmAction — confirmation dialogs with toast feedback
 *
 * Wraps PrimeVue useConfirm and useToast for consistent UX.
 */
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'

export function useConfirmAction(lexicon = {}) {
  const confirm = useConfirm()
  const toast = useToast()

  const t = (key, fallback = '') => lexicon[key] || fallback

  /**
   * Show confirmation dialog
   */
  function confirmAction(options) {
    const {
      message,
      header = t('localizator_item_remove_confirm', 'Are you sure?'),
      icon = 'pi pi-exclamation-triangle',
      acceptLabel = t('localizator_item_remove', 'Delete'),
      rejectLabel = t('localizator_cancel', 'Cancel'),
      accept,
      reject,
      severity = 'danger',
    } = options

    confirm.require({
      message,
      header,
      icon,
      acceptLabel,
      rejectLabel,
      acceptClass: `p-button-${severity}`,
      accept: async () => {
        try {
          if (accept) {
            await accept()
          }
        } catch (error) {
          showError(error.message || t('localizator_error', 'Error'))
        }
      },
      reject: () => {
        if (reject) {
          reject()
        }
      },
    })
  }

  /**
   * Show success toast
   */
  function showSuccess(message, life = 3000) {
    toast.add({
      severity: 'success',
      summary: t('localizator_success', 'Success'),
      detail: message,
      life,
    })
  }

  /**
   * Show error toast
   */
  function showError(message, life = 5000) {
    toast.add({
      severity: 'error',
      summary: t('localizator_error', 'Error'),
      detail: message,
      life,
    })
  }

  /**
   * Show info toast
   */
  function showInfo(message, life = 3000) {
    toast.add({
      severity: 'info',
      summary: 'Info',
      detail: message,
      life,
    })
  }

  /**
   * Confirm and execute delete action
   */
  function confirmDelete(itemName, onConfirm, options = {}) {
    const message = options.message || `Are you sure you want to delete ${itemName}?`

    confirmAction({
      message,
      header: options.header || t('localizator_items_remove_confirm', 'Confirm Deletion'),
      icon: options.icon || 'pi pi-trash',
      acceptLabel: options.acceptLabel || t('localizator_item_remove', 'Delete'),
      rejectLabel: options.rejectLabel || t('localizator_cancel', 'Cancel'),
      severity: options.severity || 'danger',
      accept: async () => {
        await onConfirm()
        showSuccess(options.successMessage || t('localizator_deleted', 'Deleted'))
      },
    })
  }

  return {
    confirm,
    toast,
    confirmAction,
    confirmDelete,
    showSuccess,
    showError,
    showInfo,
  }
}
