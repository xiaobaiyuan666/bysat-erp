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
  updateTypes: {
    type: Array,
    default: () => [],
  },
  visibilities: {
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

function now() {
  return new Date().toISOString().slice(0, 19).replace('T', ' ')
}

const form = reactive({
  type: 'follow_up',
  visibility: 'internal',
  content: '',
  status: '',
  next_action: '',
  created_at: '',
})

const rules = {
  type: [{ required: true, message: '请选择跟进类型', trigger: 'change' }],
  visibility: [{ required: true, message: '请选择跟进对象', trigger: 'change' }],
  content: [{ required: true, message: '请填写跟进内容', trigger: 'blur' }],
}

watch(
  () => props.modelValue,
  (visible) => {
    if (!visible) {
      return
    }

    form.type = 'follow_up'
    form.visibility = 'internal'
    form.content = ''
    form.status = props.record?.status || ''
    form.next_action = props.record?.next_action || ''
    form.created_at = now()
  },
  { immediate: true },
)

watch(
  () => form.type,
  (type) => {
    if (type === 'leader') {
      form.visibility = 'leader'
      return
    }

    if (type === 'release') {
      form.visibility = 'customer'
      return
    }

    if (type === 'status' || type === 'internal') {
      form.visibility = 'internal'
    }
  },
)

async function submit() {
  const valid = await formRef.value?.validate().catch(() => false)

  if (!valid || !props.record?.id) {
    return
  }

  emit('submit', {
    service_ticket_id: props.record.id,
    ...form,
  })
}
</script>

<template>
  <el-dialog
    :model-value="modelValue"
    title="新增问题跟进"
    width="760px"
    :close-on-click-modal="false"
    destroy-on-close
    @update:model-value="emit('update:modelValue', $event)"
  >
    <el-form ref="formRef" :model="form" :rules="rules" label-position="top">
      <el-row :gutter="16">
        <el-col :xs="24" :md="8">
          <el-form-item label="跟进类型" prop="type">
            <el-select v-model="form.type" style="width: 100%">
              <el-option v-for="item in updateTypes" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="跟进对象" prop="visibility">
            <el-select v-model="form.visibility" style="width: 100%">
              <el-option v-for="item in visibilities" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="同步状态">
            <el-select v-model="form.status" style="width: 100%" clearable placeholder="不改状态可留空">
              <el-option v-for="item in statuses" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="跟进时间">
            <el-date-picker
              v-model="form.created_at"
              type="datetime"
              value-format="YYYY-MM-DD HH:mm:ss"
              style="width: 100%"
            />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="下一步动作">
            <el-input v-model="form.next_action" placeholder="例如：明早回告客户 / 晚上确认发版结果" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-form-item label="跟进内容" prop="content">
        <el-input
          v-model="form.content"
          type="textarea"
          :rows="4"
          placeholder="记录本次沟通结论、排查结果、客户回复或领导要求。"
        />
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="emit('update:modelValue', false)">取消</el-button>
      <el-button type="primary" :loading="loading" @click="submit">保存跟进</el-button>
    </template>
  </el-dialog>
</template>
