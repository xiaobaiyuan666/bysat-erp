import {
  Aim,
  DataAnalysis,
  FolderOpened,
  Operation,
  UserFilled,
} from "@element-plus/icons-vue";

const rawModuleGroups = [
  {
    key: "workspace",
    label: "工作台",
    description: "先看全局提醒，再进入具体业务处理。",
    modules: [
      {
        name: "dashboard",
        path: "/dashboard",
        label: "经营驾驶舱",
        code: "BI",
        icon: DataAnalysis,
        permission: "dashboard.view",
        subtitle: "先看今天最急的事，再决定去财务、项目还是 APP 运营处理。",
        navDescription: "经营提醒、回款付款节点和整体经营概览。",
      },
    ],
  },
  {
    key: "business",
    label: "业务协同",
    description: "按业务流程把财务、交付和 APP 运营串起来。",
    modules: [
      {
        name: "finance",
        path: "/finance",
        label: "财务中心",
        code: "FI",
        icon: Aim,
        permission: "finance.view",
        subtitle: "先记账，再补附件，再盯回款付款，按顺序处理就行。",
        navDescription: "流水、应收应付、附件和智能记账。",
      },
      {
        name: "projects",
        path: "/projects",
        label: "项目交付",
        code: "PM",
        icon: FolderOpened,
        permission: "projects.view",
        subtitle: "先处理任务，再回头看项目进度、风险和预算。",
        navDescription: "项目台账、任务推进、风险和负责人负荷。",
      },
      {
        name: "operations",
        path: "/operations",
        label: "APP 运营",
        code: "OP",
        icon: Operation,
        permission: "operations.view",
        subtitle: "先收问题，再挂研发，再看版本和资料。",
        navDescription: "APP 生命周期、问题记录、版本发布和研发联动。",
      },
    ],
  },
  {
    key: "system",
    label: "系统管理",
    description: "统一处理员工、角色、权限和操作留痕。",
    modules: [
      {
        name: "team",
        path: "/team",
        label: "人员权限",
        code: "HR",
        icon: UserFilled,
        permission: ["audit.view", "staff.manage"],
        subtitle: "先管员工，再配角色，最后查日志和留痕。",
        navDescription: "员工账号、角色权限组和操作日志。",
      },
    ],
  },
];

export const consoleModuleGroups = rawModuleGroups.map((group, groupIndex) => ({
  ...group,
  order: groupIndex,
  modules: group.modules.map((module, moduleIndex) => ({
    ...module,
    groupKey: group.key,
    groupLabel: group.label,
    groupDescription: group.description,
    order: moduleIndex,
  })),
}));

export const consoleModules = consoleModuleGroups.flatMap((group) => group.modules);

export function findConsoleModule(name) {
  return consoleModules.find((item) => item.name === name || item.path === name) || null;
}

export function findConsoleGroup(key) {
  return consoleModuleGroups.find((item) => item.key === key) || null;
}

export function filterVisibleModuleGroups(hasPermission) {
  return consoleModuleGroups
    .map((group) => ({
      ...group,
      modules: group.modules.filter((item) => hasPermission(item.permission)),
    }))
    .filter((group) => group.modules.length > 0);
}

export function getFirstAllowedModule(hasPermission, preferredModuleName = "") {
  const preferred = consoleModules.find(
    (item) => item.name === preferredModuleName && hasPermission(item.permission),
  );

  if (preferred) {
    return preferred;
  }

  return consoleModules.find((item) => hasPermission(item.permission)) || consoleModules[0];
}
