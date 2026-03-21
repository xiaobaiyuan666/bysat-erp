<script setup>
import { computed } from "vue";
import { ArrowDown } from "@element-plus/icons-vue";
import { useViewport } from "../composables/useViewport";

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
  desktopInlineCount: {
    type: Number,
    default: 1,
  },
});

const emit = defineEmits(["select"]);
const { isMobile } = useViewport();

const visibleItems = computed(() => {
  return props.items.filter((item) => !item.hidden);
});

const mobilePrimaryItem = computed(() => {
  return (
    visibleItems.value.find((item) => item.primary) ||
    visibleItems.value[0] ||
    null
  );
});

const desktopInlineItems = computed(() => {
  return visibleItems.value.slice(0, props.desktopInlineCount);
});

const desktopMenuItems = computed(() => {
  return visibleItems.value.slice(props.desktopInlineCount);
});

const mobileMenuItems = computed(() => {
  return visibleItems.value.filter(
    (item) => item.key !== mobilePrimaryItem.value?.key,
  );
});

function buttonType(item) {
  if (item.type) {
    return item.type;
  }

  return item.danger ? "danger" : "primary";
}

function handleSelect(key) {
  emit("select", key);
}
</script>

<template>
  <div class="row-action-menu">
    <template v-if="!isMobile">
      <el-button
        v-for="item in desktopInlineItems"
        :key="item.key"
        link
        :disabled="item.disabled"
        :type="buttonType(item)"
        class="row-action-menu__button"
        @click="handleSelect(item.key)"
      >
        {{ item.label }}
      </el-button>

      <el-dropdown
        v-if="desktopMenuItems.length"
        trigger="click"
        @command="handleSelect"
      >
        <el-button link type="primary" class="row-action-menu__more">
          更多
          <el-icon><ArrowDown /></el-icon>
        </el-button>
        <template #dropdown>
          <el-dropdown-menu>
            <el-dropdown-item
              v-for="item in desktopMenuItems"
              :key="item.key"
              :command="item.key"
              :disabled="item.disabled"
              :class="{ 'is-danger': item.danger }"
            >
              {{ item.label }}
            </el-dropdown-item>
          </el-dropdown-menu>
        </template>
      </el-dropdown>
    </template>

    <template v-else>
      <el-button
        v-if="mobilePrimaryItem"
        link
        :disabled="mobilePrimaryItem.disabled"
        :type="buttonType(mobilePrimaryItem)"
        class="row-action-menu__button"
        @click="handleSelect(mobilePrimaryItem.key)"
      >
        {{ mobilePrimaryItem.label }}
      </el-button>

      <el-dropdown
        v-if="mobileMenuItems.length"
        trigger="click"
        @command="handleSelect"
      >
        <el-button link type="primary" class="row-action-menu__more">
          更多
          <el-icon><ArrowDown /></el-icon>
        </el-button>
        <template #dropdown>
          <el-dropdown-menu>
            <el-dropdown-item
              v-for="item in mobileMenuItems"
              :key="item.key"
              :command="item.key"
              :disabled="item.disabled"
              :class="{ 'is-danger': item.danger }"
            >
              {{ item.label }}
            </el-dropdown-item>
          </el-dropdown-menu>
        </template>
      </el-dropdown>
    </template>
  </div>
</template>
