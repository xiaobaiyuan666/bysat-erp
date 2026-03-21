<script setup>
import { computed, reactive, ref, watch } from 'vue'

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
  invoiceKinds: {
    type: Array,
    default: () => [],
  },
  invoiceStatuses: {
    type: Object,
    default: () => ({ receivable: [], payable: [] }),
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'submit'])
const formRef = ref(null)
const fileList = ref([])
const removeAttachmentIds = ref([])
const form = reactive({
  kind: 'receivable',
  title: '',
  counterparty: '',
  amount: 0,
  due_date: '',
  status: 'pending',
  project_id: '',
  notes: '',
})

const rules = {
  kind: [{ required: true, message: "请选择单据类型", trigger: "change" }],
  title: [{ required: true, message: '请输入单据标题', trigger: 'blur' }],
  counterparty: [{ required: true, message: "请输入往来方", trigger: "blur" }],
  amount: [{ required: true, message: '请输入金额', trigger: 'change' }],
  due_date: [{ required: true, message: "请选择到期日期", trigger: "change" }],
  status: [{ required: true, message: '请选择状态', trigger: 'change' }],
}

const isEditing = computed(() => Boolean(props.record?.id))
const currentStatuses = computed(() => props.invoiceStatuses?.[form.kind] || [])

watch(
  () => props.modelValue,
  (visible) => {
    if (!visible) {
      return
    }

    fileList.value = []
    removeAttachmentIds.value = []
    form.kind = props.record?.kind || 'receivable'
    form.title = props.record?.title || ''
    form.counterparty = props.record?.counterparty || ''
    form.amount = Number(props.record?.amount || 0)
    form.due_date = props.record?.due_date || new Date().toISOString().slice(0, 10)
    form.status = props.record?.status || 'pending'
    form.project_id = props.record?.project_id || ''
    form.notes = props.record?.notes || ''
  },
  { immediate: true },
)

watch(
  () => form.kind,
  (kind) => {
    const nextStatuses = props.invoiceStatuses?.[kind] || []
    if (!nextStatuses.some((item) => item.value === form.status)) {
      form.status = nextStatuses[0]?.value || 'pending'
    }
  },
)

function syncFiles(_file, files) {
  fileList.value = files
}

async function submit() {
  const valid = await formRef.value?.validate().catch(() => false)

  if (!valid) {
    return
  }

  emit('submit', {
    invoice_id: props.record?.id || '',
    ...form,
    remove_attachment_ids: removeAttachmentIds.value,
    attachments: fileList.value.map((item) => item.raw).filter(Boolean),
  })
}
</script>

<template>
  <el-dialog
    :model-value="modelValue"
    :title="isEditing ? '编辑应收应付' : '新增应收应付'"
    width="760px"
    :close-on-click-modal="false"
    destroy-on-close
    @update:model-value="emit('update:modelValue', $event)"
  >
    <el-form ref="formRef" :model="form" :rules="rules" label-position="top">
      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="单据类型" prop="kind">
            <el-select v-model="form.kind" style="width: 100%">
              <el-option v-for="item in invoiceKinds" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="状态" prop="status">
            <el-select v-model="form.status" style="width: 100%">
              <el-option v-for="item in currentStatuses" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="单据标题" prop="title">
            <el-input v-model="form.title" placeholder="例如：官网重构尾款" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="往来方" prop="counterparty">
            <el-input v-model="form.counterparty" placeholder="客户 / 供应商" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="金额" prop="amount">
            <el-input-number v-model="form.amount" :min="0.01" :precision="2" style="width: 100%" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="到期日期" prop="due_date">
            <el-input v-model="form.due_date" type="date" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="关联项目">
            <el-select v-model="form.project_id" clearable style="width: 100%">
              <el-option v-for="item in projects" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="备注">
            <el-input v-model="form.notes" placeholder="回款节点、付款说明、补票说明" />
          </el-form-item>
        </el-col>
      </el-row>

      <div v-if="record?.attachments?.length" class="dialog-attachments">
        <div class="dialog-attachments__title">已有附件</div>
        <el-checkbox-group v-model="removeAttachmentIds">
          <div v-for="attachment in record.attachments" :key="attachment.id" class="dialog-attachments__row">
            <span>{{ attachment.name }}</span>
            <el-checkbox :label="attachment.id">本次移除</el-checkbox>
          </div>
        </el-checkbox-group>
      </div>

      <el-upload
        drag
        multiple
        :auto-upload="false"
        :file-list="fileList"
        accept="image/*"
        :on-change="syncFiles"
        :on-remove="syncFiles"
      >
        <div class="el-upload__text">可直接上传合同页、发票、付款凭证图片</div>
      </el-upload>
    </el-form>

    <template #footer>
      <el-button @click="emit('update:modelValue', false)">取消</el-button>
      <el-button type="primary" :loading="loading" @click="submit">保存单据</el-button>
    </template>
  </el-dialog>
</template>
