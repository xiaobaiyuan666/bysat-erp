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
  techTickets: {
    type: Array,
    default: () => [],
  },
  serviceTickets: {
    type: Array,
    default: () => [],
  },
  statuses: {
    type: Array,
    default: () => [],
  },
  syncStatuses: {
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
  version: '',
  title: '',
  status: 'planned',
  owner: '',
  release_date: '',
  channel: '',
  tech_ticket_ids: [],
  service_ticket_ids: [],
  release_notes: '',
  verification_summary: '',
  customer_sync_status: 'pending',
  customer_sync_note: '',
  release_result: '',
  rollback_plan: '',
  rollback_ready: true,
  notes: '',
})

const rules = {
  ops_project_id: [{ required: true, message: '请选择 APP 项目', trigger: 'change' }],
  version: [{ required: true, message: '请输入版本号', trigger: 'blur' }],
  title: [{ required: true, message: '请输入发布标题', trigger: 'blur' }],
  owner: [{ required: true, message: '请输入负责人', trigger: 'blur' }],
  release_date: [{ required: true, message: '请选择发布时间', trigger: 'change' }],
  release_notes: [{ required: true, message: '请填写发布说明', trigger: 'blur' }],
  rollback_plan: [{ required: true, message: '请填写回滚预案', trigger: 'blur' }],
}

watch(
  () => props.modelValue,
  (visible) => {
    if (!visible) {
      return
    }

    form.ops_project_id = props.record?.ops_project_id || ''
    form.version = props.record?.version || ''
    form.title = props.record?.title || ''
    form.status = props.record?.status || 'planned'
    form.owner = props.record?.owner || ''
    form.release_date = props.record?.release_date || ''
    form.channel = props.record?.channel || ''
    form.tech_ticket_ids = Array.isArray(props.record?.tech_ticket_ids) ? [...props.record.tech_ticket_ids] : []
    form.service_ticket_ids = Array.isArray(props.record?.service_ticket_ids) ? [...props.record.service_ticket_ids] : []
    form.release_notes = props.record?.release_notes || ''
    form.verification_summary = props.record?.verification_summary || ''
    form.customer_sync_status = props.record?.customer_sync_status || (form.service_ticket_ids.length ? 'pending' : 'not_needed')
    form.customer_sync_note = props.record?.customer_sync_note || ''
    form.release_result = props.record?.release_result || ''
    form.rollback_plan = props.record?.rollback_plan || ''
    form.rollback_ready = props.record?.rollback_ready ?? true
    form.notes = props.record?.notes || ''
  },
  { immediate: true },
)

watch(
  () => form.service_ticket_ids.length,
  (count) => {
    if (count === 0 && form.customer_sync_status === 'pending') {
      form.customer_sync_status = 'not_needed'
    }

    if (count > 0 && form.customer_sync_status === 'not_needed') {
      form.customer_sync_status = 'pending'
    }
  },
)

async function submit() {
  const valid = await formRef.value?.validate().catch(() => false)

  if (!valid) {
    return
  }

  emit('submit', {
    ops_release_id: props.record?.id || '',
    ...form,
  })
}
</script>

<template>
  <el-dialog
    :model-value="modelValue"
    :title="record?.id ? '编辑版本发布' : '新增版本发布'"
    width="920px"
    :close-on-click-modal="false"
    destroy-on-close
    @update:model-value="emit('update:modelValue', $event)"
  >
    <el-form ref="formRef" :model="form" :rules="rules" label-position="top">
      <el-row :gutter="16">
        <el-col :xs="24" :md="8">
          <el-form-item label="APP 项目" prop="ops_project_id">
            <el-select v-model="form.ops_project_id" style="width: 100%">
              <el-option v-for="item in opsProjects" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="版本号" prop="version">
            <el-input v-model="form.version" placeholder="v2.8.1" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="状态">
            <el-select v-model="form.status" style="width: 100%">
              <el-option v-for="item in statuses" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="发布标题" prop="title">
            <el-input v-model="form.title" placeholder="例如：注册流程优化上线及领导反馈修复" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="6">
          <el-form-item label="负责人" prop="owner">
            <el-input v-model="form.owner" placeholder="填写负责人姓名" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="6">
          <el-form-item label="发布时间" prop="release_date">
            <el-date-picker v-model="form.release_date" type="date" value-format="YYYY-MM-DD" style="width: 100%" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="10">
          <el-form-item label="发布渠道">
            <el-input v-model="form.channel" placeholder="例如：灰度 30% / 全量 / 指定账号" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="14">
          <el-form-item label="关联研发待办">
            <el-select v-model="form.tech_ticket_ids" multiple collapse-tags filterable clearable style="width: 100%">
              <el-option v-for="item in techTickets" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="16">
          <el-form-item label="关联问题记录">
            <el-select v-model="form.service_ticket_ids" multiple collapse-tags filterable clearable style="width: 100%">
              <el-option v-for="item in serviceTickets" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="客户回告">
            <el-select v-model="form.customer_sync_status" style="width: 100%">
              <el-option v-for="item in syncStatuses" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-form-item label="发布说明" prop="release_notes">
        <el-input
          v-model="form.release_notes"
          type="textarea"
          :rows="3"
          placeholder="说明本次变更内容、影响范围，以及运营或客服需要重点关注的事项。"
        />
      </el-form-item>

      <el-form-item label="验证结论">
        <el-input
          v-model="form.verification_summary"
          type="textarea"
          :rows="3"
          placeholder="记录测试、灰度或回归验证结果。"
        />
      </el-form-item>

      <el-form-item label="回告说明">
        <el-input
          v-model="form.customer_sync_note"
          type="textarea"
          :rows="2"
          placeholder="记录由谁回告客户，以及当前回复口径。"
        />
      </el-form-item>

      <el-form-item label="发布结果">
        <el-input
          v-model="form.release_result"
          type="textarea"
          :rows="2"
          placeholder="记录发布时间、稳定性结论和后续观察。"
        />
      </el-form-item>

      <el-form-item label="回滚预案" prop="rollback_plan">
        <el-input
          v-model="form.rollback_plan"
          type="textarea"
          :rows="3"
          placeholder="写明开关、回滚路径、负责人和时间窗口。"
        />
      </el-form-item>

      <el-form-item>
        <el-checkbox v-model="form.rollback_ready">回滚预案已准备</el-checkbox>
      </el-form-item>

      <el-form-item label="备注">
        <el-input
          v-model="form.notes"
          type="textarea"
          :rows="2"
          placeholder="补充客服、运营、灰度范围或现场联动说明。"
        />
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="emit('update:modelValue', false)">取消</el-button>
      <el-button type="primary" :loading="loading" @click="submit">保存版本</el-button>
    </template>
  </el-dialog>
</template>
