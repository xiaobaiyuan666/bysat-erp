import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

const elementGroupMap = new Map([
  ['autocomplete', 'form'],
  ['cascader', 'form'],
  ['checkbox', 'form'],
  ['checkbox-button', 'form'],
  ['checkbox-group', 'form'],
  ['color-picker', 'form'],
  ['date-picker', 'form'],
  ['date-picker-panel', 'form'],
  ['form', 'form'],
  ['form-item', 'form'],
  ['input', 'form'],
  ['input-number', 'form'],
  ['radio', 'form'],
  ['radio-button', 'form'],
  ['radio-group', 'form'],
  ['rate', 'form'],
  ['select', 'form'],
  ['slider', 'form'],
  ['switch', 'form'],
  ['time-picker', 'form'],
  ['time-select', 'form'],
  ['upload', 'form'],
  ['tree-select', 'form'],
  ['table', 'data'],
  ['table-v2', 'data'],
  ['pagination', 'data'],
  ['descriptions', 'data'],
  ['descriptions-item', 'data'],
  ['tag', 'data'],
  ['tree', 'data'],
  ['tree-v2', 'data'],
  ['dialog', 'overlay'],
  ['drawer', 'overlay'],
  ['message-box', 'overlay'],
  ['popover', 'overlay'],
  ['tooltip', 'overlay'],
  ['dropdown', 'overlay'],
  ['dropdown-menu', 'overlay'],
  ['dropdown-item', 'overlay'],
  ['menu', 'navigation'],
  ['sub-menu', 'navigation'],
  ['menu-item', 'navigation'],
  ['breadcrumb', 'navigation'],
  ['breadcrumb-item', 'navigation'],
  ['tabs', 'navigation'],
  ['tab-pane', 'navigation'],
  ['steps', 'navigation'],
  ['collapse', 'navigation'],
  ['collapse-item', 'navigation'],
  ['collapse-transition', 'navigation'],
  ['alert', 'feedback'],
  ['notification', 'feedback'],
  ['message', 'feedback'],
  ['progress', 'feedback'],
  ['loading', 'feedback'],
  ['empty', 'feedback'],
  ['skeleton', 'feedback'],
  ['icon', 'base'],
  ['button', 'base'],
  ['button-group', 'base'],
  ['avatar', 'base'],
  ['avatar-group', 'base'],
  ['badge', 'base'],
  ['card', 'base'],
  ['col', 'layout'],
  ['row', 'layout'],
  ['container', 'layout'],
  ['header', 'layout'],
  ['aside', 'layout'],
  ['main', 'layout'],
  ['footer', 'layout'],
  ['space', 'layout'],
  ['divider', 'layout'],
])

function getPackageName(id, pkg) {
  const marker = `/node_modules/${pkg}/`
  const index = id.lastIndexOf(marker)
  return index >= 0 ? id.slice(index + marker.length) : ''
}

function getElementChunk(id) {
  const packageName = getPackageName(id, 'element-plus')
  if (packageName.startsWith('es/components/')) {
    const parts = packageName.split('/')
    const component = parts[2] || 'misc'
    return `element-${elementGroupMap.get(component) || component}`
  }

  if (packageName.startsWith('es/hooks/') || packageName.startsWith('es/utils/')) {
    return 'element-shared'
  }

  if (id.includes('/@element-plus/icons-vue/')) {
    return 'element-icons'
  }

  return 'element-shared'
}

function getEchartsChunk(id) {
  const packageName = getPackageName(id, 'echarts')
  if (packageName.startsWith('lib/chart/')) {
    const parts = packageName.split('/')
    return `charts-${parts[2] || 'misc'}`
  }

  if (packageName.startsWith('lib/component/')) {
    const parts = packageName.split('/')
    return `charts-${parts[2] || 'misc'}`
  }

  if (packageName.startsWith('lib/renderer/')) {
    return 'charts-renderer'
  }

  if (
    packageName.startsWith('lib/core/') ||
    packageName.startsWith('lib/data/') ||
    packageName.startsWith('lib/model/') ||
    packageName.startsWith('lib/coord/') ||
    packageName.startsWith('lib/layout/') ||
    packageName.startsWith('lib/visual/') ||
    packageName.startsWith('lib/util/')
  ) {
    return 'charts-core'
  }

  return 'charts-shared'
}

export default defineConfig({
  plugins: [vue()],
  base: '/console/',
  build: {
    outDir: '../public/console',
    emptyOutDir: true,
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('/node_modules/echarts/')) {
            return getEchartsChunk(id)
          }

          if (
            id.includes('/node_modules/element-plus/') ||
            id.includes('/node_modules/@element-plus/icons-vue/')
          ) {
            return getElementChunk(id)
          }

          if (id.includes('/node_modules/vue-router/') || id.includes('/node_modules/vue/')) {
            return 'vue'
          }

          if (id.includes('/node_modules/axios/')) {
            return 'http'
          }

          return undefined
        },
      },
    },
  },
})
