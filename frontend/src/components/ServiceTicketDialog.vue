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
  techTickets: {
    type: Array,
    default: () => [],
  },
  assignees: {
    type: Array,
    default: () => [],
  },
  sources: {
    type: Array,
    default: () => [],
  },
  channels: {
    type: Array,
    default: () => [],
  },
  categories: {
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

function now() {
  return new Date().toISOString().slice(0, 19).replace('T', ' ')
}

const form = reactive({
  source: 'customer',
  customer: '',
  contact_name: '',
  contact_phone: '',
  channel: 'app',
  category: 'usage',
  title: '',
  summary: '',
  status: 'new',
  priority: 'medium',
  assignee: '',
  opened_at: '',
  last_follow_up_at: '',
  resolve_due_at: '',
  next_action: '',
  customer_notified: false,
  customer_notified_to: '',
  customer_notified_channel: '',
  customer_notified_at: '',
  customer_feedback_result: '',
  customer_confirmed: false,
  customer_confirmed_at: '',
  customer_confirmation_note: '',
  ops_project_id: '',
  project_id: '',
  tech_ticket_id: '',
  notes: '',
})

const rules = {
  source: [{ required: true, message: '请选择反馈来源', trigger: 'change' }],
  title: [{ required: true, message: '请输入问题标题', trigger: 'blur' }],
  summary: [{ required: true, message: '请填写问题描述', trigger: 'blur' }],
  assignee: [{ required: true, message: '请选择处理人', trigger: 'change' }],
  resolve_due_at: [{ required: true, message: '请选择处理时限', trigger: 'change' }],
  ops_project_id: [{ required: true, message: '请选择关联 APP 项目', trigger: 'change' }],
}

watch(
  () => props.modelValue,
  (visible) => {
    if (!visible) {
      return
    }

    form.source = props.record?.source || 'customer'
    form.customer = props.record?.customer || ''
    form.contact_name = props.record?.contact_name || ''
    form.contact_phone = props.record?.contact_phone || ''
    form.channel = props.record?.channel || 'app'
    form.category = props.record?.category || 'usage'
    form.title = props.record?.title || ''
    form.summary = props.record?.summary || ''
    form.status = props.record?.status || 'new'
    form.priority = props.record?.priority || 'medium'
    form.assignee = props.record?.assignee || ''
    form.opened_at = props.record?.opened_at || now()
    form.last_follow_up_at = props.record?.last_follow_up_at || form.opened_at
    form.resolve_due_at = props.record?.resolve_due_at || ''
    form.next_action = props.record?.next_action || ''
    form.customer_notified = Boolean(props.record?.customer_notified)
    form.customer_notified_to = props.record?.customer_notified_to || props.record?.contact_name || props.record?.customer || ''
    form.customer_notified_channel = props.record?.customer_notified_channel || props.record?.channel || 'app'
    form.customer_notified_at = props.record?.customer_notified_at || ''
    form.customer_feedback_result = props.record?.customer_feedback_result || ''
    form.customer_confirmed = Boolean(props.record?.customer_confirmed)
    form.customer_confirmed_at = props.record?.customer_confirmed_at || ''
    form.customer_confirmation_note = props.record?.customer_confirmation_note || ''
    form.ops_project_id = props.record?.ops_project_id || ''
    form.project_id = props.record?.project_id || ''
    form.tech_ticket_id = props.record?.tech_ticket_id || ''
    form.notes = props.record?.notes || ''
  },
  { immediate: true },
)

watch(
  () => form.customer_notified,
  (notified) => {
    if (notified) {
      if (!form.customer_notified_to) {
        form.customer_notified_to = form.contact_name || form.customer || ''
      }

      if (!form.customer_notified_channel) {
        form.customer_notified_channel = form.channel || 'app'
      }

      if (!form.customer_notified_at) {
        form.customer_notified_at = form.last_follow_up_at || form.opened_at || now()
      }

      return
    }

    form.customer_notified_to = ''
    form.customer_notified_channel = ''
    form.customer_notified_at = ''
    form.customer_feedback_result = ''
    form.customer_confirmed = false
    form.customer_confirmed_at = ''
    form.customer_confirmation_note = ''
  },
)

watch(
  () => form.customer_confirmed,
  (confirmed) => {
    if (!confirmed) {
      form.customer_confirmed_at = ''
      form.customer_confirmation_note = ''
      return
    }

    form.customer_notified = true

    if (!form.customer_confirmed_at) {
      form.customer_confirmed_at = form.customer_notified_at || form.last_follow_up_at || now()
    }
  },
)

async function submit() {
  const valid = await formRef.value?.validate().catch(() => false)

  if (!valid) {
    return
  }

  emit('submit', {
    service_ticket_id: props.record?.id || '',
    ...form,
  })
}
</script>

<template>
  <el-dialog
    :model-value="modelValue"
    :title="record?.id ? '编辑问题记录' : '新建问题记录'"
    width="940px"
    :close-on-click-modal="false"
    destroy-on-close
    @update:model-value="emit('update:modelValue', $event)"
  >
    <el-form ref="formRef" :model="form" :rules="rules" label-position="top">
      <el-row :gutter="16">
        <el-col :xs="24" :md="8">
          <el-form-item label="反馈来源" prop="source">
            <el-select v-model="form.source" style="width: 100%">
              <el-option v-for="item in sources" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="反馈主体">
            <el-input v-model="form.customer" placeholder="例如：重点客户 / 销售支持组 / 领导" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="反馈人">
            <el-input v-model="form.contact_name" placeholder="提交人姓名" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="8">
          <el-form-item label="联系方式">
            <el-input v-model="form.contact_phone" placeholder="手机号 / 企业微信 / 邮箱" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="反馈渠道">
            <el-select v-model="form.channel" style="width: 100%">
              <el-option v-for="item in channels" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="问题分类">
            <el-select v-model="form.category" style="width: 100%">
              <el-option v-for="item in categories" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="问题标题" prop="title">
            <el-input v-model="form.title" placeholder="例如：筛选切换后图表空白" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="关联 APP 项目" prop="ops_project_id">
            <el-select v-model="form.ops_project_id" style="width: 100%" placeholder="请选择 APP 项目">
              <el-option v-for="item in opsProjects" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="8">
          <el-form-item label="关联交付项目">
            <el-select v-model="form.project_id" style="width: 100%" clearable placeholder="可选">
              <el-option v-for="item in deliveryProjects" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="关联研发待办">
            <el-select v-model="form.tech_ticket_id" style="width: 100%" clearable filterable placeholder="可关联缺陷 / 升级需求">
              <el-option v-for="item in techTickets" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="下一步动作">
            <el-input v-model="form.next_action" placeholder="例如：今晚 20:00 回告客户" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="6">
          <el-form-item label="状态">
            <el-select v-model="form.status" style="width: 100%">
              <el-option v-for="item in statuses" :key="item.value" :label="item.label" :value="item.value" />
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
          <el-form-item label="处理人" prop="assignee">
            <el-select v-model="form.assignee" style="width: 100%" filterable allow-create default-first-option>
              <el-option v-for="item in assignees" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="6">
          <el-form-item label="处理时限" prop="resolve_due_at">
            <el-date-picker
              v-model="form.resolve_due_at"
              type="datetime"
              value-format="YYYY-MM-DD HH:mm:ss"
              style="width: 100%"
            />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="登记时间">
            <el-date-picker
              v-model="form.opened_at"
              type="datetime"
              value-format="YYYY-MM-DD HH:mm:ss"
              style="width: 100%"
            />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="最近跟进">
            <el-date-picker
              v-model="form.last_follow_up_at"
              type="datetime"
              value-format="YYYY-MM-DD HH:mm:ss"
              style="width: 100%"
            />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="6">
          <el-form-item label="已回告客户">
            <el-switch v-model="form.customer_notified" inline-prompt active-text="是" inactive-text="否" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="回告对象">
            <el-input v-model="form.customer_notified_to" :disabled="!form.customer_notified" placeholder="例如：王琳 / 客户项目群" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="10">
          <el-form-item label="回告方式">
            <el-select v-model="form.customer_notified_channel" style="width: 100%" :disabled="!form.customer_notified" placeholder="请选择回告方式">
              <el-option v-for="item in channels" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="回告时间">
            <el-date-picker
              v-model="form.customer_notified_at"
              type="datetime"
              value-format="YYYY-MM-DD HH:mm:ss"
              style="width: 100%"
              :disabled="!form.customer_notified"
              placeholder="未回告客户时可留空"
            />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="客户已确认">
            <el-switch v-model="form.customer_confirmed" inline-prompt active-text="是" inactive-text="否" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-form-item label="问题描述" prop="summary">
        <el-input v-model="form.summary" type="textarea" :rows="3" placeholder="记录问题场景、影响范围和当前处理情况" />
      </el-form-item>

      <el-form-item label="回告结果">
        <el-input
          v-model="form.customer_feedback_result"
          type="textarea"
          :rows="2"
          :disabled="!form.customer_notified"
          placeholder="填写已经回告给客户的结论、口径或当前处理结果"
        />
      </el-form-item>

      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="确认时间">
            <el-date-picker
              v-model="form.customer_confirmed_at"
              type="datetime"
              value-format="YYYY-MM-DD HH:mm:ss"
              style="width: 100%"
              :disabled="!form.customer_confirmed"
              placeholder="客户未确认时可留空"
            />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="确认说明">
            <el-input
              v-model="form.customer_confirmation_note"
              :disabled="!form.customer_confirmed"
              placeholder="例如：客户确认按周一上线方案推进"
            />
          </el-form-item>
        </el-col>
      </el-row>

      <el-form-item label="处理备注">
        <el-input v-model="form.notes" type="textarea" :rows="2" placeholder="记录内部协同、领导反馈或补充说明" />
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="emit('update:modelValue', false)">取消</el-button>
      <el-button type="primary" :loading="loading" @click="submit">保存问题记录</el-button>
    </template>
  </el-dialog>
</template>
