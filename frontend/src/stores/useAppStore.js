import { reactive } from "vue";
import { ElMessage } from "element-plus";
import {
  extractApiError,
  fetchBootstrap,
  postAction,
  postMultipartAction,
} from "../api/client";

function createEmptyBootstrap() {
  return {
    meta: {
      company: "",
      currency: "CNY",
      version: "0.0.0",
      generated_at: "",
    },
    authenticated: false,
    sessionUserId: "",
    sessionUser: null,
    canImpersonate: false,
    currentUserId: "",
    currentUser: null,
    loginAccounts: [],
    dashboard: {},
    transactionRows: [],
    invoiceRows: [],
    projectRows: [],
    taskRows: [],
    opsProjectRows: [],
    opsMilestoneRows: [],
    opsUpdateRows: [],
    opsReleaseRows: [],
    opsMaterialRows: [],
    opsRiskRows: [],
    serviceTicketRows: [],
    serviceSummary: {},
    techTicketRows: [],
    techSummary: {},
    operationsSummary: {},
    operationsAlerts: [],
    recentTransactions: [],
    cashflowRows: [],
    incomeRows: [],
    expenseRows: [],
    invoiceSummary: {},
    taskSummary: [],
    projectHealthRows: [],
    assigneeLoadRows: [],
    dueInvoiceRows: [],
    businessAlerts: [],
    userRows: [],
    roleRows: [],
    auditLogRows: [],
    aiSettings: {},
    aiConfigured: false,
    aiConversation: [],
    aiPresets: [],
    workspace: {},
    lookups: {
      projects: [],
      opsProjects: [],
      opsMaterials: [],
      serviceTickets: [],
      users: [],
      techTickets: [],
      techOwners: [],
      serviceAssignees: [],
    },
    options: {
      transactionTypes: [],
      paymentMethods: [],
      invoiceKinds: [],
      invoiceStatuses: {
        receivable: [],
        payable: [],
      },
      projectStatuses: [],
      taskStatuses: [],
      priorities: [],
      opsProjectStatuses: [],
      opsLifecycleStages: [],
      opsMilestoneStatuses: [],
      opsReleaseStatuses: [],
      opsReleaseCustomerSyncStatuses: [],
      opsMaterialCategories: [],
      opsMaterialArchiveStatuses: [],
      opsRiskTypes: [],
      opsRiskLevels: [],
      opsRiskStatuses: [],
      serviceTicketSources: [],
      serviceTicketChannels: [],
      serviceTicketCategories: [],
      serviceTicketStatuses: [],
      serviceTicketUpdateTypes: [],
      serviceTicketUpdateVisibilities: [],
      techTicketTypes: [],
      techTicketStatuses: [],
      techTicketSeverities: [],
      techTicketSources: [],
      userStatuses: [],
      roles: [],
      permissionGroups: [],
      permissions: [],
      transactionCategories: [],
    },
  };
}

const state = reactive({
  ready: false,
  loading: false,
  submitting: false,
  assistantVisible: false,
  lastLoadedAt: "",
  bootstrap: createEmptyBootstrap(),
});

function applyBootstrap(data) {
  state.bootstrap = {
    ...createEmptyBootstrap(),
    ...data,
  };
  state.ready = true;
  state.lastLoadedAt = data?.meta?.generated_at || "";

  if (!state.bootstrap.authenticated) {
    state.assistantVisible = false;
  }
}

function isActionWithActor(action) {
  return !["login", "logout"].includes(action);
}

async function loadBootstrap(options = {}) {
  const { silent = false } = options;

  state.loading = true;

  try {
    const response = await fetchBootstrap();
    applyBootstrap(response.data);

    if (!silent) {
      ElMessage.success("数据已刷新");
    }

    return response.data;
  } catch (error) {
    const normalizedError = extractApiError(error);
    ElMessage.error(normalizedError.message);
    throw normalizedError;
  } finally {
    state.loading = false;
  }
}

async function submitAction(action, payload = {}, options = {}) {
  const { multipart = false, silent = false } = options;
  const actionPayload = {
    ...payload,
  };

  if (
    isActionWithActor(action) &&
    !Object.prototype.hasOwnProperty.call(actionPayload, "current_user_id") &&
    state.bootstrap.currentUserId
  ) {
    actionPayload.current_user_id = state.bootstrap.currentUserId;
  }

  state.submitting = true;

  try {
    const response = multipart
      ? await postMultipartAction(action, actionPayload)
      : await postAction(action, actionPayload);

    applyBootstrap(response.data);

    if (!silent && response.message) {
      ElMessage.success(response.message);
    }

    return response;
  } catch (error) {
    const normalizedError = extractApiError(error);

    if (normalizedError.payload) {
      applyBootstrap(normalizedError.payload);
    }

    if (!silent) {
      ElMessage.error(normalizedError.message);
    }

    throw normalizedError;
  } finally {
    state.submitting = false;
  }
}

function permissionFromUser(user, permission) {
  if (!permission) {
    return true;
  }

  if (Array.isArray(permission)) {
    return permission.some((item) => permissionFromUser(user, item));
  }

  const permissions = user?.effective_permissions || [];
  return permissions.includes("*") || permissions.includes(permission);
}

function hasPermission(permission) {
  return permissionFromUser(state.bootstrap.currentUser, permission);
}

function hasSessionPermission(permission) {
  return permissionFromUser(state.bootstrap.sessionUser, permission);
}

function isAuthenticated() {
  return Boolean(state.bootstrap.authenticated && state.bootstrap.sessionUserId);
}

async function login(payload) {
  return submitAction("login", payload, { silent: true });
}

async function logout() {
  return submitAction("logout", {}, { silent: true });
}

const store = {
  state,
  loadBootstrap,
  submitAction,
  hasPermission,
  hasSessionPermission,
  isAuthenticated,
  login,
  logout,
};

export function useAppStore() {
  return store;
}
