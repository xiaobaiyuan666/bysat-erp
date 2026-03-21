import { computed, onBeforeUnmount, onMounted, reactive } from 'vue'

const viewportState = reactive({
  width: typeof window === 'undefined' ? 1280 : window.innerWidth,
  listeners: 0,
})

function syncViewport() {
  viewportState.width = typeof window === 'undefined' ? 1280 : window.innerWidth
}

export function useViewport() {
  onMounted(() => {
    if (viewportState.listeners === 0) {
      syncViewport()
      window.addEventListener('resize', syncViewport, { passive: true })
    }

    viewportState.listeners += 1
  })

  onBeforeUnmount(() => {
    viewportState.listeners = Math.max(0, viewportState.listeners - 1)

    if (viewportState.listeners === 0) {
      window.removeEventListener('resize', syncViewport)
    }
  })

  return {
    width: computed(() => viewportState.width),
    isMobile: computed(() => viewportState.width <= 768),
    isTablet: computed(() => viewportState.width <= 980),
  }
}
