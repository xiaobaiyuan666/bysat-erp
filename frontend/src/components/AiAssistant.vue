<script setup>
import { computed, reactive, ref, watch } from "vue";
import {
  ChatDotRound,
  Connection,
  Delete,
  Promotion,
  Setting,
} from "@element-plus/icons-vue";
import { useViewport } from "../composables/useViewport";
import { useAppStore } from "../stores/useAppStore";
import { formatDateTime } from "../utils/formatters";

const store = useAppStore();
const { isMobile } = useViewport();

const question = ref("");
const settingsVisible = ref(false);

const settingsForm = reactive({
  provider_name: "",
  base_url: "",
  api_key: "",
  model: "",
  temperature: 0.2,
  system_prompt: "",
});

const aiSettings = computed(() => store.state.bootstrap.aiSettings || {});
const aiConfigured = computed(() => Boolean(store.state.bootstrap.aiConfigured));
const conversation = computed(() => store.state.bootstrap.aiConversation || []);
const presets = computed(() => store.state.bootstrap.aiPresets || []);
const canManageAI = computed(() => store.hasPermission("ai.manage"));
const questionRows = computed(() => (isMobile.value ? 3 : 4));

watch(
  aiSettings,
  (value) => {
    settingsForm.provider_name = value.provider_name || "OpenAI Compatible";
    settingsForm.base_url = value.base_url || "";
    settingsForm.api_key = value.api_key || "";
    settingsForm.model = value.model || "";
    settingsForm.temperature = Number(value.temperature ?? 0.2);
    settingsForm.system_prompt = value.system_prompt || "";
  },
  { immediate: true },
);

async function sendQuestion() {
  if (!question.value.trim()) {
    return;
  }

  const currentQuestion = question.value;
  question.value = "";
  await store.submitAction("ask_ai", {
    question: currentQuestion,
  });
}

async function saveSettings() {
  await store.submitAction("save_ai_settings", { ...settingsForm });
  settingsVisible.value = false;
}

async function clearConversation() {
  await store.submitAction("clear_ai_conversation", {}, { silent: true });
}

function applyPreset(prompt) {
  question.value = prompt;
}
</script>

<template>
  <section class="assistant-panel">
    <div class="assistant-panel__status">
      <div>
        <div class="assistant-panel__eyebrow">模型工作台</div>
        <h3>AI 助手</h3>
        <p>
          {{
            aiConfigured
              ? aiSettings.model || "已配置模型"
              : "还没有接入模型，当前只能保存模型参数"
          }}
        </p>
      </div>
      <el-space wrap>
        <el-tag :type="aiConfigured ? 'success' : 'warning'" effect="light">
          {{ aiConfigured ? "模型已接入" : "等待配置" }}
        </el-tag>
        <el-button
          v-if="canManageAI"
          :icon="Setting"
          @click="settingsVisible = true"
        >
          模型设置
        </el-button>
      </el-space>
    </div>

    <details v-if="isMobile" class="assistant-presets-panel">
      <summary class="assistant-presets-panel__summary">
        <span>常用分析模板</span>
        <small>{{ presets.length }} 个</small>
      </summary>
      <div class="assistant-panel__presets">
        <button
          v-for="preset in presets"
          :key="preset.key"
          class="assistant-preset"
          type="button"
          @click="applyPreset(preset.prompt)"
        >
          <span>{{ preset.label }}</span>
          <small>{{ preset.description }}</small>
        </button>
      </div>
    </details>
    <div v-else class="assistant-panel__presets">
      <button
        v-for="preset in presets"
        :key="preset.key"
        class="assistant-preset"
        type="button"
        @click="applyPreset(preset.prompt)"
      >
        <span>{{ preset.label }}</span>
        <small>{{ preset.description }}</small>
      </button>
    </div>

    <div class="assistant-panel__conversation">
      <div v-if="!conversation.length" class="assistant-empty">
        直接问现金流风险、回款节奏、项目延期原因或成本优化建议，常用模板也可以一键带入。
      </div>
      <article
        v-for="(item, index) in conversation"
        :key="`${item.role}-${item.created_at || index}`"
        class="assistant-message"
        :class="`is-${item.role}`"
      >
        <div class="assistant-message__meta">
          <el-icon><ChatDotRound /></el-icon>
          <strong>{{ item.role === "assistant" ? "系统助手" : "我" }}</strong>
          <span>{{ formatDateTime(item.created_at) }}</span>
        </div>
        <div class="assistant-message__body">{{ item.content }}</div>
      </article>
    </div>

    <div class="assistant-panel__composer">
      <el-input
        v-model="question"
        type="textarea"
        :rows="questionRows"
        resize="none"
        placeholder="直接问现金流风险、回款节奏、项目延期原因或成本优化建议。"
        @keyup.ctrl.enter="sendQuestion"
      />
      <div class="assistant-panel__actions">
        <el-button :icon="Delete" @click="clearConversation">
          清空对话
        </el-button>
        <el-button
          type="primary"
          :icon="Promotion"
          :loading="store.state.submitting"
          @click="sendQuestion"
        >
          发送
        </el-button>
      </div>
    </div>

    <el-dialog
      v-model="settingsVisible"
      title="模型设置"
      width="min(680px, calc(100vw - 24px))"
    >
      <el-form label-position="top">
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item label="服务商名称">
              <el-input
                v-model="settingsForm.provider_name"
                placeholder="OpenAI Compatible"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="模型名称">
              <el-input
                v-model="settingsForm.model"
                placeholder="gpt-4o-mini / qwen-plus / deepseek-chat"
              />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="Base URL">
          <el-input
            v-model="settingsForm.base_url"
            placeholder="https://api.openai.com/v1"
          />
        </el-form-item>
        <el-form-item label="API Key">
          <el-input
            v-model="settingsForm.api_key"
            type="password"
            show-password
            placeholder="sk-..."
          />
        </el-form-item>
        <el-form-item label="温度">
          <el-slider
            v-model="settingsForm.temperature"
            :step="0.1"
            :min="0"
            :max="1"
          />
        </el-form-item>
        <el-form-item label="系统提示词">
          <el-input
            v-model="settingsForm.system_prompt"
            type="textarea"
            :rows="8"
            resize="vertical"
            placeholder="补充业务规则、输出格式或回答口径。"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="settingsVisible = false">取消</el-button>
        <el-button
          type="primary"
          :icon="Connection"
          :loading="store.state.submitting"
          @click="saveSettings"
        >
          保存模型配置
        </el-button>
      </template>
    </el-dialog>
  </section>
</template>
