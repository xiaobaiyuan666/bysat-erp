<script setup>
import { reactive, ref, watch } from 'vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  record: {
    type: Object,
    default: null,
  },
  opsProjects: {
    type: Array,
    default: () => [],
  },
  deliveryProjects: {
    type: Array,
    default: () => [],
  },
  owners: {
    type: Array,
    default: () => [],
  },
  ticketTypes: {
    type: Array,
    default: () => [],
  },
  ticketStatuses: {
    type: Array,
    default: () => [],
  },
  severities: {
    type: Array,
    default: () => [],
  },
  priorities: {
    type: Array,
    default: () => [],
  },
  sources: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'submit'])

const formRef = ref(null)
const form = reactive({
  ops_project_id: '',
  project_id: '',
  title: '',
  type: 'bug',
  status: 'pending',
  priority: 'medium',
  severity: 'medium',
  source: 'operations',
  app_module: '',
  app_version: '',
  owner: '',
  reporter: '',
  due_date: '',
  impact: '',
  solution_plan: '',
  estimate_hours: 0,
  actual_hours: 0,
  notes: '',
})

const rules = {
  ops_project_id: [{ required: true, message: '请选择关联 APP 项目', trigger: 'change' }],
  title: [{ required: true, message: '请输入待办标题', trigger: 'blur' }],
  owner: [{ required: true, message: '请选择负责人', trigger: 'change' }],
  reporter: [{ required: true, message: '请选择提出人', trigger: 'change' }],
  due_date: [{ required: true, message: '请选择截止日期', trigger: 'change' }],
  impact: [{ required: true, message: '请填写影响说明', trigger: 'blur' }],
  solution_plan: [{ required: true, message: '请填写处理方案', trigger: 'blur' }],
}

watch(
  () => props.modelValue,
  (visible) => {
    if (!visible) {
      return
    }

    form.ops_project_id = props.record?.ops_project_id || ''
    form.project_id = props.record?.project_id || ''
    form.title = props.record?.title || ''
    form.type = props.record?.type || 'bug'
    form.status = props.record?.status || 'pending'
    form.priority = props.record?.priority || 'medium'
    form.severity = props.record?.severity || 'medium'
    form.source = props.record?.source || 'operations'
    form.app_module = props.record?.app_module || ''
    form.app_version = props.record?.app_version || ''
    form.owner = props.record?.owner || ''
    form.reporter = props.record?.reporter || ''
    form.due_date = props.record?.due_date || ''
    form.impact = props.record?.impact || ''
    form.solution_plan = props.record?.solution_plan || ''
    form.estimate_hours = Number(props.record?.estimate_hours || 0)
    form.actual_hours = Number(props.record?.actual_hours || 0)
    form.notes = props.record?.notes || ''
  },
  { immediate: true },
)

async function submit() {
  const valid = await formRef.value?.validate().catch(() => false)

  if (!valid) {
    return
  }

  emit('submit', {
    tech_ticket_id: props.record?.id || '',
    ...form,
  })
}
</script>

<template>
  <el-dialog
    :model-value="modelValue"
    :title="record?.id ? '编辑研发待办' : '新增研发待办'"
    width="880px"
    :close-on-click-modal="false"
    destroy-on-close
    @update:model-value="emit('update:modelValue', $event)"
  >
    <el-form ref="formRef" :model="form" :rules="rules" label-position="top">
      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="关联 APP 项目" prop="ops_project_id">
            <el-select v-model="form.ops_project_id" style="width: 100%" placeholder="请选择 APP 项目">
              <el-option v-for="item in opsProjects" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="关联交付项目">
            <el-select v-model="form.project_id" style="width: 100%" clearable placeholder="可选">
              <el-option v-for="item in deliveryProjects" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="待办标题" prop="title">
            <el-input v-model="form.title" placeholder="例如：注册页埋点丢失" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="APP 模块">
            <el-input v-model="form.app_module" placeholder="例如：注册流程 / 图表首页 / 消息中心" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="6">
          <el-form-item label="类型">
            <el-select v-model="form.type" style="width: 100%">
              <el-option v-for="item in ticketTypes" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="6">
          <el-form-item label="状态">
            <el-select v-model="form.status" style="width: 100%">
              <el-option v-for="item in ticketStatuses" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="6">
          <el-form-item label="优先级">
            <el-select v-model="form.priority" style="width: 100%">
              <el-option v-for="item in priorities" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="6">
          <el-form-item label="严重度">
            <el-select v-model="form.severity" style="width: 100%">
              <el-option v-for="item in severities" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="8">
          <el-form-item label="负责人" prop="owner">
            <el-select v-model="form.owner" style="width: 100%" filterable allow-create default-first-option>
              <el-option v-for="item in owners" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="提出人" prop="reporter">
            <el-select v-model="form.reporter" style="width: 100%" filterable allow-create default-first-option>
              <el-option v-for="item in owners" :key="`${item.value}-reporter`" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="来源">
            <el-select v-model="form.source" style="width: 100%">
              <el-option v-for="item in sources" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="8">
          <el-form-item label="目标版本">
            <el-input v-model="form.app_version" placeholder="例如：v2.8.1" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="截止日期" prop="due_date">
            <el-date-picker v-model="form.due_date" type="date" value-format="YYYY-MM-DD" style="width: 100%" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="4">
          <el-form-item label="预估工时">
            <el-input-number v-model="form.estimate_hours" :min="0" :step="1" style="width: 100%" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="4">
          <el-form-item label="已耗工时">
            <el-input-number v-model="form.actual_hours" :min="0" :step="1" style="width: 100%" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-form-item label="影响说明" prop="impact">
        <el-input v-model="form.impact" type="textarea" :rows="3" placeholder="说明对运营、客户或交付带来的影响" />
      </el-form-item>

      <el-form-item label="处理方案" prop="solution_plan">
        <el-input v-model="form.solution_plan" type="textarea" :rows="3" placeholder="说明预计修复或升级方案" />
      </el-form-item>

      <el-form-item label="补充备注">
        <el-input v-model="form.notes" type="textarea" :rows="2" placeholder="可记录联调说明、发布时间、依赖项等" />
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="emit('update:modelValue', false)">取消</el-button>
      <el-button type="primary" :loading="loading" @click="submit">保存研发待办</el-button>
    </template>
  </el-dialog>
</template>
