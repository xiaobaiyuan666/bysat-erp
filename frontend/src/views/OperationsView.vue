<script setup>
import { computed, nextTick, ref } from "vue";
import { CirclePlus, RefreshLeft, Search } from "@element-plus/icons-vue";
import { ElMessageBox } from "element-plus";
import MobileToolbarPanel from "../components/MobileToolbarPanel.vue";
import PurePageCard from "../components/PurePageCard.vue";
import OpsIssuePanel from "../components/OpsIssuePanel.vue";
import OpsMaterialPanel from "../components/OpsMaterialPanel.vue";
import OpsMilestoneDialog from "../components/OpsMilestoneDialog.vue";
import OpsProjectDialog from "../components/OpsProjectDialog.vue";
import OpsReleaseDialog from "../components/OpsReleaseDialog.vue";
import OpsRiskDialog from "../components/OpsRiskDialog.vue";
import OpsUpdateDialog from "../components/OpsUpdateDialog.vue";
import RecordDetailDrawer from "../components/RecordDetailDrawer.vue";
import RowActionMenu from "../components/RowActionMenu.vue";
import TechTicketDialog from "../components/TechTicketDialog.vue";
import { usePersistedState } from "../composables/usePersistedState";
import { useAppStore } from "../stores/useAppStore";
import {
  formatCurrency,
  formatPercent,
  toneToTagType,
} from "../utils/formatters";

const store = useAppStore();
const weekStart = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000)
  .toISOString()
  .slice(0, 10);
const weekEnd = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000)
  .toISOString()
  .slice(0, 10);

const activeTab = usePersistedState("console.operations.tab", "issues");
const projectFilters = usePersistedState("console.operations.project.filters", {
  keyword: "",
  status: "",
  lifecycle_stage: "",
  priority: "",
  manager: "",
});
const milestoneFilters = usePersistedState(
  "console.operations.milestone.filters",
  {
    keyword: "",
    status: "",
    ops_project_id: "",
  },
);
const updateFilters = usePersistedState("console.operations.update.filters", {
  keyword: "",
  ops_project_id: "",
  owner: "",
});
const releaseFilters = usePersistedState("console.operations.release.filters", {
  keyword: "",
  status: "",
  ops_project_id: "",
  customer_sync_status: "",
  owner: "",
});
const riskFilters = usePersistedState("console.operations.risk.filters", {
  keyword: "",
  status: "",
  level: "",
  ops_project_id: "",
});
const techFilters = usePersistedState("console.operations.tech.filters", {
  keyword: "",
  ops_project_id: "",
  status: "",
  type: "",
  severity: "",
  owner: "",
});
const activeProjectView = usePersistedState(
  "console.operations.project.view",
  "all",
);
const activeMilestoneView = usePersistedState(
  "console.operations.milestone.view",
  "all",
);
const activeUpdateView = usePersistedState(
  "console.operations.update.view",
  "all",
);
const activeReleaseView = usePersistedState(
  "console.operations.release.view",
  "all",
);
const activeRiskView = usePersistedState("console.operations.risk.view", "all");
const activeTechView = usePersistedState("console.operations.tech.view", "all");

const opsProjectDialogVisible = ref(false);
const milestoneDialogVisible = ref(false);
const updateDialogVisible = ref(false);
const releaseDialogVisible = ref(false);
const riskDialogVisible = ref(false);
const techDialogVisible = ref(false);
const detailDrawerVisible = ref(false);

const currentOpsProject = ref(null);
const currentMilestone = ref(null);
const currentUpdate = ref(null);
const currentRelease = ref(null);
const currentRisk = ref(null);
const currentTechTicket = ref(null);
const issueProjectFocusId = ref("");
const materialProjectFocusId = ref("");
const detailMode = ref("ops_project");
const detailRecord = ref(null);

const bootstrap = computed(() => store.state.bootstrap);
const currency = computed(() => bootstrap.value.meta?.currency || "CNY");
const summary = computed(() => bootstrap.value.operationsSummary || {});
const opsProjectRows = computed(() => bootstrap.value.opsProjectRows || []);
const opsMilestoneRows = computed(() => bootstrap.value.opsMilestoneRows || []);
const opsUpdateRows = computed(() => bootstrap.value.opsUpdateRows || []);
const opsReleaseRows = computed(() => bootstrap.value.opsReleaseRows || []);
const opsMaterialRows = computed(() => bootstrap.value.opsMaterialRows || []);
const opsRiskRows = computed(() => bootstrap.value.opsRiskRows || []);
const serviceTicketRows = computed(
  () => bootstrap.value.serviceTicketRows || [],
);
const techTicketRows = computed(() => bootstrap.value.techTicketRows || []);
const options = computed(() => bootstrap.value.options || {});
const lookups = computed(() => bootstrap.value.lookups || {});
const canEditOperations = computed(() =>
  store.hasPermission("operations.edit"),
);
const canEditTech = computed(() =>
  store.hasPermission(["operations.edit", "tech.edit"]),
);

const projectQuickViews = [
  { key: "all", label: "全部 APP" },
  { key: "launch", label: "上线爬坡" },
  { key: "growth", label: "拉新增长" },
  { key: "retention", label: "留存活跃" },
  { key: "monetization", label: "商业化" },
  { key: "high_risk", label: "高风险" },
];

const milestoneQuickViews = [
  { key: "all", label: "全部里程碑" },
  { key: "upcoming", label: "7天内到期" },
  { key: "overdue", label: "已逾期" },
  { key: "doing", label: "推进中" },
  { key: "pending", label: "待启动" },
];

const updateQuickViews = [
  { key: "all", label: "全部周报" },
  { key: "this_week", label: "本周已更新" },
  { key: "with_blockers", label: "有阻塞项" },
  { key: "stale", label: "待补周报" },
];

const releaseQuickViews = [
  { key: "all", label: "全部版本" },
  { key: "planned", label: "待发布" },
  { key: "testing", label: "测试中" },
  { key: "ready", label: "待上线" },
  { key: "released", label: "已发布" },
  { key: "rolled_back", label: "已回滚" },
];

releaseQuickViews.splice(releaseQuickViews.length - 1, 0, {
  key: "pending_sync",
  label: "待回告",
});

const riskQuickViews = [
  { key: "all", label: "全部问题" },
  { key: "open", label: "待处理" },
  { key: "high", label: "高等级" },
  { key: "overdue", label: "已超期" },
  { key: "issue", label: "问题/变更" },
];

const techQuickViews = [
  { key: "all", label: "全部研发项" },
  { key: "bug", label: "待修复 Bug" },
  { key: "upgrade", label: "功能升级" },
  { key: "testing", label: "测试待发版" },
  { key: "overdue", label: "已超期" },
];

function focusOperationsProjects(view = "all") {
  activeTab.value = "projects";
  resetProjectFilters();
  activeProjectView.value = view;
}

function focusOperationsIssues() {
  activeTab.value = "issues";
  issueProjectFocusId.value = "";
}

function focusOperationsReleases(view = "all") {
  activeTab.value = "releases";
  resetReleaseFilters();
  activeReleaseView.value = view;
}

function focusOperationsTech(view = "all") {
  activeTab.value = "tech";
  resetTechFilters();
  activeTechView.value = view;
}

const filteredProjects = computed(() => {
  const filters = projectFilters.value;
  const keyword = filters.keyword.trim().toLowerCase();

  return opsProjectRows.value.filter((row) => {
    const hitKeyword =
      keyword === "" ||
      [
        row.name,
        row.app_name,
        row.app_version,
        row.business_line,
        row.manager,
        row.client_owner,
        row.core_metric,
        row.target,
        row.channel,
        row.description,
      ]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(keyword));

    const hitStatus = !filters.status || row.status === filters.status;
    const hitLifecycle =
      !filters.lifecycle_stage ||
      row.lifecycle_stage === filters.lifecycle_stage;
    const hitPriority = !filters.priority || row.priority === filters.priority;
    const hitManager =
      !filters.manager ||
      String(row.manager).toLowerCase().includes(filters.manager.toLowerCase());
    const hitView = (() => {
      switch (activeProjectView.value) {
        case "launch":
          return row.lifecycle_stage === "launch";
        case "growth":
          return row.lifecycle_stage === "growth";
        case "retention":
          return row.lifecycle_stage === "retention";
        case "monetization":
          return row.lifecycle_stage === "monetization";
        case "high_risk":
          return Number(row.high_risks || 0) > 0;
        default:
          return true;
      }
    })();

    return (
      hitKeyword &&
      hitStatus &&
      hitLifecycle &&
      hitPriority &&
      hitManager &&
      hitView
    );
  });
});

const filteredMilestones = computed(() => {
  const filters = milestoneFilters.value;
  const keyword = filters.keyword.trim().toLowerCase();

  return opsMilestoneRows.value.filter((row) => {
    const hitKeyword =
      keyword === "" ||
      [row.title, row.owner, row.project_name, row.deliverable, row.notes]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(keyword));

    const hitStatus = !filters.status || row.status === filters.status;
    const hitProject =
      !filters.ops_project_id || row.ops_project_id === filters.ops_project_id;
    const hitView = (() => {
      switch (activeMilestoneView.value) {
        case "upcoming":
          return (
            row.status !== "done" && !row.overdue && row.due_date <= weekEnd
          );
        case "overdue":
          return Boolean(row.overdue);
        case "doing":
          return row.status === "doing";
        case "pending":
          return row.status === "pending";
        default:
          return true;
      }
    })();

    return hitKeyword && hitStatus && hitProject && hitView;
  });
});

const staleProjectIds = computed(() => {
  const rows = new Set();
  for (const row of opsProjectRows.value) {
    if (row.needs_update) {
      rows.add(row.id);
    }
  }
  return rows;
});

const filteredUpdates = computed(() => {
  const filters = updateFilters.value;
  const keyword = filters.keyword.trim().toLowerCase();

  return opsUpdateRows.value.filter((row) => {
    const hitKeyword =
      keyword === "" ||
      [
        row.project_name,
        row.owner,
        row.summary,
        row.result,
        row.next_actions,
        row.blockers,
      ]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(keyword));

    const hitProject =
      !filters.ops_project_id || row.ops_project_id === filters.ops_project_id;
    const hitOwner =
      !filters.owner ||
      String(row.owner).toLowerCase().includes(filters.owner.toLowerCase());
    const hitView = (() => {
      switch (activeUpdateView.value) {
        case "this_week":
          return row.report_date >= weekStart;
        case "with_blockers":
          return Boolean(String(row.blockers || "").trim());
        case "stale":
          return staleProjectIds.value.has(row.ops_project_id);
        default:
          return true;
      }
    })();

    return hitKeyword && hitProject && hitOwner && hitView;
  });
});

const filteredReleases = computed(() => {
  const filters = releaseFilters.value;
  const keyword = filters.keyword.trim().toLowerCase();

  return opsReleaseRows.value.filter((row) => {
    const hitKeyword =
      keyword === "" ||
      [
        row.version,
        row.title,
        row.project_name,
        row.app_name,
        row.owner,
        row.channel,
        row.release_notes,
        row.linked_ticket_summary,
        row.linked_service_summary,
        row.verification_summary,
        row.customer_sync_note,
        row.release_result,
      ]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(keyword));

    const hitStatus = !filters.status || row.status === filters.status;
    const hitProject =
      !filters.ops_project_id || row.ops_project_id === filters.ops_project_id;
    const hitCustomerSyncStatus =
      !filters.customer_sync_status ||
      row.customer_sync_status === filters.customer_sync_status;
    const hitOwner =
      !filters.owner ||
      String(row.owner).toLowerCase().includes(filters.owner.toLowerCase());
    const hitView = (() => {
      switch (activeReleaseView.value) {
        case "planned":
          return row.status === "planned";
        case "testing":
          return row.status === "testing";
        case "ready":
          return row.status === "ready";
        case "released":
          return row.status === "released";
        case "pending_sync":
          return Boolean(row.needs_customer_sync);
        case "rolled_back":
          return row.status === "rolled_back";
        default:
          return true;
      }
    })();

    return (
      hitKeyword &&
      hitStatus &&
      hitProject &&
      hitCustomerSyncStatus &&
      hitOwner &&
      hitView
    );
  });
});

const filteredRisks = computed(() => {
  const filters = riskFilters.value;
  const keyword = filters.keyword.trim().toLowerCase();

  return opsRiskRows.value.filter((row) => {
    const hitKeyword =
      keyword === "" ||
      [row.title, row.project_name, row.owner, row.impact, row.action_plan]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(keyword));

    const hitStatus = !filters.status || row.status === filters.status;
    const hitLevel = !filters.level || row.level === filters.level;
    const hitProject =
      !filters.ops_project_id || row.ops_project_id === filters.ops_project_id;
    const hitView = (() => {
      switch (activeRiskView.value) {
        case "open":
          return ["open", "tracking"].includes(row.status);
        case "high":
          return row.level === "high";
        case "overdue":
          return Boolean(row.overdue);
        case "issue":
          return row.type === "issue" || row.type === "change";
        default:
          return true;
      }
    })();

    return hitKeyword && hitStatus && hitLevel && hitProject && hitView;
  });
});

const filteredTechTickets = computed(() => {
  const filters = techFilters.value;
  const keyword = filters.keyword.trim().toLowerCase();

  return techTicketRows.value.filter((row) => {
    const hitKeyword =
      keyword === "" ||
      [
        row.title,
        row.app_name,
        row.ops_project_name,
        row.owner,
        row.reporter,
        row.app_module,
        row.release_display,
        row.impact,
        row.solution_plan,
      ]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(keyword));

    const hitProject =
      !filters.ops_project_id || row.ops_project_id === filters.ops_project_id;
    const hitStatus = !filters.status || row.status === filters.status;
    const hitType = !filters.type || row.type === filters.type;
    const hitSeverity = !filters.severity || row.severity === filters.severity;
    const hitOwner =
      !filters.owner ||
      String(row.owner).toLowerCase().includes(filters.owner.toLowerCase());
    const hitView = (() => {
      switch (activeTechView.value) {
        case "bug":
          return (
            row.type === "bug" && !["released", "closed"].includes(row.status)
          );
        case "upgrade":
          return (
            ["feature", "improvement"].includes(row.type) &&
            !["released", "closed"].includes(row.status)
          );
        case "testing":
          return row.status === "testing";
        case "overdue":
          return Boolean(row.overdue);
        default:
          return true;
      }
    })();

    return (
      hitKeyword &&
      hitProject &&
      hitStatus &&
      hitType &&
      hitSeverity &&
      hitOwner &&
      hitView
    );
  });
});

const techProjectStatsMap = computed(() => {
  const map = new Map();

  for (const row of techTicketRows.value) {
    const key = row.ops_project_id || "";

    if (!map.has(key)) {
      map.set(key, {
        openTotal: 0,
        openBugs: 0,
        upgrades: 0,
        testing: 0,
      });
    }

    const current = map.get(key);

    if (!["released", "closed"].includes(row.status)) {
      current.openTotal += 1;
    }

    if (row.type === "bug" && !["released", "closed"].includes(row.status)) {
      current.openBugs += 1;
    }

    if (
      ["feature", "improvement"].includes(row.type) &&
      !["released", "closed"].includes(row.status)
    ) {
      current.upgrades += 1;
    }

    if (row.status === "testing") {
      current.testing += 1;
    }
  }

  return map;
});

const serviceProjectStatsMap = computed(() => {
  const map = new Map();

  for (const row of serviceTicketRows.value) {
    const key = row.ops_project_id || "";

    if (!map.has(key)) {
      map.set(key, {
        openTotal: 0,
        escalated: 0,
        leaderFeedback: 0,
      });
    }

    const current = map.get(key);

    if (!["resolved", "closed"].includes(row.status)) {
      current.openTotal += 1;
    }

    if (row.status === "escalated") {
      current.escalated += 1;
    }

    if (
      !["resolved", "closed"].includes(row.status) &&
      row.source === "leader"
    ) {
      current.leaderFeedback += 1;
    }
  }

  return map;
});

const materialProjectStatsMap = computed(() => {
  const map = new Map();

  for (const row of opsMaterialRows.value) {
    const key = row.ops_project_id || "";

    if (!map.has(key)) {
      map.set(key, {
        total: 0,
        downloadable: 0,
      });
    }

    const current = map.get(key);
    current.total += 1;

    if (row.downloadable) {
      current.downloadable += 1;
    }
  }

  return map;
});

const detailTitle = computed(() => {
  switch (detailMode.value) {
    case "milestone":
      return "里程碑详情";
    case "update":
      return "运营周报详情";
    case "release":
      return "版本发布详情";
    case "risk":
      return "风险问题详情";
    case "tech":
      return "研发待办详情";
    default:
      return "运营项目详情";
  }
});

const detailRecordForDrawer = computed(() => {
  if (!detailRecord.value) {
    return null;
  }

  if (detailMode.value === "update") {
    return {
      ...detailRecord.value,
      title: `${detailRecord.value.project_name || "运营项目"}周报`,
      description: [
        `本周进展\n${detailRecord.value.summary || "--"}`,
        `阶段结果\n${detailRecord.value.result || "--"}`,
        `下周动作\n${detailRecord.value.next_actions || "--"}`,
        `阻塞项\n${detailRecord.value.blockers || "--"}`,
      ].join("\n\n"),
    };
  }

  if (detailMode.value === "risk") {
    return {
      ...detailRecord.value,
      description: [
        `影响说明\n${detailRecord.value.impact || "--"}`,
        `处理动作\n${detailRecord.value.action_plan || "--"}`,
      ].join("\n\n"),
    };
  }

  if (detailMode.value === "release") {
    return {
      ...detailRecord.value,
      description: [
        `验证结论\n${detailRecord.value.verification_summary || "--"}`,
        `客户回告\n${detailRecord.value.customer_sync_note || "--"}`,
        `发布结果\n${detailRecord.value.release_result || "--"}`,
        `发布说明\n${detailRecord.value.release_notes || "--"}`,
        `回滚预案\n${detailRecord.value.rollback_plan || "--"}`,
      ].join("\n\n"),
      notes: detailRecord.value.notes || "--",
    };
  }

  if (detailMode.value === "tech") {
    return {
      ...detailRecord.value,
      description: [
        `影响说明\n${detailRecord.value.impact || "--"}`,
        `处理方案\n${detailRecord.value.solution_plan || "--"}`,
        `版本挂载\n${detailRecord.value.release_display || "--"}`,
        `发版说明\n${detailRecord.value.release_notes || "--"}`,
      ].join("\n\n"),
    };
  }

  if (detailMode.value === "milestone") {
    return {
      ...detailRecord.value,
      description:
        detailRecord.value.notes || detailRecord.value.deliverable || "--",
    };
  }

  return detailRecord.value;
});

const detailFields = computed(() => {
  switch (detailMode.value) {
    case "milestone":
      return [
        { key: "project_name", label: "所属运营项目" },
        { key: "owner", label: "负责人" },
        { key: "due_date", label: "截止日期", type: "date" },
        { key: "deliverable", label: "交付物" },
      ];
    case "update":
      return [
        { key: "project_name", label: "所属运营项目" },
        { key: "owner", label: "汇报人" },
        { key: "report_date", label: "汇报日期", type: "date" },
      ];
    case "release":
      return [
        { key: "project_name", label: "所属 APP 项目" },
        { key: "app_name", label: "APP 名称" },
        { key: "version", label: "版本号" },
        { key: "owner", label: "负责人" },
        { key: "release_date", label: "发布时间", type: "date" },
        { key: "channel", label: "发布渠道" },
        { key: "linked_ticket_summary", label: "关联研发待办" },
      ];
    case "risk":
      return [
        { key: "project_name", label: "所属运营项目" },
        { key: "type_label", label: "类型" },
        { key: "level_label", label: "等级" },
        { key: "owner", label: "负责人" },
        { key: "due_date", label: "应对截止", type: "date" },
      ];
    case "tech":
      return [
        { key: "ops_project_name", label: "关联 APP 项目" },
        { key: "project_name", label: "关联交付项目" },
        { key: "app_name", label: "APP 名称" },
        { key: "app_module", label: "APP 模块" },
        { key: "app_version", label: "目标版本" },
        { key: "release_display", label: "挂载版本" },
        { key: "release_date", label: "发版时间", type: "date" },
        { key: "type_label", label: "类型" },
        { key: "severity_label", label: "严重程度" },
        { key: "owner", label: "负责人" },
        { key: "reporter", label: "提出人" },
        { key: "source_label", label: "来源" },
        { key: "due_date", label: "截止日期", type: "date" },
      ];
    default:
      return [
        { key: "app_name", label: "APP 名称" },
        { key: "app_version", label: "当前版本" },
        { key: "lifecycle_stage_label", label: "生命周期阶段" },
        { key: "business_line", label: "产品线" },
        { key: "manager", label: "运营负责人" },
        { key: "client_owner", label: "产品负责人" },
        { key: "start_date", label: "开始日期", type: "date" },
        { key: "end_date", label: "结束日期", type: "date" },
        { key: "delivery_project_name", label: "关联交付项目" },
        { key: "core_metric", label: "核心指标" },
        { key: "target", label: "生命周期目标" },
        { key: "channel", label: "增长动作/触达渠道" },
      ];
  }
});

const detailMetrics = computed(() => {
  switch (detailMode.value) {
    case "milestone":
      return [{ key: "progress", label: "完成进度", type: "percent" }];
    case "update":
      return [];
    case "release":
      return [{ key: "linked_ticket_count", label: "关联研发项" }];
    case "risk":
      return [];
    case "tech":
      return [
        { key: "estimate_hours", label: "预估工时" },
        { key: "actual_hours", label: "已耗工时" },
      ];
    default:
      return [
        { key: "budget", label: "项目预算", type: "currency" },
        { key: "actual_cost", label: "已花成本", type: "currency" },
        { key: "cost_usage", label: "预算使用", type: "percent" },
        { key: "completion", label: "里程碑完成", type: "percent" },
      ];
  }
});

const releaseDetailFields = computed(() => [
  { key: "project_name", label: "所属 APP 项目" },
  { key: "app_name", label: "APP 名称" },
  { key: "version", label: "版本号" },
  { key: "owner", label: "负责人" },
  { key: "release_date", label: "发布时间", type: "date" },
  { key: "channel", label: "发布渠道" },
  { key: "linked_ticket_summary", label: "关联研发待办" },
  { key: "linked_service_summary", label: "关联问题记录" },
  { key: "customer_sync_status_label", label: "客户回告" },
]);

const releaseDetailMetrics = computed(() => [
  { key: "linked_ticket_count", label: "关联研发项" },
  { key: "linked_service_count", label: "关联问题单" },
]);

const detailStatusLabel = computed(() => {
  if (!detailRecord.value) {
    return "";
  }

  switch (detailMode.value) {
    case "milestone":
      return detailRecord.value.status_label || "";
    case "update":
      return "周报记录";
    case "release":
      return detailRecord.value.status_label || "";
    case "risk":
      return detailRecord.value.status_label || "";
    case "tech":
      return detailRecord.value.status_label || "";
    default:
      return detailRecord.value.status_label || "";
  }
});

const detailStatusTone = computed(() => {
  if (!detailRecord.value) {
    return "info";
  }

  switch (detailMode.value) {
    case "milestone":
      return detailRecord.value.status_tone || "info";
    case "update":
      return "info";
    case "release":
      return detailRecord.value.status_tone || "info";
    case "risk":
      return detailRecord.value.status_tone || "warning";
    case "tech":
      return detailRecord.value.status_tone || "info";
    default:
      return detailRecord.value.status_tone || "info";
  }
});

const detailNotesLabel = computed(() => {
  switch (detailMode.value) {
    case "milestone":
      return "推进说明";
    case "update":
      return "进展内容";
    case "release":
      return "发布备注";
    case "risk":
      return "风险处置";
    case "tech":
      return "补充备注";
    default:
      return "项目说明";
  }
});

const detailEditable = computed(() => {
  return detailMode.value === "tech"
    ? canEditTech.value
    : canEditOperations.value;
});

function setProjectView(key) {
  activeProjectView.value = key;
}

function setMilestoneView(key) {
  activeMilestoneView.value = key;
}

function setUpdateView(key) {
  activeUpdateView.value = key;
}

function setReleaseView(key) {
  activeReleaseView.value = key;
}

function setRiskView(key) {
  activeRiskView.value = key;
}

function setTechView(key) {
  activeTechView.value = key;
}

function resetProjectFilters() {
  projectFilters.value = {
    keyword: "",
    status: "",
    lifecycle_stage: "",
    priority: "",
    manager: "",
  };
  activeProjectView.value = "all";
}

function resetMilestoneFilters() {
  milestoneFilters.value = {
    keyword: "",
    status: "",
    ops_project_id: "",
  };
  activeMilestoneView.value = "all";
}

function resetUpdateFilters() {
  updateFilters.value = {
    keyword: "",
    ops_project_id: "",
    owner: "",
  };
  activeUpdateView.value = "all";
}

function resetReleaseFilters() {
  releaseFilters.value = {
    keyword: "",
    status: "",
    ops_project_id: "",
    customer_sync_status: "",
    owner: "",
  };
  activeReleaseView.value = "all";
}

function resetRiskFilters() {
  riskFilters.value = {
    keyword: "",
    status: "",
    level: "",
    ops_project_id: "",
  };
  activeRiskView.value = "all";
}

function resetTechFilters() {
  techFilters.value = {
    keyword: "",
    ops_project_id: "",
    status: "",
    type: "",
    severity: "",
    owner: "",
  };
  activeTechView.value = "all";
}

function openOpsProjectDialog(record = null) {
  if (!canEditOperations.value) {
    return;
  }

  currentOpsProject.value = record;
  opsProjectDialogVisible.value = true;
}

function openMilestoneDialog(record = null) {
  if (!canEditOperations.value) {
    return;
  }

  currentMilestone.value = record;
  milestoneDialogVisible.value = true;
}

function openUpdateDialog(record = null) {
  if (!canEditOperations.value) {
    return;
  }

  currentUpdate.value = record;
  updateDialogVisible.value = true;
}

function openReleaseDialog(record = null) {
  if (!canEditOperations.value) {
    return;
  }

  currentRelease.value = record;
  releaseDialogVisible.value = true;
}

function openRiskDialog(record = null) {
  if (!canEditOperations.value) {
    return;
  }

  currentRisk.value = record;
  riskDialogVisible.value = true;
}

function openTechDialog(record = null, preset = {}) {
  if (!canEditTech.value) {
    return;
  }

  currentTechTicket.value = record ? { ...record } : { ...preset };
  techDialogVisible.value = true;
}

function openOpsProjectDetail(row) {
  detailMode.value = "ops_project";
  detailRecord.value = row;
  detailDrawerVisible.value = true;
}

function openMilestoneDetail(row) {
  detailMode.value = "milestone";
  detailRecord.value = row;
  detailDrawerVisible.value = true;
}

function openUpdateDetail(row) {
  detailMode.value = "update";
  detailRecord.value = row;
  detailDrawerVisible.value = true;
}

function openReleaseDetail(row) {
  detailMode.value = "release";
  detailRecord.value = row;
  detailDrawerVisible.value = true;
}

function openRiskDetail(row) {
  detailMode.value = "risk";
  detailRecord.value = row;
  detailDrawerVisible.value = true;
}

function openTechDetail(row) {
  detailMode.value = "tech";
  detailRecord.value = row;
  detailDrawerVisible.value = true;
}

function openProjectTech(row) {
  activeTab.value = "tech";
  techFilters.value = {
    ...techFilters.value,
    ops_project_id: row.id,
  };
  activeTechView.value = "all";
}

async function openProjectIssues(row) {
  activeTab.value = "issues";
  issueProjectFocusId.value = "";
  await nextTick();
  issueProjectFocusId.value = row.id;
}

async function openProjectMaterials(row) {
  activeTab.value = "materials";
  materialProjectFocusId.value = "";
  await nextTick();
  materialProjectFocusId.value = row.id;
}

function handleDetailEdit() {
  if (!detailRecord.value || !canEditOperations.value) {
    return;
  }

  switch (detailMode.value) {
    case "milestone":
      openMilestoneDialog(detailRecord.value);
      break;
    case "update":
      openUpdateDialog(detailRecord.value);
      break;
    case "release":
      openReleaseDialog(detailRecord.value);
      break;
    case "risk":
      openRiskDialog(detailRecord.value);
      break;
    case "tech":
      openTechDialog(detailRecord.value);
      break;
    default:
      openOpsProjectDialog(detailRecord.value);
      break;
  }

  detailDrawerVisible.value = false;
}

async function handleOpsProjectAction(row, action) {
  switch (action) {
    case "detail":
      openOpsProjectDetail(row);
      break;
    case "issues":
      openProjectIssues(row);
      break;
    case "materials":
      openProjectMaterials(row);
      break;
    case "tech":
      openProjectTech(row);
      break;
    case "edit":
      openOpsProjectDialog(row);
      break;
    case "delete":
      await removeOpsProject(row);
      break;
    default:
      break;
  }
}

async function handleMilestoneAction(row, action) {
  switch (action) {
    case "detail":
      openMilestoneDetail(row);
      break;
    case "edit":
      openMilestoneDialog(row);
      break;
    case "delete":
      await removeMilestone(row);
      break;
    default:
      break;
  }
}

async function handleUpdateAction(row, action) {
  switch (action) {
    case "detail":
      openUpdateDetail(row);
      break;
    case "edit":
      openUpdateDialog(row);
      break;
    case "delete":
      await removeUpdate(row);
      break;
    default:
      break;
  }
}

async function handleReleaseAction(row, action) {
  switch (action) {
    case "detail":
      openReleaseDetail(row);
      break;
    case "edit":
      openReleaseDialog(row);
      break;
    case "delete":
      await removeRelease(row);
      break;
    default:
      break;
  }
}

async function handleRiskAction(row, action) {
  switch (action) {
    case "detail":
      openRiskDetail(row);
      break;
    case "edit":
      openRiskDialog(row);
      break;
    case "delete":
      await removeRisk(row);
      break;
    default:
      break;
  }
}

async function handleTechAction(row, action) {
  switch (action) {
    case "detail":
      openTechDetail(row);
      break;
    case "edit":
      openTechDialog(row);
      break;
    case "delete":
      await removeTechTicket(row);
      break;
    default:
      break;
  }
}

async function saveOpsProject(payload) {
  if (!canEditOperations.value) {
    return;
  }

  const action = payload.ops_project_id
    ? "update_ops_project"
    : "add_ops_project";
  await store.submitAction(action, payload);
  opsProjectDialogVisible.value = false;
  currentOpsProject.value = null;
}

async function saveMilestone(payload) {
  if (!canEditOperations.value) {
    return;
  }

  const action = payload.ops_milestone_id
    ? "update_ops_milestone"
    : "add_ops_milestone";
  await store.submitAction(action, payload);
  milestoneDialogVisible.value = false;
  currentMilestone.value = null;
}

async function saveUpdate(payload) {
  if (!canEditOperations.value) {
    return;
  }

  const action = payload.ops_update_id ? "update_ops_update" : "add_ops_update";
  await store.submitAction(action, payload);
  updateDialogVisible.value = false;
  currentUpdate.value = null;
}

async function saveRelease(payload) {
  if (!canEditOperations.value) {
    return;
  }

  const action = payload.ops_release_id
    ? "update_ops_release"
    : "add_ops_release";
  await store.submitAction(action, payload);
  releaseDialogVisible.value = false;
  currentRelease.value = null;
}

async function saveRisk(payload) {
  if (!canEditOperations.value) {
    return;
  }

  const action = payload.ops_risk_id ? "update_ops_risk" : "add_ops_risk";
  await store.submitAction(action, payload);
  riskDialogVisible.value = false;
  currentRisk.value = null;
}

async function saveTechTicket(payload) {
  if (!canEditTech.value) {
    return;
  }

  const action = payload.tech_ticket_id
    ? "update_tech_ticket"
    : "add_tech_ticket";
  await store.submitAction(action, payload);
  techDialogVisible.value = false;
  currentTechTicket.value = null;
}

async function removeOpsProject(row) {
  if (!canEditOperations.value) {
    return;
  }

  await ElMessageBox.confirm(
    `确认删除运营项目“${row.name}”吗？`,
    "删除确认",
    {
      type: "warning",
    },
  );

  await store.submitAction("delete_ops_project", {
    ops_project_id: row.id,
  });
}

async function removeMilestone(row) {
  if (!canEditOperations.value) {
    return;
  }

  await ElMessageBox.confirm(
    `确认删除里程碑“${row.title}”吗？`,
    "删除确认",
    {
      type: "warning",
    },
  );

  await store.submitAction("delete_ops_milestone", {
    ops_milestone_id: row.id,
  });
}

async function removeUpdate(row) {
  if (!canEditOperations.value) {
    return;
  }

  await ElMessageBox.confirm(
    `确认删除周报“${row.project_name} ${row.report_date}”吗？`,
    "删除确认",
    {
      type: "warning",
    },
  );

  await store.submitAction("delete_ops_update", {
    ops_update_id: row.id,
  });
}

async function removeRisk(row) {
  if (!canEditOperations.value) {
    return;
  }

  await ElMessageBox.confirm(
    `确认删除风险问题“${row.title}”吗？`,
    "删除确认",
    {
      type: "warning",
    },
  );

  await store.submitAction("delete_ops_risk", {
    ops_risk_id: row.id,
  });
}

async function removeRelease(row) {
  if (!canEditOperations.value) {
    return;
  }

  await ElMessageBox.confirm(
    `确认删除版本发布“${row.version} / ${row.title}”吗？`,
    "删除确认",
    {
      type: "warning",
    },
  );

  await store.submitAction("delete_ops_release", {
    ops_release_id: row.id,
  });
}

async function removeTechTicket(row) {
  if (!canEditTech.value) {
    return;
  }

  await ElMessageBox.confirm(
    `确认删除研发待办“${row.title}”吗？`,
    "删除确认",
    {
      type: "warning",
    },
  );

  await store.submitAction("delete_tech_ticket", {
    tech_ticket_id: row.id,
  });
}

async function changeProjectStatus(row, status) {
  if (!canEditOperations.value) {
    return;
  }

  await store.submitAction(
    "update_ops_project",
    {
      ops_project_id: row.id,
      ...row,
      status,
    },
    { silent: true },
  );
}

async function changeMilestoneStatus(row, status) {
  if (!canEditOperations.value) {
    return;
  }

  await store.submitAction(
    "update_ops_milestone_status",
    {
      ops_milestone_id: row.id,
      status,
    },
    { silent: true },
  );
}

async function changeRiskStatus(row, status) {
  if (!canEditOperations.value) {
    return;
  }

  await store.submitAction(
    "update_ops_risk_status",
    {
      ops_risk_id: row.id,
      status,
    },
    { silent: true },
  );
}

async function changeReleaseStatus(row, status) {
  if (!canEditOperations.value) {
    return;
  }

  await store.submitAction(
    "update_ops_release_status",
    {
      ops_release_id: row.id,
      status,
    },
    { silent: true },
  );
}

async function changeTechStatus(row, status) {
  if (!canEditTech.value) {
    return;
  }

  await store.submitAction(
    "update_tech_ticket_status",
    {
      tech_ticket_id: row.id,
      status,
    },
    { silent: true },
  );
}
</script>

<template>
  <div class="page-grid">
    <PurePageCard
      card-class="operations-workbench"
      title="APP 运营台账"
      description="直接处理问题、发版和 APP 台账。"
    >
      <template #actions>
        <el-tag v-if="!canEditOperations" type="info" effect="plain">
          当前账号为只读
        </el-tag>
      </template>

      <section class="operations-priority-strip">
        <button
          type="button"
          class="operations-priority-card"
          @click="focusOperationsIssues()"
        >
          <span>问题记录</span>
          <strong>{{ serviceTicketRows.length }}</strong>
        </button>
        <button
          type="button"
          class="operations-priority-card"
          @click="focusOperationsTech('all')"
        >
          <span>研发待办</span>
          <strong>{{ summary.open_tech_tickets || 0 }}</strong>
        </button>
        <button
          type="button"
          class="operations-priority-card"
          @click="focusOperationsReleases('planned')"
        >
          <span>待发布版本</span>
          <strong>{{ summary.pending_releases || 0 }}</strong>
        </button>
        <button
          type="button"
          class="operations-priority-card"
          @click="focusOperationsReleases('pending_sync')"
        >
          <span>待客户回告</span>
          <strong>{{ summary.pending_customer_sync || 0 }}</strong>
        </button>
      </section>

        <el-tabs v-model="activeTab" class="ledger-tabs">
          <el-tab-pane label="APP" name="projects">
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
              :title="'\u8fd0\u8425\u9879\u76ee\u7b5b\u9009\u4e0e\u64cd\u4f5c'"
              :count="filteredProjects.length"
            >
              <div class="toolbar toolbar--wide">
                <el-input
                  v-model="projectFilters.keyword"
                  placeholder="搜项目 / 业务线 / 负责人 / 目标"
                  :prefix-icon="Search"
                  clearable
                />
                <el-select
                  v-model="projectFilters.status"
                  clearable
                  placeholder="全部状态"
                >
                  <el-option
                    v-for="item in options.opsProjectStatuses"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                  />
                </el-select>
                <el-select
                  v-model="projectFilters.lifecycle_stage"
                  clearable
                  placeholder="全部生命周期"
                >
                  <el-option
                    v-for="item in options.opsLifecycleStages"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                  />
                </el-select>
                <el-select
                  v-model="projectFilters.priority"
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
                <el-input
                  v-model="projectFilters.manager"
                  placeholder="负责人筛选"
                  clearable
                />
                <el-button :icon="RefreshLeft" @click="resetProjectFilters"
                  >重置</el-button
                >
                <el-button
                  type="primary"
                  :icon="CirclePlus"
                  :disabled="!canEditOperations"
                  @click="openOpsProjectDialog()"
                  >新建运营项目</el-button
                >
              </div>
            </MobileToolbarPanel>

            <div class="table-meta">
              <span>当前结果 {{ filteredProjects.length }} 条</span>
              <span>双击行看详情</span>
            </div>

            <div class="table-shell">
              <el-table
                class="responsive-table responsive-table--ops-projects"
                :data="filteredProjects"
                size="large"
                @row-dblclick="openOpsProjectDetail"
              >
                <el-table-column
                  prop="name"
                  label="项目名称"
                  min-width="180"
                  show-overflow-tooltip
                />
                <el-table-column
                  prop="app_name"
                  label="APP"
                  min-width="140"
                  show-overflow-tooltip
                />
                <el-table-column prop="app_version" label="版本" width="110" />
                <el-table-column
                  prop="lifecycle_stage_label"
                  label="生命周期"
                  width="120"
                />
                <el-table-column
                  prop="manager"
                  label="运营负责人"
                  width="110"
                />
                <el-table-column label="状态" width="160">
                  <template #default="{ row }">
                    <el-select
                      :model-value="row.status"
                      size="small"
                      :disabled="!canEditOperations"
                      @change="changeProjectStatus(row, $event)"
                    >
                      <el-option
                        v-for="item in options.opsProjectStatuses"
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
                        >已花 {{ formatCurrency(row.actual_cost, currency) }} /
                        占用 {{ formatPercent(row.cost_usage) }}</span
                      >
                    </div>
                  </template>
                </el-table-column>
                <el-table-column label="推进情况" min-width="220">
                  <template #default="{ row }">
                    <div class="stack-text">
                      <strong
                        >里程碑 {{ row.milestone_done }}/{{
                          row.milestone_total
                        }}</strong
                      >
                      <span
                        >完成 {{ row.completion }}% / 高风险 {{ row.high_risks }} / 待补周报
                        {{ row.needs_update ? "是" : "否" }}</span
                      >
                    </div>
                  </template>
                </el-table-column>
                <el-table-column label="问题 / 研发 / 资料" min-width="240">
                  <template #default="{ row }">
                    <div class="stack-text">
                      <strong>
                        问题
                        {{ serviceProjectStatsMap.get(row.id)?.openTotal || 0 }}
                        / 研发
                        {{ techProjectStatsMap.get(row.id)?.openTotal || 0 }} /
                        资料
                        {{ materialProjectStatsMap.get(row.id)?.total || 0 }}
                      </strong>
                      <span>
                        领导反馈
                        {{
                          serviceProjectStatsMap.get(row.id)?.leaderFeedback ||
                          0
                        }}
                        / Bug
                        {{ techProjectStatsMap.get(row.id)?.openBugs || 0 }} /
                        可下载 {{
                          materialProjectStatsMap.get(row.id)?.downloadable || 0
                        }}
                      </span>
                    </div>
                  </template>
                </el-table-column>
                <el-table-column
                  label="下个节点"
                  min-width="180"
                  show-overflow-tooltip
                >
                  <template #default="{ row }">
                    <div class="stack-text">
                      <strong>{{
                        row.next_milestone_title || "待补里程碑"
                      }}</strong>
                      <span>{{ row.next_milestone_due_date || "--" }}</span>
                    </div>
                  </template>
                </el-table-column>
                <el-table-column
                  prop="core_metric"
                  label="核心指标"
                  min-width="150"
                  show-overflow-tooltip
                />
                <el-table-column
                  prop="delivery_project_name"
                  label="关联交付项目"
                  min-width="150"
                  show-overflow-tooltip
                />
                <el-table-column label="操作" width="180" fixed="right">
                  <template #default="{ row }">
                    <RowActionMenu
                      :items="[
                        { key: 'detail', label: '\u8be6\u60c5', primary: true },
                        { key: 'issues', label: '\u95ee\u9898\u8bb0\u5f55' },
                        { key: 'materials', label: '\u5185\u90e8\u8d44\u6599' },
                        { key: 'tech', label: 'Bug/\u5347\u7ea7' },
                        {
                          key: 'edit',
                          label: '\u7f16\u8f91',
                          hidden: !canEditOperations,
                        },
                        {
                          key: 'delete',
                          label: '\u5220\u9664',
                          hidden: !canEditOperations,
                          danger: true,
                        },
                      ]"
                      @select="handleOpsProjectAction(row, $event)"
                    />
                  </template>
                </el-table-column>
              </el-table>
            </div>
          </el-tab-pane>

          <el-tab-pane label="问题记录" name="issues">
            <OpsIssuePanel :project-focus-id="issueProjectFocusId" />
          </el-tab-pane>

          <el-tab-pane label="内部资料" name="materials">
            <OpsMaterialPanel :project-focus-id="materialProjectFocusId" />
          </el-tab-pane>

          <el-tab-pane label="里程碑" name="milestones">
            <div class="quick-filter-bar">
              <button
                v-for="item in milestoneQuickViews"
                :key="item.key"
                type="button"
                class="quick-filter-chip"
                :class="{ 'is-active': activeMilestoneView === item.key }"
                @click="setMilestoneView(item.key)"
              >
                {{ item.label }}
              </button>
            </div>

            <MobileToolbarPanel
              :title="'\u91cc\u7a0b\u7891\u7b5b\u9009\u4e0e\u64cd\u4f5c'"
              :count="filteredMilestones.length"
            >
              <div class="toolbar toolbar--wide">
                <el-input
                  v-model="milestoneFilters.keyword"
                  placeholder="搜里程碑 / 负责人 / 项目"
                  :prefix-icon="Search"
                  clearable
                />
                <el-select
                  v-model="milestoneFilters.status"
                  clearable
                  placeholder="全部状态"
                >
                  <el-option
                    v-for="item in options.opsMilestoneStatuses"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                  />
                </el-select>
                <el-select
                  v-model="milestoneFilters.ops_project_id"
                  clearable
                  placeholder="全部项目"
                >
                  <el-option
                    v-for="item in lookups.opsProjects"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                  />
                </el-select>
                <el-button :icon="RefreshLeft" @click="resetMilestoneFilters"
                  >重置</el-button
                >
                <el-button
                  type="primary"
                  :icon="CirclePlus"
                  :disabled="!canEditOperations"
                  @click="openMilestoneDialog()"
                  >新建里程碑</el-button
                >
              </div>
            </MobileToolbarPanel>

            <div class="table-meta">
              <span>当前结果 {{ filteredMilestones.length }} 条</span>
              <span>双击行看详情</span>
            </div>

            <div class="table-shell">
              <el-table
                class="responsive-table responsive-table--ops-milestones"
                :data="filteredMilestones"
                size="large"
                @row-dblclick="openMilestoneDetail"
              >
                <el-table-column
                  prop="title"
                  label="里程碑标题"
                  min-width="180"
                  show-overflow-tooltip
                />
                <el-table-column
                  prop="project_name"
                  label="所属项目"
                  min-width="160"
                  show-overflow-tooltip
                />
                <el-table-column prop="owner" label="负责人" width="100" />
                <el-table-column label="状态" width="160">
                  <template #default="{ row }">
                    <el-select
                      :model-value="row.status"
                      size="small"
                      :disabled="!canEditOperations"
                      @change="changeMilestoneStatus(row, $event)"
                    >
                      <el-option
                        v-for="item in options.opsMilestoneStatuses"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                      />
                    </el-select>
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
                <el-table-column prop="due_date" label="截止日期" width="120" />
                <el-table-column
                  prop="deliverable"
                  label="交付物"
                  min-width="180"
                  show-overflow-tooltip
                />
                <el-table-column label="风险" width="120">
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
                        { key: 'detail', label: '\u8be6\u60c5', primary: true },
                        {
                          key: 'edit',
                          label: '\u7f16\u8f91',
                          hidden: !canEditOperations,
                        },
                        {
                          key: 'delete',
                          label: '\u5220\u9664',
                          hidden: !canEditOperations,
                          danger: true,
                        },
                      ]"
                      @select="handleMilestoneAction(row, $event)"
                    />
                  </template>
                </el-table-column>
              </el-table>
            </div>
          </el-tab-pane>

          <el-tab-pane label="周报" name="updates">
            <div class="quick-filter-bar">
              <button
                v-for="item in updateQuickViews"
                :key="item.key"
                type="button"
                class="quick-filter-chip"
                :class="{ 'is-active': activeUpdateView === item.key }"
                @click="setUpdateView(item.key)"
              >
                {{ item.label }}
              </button>
            </div>

            <MobileToolbarPanel
              :title="'\u5468\u62a5\u7b5b\u9009\u4e0e\u64cd\u4f5c'"
              :count="filteredUpdates.length"
            >
              <div class="toolbar toolbar--wide">
                <el-input
                  v-model="updateFilters.keyword"
                  placeholder="搜索周报 / 结果 / 下周动作 / 阻塞项"
                  :prefix-icon="Search"
                  clearable
                />
                <el-select
                  v-model="updateFilters.ops_project_id"
                  clearable
                    placeholder="全部项目"
                >
                  <el-option
                    v-for="item in lookups.opsProjects"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                  />
                </el-select>
                <el-input
                  v-model="updateFilters.owner"
                  placeholder="汇报人筛选"
                  clearable
                />
                <el-button :icon="RefreshLeft" @click="resetUpdateFilters"
                  >重置</el-button
                >
                <el-button
                  type="primary"
                  :icon="CirclePlus"
                  :disabled="!canEditOperations"
                  @click="openUpdateDialog()"
                  >新建周报</el-button
                >
              </div>
            </MobileToolbarPanel>

            <div class="table-meta">
              <span>当前结果 {{ filteredUpdates.length }} 条</span>
              <span>双击行看详情</span>
            </div>

            <div class="table-shell">
              <el-table
                class="responsive-table responsive-table--ops-updates"
                :data="filteredUpdates"
                size="large"
                @row-dblclick="openUpdateDetail"
              >
                <el-table-column
                  prop="report_date"
                  label="汇报日期"
                  width="120"
                />
                <el-table-column
                  prop="project_name"
                  label="所属项目"
                  min-width="160"
                  show-overflow-tooltip
                />
                <el-table-column prop="owner" label="汇报人" width="100" />
                <el-table-column
                  prop="summary"
                  label="本周进展"
                  min-width="220"
                  show-overflow-tooltip
                />
                <el-table-column
                  prop="result"
                  label="阶段结果"
                  min-width="180"
                  show-overflow-tooltip
                />
                <el-table-column
                  prop="next_actions"
                  label="下周动作"
                  min-width="220"
                  show-overflow-tooltip
                />
                <el-table-column label="阻塞项" width="120">
                  <template #default="{ row }">
                    <el-tag
                      :type="row.blockers ? 'warning' : 'success'"
                      effect="light"
                    >
                      {{ row.blockers ? "有" : "正常" }}
                    </el-tag>
                  </template>
                </el-table-column>
                <el-table-column label="操作" width="180" fixed="right">
                  <template #default="{ row }">
                    <RowActionMenu
                      :items="[
                        { key: 'detail', label: '\u8be6\u60c5', primary: true },
                        {
                          key: 'edit',
                          label: '\u7f16\u8f91',
                          hidden: !canEditOperations,
                        },
                        {
                          key: 'delete',
                          label: '\u5220\u9664',
                          hidden: !canEditOperations,
                          danger: true,
                        },
                      ]"
                      @select="handleUpdateAction(row, $event)"
                    />
                  </template>
                </el-table-column>
              </el-table>
            </div>
          </el-tab-pane>

          <el-tab-pane label="发版" name="releases">
            <div class="quick-filter-bar">
              <button
                v-for="item in releaseQuickViews"
                :key="item.key"
                type="button"
                class="quick-filter-chip"
                :class="{ 'is-active': activeReleaseView === item.key }"
                @click="setReleaseView(item.key)"
              >
                {{ item.label }}
              </button>
            </div>

            <MobileToolbarPanel
              :title="'\u7248\u672c\u7b5b\u9009\u4e0e\u64cd\u4f5c'"
              :count="filteredReleases.length"
            >
              <div class="toolbar toolbar--wide">
                <el-input
                  v-model="releaseFilters.keyword"
                  placeholder="搜索版本号 / 发布标题 / APP / 负责人 / 研发项"
                  :prefix-icon="Search"
                  clearable
                />
                <el-select
                  v-model="releaseFilters.status"
                  clearable
                  placeholder="全部状态"
                >
                  <el-option
                    v-for="item in options.opsReleaseStatuses"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                  />
                </el-select>
                <el-select
                  v-model="releaseFilters.ops_project_id"
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
                  v-model="releaseFilters.owner"
                  placeholder="负责人筛选"
                  clearable
                />
                <el-button :icon="RefreshLeft" @click="resetReleaseFilters"
                  >重置</el-button
                >
                <el-button
                  type="primary"
                  :icon="CirclePlus"
                  :disabled="!canEditOperations"
                  @click="openReleaseDialog()"
                  >登记版本</el-button
                >
              </div>
            </MobileToolbarPanel>

            <div class="table-meta">
              <span>当前结果 {{ filteredReleases.length }} 条</span>
              <span>双击行看详情</span>
            </div>

            <div class="table-shell">
              <el-table
                class="responsive-table responsive-table--ops-releases"
                :data="filteredReleases"
                size="large"
                @row-dblclick="openReleaseDetail"
              >
                <el-table-column label="APP / 版本" min-width="200">
                  <template #default="{ row }">
                    <div class="stack-text">
                      <strong>{{ row.app_name || row.project_name }}</strong>
                      <span>{{ row.version }}</span>
                    </div>
                  </template>
                </el-table-column>
                <el-table-column
                  prop="title"
                  label="发布标题"
                  min-width="220"
                  show-overflow-tooltip
                />
                <el-table-column prop="owner" label="负责人" width="100" />
                <el-table-column
                  prop="release_date"
                  label="发布时间"
                  width="120"
                />
                <el-table-column
                  prop="channel"
                  label="发布渠道"
                  min-width="140"
                  show-overflow-tooltip
                />
                <el-table-column label="状态" width="160">
                  <template #default="{ row }">
                    <el-select
                      :model-value="row.status"
                      size="small"
                      :disabled="!canEditOperations"
                      @change="changeReleaseStatus(row, $event)"
                    >
                      <el-option
                        v-for="item in options.opsReleaseStatuses"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                      />
                    </el-select>
                  </template>
                </el-table-column>
                <el-table-column label="关联研发项" min-width="220">
                  <template #default="{ row }">
                    <div class="stack-text">
                      <strong>{{ row.linked_ticket_count }} 项</strong>
                      <span>{{
                        row.linked_ticket_summary || "未关联研发待办"
                      }}</span>
                    </div>
                  </template>
                </el-table-column>
                <el-table-column label="回滚预案" width="120">
                  <template #default="{ row }">
                    <el-tag
                      :type="row.rollback_ready ? 'success' : 'warning'"
                      effect="light"
                    >
                        {{ row.rollback_ready ? "已准备" : "待补充" }}
                    </el-tag>
                  </template>
                </el-table-column>
                <el-table-column label="操作" width="180" fixed="right">
                  <template #default="{ row }">
                    <RowActionMenu
                      :items="[
                        { key: 'detail', label: '\u8be6\u60c5', primary: true },
                        {
                          key: 'edit',
                          label: '\u7f16\u8f91',
                          hidden: !canEditOperations,
                        },
                        {
                          key: 'delete',
                          label: '\u5220\u9664',
                          hidden: !canEditOperations,
                          danger: true,
                        },
                      ]"
                      @select="handleReleaseAction(row, $event)"
                    />
                  </template>
                </el-table-column>
              </el-table>
            </div>
          </el-tab-pane>

          <el-tab-pane label="风险" name="risks">
            <div class="quick-filter-bar">
              <button
                v-for="item in riskQuickViews"
                :key="item.key"
                type="button"
                class="quick-filter-chip"
                :class="{ 'is-active': activeRiskView === item.key }"
                @click="setRiskView(item.key)"
              >
                {{ item.label }}
              </button>
            </div>

            <MobileToolbarPanel
              :title="'\u98ce\u9669\u7b5b\u9009\u4e0e\u64cd\u4f5c'"
              :count="filteredRisks.length"
            >
              <div class="toolbar toolbar--wide">
                <el-input
                  v-model="riskFilters.keyword"
                  placeholder="搜索问题 / 影响 / 处理动作 / 负责人"
                  :prefix-icon="Search"
                  clearable
                />
                <el-select
                  v-model="riskFilters.status"
                  clearable
                  placeholder="全部状态"
                >
                  <el-option
                    v-for="item in options.opsRiskStatuses"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                  />
                </el-select>
                <el-select
                  v-model="riskFilters.level"
                  clearable
                  placeholder="全部等级"
                >
                  <el-option
                    v-for="item in options.opsRiskLevels"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                  />
                </el-select>
                <el-select
                  v-model="riskFilters.ops_project_id"
                  clearable
                  placeholder="全部项目"
                >
                  <el-option
                    v-for="item in lookups.opsProjects"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                  />
                </el-select>
                <el-button :icon="RefreshLeft" @click="resetRiskFilters"
                  >重置</el-button
                >
                <el-button
                  type="primary"
                  :icon="CirclePlus"
                  :disabled="!canEditOperations"
                  @click="openRiskDialog()"
                  >新建风险问题</el-button
                >
              </div>
            </MobileToolbarPanel>

            <div class="table-meta">
              <span>当前结果 {{ filteredRisks.length }} 条</span>
              <span>双击行看详情</span>
            </div>

            <div class="table-shell">
              <el-table
                class="responsive-table responsive-table--ops-risks"
                :data="filteredRisks"
                size="large"
                @row-dblclick="openRiskDetail"
              >
                <el-table-column
                  prop="title"
                  label="标题"
                  min-width="180"
                  show-overflow-tooltip
                />
                <el-table-column
                  prop="project_name"
                  label="所属项目"
                  min-width="160"
                  show-overflow-tooltip
                />
                <el-table-column prop="type_label" label="类型" width="100" />
                <el-table-column label="等级" width="100">
                  <template #default="{ row }">
                    <el-tag
                      :type="toneToTagType(row.level_tone)"
                      effect="light"
                      >{{ row.level_label }}</el-tag
                    >
                  </template>
                </el-table-column>
                <el-table-column label="状态" width="160">
                  <template #default="{ row }">
                    <el-select
                      :model-value="row.status"
                      size="small"
                      :disabled="!canEditOperations"
                      @change="changeRiskStatus(row, $event)"
                    >
                      <el-option
                        v-for="item in options.opsRiskStatuses"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                      />
                    </el-select>
                  </template>
                </el-table-column>
                <el-table-column prop="owner" label="负责人" width="100" />
                <el-table-column prop="due_date" label="应对截止" width="120" />
                <el-table-column
                  prop="impact"
                  label="影响说明"
                  min-width="200"
                  show-overflow-tooltip
                />
                <el-table-column label="操作" width="180" fixed="right">
                  <template #default="{ row }">
                    <RowActionMenu
                      :items="[
                          { key: 'detail', label: '详情', primary: true },
                        {
                          key: 'edit',
                            label: '编辑',
                          hidden: !canEditOperations,
                        },
                        {
                          key: 'delete',
                            label: '删除',
                          hidden: !canEditOperations,
                          danger: true,
                        },
                      ]"
                      @select="handleRiskAction(row, $event)"
                    />
                  </template>
                </el-table-column>
              </el-table>
            </div>
          </el-tab-pane>

          <el-tab-pane label="研发" name="tech">
            <div class="quick-filter-bar">
              <button
                v-for="item in techQuickViews"
                :key="item.key"
                type="button"
                class="quick-filter-chip"
                :class="{ 'is-active': activeTechView === item.key }"
                @click="setTechView(item.key)"
              >
                {{ item.label }}
              </button>
            </div>

            <MobileToolbarPanel
              :title="'\u7814\u53d1\u8054\u52a8\u7b5b\u9009\u4e0e\u64cd\u4f5c'"
              :count="filteredTechTickets.length"
            >
              <div class="toolbar toolbar--wide">
                <el-input
                  v-model="techFilters.keyword"
                  placeholder="搜 APP / 标题 / 模块 / 提出人 / 影响"
                  :prefix-icon="Search"
                  clearable
                />
                <el-select
                  v-model="techFilters.ops_project_id"
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
                <el-select
                  v-model="techFilters.status"
                  clearable
                  placeholder="全部状态"
                >
                  <el-option
                    v-for="item in options.techTicketStatuses"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                  />
                </el-select>
                <el-select
                  v-model="techFilters.type"
                  clearable
                  placeholder="全部类型"
                >
                  <el-option
                    v-for="item in options.techTicketTypes"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                  />
                </el-select>
                <el-select
                  v-model="techFilters.severity"
                  clearable
                  placeholder="全部严重度"
                >
                  <el-option
                    v-for="item in options.techTicketSeverities"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                  />
                </el-select>
                <el-input
                  v-model="techFilters.owner"
                  placeholder="负责人筛选"
                  clearable
                />
                <el-button :icon="RefreshLeft" @click="resetTechFilters"
                  >重置</el-button
                >
                <el-button
                  type="primary"
                  :icon="CirclePlus"
                  :disabled="!canEditTech"
                  @click="openTechDialog()"
                  >新增 Bug / 升级</el-button
                >
              </div>
            </MobileToolbarPanel>

            <div class="table-meta">
              <span>当前结果 {{ filteredTechTickets.length }} 条</span>
              <span>双击行看详情</span>
            </div>

            <div class="table-shell">
              <el-table
                class="responsive-table responsive-table--ops-tech"
                :data="filteredTechTickets"
                size="large"
                @row-dblclick="openTechDetail"
              >
                <el-table-column label="APP / 项目" min-width="200">
                  <template #default="{ row }">
                    <div class="stack-text">
                      <strong>{{
                        row.app_name || row.ops_project_name
                      }}</strong>
                      <span>{{ row.ops_project_name }}</span>
                    </div>
                  </template>
                </el-table-column>
                <el-table-column
                  prop="title"
                  label="待办标题"
                  min-width="220"
                  show-overflow-tooltip
                />
                <el-table-column
                  prop="app_module"
                  label="模块"
                  min-width="120"
                  show-overflow-tooltip
                />
                <el-table-column label="类型" width="110">
                  <template #default="{ row }">
                    <el-tag effect="light">{{ row.type_label }}</el-tag>
                  </template>
                </el-table-column>
                <el-table-column label="严重度" width="110">
                  <template #default="{ row }">
                    <el-tag
                      :type="toneToTagType(row.severity_tone)"
                      effect="light"
                      >{{ row.severity_label }}</el-tag
                    >
                  </template>
                </el-table-column>
                <el-table-column prop="owner" label="负责人" width="100" />
                <el-table-column prop="reporter" label="提出人" width="100" />
                <el-table-column prop="due_date" label="截止日期" width="120" />
                <el-table-column label="版本挂载" min-width="180">
                  <template #default="{ row }">
                    <div class="stack-text">
                      <strong>{{ row.release_display || "--" }}</strong>
                      <el-tag
                        v-if="row.release_display"
                        size="small"
                        :type="
                          row.release_attention
                            ? 'danger'
                            : toneToTagType(row.release_status_tone)
                        "
                        effect="light"
                      >
                        {{
                          row.release_attention
                            ? "已回滚"
                            : row.release_status_label || "待发版"
                        }}
                      </el-tag>
                      <span v-else>未挂载版本</span>
                    </div>
                  </template>
                </el-table-column>
                <el-table-column label="状态" width="160">
                  <template #default="{ row }">
                    <el-select
                      :model-value="row.status"
                      size="small"
                      :disabled="!canEditTech"
                      @change="changeTechStatus(row, $event)"
                    >
                      <el-option
                        v-for="item in options.techTicketStatuses"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                      />
                    </el-select>
                  </template>
                </el-table-column>
                <el-table-column
                  prop="impact"
                  label="影响说明"
                  min-width="220"
                  show-overflow-tooltip
                />
                <el-table-column label="操作" width="180" fixed="right">
                  <template #default="{ row }">
                    <RowActionMenu
                      :items="[
                          { key: 'detail', label: '详情', primary: true },
                        {
                          key: 'edit',
                            label: '编辑',
                          hidden: !canEditTech,
                        },
                        {
                          key: 'delete',
                            label: '删除',
                          hidden: !canEditTech,
                          danger: true,
                        },
                      ]"
                      @select="handleTechAction(row, $event)"
                    />
                  </template>
                </el-table-column>
              </el-table>
            </div>
          </el-tab-pane>
        </el-tabs>
          </PurePageCard>

    <OpsProjectDialog
      v-model="opsProjectDialogVisible"
      :record="currentOpsProject"
      :statuses="options.opsProjectStatuses"
      :lifecycle-stages="options.opsLifecycleStages"
      :priorities="options.priorities"
      :delivery-projects="lookups.projects"
      :loading="store.state.submitting"
      @submit="saveOpsProject"
    />

    <OpsMilestoneDialog
      v-model="milestoneDialogVisible"
      :record="currentMilestone"
      :ops-projects="lookups.opsProjects"
      :statuses="options.opsMilestoneStatuses"
      :loading="store.state.submitting"
      @submit="saveMilestone"
    />

    <OpsUpdateDialog
      v-model="updateDialogVisible"
      :record="currentUpdate"
      :ops-projects="lookups.opsProjects"
      :loading="store.state.submitting"
      @submit="saveUpdate"
    />

    <OpsReleaseDialog
      v-model="releaseDialogVisible"
      :record="currentRelease"
      :ops-projects="lookups.opsProjects"
      :tech-tickets="lookups.techTickets"
      :service-tickets="lookups.serviceTickets"
      :statuses="options.opsReleaseStatuses"
      :sync-statuses="options.opsReleaseCustomerSyncStatuses"
      :loading="store.state.submitting"
      @submit="saveRelease"
    />

    <OpsRiskDialog
      v-model="riskDialogVisible"
      :record="currentRisk"
      :ops-projects="lookups.opsProjects"
      :risk-types="options.opsRiskTypes"
      :risk-levels="options.opsRiskLevels"
      :risk-statuses="options.opsRiskStatuses"
      :loading="store.state.submitting"
      @submit="saveRisk"
    />

    <TechTicketDialog
      v-model="techDialogVisible"
      :record="currentTechTicket"
      :ops-projects="lookups.opsProjects"
      :delivery-projects="lookups.projects"
      :owners="lookups.techOwners"
      :ticket-types="options.techTicketTypes"
      :ticket-statuses="options.techTicketStatuses"
      :severities="options.techTicketSeverities"
      :priorities="options.priorities"
      :sources="options.techTicketSources"
      :loading="store.state.submitting"
      @submit="saveTechTicket"
    />

    <RecordDetailDrawer
      v-model="detailDrawerVisible"
      :title="detailTitle"
      :record="detailRecordForDrawer"
      :currency="currency"
      :fields="detailMode === 'release' ? releaseDetailFields : detailFields"
      :metrics="detailMode === 'release' ? releaseDetailMetrics : detailMetrics"
      :status-label="detailStatusLabel"
      :status-tone="detailStatusTone"
      :notes-label="detailNotesLabel"
      :show-attachments="false"
      :editable="detailEditable"
      @edit="handleDetailEdit"
    />
  </div>
</template>
