<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'

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
  materials: {
    type: Array,
    default: () => [],
  },
  categories: {
    type: Array,
    default: () => [],
  },
  archiveStatuses: {
    type: Array,
    default: () => [],
  },
  owners: {
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
const fileInputRef = ref(null)
const selectedFile = ref(null)
const selectedFileName = ref('')

function today() {
  return new Date().toISOString().slice(0, 10)
}

const form = reactive({
  ops_project_id: '',
  title: '',
  category: 'manual',
  owner: '',
  version_tag: '',
  applicable_versions: '',
  expires_on: '',
  archive_status: 'active',
  replacement_material_id: '',
  source_mode: 'link',
  download_name: '',
  download_url: '',
  updated_on: '',
  notes: '',
})

const rules = {
  ops_project_id: [{ required: true, message: "请选择所属 APP 项目", trigger: "change" }],
  title: [{ required: true, message: "请输入资料标题", trigger: "blur" }],
  owner: [{ required: true, message: "请选择负责人", trigger: "change" }],
}

const replacementOptions = computed(() => {
  const currentId = props.record?.id || ''
  return (props.materials || []).filter((item) => item.value !== currentId)
})

watch(
  () => props.modelValue,
  (visible) => {
    if (!visible) {
      return
    }

    form.ops_project_id = props.record?.ops_project_id || ''
    form.title = props.record?.title || ''
    form.category = props.record?.category || 'manual'
    form.owner = props.record?.owner || ''
    form.version_tag = props.record?.version_tag || ''
    form.applicable_versions = props.record?.applicable_versions || ''
    form.expires_on = props.record?.expires_on || ''
    form.archive_status = props.record?.archive_status || 'active'
    form.replacement_material_id = props.record?.replacement_material_id || ''
    form.source_mode = props.record?.is_uploaded ? 'upload' : 'link'
    form.download_name = props.record?.download_name || ''
    form.download_url = props.record?.is_uploaded ? '' : (props.record?.download_url || '')
    form.updated_on = props.record?.updated_on || today()
    form.notes = props.record?.notes || ''

    selectedFile.value = null
    selectedFileName.value = ''

    if (fileInputRef.value) {
      fileInputRef.value.value = ''
    }
  },
  { immediate: true },
)

function handleFileChange(event) {
  const [file] = event.target.files || []
  selectedFile.value = file || null
  selectedFileName.value = file?.name || ''

  if (file?.name) {
    form.download_name = file.name
  }
}

function clearSelectedFile() {
  selectedFile.value = null
  selectedFileName.value = ''

  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

async function submit() {
  const valid = await formRef.value?.validate().catch(() => false)

  if (!valid) {
    return
  }

  const hasExistingUploaded = Boolean(props.record?.is_uploaded)

  if (form.source_mode === 'link' && (!form.download_name.trim() || !form.download_url.trim())) {
    ElMessage.warning("请输入下载文件名和资料链接")
    return
  }

  if (form.source_mode === 'upload' && !selectedFile.value && !hasExistingUploaded) {
    ElMessage.warning("请先选择要上传的资料文件")
    return
  }

  emit('submit', {
    ops_material_id: props.record?.id || '',
    ops_project_id: form.ops_project_id,
    title: form.title,
    category: form.category,
    owner: form.owner,
    version_tag: form.version_tag,
    applicable_versions: form.applicable_versions,
    expires_on: form.expires_on,
    archive_status: form.archive_status,
    replacement_material_id: form.replacement_material_id,
    download_name: form.download_name,
    download_url: form.source_mode === 'link' ? form.download_url : '',
    updated_on: form.updated_on,
    notes: form.notes,
    remove_existing_upload: form.source_mode === 'link' && hasExistingUploaded ? '1' : '',
    material_file: form.source_mode === 'upload' ? selectedFile.value : null,
  })
}
</script>

<template>
  <el-dialog
    :model-value="modelValue"
    :title="record?.id ? '编辑内部资料' : '新增内部资料'"
    width="780px"
    :close-on-click-modal="false"
    destroy-on-close
    @update:model-value="emit('update:modelValue', $event)"
  >
    <el-form ref="formRef" :model="form" :rules="rules" label-position="top">
      <el-row :gutter="16">
        <el-col :xs="24" :md="12">
          <el-form-item label="所属 APP 项目" prop="ops_project_id">
            <el-select v-model="form.ops_project_id" style="width: 100%" placeholder="请选择 APP 项目">
              <el-option v-for="item in opsProjects" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-form-item label="资料标题" prop="title">
            <el-input v-model="form.title" placeholder="例如：上线常见问题 / 发布说明 / 培训脚本" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="8">
          <el-form-item label="资料分类">
            <el-select v-model="form.category" style="width: 100%">
              <el-option v-for="item in categories" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="负责人" prop="owner">
            <el-select v-model="form.owner" style="width: 100%" filterable allow-create default-first-option>
              <el-option v-for="item in owners" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="更新日期">
            <el-date-picker v-model="form.updated_on" type="date" value-format="YYYY-MM-DD" style="width: 100%" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="10">
          <el-form-item label="资料版本">
            <el-input v-model="form.version_tag" placeholder="例如：v3.2.2 / 2026.03 / 首发版" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="14">
          <el-form-item label="适用版本">
            <el-input v-model="form.applicable_versions" placeholder="例如：v3.2.x，多个版本可用逗号分隔" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="16">
        <el-col :xs="24" :md="8">
          <el-form-item label="失效时间">
            <el-date-picker
              v-model="form.expires_on"
              type="date"
              value-format="YYYY-MM-DD"
              style="width: 100%"
              placeholder="不设置则长期有效"
            />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="归档状态">
            <el-select v-model="form.archive_status" style="width: 100%">
              <el-option v-for="item in archiveStatuses" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-form-item label="替代资料">
            <el-select v-model="form.replacement_material_id" style="width: 100%" clearable placeholder="当前资料已被哪份替代">
              <el-option v-for="item in replacementOptions" :key="item.value" :label="item.label" :value="item.value" />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <el-form-item label="资料来源">
        <el-radio-group v-model="form.source_mode">
          <el-radio-button label="upload">上传文件</el-radio-button>
          <el-radio-button label="link">外部链接</el-radio-button>
        </el-radio-group>
      </el-form-item>

      <div v-if="form.source_mode === 'upload'" class="upload-card">
        <div class="upload-card__head">
          <div>
            <strong>上传资料文件</strong>
            <p>支持 PDF、Office 文档、TXT、ZIP、JPG、PNG、WEBP，单个文件不超过 20MB。</p>
          </div>
          <el-button @click="fileInputRef?.click()">选择文件</el-button>
        </div>
        <input ref="fileInputRef" class="upload-card__input" type="file" @change="handleFileChange" />
        <div v-if="selectedFileName || record?.is_uploaded" class="upload-card__file">
          <div>
            <strong>{{ selectedFileName || record?.download_name || "暂未选择文件" }}</strong>
            <span v-if="selectedFileName">保存后会替换当前资料文件。</span>
            <span v-else>未重新选择文件时，会保留当前已上传文件。</span>
          </div>
          <el-button v-if="selectedFileName" link type="danger" @click="clearSelectedFile">清空</el-button>
        </div>
      </div>

      <el-row v-else :gutter="16">
        <el-col :xs="24" :md="10">
          <el-form-item label="下载文件名">
            <el-input v-model="form.download_name" placeholder="例如：app-onboarding-script.pdf" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :md="14">
          <el-form-item label="下载地址">
            <el-input v-model="form.download_url" placeholder="例如：https://docs.example.com/app-onboarding-script.pdf" />
          </el-form-item>
        </el-col>
      </el-row>

      <el-form-item v-if="form.source_mode === 'upload'" label="下载文件名">
        <el-input v-model="form.download_name" placeholder="默认使用上传文件名，也可以手动调整显示名称" />
      </el-form-item>

      <el-form-item label="资料说明">
        <el-input
          v-model="form.notes"
          type="textarea"
          :rows="3"
          placeholder="说明这份资料给谁用、适用哪个版本，以及是否已有替代版本"
        />
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="emit('update:modelValue', false)">取消</el-button>
      <el-button type="primary" :loading="loading" @click="submit">保存资料</el-button>
    </template>
  </el-dialog>
</template>
