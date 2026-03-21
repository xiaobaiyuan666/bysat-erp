<script setup>
import { computed, ref, watch } from "vue";
import { CirclePlus, RefreshLeft, Search } from "@element-plus/icons-vue";
import { ElMessageBox } from "element-plus";
import MobileToolbarPanel from "./MobileToolbarPanel.vue";
import RecordDetailDrawer from "./RecordDetailDrawer.vue";
import RowActionMenu from "./RowActionMenu.vue";
import ServiceTicketFollowUpDialog from "./ServiceTicketFollowUpDialog.vue";
import ServiceTicketDialog from "./ServiceTicketDialog.vue";
import StatCard from "./StatCard.vue";
import { usePersistedState } from "../composables/usePersistedState";
import { useAppStore } from "../stores/useAppStore";
import { toneToTagType } from "../utils/formatters";

const props = defineProps({
  projectFocusId: {
    type: String,
    default: "",
  },
});

const store = useAppStore();

const filters = usePersistedState("console.operations.issue.filters", {
  keyword: "",
  source: "",
  status: "",
  category: "",
  priority: "",
  assignee: "",
  ops_project_id: "",
});
const activeView = usePersistedState("console.operations.issue.view", "all");

const dialogVisible = ref(false);
const followUpDialogVisible = ref(false);
const detailDrawerVisible = ref(false);
const currentRecord = ref(null);
const currentFollowUpRecord = ref(null);
const detailRecord = ref(null);

const bootstrap = computed(() => store.state.bootstrap);
const currency = computed(() => bootstrap.value.meta?.currency || "CNY");
const rows = computed(() => bootstrap.value.serviceTicketRows || []);
const summary = computed(() => bootstrap.value.serviceSummary || {});
const options = computed(() => bootstrap.value.options || {});
const lookups = computed(() => bootstrap.value.lookups || {});
const canEditIssues = computed(() =>
  store.hasPermission(["service.edit", "operations.edit"]),
);

const quickViews = [
  { key: "all", label: "全部记录" },
  { key: "leader", label: "领导反馈" },
  { key: "new", label: "待响应" },
  { key: "escalated", label: "已转研发" },
  { key: "waiting_release", label: "待版本发布" },
  { key: "pending_callback", label: "待回告客户" },
  { key: "confirmed", label: "客户已确认" },
  { key: "overdue", label: "已超期" },
];

watch(
  () => props.projectFocusId,
  (projectId) => {
    if (!projectId) {
      return;
    }

    filters.value = {
      ...filters.value,
      ops_project_id: projectId,
    };
    activeView.value = "all";
  },
  { immediate: true },
);

const callbackPendingTotal = computed(() => {
  return rows.value.filter(
    (row) =>
      !row.customer_notified && !["resolved", "closed"].includes(row.status),
  ).length;
});

const customerConfirmedTotal = computed(() => {
  return rows.value.filter((row) => row.customer_confirmed).length;
});

const filteredRows = computed(() => {
  const keyword = filters.value.keyword.trim().toLowerCase();

  return rows.value.filter((row) => {
    const hitKeyword =
      keyword === "" ||
      [
        row.ticket_no,
        row.source_label,
        row.customer,
        row.contact_name,
        row.title,
        row.summary,
        row.ops_project_name,
        row.app_name,
        row.assignee,
        row.tech_ticket_title,
        row.release_display,
        row.next_action,
        row.customer_notified_to,
        row.customer_notified_channel_label,
        row.customer_feedback_result,
        row.customer_confirmation_note,
      ]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(keyword));

    const hitSource =
      !filters.value.source || row.source === filters.value.source;
    const hitStatus =
      !filters.value.status || row.status === filters.value.status;
    const hitCategory =
      !filters.value.category || row.category === filters.value.category;
    const hitPriority =
      !filters.value.priority || row.priority === filters.value.priority;
    const hitAssignee =
      !filters.value.assignee ||
      String(row.assignee)
        .toLowerCase()
        .includes(filters.value.assignee.toLowerCase());
    const hitProject =
      !filters.value.ops_project_id ||
      row.ops_project_id === filters.value.ops_project_id;

    const hitView = (() => {
      switch (activeView.value) {
        case "leader":
          return row.source === "leader";
        case "new":
          return row.status === "new";
        case "escalated":
          return row.status === "escalated";
        case "waiting_release":
          return Boolean(row.waiting_release);
        case "pending_callback":
          return (
            !row.customer_notified &&
            !["resolved", "closed"].includes(row.status)
          );
        case "confirmed":
          return Boolean(row.customer_confirmed);
        case "overdue":
          return Boolean(row.resolve_overdue);
        default:
          return true;
      }
    })();

    return (
      hitKeyword &&
      hitSource &&
      hitStatus &&
      hitCategory &&
      hitPriority &&
      hitAssignee &&
      hitProject &&
      hitView
    );
  });
});

const urgentRows = computed(() => {
  return rows.value
    .filter(
      (row) =>
        row.resolve_overdue ||
        row.source === "leader" ||
        row.release_attention ||
        row.status === "escalated",
    )
    .slice(0, 5);
});

const followRows = computed(() => {
  return rows.value
    .filter(
      (row) =>
        !row.customer_notified ||
        row.waiting_release ||
        row.release_attention ||
        row.customer_reply_count > 0,
    )
    .slice(0, 5);
});

const detailRecordForDrawer = computed(() => {
  if (!detailRecord.value) {
    return null;
  }

  const sections = [`问题描述\n${detailRecord.value.summary || "--"}`];

  if (detailRecord.value.customer_feedback_result) {
    sections.push(`回告结果\n${detailRecord.value.customer_feedback_result}`);
  }

  if (detailRecord.value.customer_confirmation_note) {
    sections.push(`客户确认\n${detailRecord.value.customer_confirmation_note}`);
  }

  if (detailRecord.value.next_action) {
    sections.push(`下一步动作\n${detailRecord.value.next_action}`);
  }

  if (detailRecord.value.release_display) {
    sections.push(`关联版本\n${detailRecord.value.release_display}`);
  }

  return {
    ...detailRecord.value,
    description: sections.join("\n\n"),
  };
});

const detailMetrics = computed(() => {
  if (!detailRecord.value) {
    return [];
  }

  return [
    { key: "timeline_count", label: "跟进次数" },
    { key: "customer_notified_label", label: "客户回告" },
    { key: "customer_notified_at", label: "回告时间", type: "datetime" },
    { key: "customer_confirmed_label", label: "客户确认" },
    { key: "customer_confirmed_at", label: "确认时间", type: "datetime" },
    { key: "customer_reply_count", label: "客户回告记录" },
    { key: "leader_sync_count", label: "领导同步" },
  ];
});

const detailFields = [
  { key: "ticket_no", label: "记录编号" },
  { key: "source_label", label: "反馈来源" },
  { key: "customer", label: "反馈主体" },
  { key: "contact_name", label: "反馈人" },
  { key: "contact_phone", label: "联系方式" },
  { key: "ops_project_name", label: "关联 APP 项目" },
  { key: "project_name", label: "关联交付项目" },
  { key: "tech_ticket_title", label: "关联研发待办" },
  { key: "release_display", label: "关联版本" },
  { key: "channel_label", label: "反馈渠道" },
  { key: "category_label", label: "问题分类" },
  { key: "assignee", label: "处理人" },
  { key: "opened_at", label: "记录时间", type: "datetime" },
  { key: "resolve_due_at", label: "处理截止", type: "datetime" },
  { key: "customer_notified_to", label: "回告对象" },
  { key: "customer_notified_channel_label", label: "回告方式" },
  { key: "customer_notified_at", label: "回告时间", type: "datetime" },
  { key: "customer_feedback_result", label: "回告结果" },
  { key: "customer_confirmed_label", label: "客户确认" },
  { key: "customer_confirmed_at", label: "确认时间", type: "datetime" },
  { key: "customer_confirmation_note", label: "确认备注" },
  { key: "next_action", label: "下一步动作" },
];

function setView(key) {
  activeView.value = key;
}

function resetFilters() {
  filters.value = {
    keyword: "",
    source: "",
    status: "",
    category: "",
    priority: "",
    assignee: "",
    ops_project_id: "",
  };
  activeView.value = "all";
}

function openDialog(record = null) {
  currentRecord.value = record;
  dialogVisible.value = true;
}

function openDetail(record) {
  detailRecord.value = record;
  detailDrawerVisible.value = true;
}

function syncDetailRecord(recordId = "") {
  const targetId = recordId || detailRecord.value?.id || "";

  if (!targetId) {
    return;
  }

  const latest = rows.value.find((item) => item.id === targetId);

  if (latest) {
    detailRecord.value = latest;
  }
}

function openFollowUp(record) {
  currentFollowUpRecord.value = record;
  followUpDialogVisible.value = true;
}

async function handleIssueAction(row, action) {
  switch (action) {
    case "detail":
      openDetail(row);
      break;
    case "follow":
      openFollowUp(row);
      break;
    case "edit":
      openDialog(row);
      break;
    case "delete":
      await removeTicket(row);
      break;
    default:
      break;
  }
}

async function saveTicket(payload) {
  const action = payload.service_ticket_id
    ? "update_service_ticket"
    : "add_service_ticket";
  await store.submitAction(action, payload);
  dialogVisible.value = false;
  currentRecord.value = null;
  syncDetailRecord(payload.service_ticket_id);
}

async function saveFollowUp(payload) {
  await store.submitAction("add_service_ticket_update", payload);
  followUpDialogVisible.value = false;
  currentFollowUpRecord.value = null;
  syncDetailRecord(payload.service_ticket_id);
}

async function changeStatus(row, status) {
  if (!canEditIssues.value) {
    return;
  }

  await store.submitAction(
    "update_service_ticket_status",
    {
      service_ticket_id: row.id,
      status,
    },
    { silent: true },
  );

  syncDetailRecord(row.id);
}

async function removeTicket(row) {
  if (!canEditIssues.value) {
    return;
  }

  await ElMessageBox.confirm(`确认删除问题记录“${row.title}”吗？`, "删除确认", {
    type: "warning",
  });

  await store.submitAction("delete_service_ticket", {
    service_ticket_id: row.id,
  });

  if (detailRecord.value?.id === row.id) {
    detailDrawerVisible.value = false;
    detailRecord.value = null;
  }
}
</script>

<template>
  <div class="page-grid">
    <section class="stats-grid">
      <StatCard
        title="待处理问题"
        :value="String(summary.open_total || 0)"
        hint="当前仍在处理或待回告的问题总数。"
      />
      <StatCard
        title="领导反馈"
        :value="String(summary.leader_feedback_total || 0)"
        hint="需要优先同步给领导的问题记录。"
        tone="warning"
      />
      <StatCard
        title="待回告客户"
        :value="String(callbackPendingTotal)"
        hint="已经受理但还没有明确回告结果的问题。"
        tone="info"
      />
      <StatCard
        title="客户已确认"
        :value="String(customerConfirmedTotal)"
        hint="已经回告并且客户确认处理安排的问题。"
        tone="success"
      />
    </section>

    <section class="dashboard-double">
      <article class="panel-card">
        <header class="panel-card__header">
          <div>
            <h3>优先处理</h3>
            <p>
              领导反馈、超期问题和版本风险集中在这里，方便客服和运营先盯住。
            </p>
          </div>
        </header>
        <div class="panel-card__body">
          <div class="todo-list">
            <button
              v-for="row in urgentRows"
              :key="row.id"
              type="button"
              class="todo-item todo-item--action"
              @click="openDetail(row)"
            >
              <div class="todo-item__header">
                <strong>{{ row.title }}</strong>
                <el-tag
                  :type="
                    row.resolve_overdue || row.release_attention
                      ? 'danger'
                      : toneToTagType(row.status_tone)
                  "
                  effect="light"
                >
                  {{
                    row.resolve_overdue
                      ? "超期"
                      : row.release_attention
                        ? "版本风险"
                        : row.status_label
                  }}
                </el-tag>
              </div>
              <p>
                {{ row.source_label }} /
                {{ row.customer || row.contact_name || row.ops_project_name }}
              </p>
              <span>{{
                row.next_action ||
                row.release_display ||
                row.tech_ticket_title ||
                "等待进一步处理"
              }}</span>
            </button>
            <el-empty
              v-if="!urgentRows.length"
              description="当前没有需要重点盯办的问题记录"
            />
          </div>
        </div>
      </article>

      <article class="panel-card">
        <header class="panel-card__header">
          <div>
            <h3>回告视角</h3>
            <p>
              优先查看哪些问题还没有回告客户、哪些已经确认，以及最近的回告结果。
            </p>
          </div>
        </header>
        <div class="panel-card__body">
          <div class="todo-list">
            <button
              v-for="row in followRows"
              :key="row.id"
              type="button"
              class="todo-item todo-item--action"
              @click="openDetail(row)"
            >
              <div class="todo-item__header">
                <strong>{{ row.customer || row.title }}</strong>
                <el-tag
                  :type="
                    row.customer_confirmed
                      ? 'success'
                      : row.customer_notified
                        ? 'info'
                        : 'warning'
                  "
                  effect="light"
                >
                  {{
                    row.customer_confirmed
                      ? "已确认"
                      : row.customer_notified
                        ? "已回告"
                        : "待回告"
                  }}
                </el-tag>
              </div>
              <p>
                {{
                  row.customer_notified_to || row.release_display || row.title
                }}
              </p>
              <span>{{
                row.customer_confirmation_note ||
                row.customer_feedback_result ||
                row.next_action ||
                "暂无回告结果"
              }}</span>
            </button>
            <el-empty
              v-if="!followRows.length"
              description="当前没有待回告的问题"
            />
          </div>
        </div>
      </article>
    </section>

    <section class="panel-card">
      <header class="panel-card__header">
        <div>
          <h3>问题记录台账</h3>
          <p>
            客服、运营、销售和领导反馈统一记录在这里，再和 APP 项目研发联动。
          </p>
        </div>
      </header>
      <div class="panel-card__body">
        <div class="quick-filter-bar">
          <button
            v-for="item in quickViews"
            :key="item.key"
            type="button"
            class="quick-filter-chip"
            :class="{ 'is-active': activeView === item.key }"
            @click="setView(item.key)"
          >
            {{ item.label }}
          </button>
        </div>

        <MobileToolbarPanel title="问题筛选与操作" :count="filteredRows.length">
          <div class="toolbar toolbar--wide">
            <el-input
              v-model="filters.keyword"
              placeholder="搜编号 / 来源 / 标题 / APP / 回告对象 / 回告结果"
              :prefix-icon="Search"
              clearable
            />
            <el-select
              v-model="filters.source"
              clearable
                placeholder="全部来源"
            >
              <el-option
                v-for="item in options.serviceTicketSources"
                :key="item.value"
                :label="item.label"
                :value="item.value"
              />
            </el-select>
            <el-select
              v-model="filters.status"
              clearable
                placeholder="全部状态"
            >
              <el-option
                v-for="item in options.serviceTicketStatuses"
                :key="item.value"
                :label="item.label"
                :value="item.value"
              />
            </el-select>
            <el-select
              v-model="filters.category"
              clearable
                placeholder="全部分类"
            >
              <el-option
                v-for="item in options.serviceTicketCategories"
                :key="item.value"
                :label="item.label"
                :value="item.value"
              />
            </el-select>
            <el-select
              v-model="filters.priority"
              clearable
                placeholder="全部优先级"
            >
              <el-option
                v-for="item in options.priorities"
                :key="item.value"
                :label="item.label"
                :value="item.value"
              />
            </el-select>
            <el-select
              v-model="filters.ops_project_id"
              clearable
                placeholder="全部 APP 项目"
            >
              <el-option
                v-for="item in lookups.opsProjects"
                :key="item.value"
                :label="item.label"
                :value="item.value"
              />
            </el-select>
            <el-input
              v-model="filters.assignee"
                placeholder="处理人筛选"
              clearable
            />
            <el-button :icon="RefreshLeft" @click="resetFilters"
              >重置</el-button
            >
            <el-button
              type="primary"
              :icon="CirclePlus"
              :disabled="!canEditIssues"
              @click="openDialog()"
              >新增记录</el-button
            >
          </div>
        </MobileToolbarPanel>

        <div class="table-meta">
          <span>当前结果 {{ filteredRows.length }} 条</span>
          <span>双击表格可直接查看详情、处理轨迹和客户确认状态。</span>
        </div>

        <div class="table-shell">
          <el-table
            class="responsive-table responsive-table--ops-issues"
            :data="filteredRows"
            size="large"
            @row-dblclick="openDetail"
          >
            <el-table-column prop="ticket_no" label="编号" width="150" />
            <el-table-column label="来源 / 主体" min-width="220">
              <template #default="{ row }">
                <div class="stack-text">
                  <strong>{{ row.source_label }}</strong>
                  <span>{{ row.customer || row.contact_name || "--" }}</span>
                </div>
              </template>
            </el-table-column>
            <el-table-column
              prop="title"
              label="问题标题"
              min-width="220"
              show-overflow-tooltip
            />
            <el-table-column
              prop="ops_project_name"
              label="APP 项目"
              min-width="180"
              show-overflow-tooltip
            />
            <el-table-column prop="assignee" label="处理人" width="100" />
            <el-table-column label="客户回告" min-width="220">
              <template #default="{ row }">
                <div class="stack-text">
                  <strong>{{
                    row.customer_notified_to || row.customer_notified_label
                  }}</strong>
                  <span>{{
                    row.customer_notified_channel_label ||
                    row.customer_notified_label
                  }}</span>
                </div>
              </template>
            </el-table-column>
            <el-table-column label="客户确认" min-width="180">
              <template #default="{ row }">
                <div class="stack-text">
                  <el-tag
                    :type="toneToTagType(row.customer_confirmed_tone)"
                    effect="light"
                  >
                    {{ row.customer_confirmed_label }}
                  </el-tag>
                  <span>{{
                    row.customer_confirmed_at ||
                    row.customer_confirmation_note ||
                    "尚未确认"
                  }}</span>
                </div>
              </template>
            </el-table-column>
            <el-table-column label="状态" width="170">
              <template #default="{ row }">
                <el-select
                  :model-value="row.status"
                  size="small"
                  :disabled="!canEditIssues"
                  @change="changeStatus(row, $event)"
                >
                  <el-option
                    v-for="item in options.serviceTicketStatuses"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                  />
                </el-select>
              </template>
            </el-table-column>
            <el-table-column label="评论 / 回复" width="150">
              <template #default="{ row }">
                <div class="stack-text">
                  <strong>{{ row.timeline_count || 0 }} 条</strong>
                  <span
                    >客户 {{ row.customer_reply_count || 0 }} / 内部
                    {{ row.internal_note_count || 0 }}</span
                  >
                </div>
              </template>
            </el-table-column>
            <el-table-column label="版本进度" min-width="180">
              <template #default="{ row }">
                <div class="stack-text">
                  <strong>{{ row.release_display || "--" }}</strong>
                  <span>{{
                    row.release_display
                      ? row.release_status_label
                      : "未挂版本"
                  }}</span>
                </div>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="180" fixed="right">
              <template #default="{ row }">
                <RowActionMenu
                  :items="[
                    { key: 'detail', label: '详情', primary: true },
                    { key: 'follow', label: '跟进', hidden: !canEditIssues },
                    { key: 'edit', label: '编辑', hidden: !canEditIssues },
                    {
                      key: 'delete',
                      label: '删除',
                      hidden: !canEditIssues,
                      danger: true,
                    },
                  ]"
                  @select="handleIssueAction(row, $event)"
                />
              </template>
            </el-table-column>
          </el-table>
        </div>
      </div>
    </section>

    <ServiceTicketDialog
      v-model="dialogVisible"
      :record="currentRecord"
      :ops-projects="lookups.opsProjects"
      :delivery-projects="lookups.projects"
      :tech-tickets="lookups.techTickets"
      :assignees="lookups.serviceAssignees"
      :sources="options.serviceTicketSources"
      :channels="options.serviceTicketChannels"
      :categories="options.serviceTicketCategories"
      :statuses="options.serviceTicketStatuses"
      :priorities="options.priorities"
      :loading="store.state.submitting"
      @submit="saveTicket"
    />

    <ServiceTicketFollowUpDialog
      v-model="followUpDialogVisible"
      :record="currentFollowUpRecord"
      :update-types="options.serviceTicketUpdateTypes"
      :visibilities="options.serviceTicketUpdateVisibilities"
      :statuses="options.serviceTicketStatuses"
      :loading="store.state.submitting"
      @submit="saveFollowUp"
    />

    <RecordDetailDrawer
      v-model="detailDrawerVisible"
      title="问题记录详情"
      :record="detailRecordForDrawer"
      :currency="currency"
      :fields="detailFields"
      :metrics="detailMetrics"
      :timeline="detailRecord?.timeline || []"
      timeline-title="处理轨迹"
      :status-label="detailRecord?.status_label || ''"
      :status-tone="detailRecord?.status_tone || 'info'"
      notes-label="处理备注"
      :show-attachments="false"
      :editable="canEditIssues"
      :extra-action-label="canEditIssues ? '新增跟进' : ''"
      @edit="openDialog"
      @extra-action="openFollowUp"
    />
  </div>
</template>
