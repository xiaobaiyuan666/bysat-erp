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
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'submit'])
const formRef = ref(null)
const form = reactive({
  ops_project_id: '',
  report_date: '',
  owner: '',
  summary: '',
  result: '',
  next_actions: '',
  blockers: '',
})

const rules = {
  ops_project_id: [{ required: true, message: '请选择所属运营项目', trigger: 'change' }],
  report_date: [{ required: true, message: '请选择汇报日期', trigger: 'change' }],
  owner: [{ required: true, message: '请输入汇报人', trigger: 'blur' }],
  summary: [{ required: true, message: '请输入进展概述', trigger: 'blur' }],
  next_actions: [{ required: true, message: '请输入下周动作', trigger: 'blur' }],
}

watch(
  () => props.modelValue,
  (visible) => {
    if (!visible) {
      return
    }

    form.ops_project_id = props.record?.ops_project_id || props.opsProjects[0]?.value || ''
    form.report_date = props.record?.report_date || new Date().toISOString().slice(0, 10)
    form.owner = props.record?.owner || ''
    form.summary = props.record?.summary || ''
    form.result = props.record?.result || ''
    form.next_actions = props.record?.next_actions || ''
    form.blockers = props.record?.blockers || ''
  },
  { immediate: true },
)

async function submit() {
  const valid = await formRef.value?.validate().catch(() => false)

  if (!valid) {
    return
  }

  emit('submit', {
    ops_update_id: props.record?.id || '',
    ...form,
  })
}
</script>

<template>
  <el-dialog
    :model-value="modelValue"
    :title="record?.id ? '编辑运营周报' : '新建运营周报'"
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
          <el-form-item label="汇报日期" prop="report_date">
            <el-input v-model="form.report_date" type="date" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-form-item label="汇报人" prop="owner">
        <el-input v-model="form.owner" placeholder="项目负责人 / 运营负责人" />
      </el-form-item>

      <el-form-item label="本周进展" prop="summary">
        <el-input v-model="form.summary" type="textarea" :rows="3" resize="vertical" />
      </el-form-item>

      <el-form-item label="阶段结果">
        <el-input v-model="form.result" type="textarea" :rows="3" resize="vertical" />
      </el-form-item>

      <el-form-item label="下周动作" prop="next_actions">
        <el-input v-model="form.next_actions" type="textarea" :rows="3" resize="vertical" />
      </el-form-item>

      <el-form-item label="阻塞项">
        <el-input v-model="form.blockers" type="textarea" :rows="3" resize="vertical" placeholder="没有可留空" />
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="emit('update:modelValue', false)">取消</el-button>
      <el-button type="primary" :loading="loading" @click="submit">保存周报</el-button>
    </template>
  </el-dialog>
</template>
