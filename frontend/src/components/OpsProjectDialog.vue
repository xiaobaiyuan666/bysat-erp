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
  statuses: {
    type: Array,
    default: () => [],
  },
  lifecycleStages: {
    type: Array,
    default: () => [],
  },
  priorities: {
    type: Array,
    default: () => [],
  },
  deliveryProjects: {
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
  name: '',
  app_name: '',
  app_version: '',
  lifecycle_stage: 'validation',
  business_line: '',
  manager: '',
  client_owner: '',
  core_metric: '',
  status: 'planning',
  priority: 'medium',
  budget: 0,
  actual_cost: 0,
  start_date: '',
  end_date: '',
  target: '',
  channel: '',
  project_id: '',
  description: '',
})

const rules = {
  name: [{ required: true, message: '请输入运营项目名称', trigger: 'blur' }],
  app_name: [{ required: true, message: '请输入 APP 名称', trigger: 'blur' }],
  lifecycle_stage: [{ required: true, message: '请选择生命周期阶段', trigger: 'change' }],
  manager: [{ required: true, message: '请输入运营负责人', trigger: 'blur' }],
  status: [{ required: true, message: '请选择项目状态', trigger: 'change' }],
  priority: [{ required: true, message: '请选择优先级', trigger: 'change' }],
  budget: [{ required: true, message: '请输入预算', trigger: 'change' }],
  start_date: [{ required: true, message: '请选择开始日期', trigger: 'change' }],
  end_date: [{ required: true, message: '请选择结束日期', trigger: 'change' }],
  target: [{ required: true, message: '请输入阶段目标', trigger: 'blur' }],
}

watch(
  () => props.modelValue,
  (visible) => {
    if (!visible) {
      return
    }

    form.name = props.record?.name || ''
    form.app_name = props.record?.app_name || ''
    form.app_version = props.record?.app_version || ''
    form.lifecycle_stage = props.record?.lifecycle_stage || 'validation'
    form.business_line = props.record?.business_line || ''
    form.manager = props.record?.manager || ''
    form.client_owner = props.record?.client_owner || ''
    form.core_metric = props.record?.core_metric || ''
    form.status = props.record?.status || 'planning'
    form.priority = props.record?.priority || 'medium'
    form.budget = Number(props.record?.budget || 0)
    form.actual_cost = Number(props.record?.actual_cost || 0)
    form.start_date = props.record?.start_date || new Date().toISOString().slice(0, 10)
    form.end_date = props.record?.end_date || new Date().toISOString().slice(0, 10)
    form.target = props.record?.target || ''
    form.channel = props.record?.channel || ''
    form.project_id = props.record?.project_id || ''
    form.description = props.record?.description || ''
  },
  { immediate: true },
)

async function submit() {
  const valid = await formRef.value?.validate().catch(() => false)

  if (!valid) {
    return
  }

  emit('submit', {
    ops_project_id: props.record?.id || '',
    ...form,
  })
}
</script>

<template>
  <el-dialog
    :model-value="modelValue"
    :title="record?.id ? '编辑运营项目' : '新建运营项目'"
    width="960px"
    :close-on-click-modal="false"
    destroy-on-close
    @update:model-value="emit('update:modelValue', $event)"
  >
    <el-form ref="formRef" :model="form" :rules="rules" label-position="top">
      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="运营项目名称" prop="name">
            <el-input v-model="form.name" placeholder="例如：CRM App 拉新增长 / BI 看板首发上线" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="APP 名称" prop="app_name">
            <el-input v-model="form.app_name" placeholder="例如：云帆 CRM / 工单助手 / BI 看板" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="当前版本">
            <el-input v-model="form.app_version" placeholder="例如：v2.8.0" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="生命周期阶段" prop="lifecycle_stage">
            <el-select v-model="form.lifecycle_stage" style="width: 100%">
              <el-option v-for="item in lifecycleStages" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="产品线">
            <el-input v-model="form.business_line" placeholder="CRM 产品线 / 数据产品线 / SaaS 运营产品线" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="核心指标">
            <el-input v-model="form.core_metric" placeholder="例如：注册转化率 / 7 日留存 / 续费率 / ARPU" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="运营负责人" prop="manager">
            <el-input v-model="form.manager" placeholder="填写运营负责人" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="产品负责人">
            <el-input v-model="form.client_owner" placeholder="产品 / 增长 / 客户成功负责人" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="项目状态" prop="status">
            <el-select v-model="form.status" style="width: 100%">
              <el-option v-for="item in statuses" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="优先级" prop="priority">
            <el-select v-model="form.priority" style="width: 100%">
              <el-option v-for="item in priorities" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="预算" prop="budget">
            <el-input-number v-model="form.budget" :min="0.01" :precision="2" style="width: 100%" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="已花成本">
            <el-input-number v-model="form.actual_cost" :min="0" :precision="2" style="width: 100%" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="开始日期" prop="start_date">
            <el-input v-model="form.start_date" type="date" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="结束日期" prop="end_date">
            <el-input v-model="form.end_date" type="date" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="关联交付项目">
            <el-select v-model="form.project_id" clearable style="width: 100%" placeholder="可选">
              <el-option v-for="item in deliveryProjects" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="增长动作 / 触达渠道">
            <el-input v-model="form.channel" placeholder="应用商店 / 信息流 / Push / 私域 / 客户成功动作" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-form-item label="生命周期目标" prop="target">
        <el-input v-model="form.target" type="textarea" :rows="3" resize="vertical" />
      </el-form-item>

      <el-form-item label="项目说明">
        <el-input v-model="form.description" type="textarea" :rows="4" resize="vertical" />
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="emit('update:modelValue', false)">取消</el-button>
      <el-button type="primary" :loading="loading" @click="submit">保存运营项目</el-button>
    </template>
  </el-dialog>
</template>
