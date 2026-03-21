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
  riskTypes: {
    type: Array,
    default: () => [],
  },
  riskLevels: {
    type: Array,
    default: () => [],
  },
  riskStatuses: {
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
  title: '',
  type: 'risk',
  level: 'medium',
  status: 'open',
  owner: '',
  due_date: '',
  impact: '',
  action_plan: '',
})

const rules = {
  ops_project_id: [{ required: true, message: '请选择所属运营项目', trigger: 'change' }],
  title: [{ required: true, message: '请输入风险/问题标题', trigger: 'blur' }],
  type: [{ required: true, message: '请选择类型', trigger: 'change' }],
  level: [{ required: true, message: '请选择等级', trigger: 'change' }],
  status: [{ required: true, message: '请选择状态', trigger: 'change' }],
  owner: [{ required: true, message: '请输入负责人', trigger: 'blur' }],
  due_date: [{ required: true, message: '请选择应对截止日期', trigger: 'change' }],
  impact: [{ required: true, message: '请输入影响说明', trigger: 'blur' }],
  action_plan: [{ required: true, message: '请输入处理动作', trigger: 'blur' }],
}

watch(
  () => props.modelValue,
  (visible) => {
    if (!visible) {
      return
    }

    form.ops_project_id = props.record?.ops_project_id || props.opsProjects[0]?.value || ''
    form.title = props.record?.title || ''
    form.type = props.record?.type || 'risk'
    form.level = props.record?.level || 'medium'
    form.status = props.record?.status || 'open'
    form.owner = props.record?.owner || ''
    form.due_date = props.record?.due_date || new Date().toISOString().slice(0, 10)
    form.impact = props.record?.impact || ''
    form.action_plan = props.record?.action_plan || ''
  },
  { immediate: true },
)

async function submit() {
  const valid = await formRef.value?.validate().catch(() => false)

  if (!valid) {
    return
  }

  emit('submit', {
    ops_risk_id: props.record?.id || '',
    ...form,
  })
}
</script>

<template>
  <el-dialog
    :model-value="modelValue"
    :title="record?.id ? '编辑风险问题' : '新建风险问题'"
    width="860px"
    :close-on-click-modal="false"
    destroy-on-close
    @update:model-value="emit('update:modelValue', $event)"
  >
    <el-form ref="formRef" :model="form" :rules="rules" label-position="top">
      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="所属运营项目" prop="ops_project_id">
            <el-select v-model="form.ops_project_id" style="width: 100%">
              <el-option v-for="item in opsProjects" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="标题" prop="title">
            <el-input v-model="form.title" placeholder="例如：核心渠道转化异常 / 客户排期变更" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="8">
          <el-form-item label="类型" prop="type">
            <el-select v-model="form.type" style="width: 100%">
              <el-option v-for="item in riskTypes" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="等级" prop="level">
            <el-select v-model="form.level" style="width: 100%">
              <el-option v-for="item in riskLevels" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="状态" prop="status">
            <el-select v-model="form.status" style="width: 100%">
              <el-option v-for="item in riskStatuses" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="负责人" prop="owner">
            <el-input v-model="form.owner" placeholder="负责跟进的人" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="应对截止日期" prop="due_date">
            <el-input v-model="form.due_date" type="date" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-form-item label="影响说明" prop="impact">
        <el-input v-model="form.impact" type="textarea" :rows="3" resize="vertical" />
      </el-form-item>

      <el-form-item label="处理动作" prop="action_plan">
        <el-input v-model="form.action_plan" type="textarea" :rows="3" resize="vertical" />
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="emit('update:modelValue', false)">取消</el-button>
      <el-button type="primary" :loading="loading" @click="submit">保存风险问题</el-button>
    </template>
  </el-dialog>
</template>
