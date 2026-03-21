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
  projects: {
    type: Array,
    default: () => [],
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
  project_id: '',
  title: '',
  assignee: '',
  status: 'todo',
  priority: 'medium',
  due_date: '',
  estimate_hours: 0,
  actual_hours: 0,
})

const rules = {
  project_id: [{ required: true, message: '请选择所属项目', trigger: 'change' }],
  title: [{ required: true, message: '请输入任务标题', trigger: 'blur' }],
  assignee: [{ required: true, message: '请输入负责人', trigger: 'blur' }],
  status: [{ required: true, message: '请选择任务状态', trigger: 'change' }],
  priority: [{ required: true, message: '请选择优先级', trigger: 'change' }],
  due_date: [{ required: true, message: '请选择截止日期', trigger: 'change' }],
}

watch(
  () => props.modelValue,
  (visible) => {
    if (!visible) {
      return
    }

    form.project_id = props.record?.project_id || props.projects[0]?.value || ''
    form.title = props.record?.title || ''
    form.assignee = props.record?.assignee || ''
    form.status = props.record?.status || 'todo'
    form.priority = props.record?.priority || 'medium'
    form.due_date = props.record?.due_date || new Date().toISOString().slice(0, 10)
    form.estimate_hours = Number(props.record?.estimate_hours || 0)
    form.actual_hours = Number(props.record?.actual_hours || 0)
  },
  { immediate: true },
)

async function submit() {
  const valid = await formRef.value?.validate().catch(() => false)

  if (!valid) {
    return
  }

  emit('submit', {
    task_id: props.record?.id || '',
    ...form,
  })
}
</script>

<template>
  <el-dialog
    :model-value="modelValue"
    :title="record?.id ? '编辑任务' : '新增任务'"
    width="760px"
    :close-on-click-modal="false"
    destroy-on-close
    @update:model-value="emit('update:modelValue', $event)"
  >
    <el-form ref="formRef" :model="form" :rules="rules" label-position="top">
      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="所属项目" prop="project_id">
            <el-select v-model="form.project_id" style="width: 100%">
              <el-option v-for="item in projects" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="任务标题" prop="title">
            <el-input v-model="form.title" placeholder="例如：首版报表页面" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="负责人" prop="assignee">
            <el-input v-model="form.assignee" placeholder="执行人" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="截止日期" prop="due_date">
            <el-input v-model="form.due_date" type="date" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="任务状态" prop="status">
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
          <el-form-item label="预估工时">
            <el-input-number v-model="form.estimate_hours" :min="0" :precision="1" style="width: 100%" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="实际工时">
            <el-input-number v-model="form.actual_hours" :min="0" :precision="1" style="width: 100%" />
          </el-form-item>
        </el-col>
      </el-row>
    </el-form>

    <template #footer>
      <el-button @click="emit('update:modelValue', false)">取消</el-button>
      <el-button type="primary" :loading="loading" @click="submit">保存任务</el-button>
    </template>
  </el-dialog>
</template>
