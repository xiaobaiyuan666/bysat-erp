<script setup>
import { computed, reactive, ref, watch } from "vue";

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  record: {
    type: Object,
    default: null,
  },
  roles: {
    type: Array,
    default: () => [],
  },
  statuses: {
    type: Array,
    default: () => [],
  },
  permissionGroups: {
    type: Array,
    default: () => [],
  },
  permissions: {
    type: Array,
    default: () => [],
  },
  managers: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["update:modelValue", "submit"]);

const formRef = ref(null);
const form = reactive({
  account: "",
  employee_no: "",
  name: "",
  title: "",
  department: "",
  phone: "",
  email: "",
  hire_date: "",
  manager_id: "",
  role: "viewer",
  status: "active",
  password: "",
  permissions: [],
});

const rules = {
  account: [{ required: true, message: "请输入登录账号", trigger: "blur" }],
  employee_no: [{ required: true, message: "请输入员工工号", trigger: "blur" }],
  name: [{ required: true, message: "请输入员工姓名", trigger: "blur" }],
  department: [{ required: true, message: "请输入所属部门", trigger: "blur" }],
  role: [{ required: true, message: "请选择角色", trigger: "change" }],
  status: [{ required: true, message: "请选择状态", trigger: "change" }],
};

const roleMap = computed(() => {
  return new Map(props.roles.map((item) => [item.value, item]));
});

const selectedRole = computed(() => {
  return roleMap.value.get(form.role) || null;
});

const normalizedPermissionGroups = computed(() => {
  if (props.permissionGroups.length > 0) {
    return props.permissionGroups;
  }

  const groups = new Map();

  for (const item of props.permissions) {
    const key = item.group || item.module || "other";

    if (!groups.has(key)) {
      groups.set(key, {
        key,
        value: key,
        label: item.group_label || item.module_label || key,
        description: item.group_description || "",
        permissions: [],
      });
    }

    groups.get(key).permissions.push(item);
  }

  return Array.from(groups.values());
});

watch(
  () => props.modelValue,
  (visible) => {
    if (!visible) {
      return;
    }

    form.account = props.record?.account || "";
    form.employee_no = props.record?.employee_no || "";
    form.name = props.record?.name || "";
    form.title = props.record?.title || "";
    form.department = props.record?.department || "";
    form.phone = props.record?.phone || "";
    form.email = props.record?.email || "";
    form.hire_date = props.record?.hire_date || "";
    form.manager_id = props.record?.manager_id || "";
    form.role = props.record?.role || "viewer";
    form.status = props.record?.status || "active";
    form.password = "";
    form.permissions = [...(props.record?.permissions || [])];
  },
  { immediate: true },
);

async function submit() {
  const valid = await formRef.value?.validate().catch(() => false);

  if (!valid) {
    return;
  }

  emit("submit", {
    user_id: props.record?.id || "",
    ...form,
  });
}
</script>

<template>
  <el-dialog
    :model-value="modelValue"
    :title="record?.id ? '编辑工作人员' : '新增工作人员'"
    width="min(960px, calc(100vw - 24px))"
    :close-on-click-modal="false"
    destroy-on-close
    @update:model-value="emit('update:modelValue', $event)"
  >
    <el-form ref="formRef" :model="form" :rules="rules" label-position="top">
      <el-row :gutter="16">
        <el-col :xs="24" :md="8">
          <el-form-item label="登录账号" prop="account">
            <el-input
              v-model="form.account"
              placeholder="例如：admin / ops.gu / tech.zhou"
            />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="员工工号" prop="employee_no">
            <el-input
              v-model="form.employee_no"
              placeholder="例如：A0001 / T1001"
            />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="员工姓名" prop="name">
            <el-input v-model="form.name" placeholder="例如：张三" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="8">
          <el-form-item label="岗位">
            <el-input
              v-model="form.title"
              placeholder="例如：项目经理 / 财务主管"
            />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="所属部门" prop="department">
            <el-input
              v-model="form.department"
              placeholder="例如：技术部 / 财务部 / 运营部"
            />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="直属上级">
            <el-select
              v-model="form.manager_id"
              style="width: 100%"
              clearable
              placeholder="可选"
            >
              <el-option
                v-for="item in managers"
                :key="item.value"
                :label="item.label"
                :value="item.value"
              />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="8">
          <el-form-item label="手机号">
            <el-input v-model="form.phone" placeholder="例如：13800000000" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="邮箱">
            <el-input
              v-model="form.email"
              placeholder="例如：ops.gu@yfsoft.local"
            />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="入职日期">
            <el-date-picker
              v-model="form.hire_date"
              type="date"
              value-format="YYYY-MM-DD"
              style="width: 100%"
            />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="8">
          <el-form-item label="角色" prop="role">
            <el-select v-model="form.role" style="width: 100%">
              <el-option
                v-for="item in roles"
                :key="item.value"
                :label="`${item.label} / ${item.group_label}`"
                :value="item.value"
              />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="状态" prop="status">
            <el-select v-model="form.status" style="width: 100%">
              <el-option
                v-for="item in statuses"
                :key="item.value"
                :label="item.label"
                :value="item.value"
              />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item :label="record?.id ? '重置密码' : '初始密码'">
            <el-input
              v-model="form.password"
              type="password"
              show-password
              :placeholder="
                record?.id ? '留空则不修改' : '留空则默认 Start@123'
              "
            />
          </el-form-item>
        </el-col>
      </el-row>

      <article v-if="selectedRole" class="role-detail-card">
        <div class="role-detail-card__head">
          <div>
            <strong>{{ selectedRole.label }}</strong>
            <small>
                {{ selectedRole.group_label }} / 默认入口：{{ selectedRole.home_module_label }}
              </small>
            </div>
          <el-tag effect="light">
            {{
              selectedRole.permissions.includes("*")
                ? "全量权限"
                : `${selectedRole.permission_group_count} 个权限组`
            }}
          </el-tag>
        </div>
        <div class="role-detail-card__summary">
          {{ selectedRole.summary }}
        </div>
        <div class="role-detail-card__grid">
          <div class="role-detail-card__section">
            <span class="role-detail-card__label">角色自带权限组</span>
            <div class="role-detail-card__row">
              <el-tag
                v-for="group in selectedRole.permission_groups"
                :key="`${selectedRole.value}-${group.value}`"
                effect="light"
              >
                {{ group.label }}
              </el-tag>
            </div>
          </div>
          <div class="role-detail-card__section">
            <span class="role-detail-card__label">建议使用顺序</span>
            <ol class="guide-step-list guide-step-list--compact">
              <li v-for="step in selectedRole.guide_steps" :key="step">
                {{ step }}
              </li>
            </ol>
          </div>
        </div>
      </article>

      <el-alert
        title="系统最终权限 = 角色自带权限 + 下方补充权限。补充权限只建议用于个别特殊岗位。"
        type="info"
        :closable="false"
      />

      <el-form-item label="补充权限">
        <div class="permission-groups permission-groups--dialog">
          <section
            v-for="group in normalizedPermissionGroups"
            :key="group.value || group.key"
            class="permission-group"
          >
            <div class="permission-group__header">
              <div>
                <div class="permission-group__title">{{ group.label }}</div>
                <div v-if="group.description" class="permission-group__description">
                  {{ group.description }}
                </div>
              </div>
              <el-tag effect="light">{{ group.permissions.length }} 椤</el-tag>
            </div>
            <el-checkbox-group v-model="form.permissions">
              <div class="permission-group__checkboxes">
                <el-checkbox
                  v-for="item in group.permissions"
                  :key="item.value"
                  :label="item.value"
                >
                  {{ item.label }}
                </el-checkbox>
              </div>
            </el-checkbox-group>
          </section>
        </div>
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="emit('update:modelValue', false)">取消</el-button>
      <el-button type="primary" :loading="loading" @click="submit">
        保存工作人员
      </el-button>
    </template>
  </el-dialog>
</template>
