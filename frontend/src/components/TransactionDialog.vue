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
  categories: {
    type: Array,
    default: () => [],
  },
  paymentMethods: {
    type: Array,
    default: () => [],
  },
  transactionTypes: {
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
const fileList = ref([])
const removeAttachmentIds = ref([])
const form = reactive({
  date: '',
  type: 'expense',
  category: '',
  counterparty: '',
  amount: 0,
  payment_method: 'other',
  project_id: '',
  notes: '',
})

const rules = {
  date: [{ required: true, message: "请选择业务日期", trigger: "change" }],
  type: [{ required: true, message: "请选择收支方向", trigger: "change" }],
  category: [{ required: true, message: "请输入或选择科目分类", trigger: "blur" }],
  counterparty: [{ required: true, message: "请输入往来方", trigger: "blur" }],
  amount: [{ required: true, message: '请输入金额', trigger: 'change' }],
}

const isEditing = computed(() => Boolean(props.record?.id))

watch(
  () => props.modelValue,
  (visible) => {
    if (!visible) {
      return
    }

    fileList.value = []
    removeAttachmentIds.value = []
    form.date = props.record?.date || new Date().toISOString().slice(0, 10)
    form.type = props.record?.type || 'expense'
    form.category = props.record?.category || ''
    form.counterparty = props.record?.counterparty || ''
    form.amount = Number(props.record?.amount || 0)
    form.payment_method = props.record?.payment_method || 'other'
    form.project_id = props.record?.project_id || ''
    form.notes = props.record?.notes || ''
  },
  { immediate: true },
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
    transaction_id: props.record?.id || '',
    ...form,
    remove_attachment_ids: removeAttachmentIds.value,
    attachments: fileList.value.map((item) => item.raw).filter(Boolean),
  })
}
</script>

<template>
  <el-dialog
    :model-value="modelValue"
    :title="isEditing ? '编辑流水' : '新增流水'"
    width="760px"
    :close-on-click-modal="false"
    destroy-on-close
    @update:model-value="emit('update:modelValue', $event)"
  >
    <el-form ref="formRef" :model="form" :rules="rules" label-position="top">
      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="业务日期" prop="date">
            <el-input v-model="form.date" type="date" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="收支方向" prop="type">
            <el-select v-model="form.type" style="width: 100%">
              <el-option v-for="item in transactionTypes" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="科目分类" prop="category">
            <el-select v-model="form.category" filterable allow-create default-first-option style="width: 100%">
              <el-option v-for="item in categories" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="往来方" prop="counterparty">
            <el-input v-model="form.counterparty" placeholder="客户 / 供应商 / 员工" />
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
          <el-form-item label="支付方式">
            <el-select v-model="form.payment_method" style="width: 100%">
              <el-option v-for="item in paymentMethods" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
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
          <el-form-item label="说明">
            <el-input v-model="form.notes" placeholder="备注、用途、票据说明" />
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
        <div class="el-upload__text">新增附件图片，可上传付款截图、发票、回单</div>
      </el-upload>
    </el-form>

    <template #footer>
      <el-button @click="emit('update:modelValue', false)">取消</el-button>
      <el-button type="primary" :loading="loading" @click="submit">保存流水</el-button>
    </template>
  </el-dialog>
</template>
