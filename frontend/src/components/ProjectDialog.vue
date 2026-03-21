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
  priorities: {
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
  client: '',
  owner: '',
  status: 'planning',
  priority: 'medium',
  budget: 0,
  start_date: '',
  due_date: '',
  description: '',
})

const rules = {
  name: [{ required: true, message: '请输入项目名称', trigger: 'blur' }],
  owner: [{ required: true, message: '请输入负责人', trigger: 'blur' }],
  status: [{ required: true, message: '请选择项目状态', trigger: 'change' }],
  priority: [{ required: true, message: '请选择优先级', trigger: 'change' }],
  budget: [{ required: true, message: '请输入预算', trigger: 'change' }],
  start_date: [{ required: true, message: '请选择开始日期', trigger: 'change' }],
  due_date: [{ required: true, message: '请选择截止日期', trigger: 'change' }],
}

watch(
  () => props.modelValue,
  (visible) => {
    if (!visible) {
      return
    }

    form.name = props.record?.name || ''
    form.client = props.record?.client || ''
    form.owner = props.record?.owner || ''
    form.status = props.record?.status || 'planning'
    form.priority = props.record?.priority || 'medium'
    form.budget = Number(props.record?.budget || 0)
    form.start_date = props.record?.start_date || new Date().toISOString().slice(0, 10)
    form.due_date = props.record?.due_date || new Date().toISOString().slice(0, 10)
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
    project_id: props.record?.id || '',
    ...form,
  })
}
</script>

<template>
  <el-dialog
    :model-value="modelValue"
    :title="record?.id ? '编辑项目' : '新增项目'"
    width="780px"
    :close-on-click-modal="false"
    destroy-on-close
    @update:model-value="emit('update:modelValue', $event)"
  >
    <el-form ref="formRef" :model="form" :rules="rules" label-position="top">
      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="项目名称" prop="name">
            <el-input v-model="form.name" placeholder="官网重构 / 客服工单 SaaS" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="客户/业务线">
            <el-input v-model="form.client" placeholder="客户名称或内部产品线" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="负责人" prop="owner">
            <el-input v-model="form.owner" placeholder="填写项目负责人" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="预算" prop="budget">
            <el-input-number v-model="form.budget" :min="0.01" :precision="2" style="width: 100%" />
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
          <el-form-item label="开始日期" prop="start_date">
            <el-input v-model="form.start_date" type="date" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="计划截止" prop="due_date">
            <el-input v-model="form.due_date" type="date" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-form-item label="项目说明">
        <el-input v-model="form.description" type="textarea" :rows="4" resize="vertical" />
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="emit('update:modelValue', false)">取消</el-button>
      <el-button type="primary" :loading="loading" @click="submit">保存项目</el-button>
    </template>
  </el-dialog>
</template>
