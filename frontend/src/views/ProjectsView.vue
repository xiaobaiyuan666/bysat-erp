<script setup>
import { computed, ref } from "vue";
import { CirclePlus, RefreshLeft, Search } from "@element-plus/icons-vue";
import { ElMessageBox } from "element-plus";
import MobileToolbarPanel from "../components/MobileToolbarPanel.vue";
import PurePageCard from "../components/PurePageCard.vue";
import ProjectDialog from "../components/ProjectDialog.vue";
import RecordDetailDrawer from "../components/RecordDetailDrawer.vue";
import RowActionMenu from "../components/RowActionMenu.vue";
import TaskDialog from "../components/TaskDialog.vue";
import { usePersistedState } from "../composables/usePersistedState";
import { useAppStore } from "../stores/useAppStore";
import {
  formatCurrency,
  toneToTagType,
} from "../utils/formatters";

const store = useAppStore();

const activeTab = usePersistedState("console.projects.tab", "projects");
const projectFilters = usePersistedState("console.projects.project.filters", {
  keyword: "",
  status: "",
  owner: "",
});
const taskFilters = usePersistedState("console.projects.task.filters", {
  keyword: "",
  status: "",
  project_id: "",
});
const activeProjectView = usePersistedState(
  "console.projects.project.view",
  "all",
);
const activeTaskView = usePersistedState("console.projects.task.view", "all");

const projectDialogVisible = ref(false);
const taskDialogVisible = ref(false);
const detailDrawerVisible = ref(false);
const detailMode = ref("project");
const detailRecord = ref(null);
const currentProject = ref(null);
const currentTask = ref(null);

const bootstrap = computed(() => store.state.bootstrap);
const dashboard = computed(() => bootstrap.value.dashboard || {});
const projectRows = computed(() => bootstrap.value.projectRows || []);
const taskRows = computed(() => bootstrap.value.taskRows || []);
const projectHealthRows = computed(
  () => bootstrap.value.projectHealthRows || [],
);
const options = computed(() => bootstrap.value.options || {});
const lookups = computed(() => bootstrap.value.lookups || {});
const currency = computed(() => bootstrap.value.meta.currency || "CNY");
const canEditProjects = computed(() => store.hasPermission("projects.edit"));

const projectQuickViews = [
  { key: "all", label: "全部项目" },
  { key: "high_risk", label: "高风险项目" },
  { key: "delivery", label: "交付中" },
  { key: "budget_high", label: "预算高占用" },
  { key: "low_progress", label: "进度偏慢" },
];

const taskQuickViews = [
  { key: "all", label: "全部任务" },
  { key: "doing", label: "进行中" },
  { key: "todo", label: "待开始" },
  { key: "overdue", label: "已逾期" },
  { key: "high_priority", label: "高优先级" },
];

const riskMap = computed(() => {
  const rows = new Map();
  for (const row of projectHealthRows.value) {
    rows.set(row.id, row);
  }
  return rows;
});

const deliveryProjectCount = computed(() => {
  return projectRows.value.filter((row) => row.status === "delivery").length;
});

const highRiskProjectCount = computed(() => {
  return projectHealthRows.value.filter((row) => Number(row.risk_score || 0) >= 3)
    .length;
});

const doingTaskCount = computed(() => {
  return taskRows.value.filter((row) => row.status === "doing").length;
});

const overdueTaskCount = computed(() => {
  return taskRows.value.filter((row) => Boolean(row.overdue)).length;
});

const filteredProjects = computed(() => {
  const filters = projectFilters.value;
  const keyword = filters.keyword.trim().toLowerCase();

  return projectRows.value.filter((row) => {
    const risk = riskMap.value.get(row.id);
    const hitKeyword =
      keyword === "" ||
      [row.name, row.client, row.owner, row.description]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(keyword));

    const hitStatus = !filters.status || row.status === filters.status;
    const hitOwner =
      !filters.owner ||
      String(row.owner).toLowerCase().includes(filters.owner.toLowerCase());
    const hitView = (() => {
      switch (activeProjectView.value) {
        case "high_risk":
          return (risk?.risk_score || 0) >= 3;
        case "delivery":
          return row.status === "delivery";
        case "budget_high":
          return Number(row.budget_usage || 0) >= 70;
        case "low_progress":
          return (
            Number(row.progress || 0) < 40 &&
            row.status !== "planning" &&
            row.status !== "done"
          );
        default:
          return true;
      }
    })();

    return hitKeyword && hitStatus && hitOwner && hitView;
  });
});

const filteredTasks = computed(() => {
  const filters = taskFilters.value;
  const keyword = filters.keyword.trim().toLowerCase();

  return taskRows.value.filter((row) => {
    const hitKeyword =
      keyword === "" ||
      [row.title, row.assignee, row.project_name]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(keyword));

    const hitStatus = !filters.status || row.status === filters.status;
    const hitProject =
      !filters.project_id || row.project_id === filters.project_id;
    const hitView = (() => {
      switch (activeTaskView.value) {
        case "doing":
          return row.status === "doing";
        case "todo":
          return row.status === "todo";
        case "overdue":
          return Boolean(row.overdue);
        case "high_priority":
          return row.priority === "high";
        default:
          return true;
      }
    })();

    return hitKeyword && hitStatus && hitProject && hitView;
  });
});

const detailTitle = computed(() =>
  detailMode.value === "project" ? "项目详情" : "任务详情",
);

const detailFields = computed(() => {
  if (detailMode.value === "project") {
    return [
      { key: "client", label: "客户/业务线" },
      { key: "owner", label: "负责人" },
      { key: "start_date", label: "开始日期", type: "date" },
      { key: "due_date", label: "计划截止", type: "date" },
      { key: "priority_label", label: "优先级" },
      { key: "status_label", label: "项目状态" },
    ];
  }

  return [
    { key: "project_name", label: "所属项目" },
    { key: "assignee", label: "负责人" },
    { key: "due_date", label: "截止日期", type: "date" },
    { key: "priority_label", label: "优先级" },
    { key: "status_label", label: "任务状态" },
  ];
});

const detailMetrics = computed(() => {
  if (detailMode.value === "project") {
    return [
      { key: "budget", label: "项目预算", type: "currency" },
      { key: "spent", label: "已耗成本", type: "currency" },
      { key: "progress", label: "完成进度" },
    ];
  }

  return [
    { key: "estimate_hours", label: "预估工时" },
    { key: "actual_hours", label: "实际工时" },
  ];
});

const detailStatusLabel = computed(
  () => detailRecord.value?.status_label || "",
);
const detailStatusTone = computed(
  () => detailRecord.value?.status_tone || "info",
);

function setProjectView(key) {
  activeProjectView.value = key;
}

function setTaskView(key) {
  activeTaskView.value = key;
}

function focusProjects(view = "all") {
  activeTab.value = "projects";
  projectFilters.value = {
    keyword: "",
    status: "",
    owner: "",
  };
  activeProjectView.value = view;
}

function focusTasks(view = "all") {
  activeTab.value = "tasks";
  taskFilters.value = {
    keyword: "",
    status: "",
    project_id: "",
  };
  activeTaskView.value = view;
}

function resetProjectFilters() {
  projectFilters.value = {
    keyword: "",
    status: "",
    owner: "",
  };
  activeProjectView.value = "all";
}

function resetTaskFilters() {
  taskFilters.value = {
    keyword: "",
    status: "",
    project_id: "",
  };
  activeTaskView.value = "all";
}

function openProjectDialog(record = null) {
  if (!canEditProjects.value) {
    return;
  }

  currentProject.value = record;
  projectDialogVisible.value = true;
}

function openTaskDialog(record = null) {
  if (!canEditProjects.value) {
    return;
  }

  currentTask.value = record;
  taskDialogVisible.value = true;
}

function openProjectDetail(row) {
  detailMode.value = "project";
  detailRecord.value = row;
  detailDrawerVisible.value = true;
}

function openTaskDetail(row) {
  detailMode.value = "task";
  detailRecord.value = row;
  detailDrawerVisible.value = true;
}

function handleDetailEdit() {
  if (!detailRecord.value || !canEditProjects.value) {
    return;
  }

  if (detailMode.value === "project") {
    openProjectDialog(detailRecord.value);
  } else {
    openTaskDialog(detailRecord.value);
  }

  detailDrawerVisible.value = false;
}

async function handleProjectAction(row, action) {
  switch (action) {
    case "detail":
      openProjectDetail(row);
      break;
    case "edit":
      openProjectDialog(row);
      break;
    case "delete":
      await removeProject(row);
      break;
    default:
      break;
  }
}

async function handleTaskAction(row, action) {
  switch (action) {
    case "detail":
      openTaskDetail(row);
      break;
    case "edit":
      openTaskDialog(row);
      break;
    case "delete":
      await removeTask(row);
      break;
    default:
      break;
  }
}

async function saveProject(payload) {
  if (!canEditProjects.value) {
    return;
  }

  const action = payload.project_id ? "update_project" : "add_project";
  await store.submitAction(action, payload);
  projectDialogVisible.value = false;
  currentProject.value = null;
}

async function saveTask(payload) {
  if (!canEditProjects.value) {
    return;
  }

  const action = payload.task_id ? "update_task" : "add_task";
  await store.submitAction(action, payload);
  taskDialogVisible.value = false;
  currentTask.value = null;
}

async function removeProject(row) {
  if (!canEditProjects.value) {
    return;
  }

  await ElMessageBox.confirm(
    `确认删除项目“${row.name}”吗？`,
    "删除确认",
    {
      type: "warning",
    },
  );

  await store.submitAction("delete_project", {
    project_id: row.id,
  });
}

async function removeTask(row) {
  if (!canEditProjects.value) {
    return;
  }

  await ElMessageBox.confirm(
    `确认删除任务“${row.title}”吗？`,
    "删除确认",
    {
      type: "warning",
    },
  );

  await store.submitAction("delete_task", {
    task_id: row.id,
  });
}

async function changeProjectStatus(row, status) {
  if (!canEditProjects.value) {
    return;
  }

  await store.submitAction(
    "update_project_status",
    {
      project_id: row.id,
      status,
    },
    { silent: true },
  );
}

async function changeTaskStatus(row, status) {
  if (!canEditProjects.value) {
    return;
  }

  await store.submitAction(
    "update_task_status",
    {
      task_id: row.id,
      status,
    },
    { silent: true },
  );
}
</script>

<template>
  <div class="page-grid">
    <PurePageCard
      card-class="projects-workbench"
      title="项目交付台账"
      description="直接处理项目和任务。"
    >
      <template #actions>
        <template v-if="canEditProjects">
          <el-button type="primary" @click="openProjectDialog()">
            <el-icon><CirclePlus /></el-icon>
            新增项目
          </el-button>
          <el-button @click="openTaskDialog()">
            <el-icon><CirclePlus /></el-icon>
            新增任务
          </el-button>
        </template>
        <el-tag v-else type="info" effect="plain">当前账号为只读</el-tag>
      </template>

      <section class="projects-priority-strip">
        <button
          type="button"
          class="projects-priority-card"
          @click="focusProjects('delivery')"
        >
          <span>交付中项目</span>
          <strong>{{ deliveryProjectCount }}</strong>
        </button>
        <button
          type="button"
          class="projects-priority-card"
          @click="focusProjects('high_risk')"
        >
          <span>高风险项目</span>
          <strong>{{ highRiskProjectCount }}</strong>
        </button>
        <button
          type="button"
          class="projects-priority-card"
          @click="focusTasks('overdue')"
        >
          <span>逾期任务</span>
          <strong>{{ overdueTaskCount }}</strong>
        </button>
        <button
          type="button"
          class="projects-priority-card"
          @click="focusTasks('doing')"
        >
          <span>进行中任务</span>
          <strong>{{ doingTaskCount }}</strong>
        </button>
      </section>

        <el-tabs v-model="activeTab" class="ledger-tabs">
          <el-tab-pane label="项目" name="projects">
            <div class="quick-filter-bar">
              <button
                v-for="item in projectQuickViews"
                :key="item.key"
                type="button"
                class="quick-filter-chip"
                :class="{ 'is-active': activeProjectView === item.key }"
                @click="setProjectView(item.key)"
              >
                {{ item.label }}
              </button>
            </div>

            <MobileToolbarPanel
              title="项目筛选与操作"
              :count="filteredProjects.length"
            >
              <div class="toolbar toolbar--wide">
                <el-input
                  v-model="projectFilters.keyword"
                  placeholder="搜项目 / 客户 / 负责人"
                  :prefix-icon="Search"
                  clearable
                />
                <el-select
                  v-model="projectFilters.status"
                  clearable
                  placeholder="全部状态"
                >
                  <el-option
                    v-for="item in options.projectStatuses"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                  />
                </el-select>
                <el-input
                  v-model="projectFilters.owner"
                  placeholder="负责人筛选"
                  clearable
                />
                <el-button :icon="RefreshLeft" @click="resetProjectFilters"
                  >重置</el-button
                >
              </div>
            </MobileToolbarPanel>

            <div class="table-meta">
              <span>当前结果 {{ filteredProjects.length }} 条</span>
              <span>双击行看详情</span>
            </div>

            <div class="table-shell">
              <el-table
                class="responsive-table responsive-table--projects"
                :data="filteredProjects"
                size="large"
                @row-dblclick="openProjectDetail"
              >
                <el-table-column
                  prop="name"
                  label="项目名称"
                  min-width="180"
                  show-overflow-tooltip
                />
                <el-table-column
                  prop="client"
                  label="客户/业务线"
                  min-width="140"
                  show-overflow-tooltip
                />
                <el-table-column prop="owner" label="负责人" width="100" />
                <el-table-column label="状态" width="160">
                  <template #default="{ row }">
                    <el-select
                      :model-value="row.status"
                      size="small"
                      :disabled="!canEditProjects"
                      @change="changeProjectStatus(row, $event)"
                    >
                      <el-option
                        v-for="item in options.projectStatuses"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                      />
                    </el-select>
                  </template>
                </el-table-column>
                <el-table-column
                  prop="priority_label"
                  label="优先级"
                  width="100"
                />
                <el-table-column label="预算 / 成本" min-width="220">
                  <template #default="{ row }">
                    <div class="stack-text">
                      <strong>{{
                        formatCurrency(row.budget, currency)
                      }}</strong>
                      <span
                        >已花 {{ formatCurrency(row.spent, currency) }} / 利润
                        {{ formatCurrency(row.margin, currency) }}</span
                      >
                    </div>
                  </template>
                </el-table-column>
                <el-table-column label="进度" min-width="180">
                  <template #default="{ row }">
                    <div class="stack-text">
                      <span>{{ row.progress }}%</span>
                      <el-progress
                        :percentage="row.progress"
                        :stroke-width="8"
                      />
                    </div>
                  </template>
                </el-table-column>
                <el-table-column prop="due_date" label="截止日" width="120" />
                <el-table-column label="操作" width="180" fixed="right">
                  <template #default="{ row }">
                    <RowActionMenu
                      :items="[
                        { key: 'detail', label: '详情', primary: true },
                        {
                          key: 'edit',
                          label: '编辑',
                          hidden: !canEditProjects,
                        },
                        {
                          key: 'delete',
                          label: '删除',
                          hidden: !canEditProjects,
                          danger: true,
                        },
                      ]"
                      @select="handleProjectAction(row, $event)"
                    />
                  </template>
                </el-table-column>
              </el-table>
            </div>
          </el-tab-pane>

          <el-tab-pane label="任务" name="tasks">
            <div class="quick-filter-bar">
              <button
                v-for="item in taskQuickViews"
                :key="item.key"
                type="button"
                class="quick-filter-chip"
                :class="{ 'is-active': activeTaskView === item.key }"
                @click="setTaskView(item.key)"
              >
                {{ item.label }}
              </button>
            </div>

            <MobileToolbarPanel
              title="任务筛选与操作"
              :count="filteredTasks.length"
            >
              <div class="toolbar toolbar--wide">
                <el-input
                  v-model="taskFilters.keyword"
                  placeholder="搜任务 / 负责人 / 项目"
                  :prefix-icon="Search"
                  clearable
                />
                <el-select
                  v-model="taskFilters.status"
                  clearable
                  placeholder="全部状态"
                >
                  <el-option
                    v-for="item in options.taskStatuses"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                  />
                </el-select>
                <el-select
                  v-model="taskFilters.project_id"
                  clearable
                  placeholder="全部项目"
                >
                  <el-option
                    v-for="item in lookups.projects"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                  />
                </el-select>
                <el-button :icon="RefreshLeft" @click="resetTaskFilters"
                  >重置</el-button
                >
              </div>
            </MobileToolbarPanel>

            <div class="table-meta">
              <span>当前结果 {{ filteredTasks.length }} 条</span>
              <span>双击行看详情</span>
            </div>

            <div class="table-shell">
              <el-table
                class="responsive-table responsive-table--tasks"
                :data="filteredTasks"
                size="large"
                @row-dblclick="openTaskDetail"
              >
                <el-table-column
                  prop="title"
                  label="任务标题"
                  min-width="180"
                  show-overflow-tooltip
                />
                <el-table-column
                  prop="project_name"
                  label="所属项目"
                  min-width="150"
                  show-overflow-tooltip
                />
                <el-table-column prop="assignee" label="负责人" width="100" />
                <el-table-column label="状态" width="150">
                  <template #default="{ row }">
                    <el-select
                      :model-value="row.status"
                      size="small"
                      :disabled="!canEditProjects"
                      @change="changeTaskStatus(row, $event)"
                    >
                      <el-option
                        v-for="item in options.taskStatuses"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                      />
                    </el-select>
                  </template>
                </el-table-column>
                <el-table-column
                  prop="priority_label"
                  label="优先级"
                  width="100"
                />
                <el-table-column label="工时" min-width="150">
                  <template #default="{ row }">
                    <div class="stack-text">
                      <strong>预估 {{ row.estimate_hours }}h</strong>
                      <span>实际 {{ row.actual_hours }}h</span>
                    </div>
                  </template>
                </el-table-column>
                <el-table-column prop="due_date" label="截止日" width="120" />
                <el-table-column label="风险" width="110">
                  <template #default="{ row }">
                    <el-tag
                      :type="
                        row.overdue ? 'danger' : toneToTagType(row.status_tone)
                      "
                      effect="light"
                    >
                        {{ row.overdue ? "已逾期" : row.status_label }}
                    </el-tag>
                  </template>
                </el-table-column>
                <el-table-column label="操作" width="180" fixed="right">
                  <template #default="{ row }">
                    <RowActionMenu
                      :items="[
                        { key: 'detail', label: '详情', primary: true },
                        {
                          key: 'edit',
                          label: '编辑',
                          hidden: !canEditProjects,
                        },
                        {
                          key: 'delete',
                          label: '删除',
                          hidden: !canEditProjects,
                          danger: true,
                        },
                      ]"
                      @select="handleTaskAction(row, $event)"
                    />
                  </template>
                </el-table-column>
              </el-table>
            </div>
          </el-tab-pane>
        </el-tabs>
          </PurePageCard>

    <ProjectDialog
      v-model="projectDialogVisible"
      :record="currentProject"
      :statuses="options.projectStatuses"
      :priorities="options.priorities"
      :loading="store.state.submitting"
      @submit="saveProject"
    />

    <TaskDialog
      v-model="taskDialogVisible"
      :record="currentTask"
      :projects="lookups.projects"
      :statuses="options.taskStatuses"
      :priorities="options.priorities"
      :loading="store.state.submitting"
      @submit="saveTask"
    />

    <RecordDetailDrawer
      v-model="detailDrawerVisible"
      :title="detailTitle"
      :record="detailRecord"
      :currency="currency"
      :fields="detailFields"
      :metrics="detailMetrics"
      :status-label="detailStatusLabel"
      :status-tone="detailStatusTone"
      :notes-label="detailMode === 'project' ? '项目说明' : '任务说明'"
      :show-attachments="false"
      :editable="canEditProjects"
      @edit="handleDetailEdit"
    />
  </div>
</template>
