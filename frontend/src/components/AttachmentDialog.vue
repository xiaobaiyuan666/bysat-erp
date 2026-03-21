<script setup>
import { computed, ref, watch } from "vue";
import { formatDateTime, formatFileSize } from "../utils/formatters";

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  record: {
    type: Object,
    default: null,
  },
  title: {
    type: String,
    default: "附件管理",
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["update:modelValue", "submit"]);

const fileList = ref([]);
const removeAttachmentIds = ref([]);

const attachments = computed(() => props.record?.attachments || []);

watch(
  () => props.modelValue,
  (visible) => {
    if (visible) {
      fileList.value = [];
      removeAttachmentIds.value = [];
    }
  },
);

function syncFiles(_file, files) {
  fileList.value = files;
}

function handleSubmit() {
  emit("submit", {
    remove_attachment_ids: removeAttachmentIds.value,
    attachments: fileList.value.map((item) => item.raw).filter(Boolean),
  });
}
</script>

<template>
  <el-dialog
    :model-value="modelValue"
    :title="title"
    width="720px"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <div class="attachment-dialog">
      <div v-if="attachments.length" class="attachment-grid">
        <label
          v-for="attachment in attachments"
          :key="attachment.id"
          class="attachment-tile"
        >
          <img :src="attachment.url" :alt="attachment.name" />
          <div class="attachment-tile__body">
            <strong>{{ attachment.name }}</strong>
            <span>{{ formatFileSize(attachment.size) }}</span>
            <small>{{ formatDateTime(attachment.uploaded_at) }}</small>
          </div>
          <el-checkbox v-model="removeAttachmentIds" :label="attachment.id">
            本次移除
          </el-checkbox>
        </label>
      </div>
      <el-empty v-else description="当前没有附件，可直接补传图片。" />

      <el-upload
        drag
        multiple
        :auto-upload="false"
        :file-list="fileList"
        accept="image/*"
        :on-change="syncFiles"
        :on-remove="syncFiles"
      >
        <div class="el-upload__text">
          拖入付款截图、发票、回单图片，或点击上传
        </div>
      </el-upload>
    </div>

    <template #footer>
      <el-button @click="emit('update:modelValue', false)">取消</el-button>
      <el-button type="primary" :loading="loading" @click="handleSubmit">
        保存附件
      </el-button>
    </template>
  </el-dialog>
</template>
