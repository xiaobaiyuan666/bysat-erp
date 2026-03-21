<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
  QuestionFilled,
  RefreshRight,
  SwitchButton,
} from "@element-plus/icons-vue";
import AiAssistant from "../components/AiAssistant.vue";
import GuideDrawer from "../components/GuideDrawer.vue";
import { useViewport } from "../composables/useViewport";
import {
  consoleModules,
  filterVisibleModuleGroups,
  findConsoleModule,
  getFirstAllowedModule,
} from "../config/console";
import { useAppStore } from "../stores/useAppStore";

const store = useAppStore();
const route = useRoute();
const router = useRouter();
const { isMobile } = useViewport();

const guideVisible = ref(false);

const visibleModuleGroups = computed(() =>
  filterVisibleModuleGroups((permission) => store.hasPermission(permission)),
);

const visibleModules = computed(() =>
  visibleModuleGroups.value.flatMap((group) => group.modules),
);

const activeItem = computed(() => {
  return (
    visibleModules.value.find((item) => item.name === route.name) ||
    findConsoleModule(route.name) ||
    visibleModules.value[0] ||
    consoleModules[0]
  );
});

const activeGroup = computed(() => {
  return (
    visibleModuleGroups.value.find(
      (group) => group.key === activeItem.value?.groupKey,
    ) ||
    visibleModuleGroups.value[0] ||
    null
  );
});

const sessionUser = computed(() => store.state.bootstrap.sessionUser || {});
const currentUser = computed(() => store.state.bootstrap.currentUser || {});
const currentUserId = computed(() => store.state.bootstrap.currentUserId || "");

const currentUserOptions = computed(() => {
  return (store.state.bootstrap.userRows || [])
    .filter((item) => item.status === "active")
    .map((item) => ({
      value: item.id,
      label: `${item.name} / ${item.title || item.role_label}`,
    }));
});

const canUseAI = computed(() => store.hasPermission("ai.use"));
const canSwitchIdentity = computed(() =>
  Boolean(store.state.bootstrap.canImpersonate),
);

const guideDrawerSize = computed(() => (isMobile.value ? "100%" : "520px"));
const assistantDrawerSize = computed(() => (isMobile.value ? "100%" : "460px"));
const guideButtonLabel = computed(() => (isMobile.value ? "说明" : "使用说明"));

const headerIdentityName = computed(
  () => currentUser.value.name || sessionUser.value.name || "--",
);

const headerIdentityMeta = computed(() => {
  const parts = [
    currentUser.value.title || currentUser.value.role_label,
    currentUser.value.department,
  ].filter(Boolean);

  return parts.join(" / ") || "未分配岗位";
});

const activeGroupSummary = computed(() => {
  if (!activeGroup.value) {
    return "";
  }

  return `${activeGroup.value.label} · ${activeGroup.value.description}`;
});

const breadcrumbItems = computed(() => {
  const items = [{ label: "工作台", path: "/dashboard" }];

  if (
    activeGroup.value &&
    activeGroup.value.key !== "workspace" &&
    activeItem.value?.path
  ) {
    items.push({
      label: activeGroup.value.label,
      path: activeItem.value.path,
    });
  }

  if (route.name && route.name !== "dashboard" && activeItem.value?.path) {
    items.push({
      label: activeItem.value.label,
      path: activeItem.value.path,
    });
  }

  return items;
});

function resolvePreferredModule() {
  const preferredModuleName =
    currentUser.value.role_home_module ||
    currentUser.value.home_module ||
    currentUser.value.default_module ||
    "";

  return getFirstAllowedModule(
    (permission) => store.hasPermission(permission),
    preferredModuleName,
  );
}

async function ensureAuthorized() {
  if (!store.isAuthenticated() || visibleModules.value.length === 0) {
    return;
  }

  if (!visibleModules.value.some((item) => item.name === route.name)) {
    await router.replace(resolvePreferredModule()?.path || visibleModules.value[0].path);
  }

  if (!canUseAI.value) {
    store.state.assistantVisible = false;
  }
}

onMounted(async () => {
  if (!store.state.ready) {
    await store.loadBootstrap({ silent: true });
  }

  await ensureAuthorized();
});

watch(
  [
    () => route.name,
    visibleModules,
    canUseAI,
    () => store.state.bootstrap.currentUserId,
  ],
  async () => {
    await ensureAuthorized();
  },
  { deep: true },
);

function jump(path) {
  router.push(path);
}

async function switchCurrentUser(userId) {
  if (!canSwitchIdentity.value || !userId || userId === currentUserId.value) {
    return;
  }

  await store.submitAction(
    "switch_current_user",
    {
      current_user_id: userId,
    },
    { silent: true },
  );

  await ensureAuthorized();
}

async function logout() {
  await store.logout();
  await router.replace("/login");
}
</script>

<template>
  <div class="console-shell">
    <aside class="console-aside">
      <div class="console-brand">
        <div class="console-brand__logo">PA</div>
        <div>
          <h1>{{ store.state.bootstrap.meta.company || "企业管理系统" }}</h1>
        </div>
      </div>

      <div class="console-aside__content">
        <section
          v-for="group in visibleModuleGroups"
          :key="group.key"
          class="console-menu-group"
        >
          <header class="console-menu-group__title">
            <strong>{{ group.label }}</strong>
            <small>{{ group.description }}</small>
          </header>

          <div class="console-menu">
            <button
              v-for="item in group.modules"
              :key="item.name"
              type="button"
              class="console-menu__item"
              :class="{ 'is-active': route.name === item.name }"
              @click="jump(item.path)"
            >
              <span class="console-menu__code console-menu__icon">
                <component :is="item.icon" />
              </span>
              <span class="console-menu__body">
                <span class="console-menu__label">{{ item.label }}</span>
                <small class="console-menu__desc">{{ item.navDescription }}</small>
              </span>
            </button>
          </div>
        </section>
      </div>

      <div class="console-aside__meta">
        <div class="aside-meta-card">
          <span>部署方式</span>
          <strong>PHP + Vue</strong>
        </div>
        <div class="aside-meta-card">
          <span>模型状态</span>
          <strong>{{ store.state.bootstrap.aiConfigured ? "已接入模型" : "未配置模型" }}</strong>
        </div>
        <div class="aside-meta-card">
          <span>数据版本</span>
          <strong>v{{ store.state.bootstrap.meta.version || "0.0.0" }}</strong>
        </div>
      </div>
    </aside>

    <div class="console-main">
      <header class="console-header">
        <div class="console-header__main">
          <div class="console-breadcrumb">
            <template v-for="(item, index) in breadcrumbItems" :key="`${item.label}-${index}`">
              <button
                v-if="index < breadcrumbItems.length - 1"
                type="button"
                class="console-breadcrumb__item"
                @click="jump(item.path)"
              >
                {{ item.label }}
              </button>
              <span v-else class="console-breadcrumb__current">{{ item.label }}</span>
              <span
                v-if="index < breadcrumbItems.length - 1"
                class="console-breadcrumb__separator"
              >
                /
              </span>
            </template>
          </div>

          <div class="console-header__title">
            <div class="console-header__eyebrow">
              {{ activeGroupSummary || "统一后台工作台" }}
            </div>
            <h2>{{ activeItem?.label || "工作台" }}</h2>
            <p>{{ route.meta.subtitle || activeItem?.subtitle }}</p>
          </div>
        </div>

        <div class="console-header__actions">
          <template v-if="isMobile">
            <div class="operator-strip">
              <div class="operator-strip__meta">
                <strong>{{ headerIdentityName }}</strong>
                <span>{{ headerIdentityMeta }}</span>
              </div>
              <el-select
                v-if="canSwitchIdentity"
                :model-value="currentUserId"
                class="operator-strip__select"
                placeholder="选择身份"
                @change="switchCurrentUser"
              >
                <el-option
                  v-for="item in currentUserOptions"
                  :key="item.value"
                  :label="item.label"
                  :value="item.value"
                />
              </el-select>
            </div>
          </template>

          <template v-else>
            <div class="operator-card operator-card--pure">
              <span class="operator-card__label">当前身份</span>
              <strong>{{ headerIdentityName }}</strong>
              <small>{{ headerIdentityMeta }}</small>
              <el-select
                v-if="canSwitchIdentity"
                :model-value="currentUserId"
                class="operator-card__select"
                placeholder="切换工作身份"
                @change="switchCurrentUser"
              >
                <el-option
                  v-for="item in currentUserOptions"
                  :key="item.value"
                  :label="item.label"
                  :value="item.value"
                />
              </el-select>
            </div>
          </template>

          <span class="console-header__stamp">
            最近同步 {{ store.state.lastLoadedAt || "--" }}
          </span>
          <el-button
            :icon="RefreshRight"
            :loading="store.state.loading"
            @click="store.loadBootstrap()"
          >
            刷新
          </el-button>
          <el-button :icon="QuestionFilled" @click="guideVisible = true">
            {{ guideButtonLabel }}
          </el-button>
          <el-button
            v-if="canUseAI"
            type="primary"
            plain
            @click="store.state.assistantVisible = true"
          >
            AI 助手
          </el-button>
          <el-button :icon="SwitchButton" @click="logout">退出</el-button>
        </div>
      </header>

      <main class="console-body">
        <el-skeleton
          v-if="store.state.loading && !store.state.ready"
          :rows="10"
          animated
        />
        <router-view v-else v-slot="{ Component, route: currentRoute }">
          <keep-alive>
            <component :is="Component" :key="currentRoute.name" />
          </keep-alive>
        </router-view>
      </main>
    </div>

    <el-drawer
      v-if="canUseAI"
      v-model="store.state.assistantVisible"
      class="assistant-drawer"
      title="AI 助手"
      :size="assistantDrawerSize"
      destroy-on-close
    >
      <AiAssistant />
    </el-drawer>

    <el-drawer
      v-model="guideVisible"
      class="guide-drawer-shell"
      title="使用说明"
      :size="guideDrawerSize"
      destroy-on-close
    >
      <GuideDrawer
        :active-module="activeItem?.name"
        :visible-modules="visibleModules"
        :current-user="currentUser"
      />
    </el-drawer>
  </div>
</template>
