import { createRouter, createWebHashHistory } from "vue-router";
import {
  consoleModules,
  getFirstAllowedModule,
} from "../config/console";
import ConsoleLayout from "../layouts/ConsoleLayout.vue";
import LoginView from "../views/LoginView.vue";
import { useAppStore } from "../stores/useAppStore";

const viewLoaders = {
  dashboard: () => import("../views/DashboardView.vue"),
  finance: () => import("../views/FinanceView.vue"),
  projects: () => import("../views/ProjectsView.vue"),
  operations: () => import("../views/OperationsView.vue"),
  team: () => import("../views/TeamView.vue"),
};

function resolvePreferredModule(store) {
  const currentUser = store.state.bootstrap.currentUser || {};
  const preferredModuleName =
    currentUser.role_home_module ||
    currentUser.home_module ||
    currentUser.default_module ||
    "";

  return getFirstAllowedModule(
    (permission) => store.hasPermission(permission),
    preferredModuleName,
  );
}

const router = createRouter({
  history: createWebHashHistory(),
  routes: [
    {
      path: "/login",
      name: "login",
      component: LoginView,
      meta: {
        title: "登录",
        requiresAuth: false,
      },
    },
    {
      path: "/tech",
      redirect: "/operations",
    },
    {
      path: "/service",
      redirect: "/operations",
    },
    {
      path: "/supervisor",
      redirect: "/operations",
    },
    {
      path: "/",
      component: ConsoleLayout,
      meta: {
        requiresAuth: true,
      },
      children: [
        {
          path: "",
          redirect: "/dashboard",
        },
        ...consoleModules.map((item) => ({
          path: item.path.replace(/^\//, ""),
          name: item.name,
          component: viewLoaders[item.name],
          meta: {
            title: item.label,
            subtitle: item.subtitle,
            navDescription: item.navDescription,
            groupKey: item.groupKey,
            groupLabel: item.groupLabel,
            permission: item.permission,
            requiresAuth: true,
          },
        })),
      ],
    },
  ],
});

router.beforeEach(async (to) => {
  const store = useAppStore();

  if (!store.state.ready) {
    try {
      await store.loadBootstrap({ silent: true });
    } catch {
      // Let the target page handle bootstrap failures.
    }
  }

  const authenticated = store.isAuthenticated();

  if (to.meta.requiresAuth && !authenticated) {
    return {
      name: "login",
      query: {
        redirect: to.fullPath,
      },
    };
  }

  if (to.name === "login" && authenticated) {
    return resolvePreferredModule(store)?.path || "/dashboard";
  }

  if (to.meta.requiresAuth && to.meta.permission && !store.hasPermission(to.meta.permission)) {
    return resolvePreferredModule(store)?.path || "/dashboard";
  }

  return true;
});

export default router;
