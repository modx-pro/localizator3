/**
 * useLexicon — lexicon helper for Localizator3
 *
 * Wraps raw lexicon object from PHP with safe accessor.
 * Compatible with @vuetools/useLexicon if VueTools installed.
 */
import { computed } from 'vue'

export function useLexicon(rawLexicon = {}) {
  const lexicon = computed(() => rawLexicon?.value ?? rawLexicon ?? {})

  /**
   * Get lexicon key with fallback
   */
  function t(key, fallback = '') {
    return lexicon.value[key] ?? fallback ?? key
  }

  /**
   * Check if key exists
   */
  function has(key) {
    return key in lexicon.value && lexicon.value[key] !== ''
  }

  return {
    t,
    has,
    lexicon,
  }
}
