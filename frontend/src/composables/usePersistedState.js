import { ref, watch } from 'vue'

function cloneValue(value) {
  if (Array.isArray(value)) {
    return value.slice()
  }

  if (value && typeof value === 'object') {
    return { ...value }
  }

  return value
}

export function usePersistedState(key, defaultValue) {
  const initialValue = readPersistedValue(key, defaultValue)
  const state = ref(initialValue)

  watch(
    state,
    (value) => {
      try {
        localStorage.setItem(key, JSON.stringify(value))
      } catch {
        // Ignore localStorage write failures in restricted environments.
      }
    },
    { deep: true },
  )

  return state
}

function readPersistedValue(key, defaultValue) {
  try {
    const raw = localStorage.getItem(key)

    if (!raw) {
      return cloneValue(defaultValue)
    }

    return JSON.parse(raw)
  } catch {
    return cloneValue(defaultValue)
  }
}
