<script setup>
import { computed, reactive, ref } from "vue";
import { Lightning, MagicStick } from "@element-plus/icons-vue";
import { useAppStore } from "../stores/useAppStore";

const props = defineProps({
  projects: {
    type: Array,
    default: () => [],
  },
  aiConfigured: {
    type: Boolean,
    default: false,
  },
  modelName: {
    type: String,
    default: "",
  },
  editable: {
    type: Boolean,
    default: true,
  },
});

const store = useAppStore();
const fileList = ref([]);
const form = reactive({
  smart_text: "",
  project_id: "",
});

const examples = [
  "今天给晨光办公付款 500 元，微信支付，购买办公用品。",
  "昨天收到星环科技回款 50000 元，银行转账，官网项目尾款。",
  "今天给晓石测试打款 3000 元，银行转账，客服工单项目测试费。",
];

const attachmentFiles = computed(() => {
  return fileList.value.map((item) => item.raw).filter(Boolean);
});

function fillExample(text) {
  if (!props.editable) {
    return;
  }

  form.smart_text = text;
}

function syncFiles(_file, files) {
  fileList.value = files;
}

async function submit() {
  if (!props.editable) {
    return;
  }

  await store.submitAction(
    "smart_bookkeeping",
    {
      ...form,
      attachments: attachmentFiles.value,
    },
    { multipart: true },
  );

  form.smart_text = "";
  form.project_id = "";
  fileList.value = [];
}
</script>

<template>
  <section class="panel-card">
    <header class="panel-card__header">
      <div>
        <h3>智能记账</h3>
        <p>一句话录入，优先走大模型结构化解析，失败再用规则兜底。</p>
      </div>
      <el-tag :type="aiConfigured ? 'success' : 'warning'" effect="light">
        {{ aiConfigured ? modelName || "模型优先" : "规则兜底" }}
      </el-tag>
    </header>
    <div class="panel-card__body smart-bookkeeping">
      <el-alert
        v-if="!editable"
        title="当前账号只有查看权限，不能执行智能入账。"
        type="warning"
        :closable="false"
      />

      <el-input
        v-model="form.smart_text"
        type="textarea"
        resize="none"
        :rows="4"
        :disabled="!editable"
        placeholder="例如：今天给 xx 付款 100 元，微信支付，办公用品。"
      />

      <div class="smart-bookkeeping__meta">
        <el-icon><MagicStick /></el-icon>
        <span>支持先入账，再走“补传附件”补票据图片。</span>
      </div>

      <el-row :gutter="14">
        <el-col :xs="24" :md="12">
          <el-select
            v-model="form.project_id"
            clearable
            placeholder="选择关联项目"
            style="width: 100%"
            :disabled="!editable"
          >
            <el-option
              v-for="project in projects"
              :key="project.value"
              :label="project.label"
              :value="project.value"
            />
          </el-select>
        </el-col>
        <el-col :xs="24" :md="12">
          <el-upload
            class="smart-upload"
            drag
            multiple
            :disabled="!editable"
            :auto-upload="false"
            :file-list="fileList"
            accept="image/*"
            :on-change="syncFiles"
            :on-remove="syncFiles"
          >
            <el-icon class="el-icon--upload"><Lightning /></el-icon>
            <div class="el-upload__text">拖图片到这里，或点击补记票据</div>
          </el-upload>
        </el-col>
      </el-row>

      <div class="smart-examples">
        <button
          v-for="example in examples"
          :key="example"
          type="button"
          class="smart-example"
          :disabled="!editable"
          @click="fillExample(example)"
        >
          {{ example }}
        </button>
      </div>

      <div class="smart-bookkeeping__actions">
        <el-button :disabled="!editable" @click="form.smart_text = ''">
          清空
        </el-button>
        <el-button
          type="primary"
          :disabled="!editable"
          :loading="store.state.submitting"
          @click="submit"
        >
          智能入账
        </el-button>
      </div>
    </div>
  </section>
</template>
