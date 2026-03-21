<script setup>
import { computed, ref } from "vue";
import {
  CirclePlus,
  Lock,
  RefreshLeft,
  Search,
  Clock,
} from "@element-plus/icons-vue";
import { ElMessageBox } from "element-plus";
import MobileToolbarPanel from "../components/MobileToolbarPanel.vue";
import PurePageCard from "../components/PurePageCard.vue";
import RowActionMenu from "../components/RowActionMenu.vue";
import UserDialog from "../components/UserDialog.vue";
import { usePersistedState } from "../composables/usePersistedState";
import { useAppStore } from "../stores/useAppStore";

const store = useAppStore();

const activeTab = usePersistedState("console.team.tab", "users");
const userFilters = usePersistedState("console.team.user.filters", {
  keyword: "",
  role: "",
  status: "",
});
const logFilters = usePersistedState("console.team.log.filters", {
  keyword: "",
  module: "",
  action: "",
  user_name: "",
});

const dialogVisible = ref(false);
const currentUserRecord = ref(null);

const bootstrap = computed(() => store.state.bootstrap);
const users = computed(() => bootstrap.value.userRows || []);
const roles = computed(() => bootstrap.value.roleRows || []);
const logs = computed(() => bootstrap.value.auditLogRows || []);
const options = computed(() => bootstrap.value.options || {});
const currentUserId = computed(() => bootstrap.value.currentUserId || "");
const canManageStaff = computed(() => store.hasPermission("staff.manage"));
const canSwitchIdentity = computed(() =>
  Boolean(bootstrap.value.canImpersonate),
);

const roleOptions = computed(() =>
  roles.value.map((item) => ({
    value: item.value,
    label: item.label,
  })),
);

const today = new Date().toISOString().slice(0, 10);

const stats = computed(() => {
  const activeUsers = users.value.filter(
    (item) => item.status === "active",
  ).length;
  const adminUsers = users.value.filter(
    (item) => item.role === "admin" && item.status === "active",
  ).length;
  const opsUsers = users.value.filter((item) =>
    ["operations", "service", "tech"].includes(item.role),
  ).length;
  const todayLogs = logs.value.filter((item) =>
    String(item.occurred_at || "").startsWith(today),
  ).length;

  return {
    activeUsers,
    adminUsers,
    opsUsers,
    todayLogs,
  };
});
const roleGroupCount = computed(() => roles.value.length);

const filteredUsers = computed(() => {
  const keyword = userFilters.value.keyword.trim().toLowerCase();

  return users.value.filter((row) => {
    const hitKeyword =
      keyword === "" ||
      [
        row.name,
        row.account,
        row.employee_no,
        row.title,
        row.department,
        row.role_label,
        row.role_group_label,
        row.manager_name,
        row.email,
        row.phone,
      ]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(keyword));

    const hitRole =
      !userFilters.value.role || row.role === userFilters.value.role;
    const hitStatus =
      !userFilters.value.status || row.status === userFilters.value.status;

    return hitKeyword && hitRole && hitStatus;
  });
});

const managerOptions = computed(() => {
  return users.value
    .filter((item) => item.status === "active")
    .map((item) => ({
      value: item.id,
      label: `${item.name} / ${item.title || item.role_label}`,
    }));
});

const logActions = computed(() => {
  return Array.from(
    new Map(
      logs.value.map((item) => [
        item.action,
        { value: item.action, label: item.action_label },
      ]),
    ).values(),
  );
});

const filteredLogs = computed(() => {
  const keyword = logFilters.value.keyword.trim().toLowerCase();

  return logs.value.filter((row) => {
    const hitKeyword =
      keyword === "" ||
      [
        row.summary,
        row.target_id,
        row.module_label,
        row.action_label,
        row.user_name,
      ]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(keyword));

    const hitModule =
      !logFilters.value.module || row.module === logFilters.value.module;
    const hitAction =
      !logFilters.value.action || row.action === logFilters.value.action;
    const hitUser =
      !logFilters.value.user_name ||
      String(row.user_name)
        .toLowerCase()
        .includes(logFilters.value.user_name.toLowerCase());

    return hitKeyword && hitModule && hitAction && hitUser;
  });
});

function resetUserFilters() {
  userFilters.value = {
    keyword: "",
    role: "",
    status: "",
  };
}

function resetLogFilters() {
  logFilters.value = {
    keyword: "",
    module: "",
    action: "",
    user_name: "",
  };
}

function openUserDialog(record = null) {
  currentUserRecord.value = record;
  dialogVisible.value = true;
}

async function saveUser(payload) {
  const action = payload.user_id ? "update_user" : "add_user";
  await store.submitAction(action, payload);
  dialogVisible.value = false;
  currentUserRecord.value = null;
}

async function removeUser(row) {
  await ElMessageBox.confirm(
    `确认删除工作人员“${row.name}”吗？删除后该账号将无法继续登录系统。`,
    "删除确认",
    {
      type: "warning",
    },
  );

  await store.submitAction("delete_user", {
    user_id: row.id,
  });
}

async function switchUser(row) {
  if (!canSwitchIdentity.value || row.id === currentUserId.value) {
    return;
  }

  await store.submitAction(
    "switch_current_user",
    {
      current_user_id: row.id,
    },
    { silent: true },
  );
}

async function handleUserAction(row, action) {
  switch (action) {
    case "switch":
      await switchUser(row);
      break;
    case "edit":
      openUserDialog(row);
      break;
    case "delete":
      await removeUser(row);
      break;
    default:
      break;
  }
}
</script>

<template>
  <div class="page-grid">
    <PurePageCard
      title="人员与权限台账"
      description="直接管理员工、角色和日志。"
    >
      <template #actions>
        <el-button v-if="canManageStaff" type="primary" @click="openUserDialog()">
          <el-icon><CirclePlus /></el-icon>
          新增员工
        </el-button>
      </template>

      <section class="team-priority-strip">
        <button
          type="button"
          class="team-priority-card"
          @click="activeTab = 'users'"
        >
          <span>在岗人员</span>
          <strong>{{ stats.activeUsers }}</strong>
        </button>
        <button
          type="button"
          class="team-priority-card"
          @click="activeTab = 'roles'"
        >
          <span>角色组</span>
          <strong>{{ roleGroupCount }}</strong>
        </button>
        <button
          type="button"
          class="team-priority-card"
          @click="activeTab = 'logs'"
        >
          <span>今日日志</span>
          <strong>{{ stats.todayLogs }}</strong>
        </button>
        <button
          type="button"
          class="team-priority-card"
          @click="activeTab = 'users'"
        >
          <span>管理员账号</span>
          <strong>{{ stats.adminUsers }}</strong>
        </button>
      </section>

        <el-tabs v-model="activeTab" class="ledger-tabs">
          <el-tab-pane label="工作人员" name="users">
            <MobileToolbarPanel
              title="人员筛选与操作"
              :count="filteredUsers.length"
            >
              <div class="toolbar toolbar--wide">
                <el-input
                  v-model="userFilters.keyword"
                  placeholder="搜姓名 / 账号 / 工号 / 岗位 / 部门"
                  :prefix-icon="Search"
                  clearable
                />
                <el-select
                  v-model="userFilters.role"
                  clearable
                  placeholder="全部角色"
                >
                  <el-option
                    v-for="item in roleOptions"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                  />
                </el-select>
                <el-select
                  v-model="userFilters.status"
                  clearable
                  placeholder="全部状态"
                >
                  <el-option
                    v-for="item in options.userStatuses"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                  />
                </el-select>
                <el-button :icon="RefreshLeft" @click="resetUserFilters">
                  重置
                </el-button>
              </div>
            </MobileToolbarPanel>

            <div class="table-meta">
              <span>当前结果 {{ filteredUsers.length }} 条</span>
              <span>
                {{
                  canSwitchIdentity
                    ? "支持切换工作身份"
                    : "当前账号不能切换其他身份"
                }}
              </span>
            </div>

            <div class="table-shell">
              <el-table
                class="responsive-table responsive-table--team-users"
                :data="filteredUsers"
                size="large"
              >
                <el-table-column label="人员" min-width="180">
                  <template #default="{ row }">
                    <div class="stack-text">
                      <strong>{{ row.name }}</strong>
                      <span>{{ row.title || row.department }}</span>
                    </div>
                  </template>
                </el-table-column>

                <el-table-column label="账号信息" min-width="180">
                  <template #default="{ row }">
                    <div class="stack-text">
                      <strong>{{ row.account }}</strong>
                      <span>{{ row.employee_no }}</span>
                    </div>
                  </template>
                </el-table-column>

                <el-table-column prop="department" label="部门" width="120" />

                <el-table-column label="角色 / 权限组" min-width="160">
                  <template #default="{ row }">
                    <div class="stack-text">
                      <strong>{{ row.role_label }}</strong>
                      <span>{{ row.role_group_label }}</span>
                    </div>
                  </template>
                </el-table-column>

                <el-table-column
                  prop="manager_name"
                  label="直属上级"
                  width="120"
                />

                <el-table-column label="联系方式" min-width="180">
                  <template #default="{ row }">
                    <div class="stack-text">
                      <strong>{{ row.phone || "--" }}</strong>
                      <span>{{ row.email || "--" }}</span>
                    </div>
                  </template>
                </el-table-column>

                <el-table-column
                  prop="hire_date"
                  label="入职日期"
                  width="120"
                />

                <el-table-column label="状态" width="100">
                  <template #default="{ row }">
                    <el-tag
                      :type="
                        row.status_tone === 'danger' ? 'danger' : 'success'
                      "
                      effect="light"
                    >
                      {{ row.status_label }}
                    </el-tag>
                  </template>
                </el-table-column>

                <el-table-column
                  prop="last_login_at"
                  label="最近登录"
                  width="170"
                />

                <el-table-column label="操作" width="180" fixed="right">
                  <template #default="{ row }">
                    <RowActionMenu
                      :items="[
                        {
                          key: 'switch',
                          label:
                            row.id === currentUserId
                              ? '当前工作身份'
                              : '切换到当前身份',
                          primary: true,
                          hidden: !canSwitchIdentity,
                          disabled: row.id === currentUserId,
                        },
                        { key: 'edit', label: '编辑', hidden: !canManageStaff },
                        {
                          key: 'delete',
                          label: '删除',
                          hidden: !canManageStaff,
                          danger: true,
                        },
                      ]"
                      @select="handleUserAction(row, $event)"
                    />
                  </template>
                </el-table-column>
              </el-table>
            </div>
          </el-tab-pane>

          <el-tab-pane label="角色权限" name="roles">
            <el-alert
              title="权限分配建议：先给角色组，再补额外权限。和 FastAdmin 一样，角色是主入口，单独权限只做补丁。"
              type="info"
              :closable="false"
            />

            <div class="role-accordion">
              <details
                v-for="role in roles"
                :key="`role-${role.value}`"
                class="role-accordion__item"
              >
                <summary class="role-accordion__summary">
                  <div class="role-accordion__title">
                    <strong>{{ role.label }}</strong>
                    <small>{{ role.group_label }} / 默认入口：{{ role.home_module_label }}</small>
                  </div>
                  <div class="role-accordion__badges">
                    <span>{{ role.member_count }} 人</span>
                    <el-tag effect="light">
                      {{
                        role.permissions.includes("*")
                          ? "全量权限"
                          : `${role.permission_group_count} 个权限组`
                      }}
                    </el-tag>
                  </div>
                </summary>

                <div class="role-accordion__content">
                  <div class="role-accordion__hint">
                    {{ role.summary }}
                  </div>

                  <div class="role-guide-steps">
                    <span class="role-guide-steps__label">使用顺序</span>
                    <ol class="guide-step-list guide-step-list--compact">
                      <li v-for="step in role.guide_steps" :key="step">
                        {{ step }}
                      </li>
                    </ol>
                  </div>

                  <div class="role-permission-groups">
                    <article
                      v-for="group in role.permission_groups"
                      :key="`${role.value}-${group.value}`"
                      class="role-permission-group-card"
                    >
                      <div class="role-permission-group-card__head">
                        <div>
                          <strong>{{ group.label }}</strong>
                          <small>{{ group.description }}</small>
                        </div>
                        <el-tag effect="light">
                          {{ group.permission_count }} 项</el-tag>
                      </div>
                      <div class="role-accordion__tags">
                        <span
                          v-for="item in group.permissions"
                          :key="item.value"
                          class="role-accordion__permission"
                        >
                          <el-icon><Lock /></el-icon>
                          <span>{{ item.label }}</span>
                        </span>
                      </div>
                    </article>
                  </div>
                </div>
              </details>
            </div>
          </el-tab-pane>

          <el-tab-pane label="操作日志" name="logs">
            <MobileToolbarPanel
              title="日志筛选与操作"
              :count="filteredLogs.length"
            >
              <div class="toolbar toolbar--wide">
                <el-input
                  v-model="logFilters.keyword"
                  placeholder="搜摘要 / 目标 ID / 模块 / 操作人"
                  :prefix-icon="Search"
                  clearable
                />
                <el-select
                  v-model="logFilters.module"
                  clearable
                  placeholder="全部模块"
                >
                  <el-option label="财务中心" value="finance" />
                  <el-option label="项目交付" value="projects" />
                  <el-option label="APP 运营" value="operations" />
                  <el-option label="问题记录" value="service" />
                  <el-option label="研发联动" value="tech" />
                  <el-option label="人员权限" value="staff" />
                  <el-option label="登录认证" value="auth" />
                  <el-option label="AI 助手" value="ai" />
                </el-select>
                <el-select
                  v-model="logFilters.action"
                  clearable
                  placeholder="全部动作"
                >
                  <el-option
                    v-for="item in logActions"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                  />
                </el-select>
                <el-input
                  v-model="logFilters.user_name"
                  placeholder="操作人筛选"
                  clearable
                />
                <el-button :icon="RefreshLeft" @click="resetLogFilters">
                  重置
                </el-button>
              </div>
            </MobileToolbarPanel>

            <div class="table-meta">
              <span>当前结果 {{ filteredLogs.length }} 条</span>
              <span>登录和增删改都会留痕</span>
            </div>

            <div class="table-shell">
              <el-table
                class="responsive-table responsive-table--team-logs"
                :data="filteredLogs"
                size="large"
              >
                <el-table-column label="时间" width="180">
                  <template #default="{ row }">
                    <div class="stack-text">
                      <strong>{{ row.occurred_at }}</strong>
                      <span><el-icon><Clock /></el-icon>{{ row.user_name }}</span>
                    </div>
                  </template>
                </el-table-column>
                <el-table-column prop="module_label" label="模块" width="120" />
                <el-table-column prop="action_label" label="动作" width="120" />
                <el-table-column prop="target_id" label="目标 ID" width="160" />
                <el-table-column
                  prop="summary"
                  label="摘要"
                  min-width="360"
                  show-overflow-tooltip
                />
              </el-table>
            </div>
          </el-tab-pane>
        </el-tabs>
          </PurePageCard>

    <UserDialog
      v-model="dialogVisible"
      :record="currentUserRecord"
      :roles="roles"
      :statuses="options.userStatuses"
      :permission-groups="options.permissionGroups"
      :permissions="options.permissions"
      :managers="managerOptions"
      :loading="store.state.submitting"
      @submit="saveUser"
    />
  </div>
</template>
