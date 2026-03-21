<script setup>
import { computed } from "vue";
import { useRouter } from "vue-router";
import {
  getModuleGuide,
  getRoleGuide,
  guideBasics,
  guideWarnings,
} from "../config/guide";

const props = defineProps({
  activeModule: {
    type: String,
    default: "dashboard",
  },
  visibleModules: {
    type: Array,
    default: () => [],
  },
  currentUser: {
    type: Object,
    default: () => ({}),
  },
});

const router = useRouter();

const visibleModuleMap = computed(() => {
  return new Map(props.visibleModules.map((item) => [item.name, item]));
});

const currentGuide = computed(() => getModuleGuide(props.activeModule));

const currentRoleGuide = computed(() =>
  getRoleGuide(props.currentUser?.role_group || "readonly"),
);

const recommendedModule = computed(() => {
  const homeModule = props.currentUser?.role_home_module || "";

  if (homeModule && visibleModuleMap.value.has(homeModule)) {
    return visibleModuleMap.value.get(homeModule);
  }

  if (visibleModuleMap.value.has(currentRoleGuide.value.target)) {
    return visibleModuleMap.value.get(currentRoleGuide.value.target);
  }

  return props.visibleModules[0] || null;
});

const visibleModuleGuides = computed(() => {
  return props.visibleModules.map((item) => ({
    name: item.name,
    path: item.path,
    label: item.label,
    code: item.code,
    summary: getModuleGuide(item.name).summary,
  }));
});

function jumpToModule(name) {
  const item = visibleModuleMap.value.get(name);

  if (!item) {
    return;
  }

  router.push(item.path);
}
</script>

<template>
  <div class="guide-drawer">
    <section class="guide-section guide-section--hero">
      <div class="guide-callout">
        先进入自己负责的模块，按页面流程完成处理，最后再回驾驶舱看结果。系统会按你的权限组只展示真正需要用到的入口。
      </div>
      <ul class="guide-list">
        <li v-for="item in guideBasics" :key="item">{{ item }}</li>
      </ul>
    </section>

    <section class="guide-section">
      <div class="guide-section__head">
        <div>
          <h3>你当前该怎么用</h3>
          <p>按当前工作身份给出默认入口和操作顺序，避免在整个系统里来回找。</p>
        </div>
      </div>

      <article class="guide-card guide-card--focus">
        <div class="guide-card__head">
          <div>
            <strong>{{ currentUser.role_label || "未分配角色" }}</strong>
            <small>
              {{ currentUser.role_group_label || currentRoleGuide.title }}
              <template v-if="recommendedModule">
                / 默认入口：{{ recommendedModule.label }}
              </template>
            </small>
          </div>
          <el-button
            v-if="recommendedModule"
            text
            type="primary"
            size="small"
            @click="jumpToModule(recommendedModule.name)"
          >
            打开默认入口
          </el-button>
        </div>
        <div class="guide-card__note">
          {{ currentUser.role_summary || currentRoleGuide.note }}
        </div>
        <ol class="guide-step-list">
          <li
            v-for="step in currentUser.role_guide_steps?.length
              ? currentUser.role_guide_steps
              : currentRoleGuide.steps"
            :key="step"
          >
            {{ step }}
          </li>
        </ol>
      </article>
    </section>

    <section class="guide-section">
      <div class="guide-section__head">
        <div>
          <h3>当前页面怎么用</h3>
          <p>{{ currentGuide.summary }}</p>
        </div>
      </div>
      <ol class="guide-step-list">
        <li v-for="item in currentGuide.steps" :key="item">{{ item }}</li>
      </ol>
    </section>

    <section class="guide-section">
      <div class="guide-section__head">
        <div>
          <h3>你能进入的模块</h3>
          <p>这里只展示当前权限组能用到的主模块，不再把无关模块混进来。</p>
        </div>
      </div>
      <div class="guide-card-grid">
        <article
          v-for="item in visibleModuleGuides"
          :key="item.name"
          class="guide-card"
        >
          <div class="guide-card__head">
            <div>
              <strong>{{ item.label }}</strong>
              <small>{{ item.code }}</small>
            </div>
            <el-button
              text
              type="primary"
              size="small"
              @click="jumpToModule(item.name)"
            >
              打开
            </el-button>
          </div>
          <div class="guide-card__note">{{ item.summary }}</div>
        </article>
      </div>
    </section>

    <section class="guide-section">
      <div class="guide-section__head">
        <div>
          <h3>容易用错的地方</h3>
          <p>先记住分工和默认入口，系统就不会显得乱。</p>
        </div>
      </div>
      <ul class="guide-list guide-list--compact">
        <li v-for="item in guideWarnings" :key="item">{{ item }}</li>
      </ul>
    </section>
  </div>
</template>
