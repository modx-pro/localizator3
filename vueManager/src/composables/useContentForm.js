/**
 * useContentForm — locale form state for ContentGrid (resource Localization tab).
 */
import { ref, computed, watch, toValue } from 'vue'
import { useConnector } from './useConnector.js'
import { useLexicon } from './useLexicon.js'
import { useConfirmAction } from './useConfirmAction.js'
import { useGridCrud } from './useGridCrud.js'

export function useContentForm(options = {}) {
  const resourceId = computed(() => toValue(options.resourceId))
  const connectorUrl = computed(() => toValue(options.connectorUrl) || '')
  const modAuth = computed(() => toValue(options.modAuth) || '')
  const rawLexicon = computed(() => toValue(options.lexicon) || {})

  const { lexicon, t } = useLexicon(rawLexicon)
  const connector = useConnector(connectorUrl, modAuth)
  const { showSuccess, showError, confirmAction } = useConfirmAction(lexicon)
  const { executeAction, actionPath } = useGridCrud({
    connector,
    lexicon: { t },
    actionPrefix: 'mgr/content',
    onSuccess: showSuccess,
    onError: showError,
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
  const formEl = ref(null)
  const isEdit = ref(false)
  const activeTab = ref(null)
  const defaultFromResource = ref(false)

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
    const lx = lexicon.value
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

  const sectionDesc = computed(() => {
    const lx = lexicon.value
    if (defaultFromResource.value) {
      return lx.localizator_content_section_desc_native
        || lx.localizator_content_section_desc
        || 'Translations for other languages. Edit the default language in the main resource form.'
    }
    return lx.localizator_content_section_desc
      || 'Manage resource field translations, TVs and auto-translation.'
  })

  const emptyLanguagesMessage = computed(() => {
    const lx = lexicon.value
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
    if (tabs.length > 0 && !tabs.some((tab) => tab.id === activeTab.value)) {
      activeTab.value = tabs[0].id
    }
  }, { immediate: true })

  function isActiveFlag(value) {
    return value === true || value === 1 || value === '1'
  }

  function visibleFields(tab) {
    return (tab.fields || []).filter((field) => {
      if (field.visible === false || field.field === 'id' || field.field === 'key') return false
      return true
    })
  }

  function tvFieldName(field) {
    if (field.type === 'tv' && field.inputTV) {
      return 'tvlocalizator_' + field.inputTV
    }
    return field.field
  }

  function fieldClasses(field) {
    const textTypes = ['textarea', 'richtext']
    const isLongTv = field.type === 'tv'
      && (textTypes.includes(field.tvType) || field.renderMode === 'html')
    return {
      'loc-form-field--full': field.field === 'content'
        || textTypes.includes(field.type)
        || isLongTv,
      'loc-form-field--hidden': field.type === 'tv' && field.tvType === 'hidden',
    }
  }

  function tvFields() {
    const tabs = formConfig.value?.formtabs?.tvs?.tabs || []
    const fields = []
    for (const tab of tabs) {
      for (const field of tab.fields || []) {
        if (field.type === 'tv') {
          fields.push(field)
        }
      }
    }
    return fields
  }

  function emptyTvValue(field) {
    if (field.tvType === 'checkbox' || field.tvType === 'listbox-multiple') {
      return []
    }
    if (field.tvType === 'number') {
      return null
    }
    return ''
  }

  function normalizeTvRecord(record) {
    const normalized = { ...record }
    for (const field of tvFields()) {
      const key = tvFieldName(field)
      if (!(key in normalized)) {
        continue
      }
      const value = normalized[key]
      if (field.tvType === 'checkbox' || field.tvType === 'listbox-multiple') {
        normalized[key] = value === '' || value === null || value === undefined
          ? []
          : (Array.isArray(value) ? value : String(value).split('||'))
      } else if (field.tvType === 'number') {
        normalized[key] = value === '' || value === null || value === undefined
          ? null
          : Number(value)
      }
    }
    return normalized
  }

  function collectRenderedTvValues(payload) {
    const root = formEl.value
    if (!root) {
      return payload
    }

    const grouped = new Map()
    const controls = root.querySelectorAll(
      'input[name^="tvlocalizator_"], textarea[name^="tvlocalizator_"], select[name^="tvlocalizator_"]'
    )
    for (const el of controls) {
      const rawName = el.getAttribute('name')
      if (!rawName || el.disabled) {
        continue
      }
      const name = rawName.replace(/\[\]$/, '')
      let entry = grouped.get(name)
      if (!entry) {
        entry = { single: null, values: [], many: false }
        grouped.set(name, entry)
      }
      if (el.type === 'checkbox') {
        entry.many = true
        if (el.checked) {
          entry.values.push(el.value)
        }
      } else if (el.type === 'radio') {
        if (el.checked) {
          entry.single = el.value
        }
      } else if (el.tagName === 'SELECT' && el.multiple) {
        entry.many = true
        entry.values = Array.from(el.selectedOptions).map((option) => option.value)
      } else {
        entry.single = el.value
      }
    }

    const result = { ...payload }
    for (const [name, entry] of grouped) {
      if (entry.many) {
        result[name] = entry.values
      } else if (entry.single !== null) {
        result[name] = entry.single
      }
    }
    return result
  }

  function applyFormConfigObject(object, includeRecord = false) {
    formConfig.value = {
      formtabs: object.formtabs,
      customization: object.customization,
      totalActiveLanguages: object.totalActiveLanguages ?? 0,
      existingCount: object.existingCount ?? 0,
      defaultLanguageKey: object.defaultLanguageKey || '',
    }
    defaultFromResource.value = !!object.defaultFromResource
    if (object.activeLanguages?.length) {
      activeLanguages.value = object.activeLanguages
    }
    if (includeRecord && object.record) {
      form.value = normalizeTvRecord({ ...object.record, resource_id: resourceId.value })
      isEdit.value = true
      selectedLocaleKey.value = object.record.key || selectedLocaleKey.value
    }
  }

  async function fetchFormConfig(locId = 0) {
    const params = {
      resource_id: resourceId.value,
    }
    if (locId) {
      params.loc_id = locId
    }
    const data = await connector.post('mgr/content/getformconfig', params)
    if (!data.object) {
      throw new Error(data.message || t('localizator_unknown_error', 'Unknown error'))
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
      showError(e.message || String(e))
      return null
    } finally {
      formConfigLoading.value = false
    }
  }

  async function loadAllItems() {
    loading.value = true
    try {
      const data = await connector.post('mgr/content/getlist', {
        resource_id: resourceId.value,
        start: 0,
        limit: 999,
        sort: 'id',
        dir: 'ASC',
      })
      items.value = data.results || []
    } catch (e) {
      showError(e.message || String(e))
    } finally {
      loading.value = false
    }
  }

  function initEmptyForm() {
    form.value = { resource_id: resourceId.value, key: '', active: 1 }
    if (formConfig.value?.formtabs?.document?.fields) {
      for (const field of formConfig.value.formtabs.document.fields) {
        if (field.visible !== false && field.field !== 'id' && field.field !== 'key') {
          form.value[field.field] = form.value[field.field] ?? ''
        }
      }
    }
    for (const field of tvFields()) {
      form.value[tvFieldName(field)] = emptyTvValue(field)
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
      showError(e.message || String(e))
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
    if (selectedLocaleKey.value) {
      form.value.key = selectedLocaleKey.value
    }
    if (!form.value.key) {
      return
    }
    saving.value = true
    const action = isEdit.value ? actionPath('update') : actionPath('create')
    try {
      const payload = collectRenderedTvValues({ ...form.value })
      await connector.post(action, payload)
      showSuccess(
        isEdit.value
          ? (t('localizator_content_updated', '') || t('localizator_save', 'Saved'))
          : (t('localizator_content_created', '') || t('localizator_item_create', 'Created'))
      )
      await loadAllItems()
      const savedKey = form.value.key
      if (savedKey) {
        await selectLocaleKey(savedKey)
      } else {
        await refreshAll()
      }
    } catch (e) {
      showError(e.message || String(e))
    } finally {
      saving.value = false
    }
  }

  async function runAction(name, extra = {}) {
    await executeAction(actionPath(name), extra, { successMessage: t('localizator_success', 'Success') })
    await refreshAll()
  }

  function toggleActive(enableFlag) {
    if (!form.value.id) return
    runAction(enableFlag ? 'enable' : 'disable', {
      id: form.value.id,
      key: form.value.key || '',
    }).catch((e) => showError(e.message || String(e)))
  }

  function confirmRemoveCurrent() {
    if (!form.value.id) return
    confirmAction({
      message: t('localizator_items_remove_confirm', 'Are you sure you want to delete?'),
      header: t('localizator_items_remove', 'Delete'),
      accept: () => runAction('remove', {
        id: form.value.id,
        key: form.value.key || '',
      }),
    })
  }

  function translateCurrent() {
    if (!form.value.key) return
    confirmAction({
      message: t('localizator_translate_confirm', 'Translate this localization?'),
      header: t('localizator_translate', 'Translate'),
      icon: 'pi pi-language',
      severity: 'secondary',
      acceptLabel: t('localizator_translate', 'Translate'),
      accept: async () => {
        await connector.post(actionPath('translate'), {
          resource_id: resourceId.value,
          keys: JSON.stringify([form.value.key]),
        })
        await loadLocalization(form.value.id)
        showSuccess(t('localizator_translate', 'Translate'))
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
    const defaultKey = formConfig.value?.defaultLanguageKey || ''
    const preferredItem = items.value.find((item) => item.key && item.key !== defaultKey)
    const preferredKey = preferredItem?.key
      || activeLanguages.value.find((lang) => lang.key !== defaultKey)?.key
      || activeLanguages.value[0].key
    await selectLocaleKey(preferredKey)
  }

  return {
    lexicon,
    loading,
    saving,
    items,
    selectedLocaleKey,
    activeLanguages,
    formConfig,
    formConfigLoading,
    formConfigError,
    form,
    formEl,
    isEdit,
    activeTab,
    localeOptions,
    formReady,
    sectionDesc,
    emptyLanguagesMessage,
    visibleTabs,
    isActiveFlag,
    visibleFields,
    tvFieldName,
    fieldClasses,
    submitForm,
    translateCurrent,
    toggleActive,
    confirmRemoveCurrent,
    bootstrap,
  }
}
