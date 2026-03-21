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
  statuses: {
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
  owner: '',
  due_date: '',
  status: 'pending',
  progress: 0,
  deliverable: '',
  notes: '',
})

const rules = {
  ops_project_id: [{ required: true, message: '请选择所属运营项目', trigger: 'change' }],
  title: [{ required: true, message: '请输入里程碑标题', trigger: 'blur' }],
  owner: [{ required: true, message: '请输入负责人', trigger: 'blur' }],
  due_date: [{ required: true, message: '请选择截止日期', trigger: 'change' }],
  status: [{ required: true, message: '请选择当前状态', trigger: 'change' }],
}

watch(
  () => props.modelValue,
  (visible) => {
    if (!visible) {
      return
    }

    form.ops_project_id = props.record?.ops_project_id || props.opsProjects[0]?.value || ''
    form.title = props.record?.title || ''
    form.owner = props.record?.owner || ''
    form.due_date = props.record?.due_date || new Date().toISOString().slice(0, 10)
    form.status = props.record?.status || 'pending'
    form.progress = Number(props.record?.progress || 0)
    form.deliverable = props.record?.deliverable || ''
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
    ops_milestone_id: props.record?.id || '',
    ...form,
  })
}
</script>

<template>
  <el-dialog
    :model-value="modelValue"
    :title="record?.id ? '编辑里程碑' : '新建里程碑'"
    width="820px"
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
          <el-form-item label="里程碑标题" prop="title">
            <el-input v-model="form.title" placeholder="例如：投放方案确认 / 首批试用上线" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="负责人" prop="owner">
            <el-input v-model="form.owner" placeholder="填写里程碑负责人" />
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
          <el-form-item label="当前状态" prop="status">
            <el-select v-model="form.status" style="width: 100%">
              <el-option v-for="item in statuses" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="完成进度">
            <el-slider v-model="form.progress" :min="0" :max="100" show-input />
          </el-form-item>
        </el-col>
      </el-row>

      <el-form-item label="交付物">
        <el-input v-model="form.deliverable" placeholder="素材排期表 / 活动方案 / 埋点联调单" />
      </el-form-item>

      <el-form-item label="备注说明">
        <el-input v-model="form.notes" type="textarea" :rows="4" resize="vertical" />
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="emit('update:modelValue', false)">取消</el-button>
      <el-button type="primary" :loading="loading" @click="submit">保存里程碑</el-button>
    </template>
  </el-dialog>
</template>
