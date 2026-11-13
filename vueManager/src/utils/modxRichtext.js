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
      if (!area.id || window.tinymce?.get?.(area.id)) {
        continue
      }
      window.MODx.loadRTE(area.id)
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
