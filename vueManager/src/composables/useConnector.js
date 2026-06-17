/**
 * useConnector — unified API connector for MODX connector.php
 *
 * Provides POST-based API communication with automatic modAuth injection,
 * URL building, and consistent error handling.
 */
import { ref, computed } from 'vue'

const DEFAULT_TIMEOUT = 30000

class ConnectorError extends Error {
  constructor(message, response = null, status = null) {
    super(message)
    this.name = 'ConnectorError'
    this.response = response
    this.status = status
  }
}

export function useConnector(connectorUrl, modAuth) {
  const baseUrl = computed(() => {
    const url = connectorUrl?.value ?? connectorUrl ?? ''
    return url.replace(/\/$/, '')
  })

  const authToken = computed(() => modAuth?.value ?? modAuth ?? '')

  /**
   * Build URL-encoded form data from params object
   */
  function buildFormData(params) {
    const data = new URLSearchParams()
    for (const [key, value] of Object.entries(params)) {
      if (value !== undefined && value !== null) {
        data.append(key, String(value))
      }
    }
    // Add authentication
    if (authToken.value) {
      data.append('modAuth', authToken.value)
    }
    data.append('HTTP_MODAUTH', authToken.value || '')
    return data
  }

  /**
   * Make POST request to connector
   */
  async function post(action, params = {}, options = {}) {
    const url = `${baseUrl.value}`
    const formData = buildFormData({ ...params, action })

    const controller = new AbortController()
    const timeoutId = setTimeout(() => controller.abort(), options.timeout || DEFAULT_TIMEOUT)

    try {
      const response = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData.toString(),
        signal: controller.signal,
        credentials: 'same-origin',
      })

      clearTimeout(timeoutId)

      if (!response.ok) {
        throw new ConnectorError(
          `HTTP error ${response.status}: ${response.statusText}`,
          null,
          response.status
        )
      }

      const data = await response.json()

      if (!data.success) {
        throw new ConnectorError(data.message || 'Request failed', data)
      }

      return data
    } catch (error) {
      clearTimeout(timeoutId)

      if (error.name === 'AbortError') {
        throw new ConnectorError('Request timeout')
      }

      throw error instanceof ConnectorError ? error : new ConnectorError(error.message)
    }
  }

  /**
   * GET request (for simple queries, prefer POST for actions)
   */
  async function get(action, params = {}, options = {}) {
    const searchParams = new URLSearchParams({ action })
    for (const [key, value] of Object.entries(params)) {
      if (value !== undefined && value !== null) {
        searchParams.append(key, String(value))
      }
    }
    if (authToken.value) {
      searchParams.append('modAuth', authToken.value)
      searchParams.append('HTTP_MODAUTH', authToken.value)
    }

    const url = `${baseUrl.value}?${searchParams.toString()}`

    const controller = new AbortController()
    const timeoutId = setTimeout(() => controller.abort(), options.timeout || DEFAULT_TIMEOUT)

    try {
      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
        signal: controller.signal,
        credentials: 'same-origin',
      })

      clearTimeout(timeoutId)

      if (!response.ok) {
        throw new ConnectorError(
          `HTTP error ${response.status}: ${response.statusText}`,
          null,
          response.status
        )
      }

      const data = await response.json()

      if (!data.success) {
        throw new ConnectorError(data.message || 'Request failed', data)
      }

      return data
    } catch (error) {
      clearTimeout(timeoutId)

      if (error.name === 'AbortError') {
        throw new ConnectorError('Request timeout')
      }

      throw error instanceof ConnectorError ? error : new ConnectorError(error.message)
    }
  }

  return {
    post,
    get,
    buildFormData,
    ConnectorError,
  }
}
