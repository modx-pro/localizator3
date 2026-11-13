/**
 * MODX manager RTE helpers (TinyMCE / Ace).
 * Resource RTE scans .modx-richtext once on open; localization fields mount later.
 */

export function initManagerRichtext(containerOrAreas) {
  const areas = normalizeAreas(containerOrAreas)
  if (!areas.length) {
    return
  }

  const which = window.MODx?.config?.which_editor || ''
  if (/tinymce/i.test(which) && typeof window.MODx?.loadRTE === 'function') {
    for (const area of areas) {
      if (!area.id) {
        continue
      }
      // After Save/re-render the textarea is new DOM; a stale TinyMCE instance
      // with the same id blocks loadRTE and leaves a plain textarea.
      if (!editorBoundToArea(area)) {
        destroyManagerRichtext(area.id)
        window.MODx.loadRTE(area.id)
      }
    }
    return
  }

  if (typeof window.MODx?.ux?.Ace?.replaceTextAreas === 'function') {
    const pending = areas.filter((area) => area.name || area.id)
    if (pending.length) {
      window.MODx.ux.Ace.replaceTextAreas(pending)
    }
    return
  }

  if (typeof window.MODx?.loadRTE === 'function') {
    for (const area of areas) {
      if (area.id) {
        if (!editorBoundToArea(area)) {
          destroyManagerRichtext(area.id)
        }
        window.MODx.loadRTE(area.id)
      }
    }
  }
}

export function syncManagerRichtext() {
  if (typeof window.tinymce?.triggerSave === 'function') {
    window.tinymce.triggerSave()
  }
}

export function destroyManagerRichtext(id) {
  if (!id) {
    return
  }
  const editor = window.tinymce?.get?.(id)
  if (editor) {
    editor.remove()
  }
}

/**
 * Remove TinyMCE instances whose targets live under container (or match listed areas).
 * Call before replacing TV HTML after Save — otherwise ids collide with stale editors.
 */
export function destroyManagerRichtextIn(containerOrAreas) {
  const areas = normalizeAreas(containerOrAreas)
  for (const area of areas) {
    if (area?.id) {
      destroyManagerRichtext(area.id)
    }
  }
  if (containerOrAreas && typeof containerOrAreas.querySelectorAll === 'function') {
    const hosts = containerOrAreas.querySelectorAll('.tox-tinymce')
    for (const tox of hosts) {
      const id = tox.getAttribute('id')?.replace(/^tox-/, '') || tox.previousElementSibling?.id
      if (id) {
        destroyManagerRichtext(id)
      }
    }
  }
}

function editorBoundToArea(area) {
  if (!area?.id || !window.tinymce?.get) {
    return false
  }
  const editor = window.tinymce.get(area.id)
  if (!editor) {
    return false
  }
  const target = editor.getElement?.() || editor.targetElm
  return target === area
}

export function setManagerRichtextContent(id, value) {
  const html = value == null ? '' : String(value)
  const editor = window.tinymce?.get?.(id)
  if (editor) {
    editor.setContent(html)
    return
  }
  const area = document.getElementById(id)
  if (area) {
    area.value = html
  }
}

export function getManagerRichtextContent(id) {
  const editor = window.tinymce?.get?.(id)
  if (editor) {
    return editor.getContent()
  }
  const area = document.getElementById(id)
  return area ? area.value : ''
}

function normalizeAreas(containerOrAreas) {
  if (!containerOrAreas) {
    return []
  }
  if (Array.isArray(containerOrAreas)) {
    return containerOrAreas.filter(Boolean)
  }
  if (containerOrAreas instanceof HTMLTextAreaElement) {
    return [containerOrAreas]
  }
  if (typeof containerOrAreas.querySelectorAll === 'function') {
    return Array.from(containerOrAreas.querySelectorAll('textarea.modx-richtext'))
  }
  return []
}
