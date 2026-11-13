<template>
  <textarea
    :id="inputId"
    ref="area"
    class="modx-richtext loc-richtext-field"
    :name="name || inputId"
    :rows="rows"
    :required="required"
    :value="stringValue"
    @input="onNativeInput"
  />
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import {
  destroyManagerRichtext,
  getManagerRichtextContent,
  initManagerRichtext,
  setManagerRichtextContent,
} from '../../utils/modxRichtext.js'

const props = defineProps({
  id: { type: String, required: true },
  name: { type: String, default: '' },
  modelValue: { type: [String, Number, null], default: '' },
  rows: { type: Number, default: 12 },
  required: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const area = ref(null)
let changeBound = false
let io = null
let retryTimer = null
let extTabListener = null
let mountedEditor = false

const inputId = computed(() => props.id)
const stringValue = computed(() => {
  if (props.modelValue === null || props.modelValue === undefined) {
    return ''
  }
  return String(props.modelValue)
})

function onNativeInput(event) {
  emit('update:modelValue', event.target.value)
}

function isAreaVisible(el) {
  if (!el?.isConnected) {
    return false
  }
  const style = window.getComputedStyle(el)
  if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') {
    return false
  }
  // Hidden ExtJS tabs often keep layout size but ancestors are display:none
  let node = el
  while (node && node !== document.body) {
    const cs = window.getComputedStyle(node)
    if (cs.display === 'none' || cs.visibility === 'hidden') {
      return false
    }
    node = node.parentElement
  }
  const rect = el.getBoundingClientRect()
  return rect.width > 0 && rect.height > 0
}

function editorAttached(id) {
  const editor = window.tinymce?.get?.(id)
  if (!editor) {
    return false
  }
  // Successful TinyMCE hides the original textarea
  const el = area.value
  if (el && isAreaVisible(el) && el.offsetHeight > 0 && el.style.display !== 'none') {
    const tox = el.nextElementSibling
    if (!tox || !tox.classList?.contains('tox-tinymce')) {
      return false
    }
  }
  return true
}

function bindEditorEvents() {
  if (changeBound) {
    return
  }
  const editor = window.tinymce?.get?.(inputId.value)
  if (!editor) {
    return
  }
  changeBound = true
  editor.on('change keyup SetContent', () => {
    emit('update:modelValue', editor.getContent())
  })
}

function tryMountEditor() {
  if (!area.value || mountedEditor) {
    return mountedEditor
  }
  if (!isAreaVisible(area.value)) {
    return false
  }

  const id = inputId.value
  // Drop failed/half-init instances so loadRTE can run again
  if (window.tinymce?.get?.(id) && !editorAttached(id)) {
    destroyManagerRichtext(id)
    changeBound = false
  }

  area.value.value = stringValue.value
  initManagerRichtext([area.value])

  window.setTimeout(() => {
    if (editorAttached(id) || window.MODx?.ux?.Ace) {
      mountedEditor = true
      bindEditorEvents()
      setManagerRichtextContent(id, stringValue.value)
      stopWatchers()
    }
  }, 80)

  window.setTimeout(() => {
    if (editorAttached(id)) {
      mountedEditor = true
      bindEditorEvents()
      setManagerRichtextContent(id, stringValue.value)
      stopWatchers()
    }
  }, 500)

  return true
}

function stopWatchers() {
  if (io) {
    io.disconnect()
    io = null
  }
  if (retryTimer) {
    window.clearInterval(retryTimer)
    retryTimer = null
  }
  if (extTabListener && window.Ext?.getCmp) {
    const tab = window.Ext.getCmp('localizator3-resource-tab')
    if (tab?.un) {
      tab.un('activate', extTabListener)
    }
    extTabListener = null
  }
}

function startWatchers() {
  stopWatchers()
  if (!area.value) {
    return
  }

  if (typeof IntersectionObserver !== 'undefined') {
    io = new IntersectionObserver(() => {
      tryMountEditor()
    }, { threshold: 0.01 })
    io.observe(area.value)
  }

  retryTimer = window.setInterval(() => {
    if (tryMountEditor() && mountedEditor) {
      stopWatchers()
    }
  }, 400)

  // ExtJS resource tab «Локализация» — init only after it becomes active
  const Ext = window.Ext
  if (Ext?.getCmp) {
    extTabListener = () => {
      window.setTimeout(() => tryMountEditor(), 50)
    }
    const bindTab = () => {
      const tab = Ext.getCmp('localizator3-resource-tab')
      if (tab?.on) {
        tab.on('activate', extTabListener)
        if (tab === Ext.getCmp('modx-resource-tabs')?.getActiveTab?.()) {
          tryMountEditor()
        }
        return true
      }
      return false
    }
    if (!bindTab()) {
      window.setTimeout(bindTab, 300)
      window.setTimeout(bindTab, 1000)
    }
  }

  tryMountEditor()
}

onMounted(() => {
  nextTick(() => {
    startWatchers()
  })
})

watch(
  () => props.modelValue,
  (value) => {
    const next = value == null ? '' : String(value)
    if (area.value && !mountedEditor) {
      area.value.value = next
    }
    const current = getManagerRichtextContent(inputId.value)
    if (current === next) {
      return
    }
    setManagerRichtextContent(inputId.value, next)
  }
)

onBeforeUnmount(() => {
  stopWatchers()
  destroyManagerRichtext(inputId.value)
  changeBound = false
  mountedEditor = false
})
</script>

<style scoped>
.loc-richtext-field {
  width: 100%;
  min-height: 12rem;
  box-sizing: border-box;
}
</style>
