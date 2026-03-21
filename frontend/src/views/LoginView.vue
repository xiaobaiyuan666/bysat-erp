<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { Lock, User } from "@element-plus/icons-vue";
import { useAppStore } from "../stores/useAppStore";

const store = useAppStore();
const route = useRoute();
const router = useRouter();
const formRef = ref(null);

const form = reactive({
  account: "admin",
  password: "Admin@123",
});

const rules = {
  account: [{ required: true, message: "请输入账号或工号", trigger: "blur" }],
  password: [{ required: true, message: "请输入密码", trigger: "blur" }],
};

const loginAccounts = computed(() => store.state.bootstrap.loginAccounts || []);
const companyName = computed(
  () => store.state.bootstrap.meta?.company || "企业管理系统",
);

onMounted(async () => {
  if (!store.state.ready) {
    try {
      await store.loadBootstrap({ silent: true });
    } catch {
      // Keep the login page usable even if bootstrap fails.
    }
  }
});

function fillDemoAccount(account) {
  form.account = account.account || account.employee_no || "";
  form.password = account.account === "admin" ? "Admin@123" : "Start@123";
}

async function submit() {
  const valid = await formRef.value?.validate().catch(() => false);

  if (!valid) {
    return;
  }

  await store.login(form);
  const redirect =
    typeof route.query.redirect === "string" && route.query.redirect
      ? route.query.redirect
      : "/dashboard";
  await router.replace(redirect);
}
</script>

<template>
  <div class="login-shell">
    <section class="login-hero login-hero--pure">
      <div class="login-hero__badge">vue-pure-admin 风格</div>
      <h1>{{ companyName }}</h1>
      <p>后台统一采用左侧菜单、顶部标签、轻量卡片和清晰工作流，不再做成一屏堆满信息的样子。</p>

      <div class="login-hero__cards">
        <article class="login-info-card">
          <strong>默认管理员</strong>
          <span>账号 `admin`</span>
          <span>密码 `Admin@123`</span>
        </article>
        <article class="login-info-card">
          <strong>推荐使用顺序</strong>
          <span>财务先记账，项目先看任务，APP 运营先收问题。</span>
          <span>其他员工默认密码是 `Start@123`。</span>
        </article>
      </div>
    </section>

    <section class="login-panel login-panel--pure">
      <div class="login-panel__head">
        <div>
          <h2>登录工作台</h2>
          <p>支持账号、工号或邮箱登录</p>
        </div>
      </div>

      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-position="top"
        class="login-form"
      >
        <el-form-item label="账号" prop="account">
          <el-input
            v-model="form.account"
            :prefix-icon="User"
            placeholder="请输入账号 / 工号 / 邮箱"
            clearable
          />
        </el-form-item>

        <el-form-item label="密码" prop="password">
          <el-input
            v-model="form.password"
            :prefix-icon="Lock"
            type="password"
            placeholder="请输入密码"
            show-password
          />
        </el-form-item>

        <el-button
          type="primary"
          class="login-form__submit"
          :loading="store.state.submitting"
          @click="submit"
        >
          登录进入工作台
        </el-button>
      </el-form>

      <div class="login-demo">
        <div class="login-demo__title">演示账号</div>
        <div class="login-demo__list">
          <button
            v-for="account in loginAccounts"
            :key="account.id"
            type="button"
            class="login-demo__item"
            @click="fillDemoAccount(account)"
          >
            <strong>{{ account.name }}</strong>
            <span>{{ account.department }} / {{ account.role_label }}</span>
            <small>{{ account.account }} / {{ account.employee_no }}</small>
          </button>
        </div>
      </div>
    </section>
  </div>
</template>
