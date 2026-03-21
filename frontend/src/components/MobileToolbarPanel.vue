<script setup>
import { ref, watch } from "vue";
import { useViewport } from "../composables/useViewport";

defineProps({
  title: {
    type: String,
    default: "筛选与操作",
  },
  count: {
    type: [Number, String],
    default: "",
  },
  description: {
    type: String,
    default: "先缩小范围，再处理当前台账。",
  },
});

const { isMobile } = useViewport();
const opened = ref(!isMobile.value);

watch(isMobile, (mobile) => {
  opened.value = !mobile;
});

function handleToggle(event) {
  opened.value = event.target.open;
}
</script>

<template>
  <details class="mobile-toolbar-panel" :open="opened" @toggle="handleToggle">
    <summary class="mobile-toolbar-panel__summary">
      <div class="mobile-toolbar-panel__summary-main">
        <strong>{{ title }}</strong>
        <small v-if="count !== '' && count !== null">共 {{ count }} 条</small>
      </div>
    </summary>

    <div class="mobile-toolbar-panel__body">
      <div class="mobile-toolbar-panel__head">
        <div class="mobile-toolbar-panel__title">
          <strong>{{ title }}</strong>
          <span>{{ description }}</span>
        </div>
        <el-tag
          v-if="count !== '' && count !== null"
          size="small"
          effect="plain"
          type="info"
        >
          {{ count }} 条
        </el-tag>
      </div>

      <slot />
    </div>
  </details>
</template>
