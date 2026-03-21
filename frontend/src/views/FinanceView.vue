<script setup>
import { computed, ref } from "vue";
import {
  CirclePlus,
  FolderAdd,
  RefreshLeft,
  Search,
  Tickets,
} from "@element-plus/icons-vue";
import { ElMessageBox } from "element-plus";
import AttachmentDialog from "../components/AttachmentDialog.vue";
import InvoiceDialog from "../components/InvoiceDialog.vue";
import MobileToolbarPanel from "../components/MobileToolbarPanel.vue";
import PurePageCard from "../components/PurePageCard.vue";
import RecordDetailDrawer from "../components/RecordDetailDrawer.vue";
import RowActionMenu from "../components/RowActionMenu.vue";
import TransactionDialog from "../components/TransactionDialog.vue";
import { usePersistedState } from "../composables/usePersistedState";
import { useAppStore } from "../stores/useAppStore";
import { formatCurrency } from "../utils/formatters";

const store = useAppStore();

const activeTab = usePersistedState("console.finance.tab", "transactions");
const transactionFilters = usePersistedState(
  "console.finance.transaction.filters",
  {
    keyword: "",
    type: "",
    project_id: "",
    attachment: "all",
  },
);
const invoiceFilters = usePersistedState("console.finance.invoice.filters", {
  keyword: "",
  kind: "",
  status: "",
  attachment: "all",
});
const activeTransactionView = usePersistedState(
  "console.finance.transaction.view",
  "all",
);
const activeInvoiceView = usePersistedState(
  "console.finance.invoice.view",
  "all",
);

const transactionDialogVisible = ref(false);
const invoiceDialogVisible = ref(false);
const attachmentDialogVisible = ref(false);
const detailDrawerVisible = ref(false);
const currentTransaction = ref(null);
const currentInvoice = ref(null);
const attachmentTarget = ref(null);
const detailMode = ref("transaction");
const detailRecord = ref(null);

const bootstrap = computed(() => store.state.bootstrap);
const currency = computed(() => bootstrap.value.meta.currency || "CNY");
const dashboard = computed(() => bootstrap.value.dashboard || {});
const transactions = computed(() => bootstrap.value.transactionRows || []);
const invoices = computed(() => bootstrap.value.invoiceRows || []);
const lookups = computed(() => bootstrap.value.lookups || {});
const options = computed(() => bootstrap.value.options || {});
const canEditFinance = computed(() => store.hasPermission("finance.edit"));

const transactionQuickViews = [
  { key: "all", label: "全部流水" },
  { key: "expense", label: "只看支出" },
  { key: "income", label: "只看收入" },
  { key: "missing_attachments", label: "待补附件" },
  { key: "linked_projects", label: "项目流水" },
];

const invoiceQuickViews = [
  { key: "all", label: "全部单据" },
  { key: "receivable", label: "待回款" },
  { key: "payable", label: "待付款" },
  { key: "overdue", label: "临近/逾期" },
  { key: "missing_attachments", label: "待补附件" },
];

const filteredTransactions = computed(() => {
  const filters = transactionFilters.value;
  const keyword = filters.keyword.trim().toLowerCase();

  return transactions.value.filter((row) => {
    const hitKeyword =
      keyword === "" ||
      [row.counterparty, row.category, row.project_name, row.notes]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(keyword));

    const hitType = !filters.type || row.type === filters.type;
    const hitProject =
      !filters.project_id || row.project_id === filters.project_id;
    const hitAttachment =
      filters.attachment === "all" ||
      (filters.attachment === "with"
        ? row.attachment_count > 0
        : row.attachment_count === 0);

    const hitView = (() => {
      switch (activeTransactionView.value) {
        case "expense":
          return row.type === "expense";
        case "income":
          return row.type === "income";
        case "missing_attachments":
          return row.attachment_count === 0;
        case "linked_projects":
          return Boolean(row.project_id);
        default:
          return true;
      }
    })();

    return hitKeyword && hitType && hitProject && hitAttachment && hitView;
  });
});

const filteredInvoices = computed(() => {
  const filters = invoiceFilters.value;
  const keyword = filters.keyword.trim().toLowerCase();

  return invoices.value.filter((row) => {
    const hitKeyword =
      keyword === "" ||
      [row.title, row.counterparty, row.project_name, row.notes]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(keyword));

    const hitKind = !filters.kind || row.kind === filters.kind;
    const hitStatus = !filters.status || row.status === filters.status;
    const hitAttachment =
      filters.attachment === "all" ||
      (filters.attachment === "with"
        ? row.attachment_count > 0
        : row.attachment_count === 0);

    const hitView = (() => {
      switch (activeInvoiceView.value) {
        case "receivable":
          return row.kind === "receivable" && row.status !== "paid";
        case "payable":
          return row.kind === "payable" && row.status !== "paid";
        case "overdue":
          return row.overdue || row.status !== "paid";
        case "missing_attachments":
          return row.attachment_count === 0;
        default:
          return true;
      }
    })();

    return hitKeyword && hitKind && hitStatus && hitAttachment && hitView;
  });
});

const financeTodoCount = computed(() => {
  return (bootstrap.value.dueInvoiceRows || []).length;
});

const attachmentBacklogCount = computed(() => {
  const transactionCount = transactions.value.filter(
    (row) => row.attachment_count === 0,
  ).length;
  const invoiceCount = invoices.value.filter(
    (row) => row.attachment_count === 0,
  ).length;

  return transactionCount + invoiceCount;
});

const detailTitle = computed(() => {
  if (!detailRecord.value) {
    return "详情";
  }

  return detailMode.value === "transaction" ? "流水详情" : "单据详情";
});

const detailFields = computed(() => {
  if (detailMode.value === "transaction") {
    return [
      { key: "date", label: "业务日期", type: "date" },
      { key: "type_label", label: "收支方向" },
      { key: "category", label: "科目分类" },
      { key: "counterparty", label: "往来方" },
      { key: "payment_method_label", label: "支付方式" },
      { key: "project_name", label: "关联项目" },
    ];
  }

  return [
    { key: "kind_label", label: "单据类型" },
    { key: "counterparty", label: "往来方" },
    { key: "project_name", label: "关联项目" },
    { key: "due_date", label: "到期日期", type: "date" },
    { key: "status_label", label: "当前状态" },
  ];
});

const detailMetrics = computed(() => {
  if (detailMode.value === "transaction") {
    return [
      { key: "amount", label: "金额", type: "currency" },
      { key: "attachment_count", label: "附件数" },
    ];
  }

  return [
    { key: "amount", label: "单据金额", type: "currency" },
    { key: "attachment_count", label: "附件数" },
  ];
});

const detailStatusLabel = computed(() => {
  if (!detailRecord.value) {
    return "";
  }

  return detailMode.value === "transaction"
    ? detailRecord.value.type_label
    : detailRecord.value.status_label;
});

const detailStatusTone = computed(() => {
  if (!detailRecord.value) {
    return "info";
  }

  return detailMode.value === "transaction"
    ? detailRecord.value.type === "income"
      ? "success"
      : "danger"
    : detailRecord.value.status_tone;
});

function setTransactionView(key) {
  activeTransactionView.value = key;
}

function setInvoiceView(key) {
  activeInvoiceView.value = key;
}

function focusTransactions(view = "all") {
  activeTab.value = "transactions";
  activeTransactionView.value = view;
  transactionFilters.value = {
    keyword: "",
    type: "",
    project_id: "",
    attachment: view === "missing_attachments" ? "without" : "all",
  };
}

function focusInvoices(view = "all") {
  activeTab.value = "invoices";
  activeInvoiceView.value = view;
  invoiceFilters.value = {
    keyword: "",
    kind:
      view === "receivable"
        ? "receivable"
        : view === "payable"
          ? "payable"
          : "",
    status: "",
    attachment: view === "missing_attachments" ? "without" : "all",
  };
}

function resetTransactionFilters() {
  transactionFilters.value = {
    keyword: "",
    type: "",
    project_id: "",
    attachment: "all",
  };
  activeTransactionView.value = "all";
}

function resetInvoiceFilters() {
  invoiceFilters.value = {
    keyword: "",
    kind: "",
    status: "",
    attachment: "all",
  };
  activeInvoiceView.value = "all";
}

function openTransactionDialog(record = null) {
  if (!canEditFinance.value) {
    return;
  }

  currentTransaction.value = record;
  transactionDialogVisible.value = true;
}

function openInvoiceDialog(record = null) {
  if (!canEditFinance.value) {
    return;
  }

  currentInvoice.value = record;
  invoiceDialogVisible.value = true;
}

function openAttachmentDialog(kind, record) {
  if (!canEditFinance.value) {
    return;
  }

  attachmentTarget.value = { kind, record };
  attachmentDialogVisible.value = true;
}

function openTransactionDetail(row) {
  detailMode.value = "transaction";
  detailRecord.value = row;
  detailDrawerVisible.value = true;
}

function openInvoiceDetail(row) {
  detailMode.value = "invoice";
  detailRecord.value = row;
  detailDrawerVisible.value = true;
}

function handleDetailEdit() {
  if (!detailRecord.value || !canEditFinance.value) {
    return;
  }

  if (detailMode.value === "transaction") {
    openTransactionDialog(detailRecord.value);
  } else {
    openInvoiceDialog(detailRecord.value);
  }

  detailDrawerVisible.value = false;
}

function handleDetailAttachments() {
  if (!detailRecord.value || !canEditFinance.value) {
    return;
  }

  openAttachmentDialog(detailMode.value, detailRecord.value);
}

async function handleTransactionAction(row, action) {
  switch (action) {
    case "detail":
      openTransactionDetail(row);
      break;
    case "edit":
      openTransactionDialog(row);
      break;
    case "attachments":
      openAttachmentDialog("transaction", row);
      break;
    case "delete":
      await removeTransaction(row);
      break;
    default:
      break;
  }
}

async function handleInvoiceAction(row, action) {
  switch (action) {
    case "detail":
      openInvoiceDetail(row);
      break;
    case "edit":
      openInvoiceDialog(row);
      break;
    case "attachments":
      openAttachmentDialog("invoice", row);
      break;
    case "delete":
      await removeInvoice(row);
      break;
    default:
      break;
  }
}

async function saveTransaction(payload) {
  if (!canEditFinance.value) {
    return;
  }

  const action = payload.transaction_id
    ? "update_transaction"
    : "add_transaction";
  await store.submitAction(action, payload, { multipart: true });
  transactionDialogVisible.value = false;
  currentTransaction.value = null;
}

async function saveInvoice(payload) {
  if (!canEditFinance.value) {
    return;
  }

  const action = payload.invoice_id ? "update_invoice" : "add_invoice";
  await store.submitAction(action, payload, { multipart: true });
  invoiceDialogVisible.value = false;
  currentInvoice.value = null;
}

async function saveAttachment(payload) {
  if (!attachmentTarget.value || !canEditFinance.value) {
    return;
  }

  const action =
    attachmentTarget.value.kind === "transaction"
      ? "append_transaction_attachments"
      : "append_invoice_attachments";

  const key =
    attachmentTarget.value.kind === "transaction"
      ? "transaction_id"
      : "invoice_id";

  await store.submitAction(
    action,
    {
      [key]: attachmentTarget.value.record.id,
      ...payload,
    },
    { multipart: true },
  );

  attachmentDialogVisible.value = false;
  attachmentTarget.value = null;
}

async function removeTransaction(row) {
  if (!canEditFinance.value) {
    return;
  }

  await ElMessageBox.confirm(
    `确认删除流水“${row.counterparty}”吗？`,
    "删除确认",
    {
      type: "warning",
    },
  );

  await store.submitAction("delete_transaction", {
    transaction_id: row.id,
  });
}

async function removeInvoice(row) {
  if (!canEditFinance.value) {
    return;
  }

  await ElMessageBox.confirm(
    `确认删除单据“${row.title}”吗？`,
    "删除确认",
    {
      type: "warning",
    },
  );

  await store.submitAction("delete_invoice", {
    invoice_id: row.id,
  });
}

async function changeInvoiceStatus(row, status) {
  if (!canEditFinance.value) {
    return;
  }

  await store.submitAction(
    "update_invoice_status",
    {
      invoice_id: row.id,
      kind: row.kind,
      status,
    },
    { silent: true },
  );
}
</script>

<template>
  <div class="page-grid">
    <PurePageCard
      card-class="finance-ledger-panel"
      title="财务台账"
      description="直接记账、补附件、看回款付款。"
    >
      <template #actions>
        <template v-if="canEditFinance">
          <el-button type="primary" @click="openTransactionDialog()">
            <el-icon><CirclePlus /></el-icon>
            记一笔流水
          </el-button>
          <el-button
            @click="openInvoiceDialog({ kind: 'receivable', status: 'pending' })"
          >
            <el-icon><FolderAdd /></el-icon>
            新增应收
          </el-button>
          <el-button
            @click="openInvoiceDialog({ kind: 'payable', status: 'pending' })"
          >
            <el-icon><Tickets /></el-icon>
            新增应付
          </el-button>
        </template>
        <el-tag v-else type="info" effect="plain">当前账号为只读</el-tag>
      </template>

      <section class="finance-priority-strip">
        <button
          type="button"
          class="finance-priority-card"
          @click="focusInvoices('receivable')"
        >
          <span>待回款</span>
          <strong>{{ formatCurrency(dashboard.open_receivables, currency) }}</strong>
        </button>
        <button
          type="button"
          class="finance-priority-card"
          @click="focusInvoices('payable')"
        >
          <span>待付款</span>
          <strong>{{ formatCurrency(dashboard.open_payables, currency) }}</strong>
        </button>
        <button
          type="button"
          class="finance-priority-card"
          @click="focusTransactions('missing_attachments')"
        >
          <span>待补附件</span>
          <strong>{{ attachmentBacklogCount }}</strong>
        </button>
        <button
          type="button"
          class="finance-priority-card"
          @click="focusInvoices('overdue')"
        >
          <span>待跟进单据</span>
          <strong>{{ financeTodoCount }}</strong>
        </button>
      </section>

        <el-tabs v-model="activeTab" class="ledger-tabs">
          <el-tab-pane label="流水" name="transactions">
            <div class="quick-filter-bar">
              <button
                v-for="item in transactionQuickViews"
                :key="item.key"
                type="button"
                class="quick-filter-chip"
                :class="{ 'is-active': activeTransactionView === item.key }"
                @click="setTransactionView(item.key)"
              >
                {{ item.label }}
              </button>
            </div>
            <MobileToolbarPanel
              title="流水筛选与操作"
              :count="filteredTransactions.length"
            >
              <div class="toolbar toolbar--wide">
                <el-input
                  v-model="transactionFilters.keyword"
                  placeholder="搜往来方 / 科目 / 项目"
                  :prefix-icon="Search"
                  clearable
                />
                <el-select
                  v-model="transactionFilters.type"
                  clearable
                    placeholder="全部收支"
                >
                  <el-option
                    v-for="item in options.transactionTypes"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                  />
                </el-select>
                <el-select
                  v-model="transactionFilters.project_id"
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
                <el-select
                  v-model="transactionFilters.attachment"
                  placeholder="附件状态"
                >
                  <el-option label="全部附件" value="all" />
                  <el-option label="已有附件" value="with" />
                  <el-option label="待补附件" value="without" />
                </el-select>
                <el-button :icon="RefreshLeft" @click="resetTransactionFilters"
                  >重置</el-button
                >
              </div>
            </MobileToolbarPanel>
            <div class="table-meta">
              <span>当前结果 {{ filteredTransactions.length }} 条</span>
              <span>双击行看详情</span>
            </div>

            <div class="table-shell">
              <el-table
                class="responsive-table responsive-table--finance-transactions"
                :data="filteredTransactions"
                size="large"
                @row-dblclick="openTransactionDetail"
              >
                <el-table-column prop="date" label="日期" width="110" />
                <el-table-column
                  prop="counterparty"
                  label="往来方"
                  min-width="150"
                  show-overflow-tooltip
                />
                <el-table-column prop="category" label="科目" min-width="130" />
                <el-table-column
                  prop="project_name"
                  label="项目"
                  min-width="140"
                  show-overflow-tooltip
                />
                <el-table-column
                  prop="payment_method_label"
                  label="支付方式"
                  width="120"
                />
                <el-table-column label="金额" width="150" align="right">
                  <template #default="{ row }">
                    <span
                      :class="
                        row.type === 'income'
                          ? 'amount-positive'
                          : 'amount-negative'
                      "
                    >
                      {{ formatCurrency(row.amount, currency) }}
                    </span>
                  </template>
                </el-table-column>
                <el-table-column label="附件" width="90" align="center">
                  <template #default="{ row }">
                    <el-tag
                      :type="row.attachment_count > 0 ? 'success' : 'info'"
                      effect="light"
                      >{{ row.attachment_count }}</el-tag
                    >
                  </template>
                </el-table-column>
                <el-table-column
                  prop="notes"
                  label="说明"
                  min-width="180"
                  show-overflow-tooltip
                />
                <el-table-column label="操作" width="180" fixed="right">
                  <template #default="{ row }">
                    <RowActionMenu
                      :items="[
                        { key: 'detail', label: '详情', primary: true },
                        { key: 'edit', label: '编辑', hidden: !canEditFinance },
                        {
                          key: 'attachments',
                          label: '补附件',
                          hidden: !canEditFinance,
                        },
                        {
                          key: 'delete',
                          label: '删除',
                          hidden: !canEditFinance,
                          danger: true,
                        },
                      ]"
                      @select="handleTransactionAction(row, $event)"
                    />
                  </template>
                </el-table-column>
              </el-table>
            </div>
          </el-tab-pane>

          <el-tab-pane label="单据" name="invoices">
            <div class="quick-filter-bar">
              <button
                v-for="item in invoiceQuickViews"
                :key="item.key"
                type="button"
                class="quick-filter-chip"
                :class="{ 'is-active': activeInvoiceView === item.key }"
                @click="setInvoiceView(item.key)"
              >
                {{ item.label }}
              </button>
            </div>
            <MobileToolbarPanel
              title="单据筛选与操作"
              :count="filteredInvoices.length"
            >
              <div class="toolbar toolbar--wide">
                <el-input
                  v-model="invoiceFilters.keyword"
                  placeholder="搜单据 / 往来方 / 项目"
                  :prefix-icon="Search"
                  clearable
                />
                <el-select
                  v-model="invoiceFilters.kind"
                  clearable
                  placeholder="全部类型"
                >
                  <el-option
                    v-for="item in options.invoiceKinds"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                  />
                </el-select>
                <el-select
                  v-model="invoiceFilters.status"
                  clearable
                  placeholder="全部状态"
                >
                  <el-option label="待处理" value="pending" />
                  <el-option label="部分结清" value="partial" />
                  <el-option label="已完成" value="paid" />
                </el-select>
                <el-select
                  v-model="invoiceFilters.attachment"
                  placeholder="附件状态"
                >
                  <el-option label="全部附件" value="all" />
                  <el-option label="已有附件" value="with" />
                  <el-option label="待补附件" value="without" />
                </el-select>
                <el-button :icon="RefreshLeft" @click="resetInvoiceFilters"
                  >重置</el-button
                >
              </div>
            </MobileToolbarPanel>
            <div class="table-meta">
              <span>当前结果 {{ filteredInvoices.length }} 条</span>
              <span>双击行看详情</span>
            </div>

            <div class="table-shell">
              <el-table
                class="responsive-table responsive-table--finance-invoices"
                :data="filteredInvoices"
                size="large"
                @row-dblclick="openInvoiceDetail"
              >
                <el-table-column
                  prop="title"
                  label="单据标题"
                  min-width="180"
                  show-overflow-tooltip
                />
                <el-table-column prop="kind_label" label="类型" width="100" />
                <el-table-column
                  prop="counterparty"
                  label="往来方"
                  min-width="140"
                  show-overflow-tooltip
                />
                <el-table-column
                  prop="project_name"
                  label="项目"
                  min-width="140"
                  show-overflow-tooltip
                />
                <el-table-column label="金额" width="150" align="right">
                  <template #default="{ row }">
                    {{ formatCurrency(row.amount, currency) }}
                  </template>
                </el-table-column>
                <el-table-column prop="due_date" label="到期日" width="120" />
                <el-table-column label="状态" width="160">
                  <template #default="{ row }">
                    <el-select
                      :model-value="row.status"
                      size="small"
                      :disabled="!canEditFinance"
                      @change="changeInvoiceStatus(row, $event)"
                    >
                      <el-option
                        v-for="item in options.invoiceStatuses[row.kind] || []"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                      />
                    </el-select>
                  </template>
                </el-table-column>
                <el-table-column label="附件" width="90" align="center">
                  <template #default="{ row }">
                    <el-tag
                      :type="row.attachment_count > 0 ? 'success' : 'info'"
                      effect="light"
                      >{{ row.attachment_count }}</el-tag
                    >
                  </template>
                </el-table-column>
                <el-table-column label="操作" width="180" fixed="right">
                  <template #default="{ row }">
                    <RowActionMenu
                      :items="[
                        { key: 'detail', label: '详情', primary: true },
                        { key: 'edit', label: '编辑', hidden: !canEditFinance },
                        {
                          key: 'attachments',
                          label: '补附件',
                          hidden: !canEditFinance,
                        },
                        {
                          key: 'delete',
                          label: '删除',
                          hidden: !canEditFinance,
                          danger: true,
                        },
                      ]"
                      @select="handleInvoiceAction(row, $event)"
                    />
                  </template>
                </el-table-column>
              </el-table>
            </div>
          </el-tab-pane>
        </el-tabs>
      
    </PurePageCard>

    <TransactionDialog
      v-model="transactionDialogVisible"
      :record="currentTransaction"
      :projects="lookups.projects"
      :categories="options.transactionCategories"
      :payment-methods="options.paymentMethods"
      :transaction-types="options.transactionTypes"
      :loading="store.state.submitting"
      @submit="saveTransaction"
    />

    <InvoiceDialog
      v-model="invoiceDialogVisible"
      :record="currentInvoice"
      :projects="lookups.projects"
      :invoice-kinds="options.invoiceKinds"
      :invoice-statuses="options.invoiceStatuses"
      :loading="store.state.submitting"
      @submit="saveInvoice"
    />

    <AttachmentDialog
      v-model="attachmentDialogVisible"
      :record="attachmentTarget?.record"
      :loading="store.state.submitting"
      :title="
        attachmentTarget?.kind === 'transaction'
          ? '流水附件管理'
          : '单据附件管理'
      "
      @submit="saveAttachment"
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
      :notes-label="detailMode === 'transaction' ? '流水说明' : '单据说明'"
      :editable="canEditFinance"
      :attachments-editable="canEditFinance"
      @edit="handleDetailEdit"
      @attachments="handleDetailAttachments"
    />
  </div>
</template>
