<script setup>
import { computed, ref, watch } from "vue";
import { CirclePlus, RefreshLeft, Search } from "@element-plus/icons-vue";
import { ElMessageBox } from "element-plus";
import MobileToolbarPanel from "./MobileToolbarPanel.vue";
import OpsMaterialDialog from "./OpsMaterialDialog.vue";
import RecordDetailDrawer from "./RecordDetailDrawer.vue";
import RowActionMenu from "./RowActionMenu.vue";
import StatCard from "./StatCard.vue";
import { usePersistedState } from "../composables/usePersistedState";
import { useAppStore } from "../stores/useAppStore";
import { formatFileSize, toneToTagType } from "../utils/formatters";

const props = defineProps({
  projectFocusId: {
    type: String,
    default: "",
  },
});

const store = useAppStore();

const filters = usePersistedState("console.operations.material.filters", {
  keyword: "",
  category: "",
  owner: "",
  ops_project_id: "",
  archive_status: "",
});
const activeView = usePersistedState("console.operations.material.view", "all");

const dialogVisible = ref(false);
const detailDrawerVisible = ref(false);
const currentRecord = ref(null);
const detailRecord = ref(null);

const bootstrap = computed(() => store.state.bootstrap);
const currency = computed(() => bootstrap.value.meta?.currency || "CNY");
const rows = computed(() => bootstrap.value.opsMaterialRows || []);
const options = computed(() => bootstrap.value.options || {});
const lookups = computed(() => bootstrap.value.lookups || {});
const canEditMaterials = computed(() => store.hasPermission("operations.edit"));

const quickViews = [
  { key: "all", label: "全部资料" },
  { key: "active", label: "在用资料" },
  { key: "expiring", label: "即将失效" },
  { key: "expired", label: "已失效" },
  { key: "archived", label: "已归档" },
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

const filteredRows = computed(() => {
  const keyword = filters.value.keyword.trim().toLowerCase();

  return rows.value.filter((row) => {
    const hitKeyword =
      keyword === "" ||
      [
        row.title,
        row.category_label,
        row.owner,
        row.project_name,
        row.app_name,
        row.download_name,
        row.version_tag,
        row.applicable_versions,
        row.replacement_display,
        row.notes,
      ]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(keyword));

    const hitCategory =
      !filters.value.category || row.category === filters.value.category;
    const hitOwner =
      !filters.value.owner ||
      String(row.owner)
        .toLowerCase()
        .includes(filters.value.owner.toLowerCase());
    const hitProject =
      !filters.value.ops_project_id ||
      row.ops_project_id === filters.value.ops_project_id;
    const hitArchiveStatus =
      !filters.value.archive_status ||
      row.archive_status === filters.value.archive_status;

    const hitView = (() => {
      switch (activeView.value) {
        case "active":
          return !row.is_archived && !row.expired;
        case "expiring":
          return Boolean(row.expires_soon);
        case "expired":
          return Boolean(row.expired);
        case "archived":
          return Boolean(row.is_archived);
        default:
          return true;
      }
    })();

    return (
      hitKeyword &&
      hitCategory &&
      hitOwner &&
      hitProject &&
      hitArchiveStatus &&
      hitView
    );
  });
});

const recentRows = computed(() => rows.value.slice(0, 5));
const uploadedTotal = computed(
  () => rows.value.filter((row) => row.is_uploaded).length,
);
const expiredTotal = computed(
  () => rows.value.filter((row) => row.expired).length,
);
const replacementTotal = computed(
  () => rows.value.filter((row) => row.has_replacement).length,
);

const detailFields = [
  { key: "project_name", label: "所属 APP 项目" },
  { key: "app_name", label: "APP 名称" },
  { key: "app_version", label: "当前 APP 版本" },
  { key: "category_label", label: "资料分类" },
  { key: "owner", label: "负责人" },
  { key: "version_tag", label: "资料版本" },
  { key: "applicable_versions", label: "适用版本" },
  { key: "archive_status_label", label: "归档状态" },
  { key: "expires_on", label: "失效时间", type: "date" },
  { key: "replacement_display", label: "替代资料" },
  { key: "storage_label", label: "资料来源" },
  { key: "download_name", label: "下载文件名" },
  { key: "file_size", label: "文件大小", type: "filesize" },
  { key: "download_url", label: "下载地址", type: "link" },
  { key: "updated_on", label: "更新日期", type: "date" },
];

const detailMetrics = computed(() => {
  if (!detailRecord.value) {
    return [];
  }

  return [
    { key: "storage_label", label: "资料来源" },
    { key: "archive_status_label", label: "归档状态" },
    { key: "expires_on", label: "失效时间", type: "date" },
    { key: "replacement_display", label: "替代资料" },
  ];
});

const detailPreview = computed(() => {
  if (!detailRecord.value?.previewable) {
    return null;
  }

  return {
    url: detailRecord.value.preview_url,
    type: detailRecord.value.preview_type,
    title: detailRecord.value.title || "资料预览",
    alt:
      detailRecord.value.download_name ||
      detailRecord.value.title ||
      "资料预览",
  };
});

const detailStatusLabel = computed(() => {
  if (!detailRecord.value) {
    return "";
  }

  if (detailRecord.value.is_archived) {
    return "已归档";
  }

  if (detailRecord.value.expired) {
    return "已失效";
  }

  if (detailRecord.value.expires_soon) {
    return "即将失效";
  }

  return detailRecord.value.archive_status_label || "";
});

const detailStatusTone = computed(() => {
  if (!detailRecord.value) {
    return "info";
  }

  if (detailRecord.value.is_archived) {
    return "neutral";
  }

  if (detailRecord.value.expired) {
    return "danger";
  }

  if (detailRecord.value.expires_soon) {
    return "warning";
  }

  return detailRecord.value.archive_status_tone || "success";
});

function setView(key) {
  activeView.value = key;
}

function resetFilters() {
  filters.value = {
    keyword: "",
    category: "",
    owner: "",
    ops_project_id: "",
    archive_status: "",
  };
  activeView.value = "all";
}

function openDialog(record = null) {
  if (!canEditMaterials.value) {
    return;
  }

  currentRecord.value = record;
  dialogVisible.value = true;
}

function openDetail(record) {
  detailRecord.value = record;
  detailDrawerVisible.value = true;
}

async function handleMaterialAction(row, action) {
  switch (action) {
    case "preview":
      openDetail(row);
      break;
    case "download":
      if (row.download_url) {
        window.open(row.download_url, "_blank", "noopener");
      }
      break;
    case "detail":
      openDetail(row);
      break;
    case "edit":
      openDialog(row);
      break;
    case "delete":
      await removeMaterial(row);
      break;
    default:
      break;
  }
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

async function saveMaterial(payload) {
  if (!canEditMaterials.value) {
    return;
  }

  const action = payload.ops_material_id
    ? "update_ops_material"
    : "add_ops_material";

  await store.submitAction(action, payload, {
    multipart: Boolean(payload.material_file),
  });

  dialogVisible.value = false;
  syncDetailRecord(payload.ops_material_id);
  currentRecord.value = null;
}

async function removeMaterial(row) {
  if (!canEditMaterials.value) {
    return;
  }

  await ElMessageBox.confirm(`确认删除资料“${row.title}”吗？`, "删除确认", {
    type: "warning",
  });

  await store.submitAction("delete_ops_material", {
    ops_material_id: row.id,
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
        title="内部资料总数"
        :value="String(rows.length)"
        hint="挂在 APP 项目下的手册、FAQ、发布说明和培训资料。"
      />
      <StatCard
        title="已上传文件"
        :value="String(uploadedTotal)"
        hint="已经纳入系统上传目录统一管理的资料。"
        tone="success"
      />
      <StatCard
        title="已失效资料"
        :value="String(expiredTotal)"
        hint="已经超过失效时间，需要替换或下线的资料。"
        tone="danger"
      />
      <StatCard
        title="已设替代"
        :value="String(replacementTotal)"
        hint="已经明确指定替代资料的旧版本口径。"
        tone="warning"
      />
    </section>

    <section class="dashboard-double">
      <article class="panel-card">
        <header class="panel-card__header">
          <div>
            <h3>最近更新</h3>
            <p>优先看近期有变动的资料，避免客服和运营继续使用旧口径。</p>
          </div>
        </header>
        <div class="panel-card__body">
          <div class="todo-list">
            <button
              v-for="row in recentRows"
              :key="row.id"
              type="button"
              class="todo-item todo-item--action"
              @click="openDetail(row)"
            >
              <div class="todo-item__header">
                <strong>{{ row.title }}</strong>
                <span>{{ row.updated_on }}</span>
              </div>
              <p>{{ row.project_name }}</p>
              <span>{{
                row.replacement_display ||
                row.version_tag ||
                row.applicable_versions ||
                row.archive_status_label
              }}</span>
            </button>
            <el-empty
              v-if="!recentRows.length"
              description="当前还没有内部资料"
            />
          </div>
        </div>
      </article>

      <article class="panel-card">
        <header class="panel-card__header">
          <div>
            <h3>资料维护建议</h3>
            <p>
              把失效时间、归档状态和替代关系维护清楚，前线就不会继续用旧文档。
            </p>
          </div>
        </header>
        <div class="panel-card__body">
          <ol class="simple-list">
            <li>超过有效期的资料要么更新，要么直接归档，并补上替代资料。</li>
            <li>FAQ、发布说明和培训脚本尽量写清资料版本和适用版本。</li>
            <li>可预览的图片和 PDF，优先在线确认内容是否仍然有效。</li>
          </ol>
        </div>
      </article>
    </section>

    <section class="panel-card">
      <header class="panel-card__header">
        <div>
            <h3>内部资料中心</h3>
            <p>把 APP 项目的 FAQ、培训脚本、发布说明和话术统一维护在这里。</p>
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

        <MobileToolbarPanel title="资料筛选与操作" :count="filteredRows.length">
          <div class="toolbar toolbar--wide">
            <el-input
              v-model="filters.keyword"
              placeholder="搜资料标题 / APP / 文件名 / 版本 / 替代资料 / 负责人"
              :prefix-icon="Search"
              clearable
            />
            <el-select
              v-model="filters.category"
              clearable
                placeholder="全部分类"
            >
              <el-option
                v-for="item in options.opsMaterialCategories"
                :key="item.value"
                :label="item.label"
                :value="item.value"
              />
            </el-select>
            <el-select
              v-model="filters.archive_status"
              clearable
                placeholder="全部归档状态"
            >
              <el-option
                v-for="item in options.opsMaterialArchiveStatuses"
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
              v-model="filters.owner"
              placeholder="负责人筛选"
              clearable
            />
            <el-button :icon="RefreshLeft" @click="resetFilters"
              >重置</el-button
            >
            <el-button
              type="primary"
              :icon="CirclePlus"
              :disabled="!canEditMaterials"
              @click="openDialog()"
              >新增资料</el-button
            >
          </div>
        </MobileToolbarPanel>

        <div class="table-meta">
          <span>当前结果 {{ filteredRows.length }} 条</span>
          <span>双击表格可直接查看详情、在线预览、失效时间和替代关系。</span>
        </div>

        <div class="table-shell">
          <el-table
            class="responsive-table responsive-table--ops-materials"
            :data="filteredRows"
            size="large"
            @row-dblclick="openDetail"
          >
            <el-table-column
              prop="title"
              label="资料标题"
              min-width="220"
              show-overflow-tooltip
            />
            <el-table-column label="APP / 项目" min-width="220">
              <template #default="{ row }">
                <div class="stack-text">
                  <strong>{{ row.app_name || row.project_name }}</strong>
                  <span>{{ row.project_name }}</span>
                </div>
              </template>
            </el-table-column>
            <el-table-column prop="category_label" label="分类" width="120" />
            <el-table-column label="版本信息" min-width="180">
              <template #default="{ row }">
                <div class="stack-text">
                  <strong>{{ row.version_tag || "--" }}</strong>
                  <span>{{ row.applicable_versions || "未填写适用版本" }}</span>
                </div>
              </template>
            </el-table-column>
            <el-table-column label="生命周期" min-width="180">
              <template #default="{ row }">
                <div class="stack-text">
                  <el-space wrap>
                    <el-tag
                      :type="toneToTagType(row.archive_status_tone)"
                      effect="light"
                    >
                      {{ row.archive_status_label }}
                    </el-tag>
                    <el-tag v-if="row.expired" type="danger" effect="light">
                      已失效
                    </el-tag>
                    <el-tag
                      v-else-if="row.expires_soon"
                      type="warning"
                      effect="light"
                    >
                      30 天内失效
                    </el-tag>
                  </el-space>
                  <span>{{ row.expires_on || "未设置失效时间" }}</span>
                </div>
              </template>
            </el-table-column>
            <el-table-column label="替代关系" min-width="200">
              <template #default="{ row }">
                <div class="stack-text">
                  <strong>{{ row.replacement_display || "--" }}</strong>
                  <span>{{
                    row.has_replacement ? "已配置替代资料" : "暂无替代资料"
                  }}</span>
                </div>
              </template>
            </el-table-column>
            <el-table-column label="来源" width="150">
              <template #default="{ row }">
                <div class="stack-text">
                  <el-tag
                    :type="row.is_uploaded ? 'success' : 'info'"
                    effect="light"
                  >
                    {{ row.storage_label }}
                  </el-tag>
                  <span>{{
                    row.is_uploaded ? formatFileSize(row.file_size) : "外部链接"
                  }}</span>
                </div>
              </template>
            </el-table-column>
            <el-table-column prop="updated_on" label="更新日期" width="120" />
            <el-table-column label="操作" width="180" fixed="right">
              <template #default="{ row }">
                <RowActionMenu
                  :items="[
                    {
                      key: 'preview',
                      label: '预览',
                      primary: row.previewable,
                      hidden: !row.previewable,
                    },
                    { key: 'detail', label: '详情', primary: !row.previewable },
                    {
                      key: 'download',
                      label: '下载',
                      hidden: !row.download_url,
                    },
                    { key: 'edit', label: '编辑', hidden: !canEditMaterials },
                    {
                      key: 'delete',
                      label: '删除',
                      hidden: !canEditMaterials,
                      danger: true,
                    },
                  ]"
                  @select="handleMaterialAction(row, $event)"
                />
              </template>
            </el-table-column>
          </el-table>
        </div>
      </div>
    </section>

    <OpsMaterialDialog
      v-model="dialogVisible"
      :record="currentRecord"
      :ops-projects="lookups.opsProjects"
      :materials="lookups.opsMaterials"
      :categories="options.opsMaterialCategories"
      :archive-statuses="options.opsMaterialArchiveStatuses"
      :owners="lookups.serviceAssignees"
      :loading="store.state.submitting"
      @submit="saveMaterial"
    />

    <RecordDetailDrawer
      v-model="detailDrawerVisible"
      title="内部资料详情"
      :record="detailRecord"
      :currency="currency"
      :fields="detailFields"
      :metrics="detailMetrics"
      :preview="detailPreview"
      preview-title="资料预览"
      :status-label="detailStatusLabel"
      :status-tone="detailStatusTone"
      notes-label="资料说明"
      :show-attachments="false"
      :editable="canEditMaterials"
      @edit="openDialog"
    />
  </div>
</template>
