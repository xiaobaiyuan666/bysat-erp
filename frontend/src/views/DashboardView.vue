<script setup>
import { computed } from "vue";
import { useRouter } from "vue-router";
import PurePageCard from "../components/PurePageCard.vue";
import { consoleModules } from "../config/console";
import { useAppStore } from "../stores/useAppStore";
import { formatCurrency } from "../utils/formatters";

const store = useAppStore();
const router = useRouter();

const bootstrap = computed(() => store.state.bootstrap || {});
const currency = computed(() => bootstrap.value.meta?.currency || "CNY");
const dashboard = computed(() => bootstrap.value.dashboard || {});
const currentUser = computed(() => bootstrap.value.currentUser || {});
const alerts = computed(() => bootstrap.value.businessAlerts || []);
const dueInvoiceRows = computed(() => bootstrap.value.dueInvoiceRows || []);
const topAlerts = computed(() => alerts.value.slice(0, 4));
const topDueInvoices = computed(() => dueInvoiceRows.value.slice(0, 4));

const entryActions = computed(() => {
  return [
    {
      name: "finance",
      title: "财务中心",
      description: "记账、附件、回款付款",
    },
    {
      name: "projects",
      title: "项目交付",
      description: "项目、任务、逾期处理",
    },
    {
      name: "operations",
      title: "APP 运营",
      description: "问题、发版、资料",
    },
    {
      name: "team",
      title: "人员权限",
      description: "员工、角色、日志",
    },
  ]
    .map((item) => ({
      ...item,
      module: consoleModules.find((moduleItem) => moduleItem.name === item.name),
    }))
    .filter(
      (item) => item.module && store.hasPermission(item.module.permission),
    );
});

const defaultModuleAction = computed(() => {
  const preferredModule =
    currentUser.value.role_home_module ||
    currentUser.value.home_module ||
    currentUser.value.default_module;

  const businessDefault =
    entryActions.value.find((item) => item.name !== "team") ||
    entryActions.value[0] ||
    null;

  return (
    entryActions.value.find(
      (item) => item.name === preferredModule && item.name !== "team",
    ) ||
    businessDefault
  );
});

function openModule(name) {
  const item = consoleModules.find((moduleItem) => moduleItem.name === name);

  if (!item) {
    return;
  }

  router.push(item.path);
}
</script>

<template>
  <div class="page-grid">
    <PurePageCard title="工作入口" description="先去对应模块办事。">
      <section class="dashboard-priority-strip">
        <button
          v-if="defaultModuleAction"
          type="button"
          class="dashboard-priority-card"
          @click="openModule(defaultModuleAction.name)"
        >
          <span>默认入口</span>
          <strong>{{ defaultModuleAction.title }}</strong>
        </button>

        <button
          type="button"
          class="dashboard-priority-card"
          @click="openModule('finance')"
        >
          <span>待回款</span>
          <strong>{{ formatCurrency(dashboard.open_receivables, currency) }}</strong>
        </button>

        <button
          type="button"
          class="dashboard-priority-card"
          @click="openModule('finance')"
        >
          <span>待付款</span>
          <strong>{{ formatCurrency(dashboard.open_payables, currency) }}</strong>
        </button>

        <button
          type="button"
          class="dashboard-priority-card"
          @click="openModule('projects')"
        >
          <span>进行中项目</span>
          <strong>{{ dashboard.active_projects || 0 }}</strong>
        </button>
      </section>

      <div class="page-focus-layout">
        <section class="page-focus-block">
          <div class="page-focus-block__title">常用入口</div>
          <div class="action-stack">
            <button
              v-for="item in entryActions"
              :key="item.name"
              type="button"
              class="action-tile"
              @click="openModule(item.name)"
            >
              <div>
                <strong>{{ item.title }}</strong>
                <span>{{ item.description }}</span>
              </div>
            </button>
          </div>
        </section>

        <section class="page-focus-block">
          <div class="page-focus-block__title">今天提醒</div>
          <ul v-if="topAlerts.length" class="simple-list">
            <li v-for="alert in topAlerts" :key="alert">{{ alert }}</li>
          </ul>
          <el-empty v-else description="当前没有新的经营提醒" />
        </section>

        <section class="page-focus-block">
          <div class="page-focus-block__title">最近到期单据</div>
          <div v-if="topDueInvoices.length" class="compact-stack">
            <article
              v-for="row in topDueInvoices"
              :key="row.id"
              class="compact-item"
            >
              <div class="compact-item__head">
                <strong>{{ row.title }}</strong>
                <el-tag
                  :type="row.overdue ? 'danger' : 'warning'"
                  effect="light"
                >
                  {{ row.kind_label }}
                </el-tag>
              </div>
              <div class="compact-item__meta">
                <span>{{ row.counterparty }}</span>
                <span>{{ row.due_date }}</span>
                <span>{{ formatCurrency(row.amount, currency) }}</span>
              </div>
            </article>
          </div>
          <el-empty v-else description="当前没有临近的回款或付款节点" />
        </section>
      </div>
    </PurePageCard>
  </div>
</template>
