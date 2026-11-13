<template>
  <div ref="host" class="loc-tv-rendered"></div>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { destroyManagerRichtextIn, initManagerRichtext } from '../../utils/modxRichtext.js'

const props = defineProps({
  html: {
    type: String,
    default: '',
  },
  renderId: {
    type: [Number, String],
    default: null,
  },
  targetName: {
    type: String,
    default: '',
  },
})

const host = ref(null)
let createdComponents = []
let snapshotBefore = null
let nameObserver = null

function ext() {
  return typeof window !== 'undefined' ? window.Ext : null
}

function snapshotComponentIds() {
  const ids = new Set()
  const Ext = ext()
  if (!Ext?.ComponentMgr?.all) {
    return ids
  }
  Ext.ComponentMgr.all.each((component) => {
    if (component?.id) {
      ids.add(component.id)
    }
  })
  return ids
}

function collectNewComponents(knownIds) {
  const Ext = ext()
  const created = []
  if (!Ext?.ComponentMgr?.all) {
    return created
  }
  Ext.ComponentMgr.all.each((component) => {
    if (component?.id && !knownIds.has(component.id)) {
      created.push(component)
    }
  })
  return created
}

function cleanup() {
  stopNameObserver()
  if (host.value) {
    // TinyMCE keeps instances by textarea id; clear them before wiping DOM
    // or Save → re-render leaves plain textareas with orphaned editors.
    destroyManagerRichtextIn(host.value)
  }
  const Ext = ext()
  const merged = [...createdComponents]
  if (snapshotBefore) {
    // Ext.onReady может отрабатывать асинхронно — добираем компоненты,
    // созданные после runScripts().
    for (const component of collectNewComponents(snapshotBefore)) {
      if (!merged.includes(component)) {
        merged.push(component)
      }
    }
  }
  if (merged.length && Ext) {
    const resourcePanel = typeof Ext.getCmp === 'function'
      ? Ext.getCmp('modx-panel-resource')
      : null
    const resourceForm = resourcePanel?.getForm ? resourcePanel.getForm() : null

    for (const component of merged) {
      try {
        if (resourceForm && component?.getEl) {
          resourceForm.remove(component, false)
        }
      } catch (e) {
        // Компонент мог быть добавлен не в форму ресурса.
      }
      try {
        if (component && !component.isDestroyed) {
          component.destroy()
        }
      } catch (e) {
        // Компонент уже уничтожен вместе с DOM.
      }
    }
  }
  createdComponents = []
  snapshotBefore = null
  if (host.value) {
    host.value.innerHTML = ''
  }
}

function runScripts(container) {
  snapshotBefore = snapshotComponentIds()
  const scripts = Array.from(container.querySelectorAll('script'))

  try {
    for (const oldScript of scripts) {
      const script = document.createElement('script')
      for (const attr of Array.from(oldScript.attributes)) {
        script.setAttribute(attr.name, attr.value)
      }
      if (oldScript.src) {
        script.src = oldScript.src
      } else {
        script.text = oldScript.textContent || ''
      }
      oldScript.parentNode.replaceChild(script, oldScript)
    }
  } finally {
    createdComponents = collectNewComponents(snapshotBefore)
  }
}

function render() {
  if (!host.value) {
    return
  }
  cleanup()
  host.value.innerHTML = props.html || ''
  runScripts(host.value)
  renameControls()
  initManagerRichtext(host.value)
  startNameObserver()
}

/**
 * Кастомные TV-рендеры создают поля с name="tv{id}". Для сохранения
 * локализации они должны называться tvlocalizator_{name}. Статическую
 * разметку переименовывает сервер, здесь добиваем поля, созданные JS/ExtJS.
 */
function renameControls() {
  if (!host.value) {
    return
  }
  const renderId = props.renderId
  if (renderId === null || renderId === undefined || renderId === '' || !props.targetName) {
    return
  }
  const pattern = new RegExp(`^tv${String(renderId)}(\\[\\])?$`)
  const controls = host.value.querySelectorAll('input[name], textarea[name], select[name]')
  for (const control of controls) {
    const name = control.getAttribute('name')
    if (!name || !pattern.test(name)) {
      continue
    }
    control.setAttribute('name', props.targetName + (name.endsWith('[]') ? '[]' : ''))
  }
}

function startNameObserver() {
  stopNameObserver()
  if (!host.value || typeof MutationObserver === 'undefined') {
    return
  }
  nameObserver = new MutationObserver(renameControls)
  nameObserver.observe(host.value, {
    childList: true,
    subtree: true,
    attributes: true,
    attributeFilter: ['name'],
  })
}

function stopNameObserver() {
  if (nameObserver) {
    nameObserver.disconnect()
    nameObserver = null
  }
}

onMounted(() => {
  nextTick(render)
})

watch(() => props.html, () => {
  nextTick(render)
})

onBeforeUnmount(() => {
  stopNameObserver()
  cleanup()
})
</script>

<style scoped>
.loc-tv-rendered {
  width: 100%;
}

.loc-tv-rendered :deep(.x-form-item) {
  margin: 0;
}

.loc-tv-rendered :deep(.x-form-element) {
  padding-left: 0;
}
</style>
