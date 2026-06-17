/**
 * useDataTable — DataTable state management (pagination, sorting, loading)
 *
 * Unified composable for lazy-loaded PrimeVue DataTable.
 */
import { ref, computed } from 'vue'

export function useDataTable(options = {}) {
  const {
    defaultRows = 10,
    defaultSortField = 'id',
    defaultSortOrder = 'DESC',
  } = options

  // State
  const loading = ref(false)
  const items = ref([])
  const totalRecords = ref(0)
  const first = ref(0)
  const rows = ref(defaultRows)
  const sortField = ref(defaultSortField)
  const sortOrder = ref(defaultSortOrder === 'DESC' ? -1 : 1)
  const searchQuery = ref('')

  // Computed
  const page = computed(() => Math.floor(first.value / rows.value) + 1)

  const params = computed(() => ({
    start: first.value,
    limit: rows.value,
    sort: sortField.value,
    dir: sortOrder.value === -1 ? 'DESC' : 'ASC',
    query: searchQuery.value.trim(),
  }))

  /**
   * Handle page change event from DataTable
   */
  function onPage(event) {
    first.value = event.first
    rows.value = event.rows
    return load()
  }

  /**
   * Handle sort change event from DataTable
   */
  function onSort(event) {
    sortField.value = event.sortField || defaultSortField
    sortOrder.value = event.sortOrder ?? 1
    first.value = 0 // Reset to first page on sort
    return load()
  }

  /**
   * Update search and reload
   */
  function setSearch(query) {
    searchQuery.value = query
    first.value = 0
    return load()
  }

  /**
   * Reset to defaults
   */
  function reset() {
    first.value = 0
    rows.value = defaultRows
    sortField.value = defaultSortField
    sortOrder.value = defaultSortOrder === 'DESC' ? -1 : 1
    searchQuery.value = ''
    items.value = []
    totalRecords.value = 0
  }

  /**
   * Refresh current view
   */
  function refresh() {
    return load()
  }

  // Placeholder for load function - set by consumer
  let load = async () => {}

  function setLoadFn(fn) {
    load = fn
  }

  return {
    // State (refs)
    loading,
    items,
    totalRecords,
    first,
    rows,
    sortField,
    sortOrder,
    searchQuery,

    // Computed
    page,
    params,

    // Methods
    onPage,
    onSort,
    setSearch,
    reset,
    refresh,
    setLoadFn,
  }
}
