<script setup>
import { computed } from 'vue'
import { formatCurrency, formatDate, formatDateTime, formatFileSize, toneToTagType } from '../utils/formatters'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: '详情',
  },
  record: {
    type: Object,
    default: null,
  },
  currency: {
    type: String,
    default: 'CNY',
  },
  fields: {
    type: Array,
    default: () => [],
  },
  metrics: {
    type: Array,
    default: () => [],
  },
  timeline: {
    type: Array,
    default: () => [],
  },
  timelineTitle: {
    type: String,
    default: '处理轨迹',
  },
  preview: {
    type: Object,
    default: null,
  },
  previewTitle: {
    type: String,
    default: '在线预览',
  },
  statusLabel: {
    type: String,
    default: '',
  },
  statusTone: {
    type: String,
    default: 'info',
  },
  notesLabel: {
    type: String,
    default: '备注说明',
  },
  showAttachments: {
    type: Boolean,
    default: true,
  },
  editable: {
    type: Boolean,
    default: true,
  },
  attachmentsEditable: {
    type: Boolean,
    default: true,
  },
  extraActionLabel: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['update:modelValue', 'edit', 'attachments', 'extra-action'])

const attachments = computed(() => props.record?.attachments || [])
const hasPreview = computed(() => Boolean(props.preview?.url))

const auditFields = computed(() => {
  if (!props.record) {
    return []
  }

  return [
    { label: '创建人', value: props.record.created_by_name || '--' },
    { label: '创建时间', value: formatDateTime(props.record.created_at) },
    { label: '最近操作人', value: props.record.updated_by_name || props.record.created_by_name || '--' },
    { label: '最近操作时间', value: formatDateTime(props.record.updated_at || props.record.created_at) },
  ]
})

const showAudit = computed(() => Boolean(props.record?.created_at || props.record?.updated_at))

function formatValue(field, value) {
  if (value === undefined || value === null || value === '') {
    return '--'
  }

  if (field.type === 'currency') {
    return formatCurrency(value, props.currency)
  }

  if (field.type === 'date') {
    return formatDate(value)
  }

  if (field.type === 'datetime') {
    return formatDateTime(value)
  }

  if (field.type === 'filesize') {
    return Number(value || 0) > 0 ? formatFileSize(value) : '--'
  }

  if (field.type === 'percent') {
    return `${Number(value).toFixed(1)}%`
  }

  return String(value)
}
</script>

<template>
  <el-drawer
    :model-value="modelValue"
    :title="title"
    size="520px"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <div v-if="record" class="detail-drawer">
      <section class="detail-hero">
        <div>
          <div class="detail-hero__title">{{ title }}</div>
          <div class="detail-hero__name">{{ record.title || record.name || record.counterparty || record.id }}</div>
        </div>
        <el-tag v-if="statusLabel" :type="toneToTagType(statusTone)" effect="light">{{ statusLabel }}</el-tag>
      </section>

      <section v-if="metrics.length" class="detail-metrics">
        <article v-for="item in metrics" :key="item.label" class="detail-metric">
          <span>{{ item.label }}</span>
          <strong>{{ formatValue(item, record[item.key]) }}</strong>
        </article>
      </section>

      <section class="detail-section">
        <div class="detail-section__title">鍩虹淇℃伅</div>
        <div class="detail-grid">
          <article v-for="field in fields" :key="field.key" class="detail-grid__item">
            <span>{{ field.label }}</span>
            <template v-if="field.type === 'link' && record[field.key]">
              <el-link :href="record[field.key]" target="_blank" type="primary">鎵撳紑閾炬帴</el-link>
              <small>{{ record[field.key] }}</small>
            </template>
            <strong v-else>{{ formatValue(field, record[field.key]) }}</strong>
          </article>
        </div>
      </section>

      <section v-if="showAudit" class="detail-section">
        <div class="detail-section__title">璁板綍鐣欑棔</div>
        <div class="detail-grid detail-grid--audit">
          <article v-for="item in auditFields" :key="item.label" class="detail-grid__item">
            <span>{{ item.label }}</span>
            <strong>{{ item.value || '--' }}</strong>
          </article>
        </div>
      </section>

      <section v-if="timeline.length" class="detail-section">
        <div class="detail-section__title">{{ timelineTitle }}</div>
        <div class="detail-timeline">
          <article v-for="item in timeline" :key="item.id" class="detail-timeline__item">
            <div class="detail-timeline__head">
              <div>
                <strong>{{ item.type_label || item.title || '璺熻繘璁板綍' }}</strong>
                <span>{{ item.created_by_name || item.user_name || '--' }} / {{ formatDateTime(item.occurred_at || item.created_at) }}</span>
              </div>
              <div class="detail-timeline__meta">
                <el-tag v-if="item.visibility_label" :type="toneToTagType(item.visibility_tone || 'neutral')" effect="light">
                  {{ item.visibility_label }}
                </el-tag>
                <el-tag v-if="item.status_label" :type="toneToTagType(item.status_tone || 'info')" effect="light">
                  {{ item.status_label }}
                </el-tag>
              </div>
            </div>
            <div class="detail-timeline__body">{{ item.content || '--' }}</div>
            <div v-if="item.next_action" class="detail-timeline__foot">涓嬩竴姝ワ細{{ item.next_action }}</div>
          </article>
        </div>
      </section>

      <section v-if="hasPreview" class="detail-section">
        <div class="detail-section__title">{{ previewTitle }}</div>
        <div class="detail-preview">
          <img
            v-if="preview.type === 'image'"
            class="detail-preview__image"
            :src="preview.url"
            :alt="preview.alt || record.title || 'preview'"
          />
          <iframe
            v-else-if="preview.type === 'pdf'"
            class="detail-preview__frame"
            :src="preview.url"
            :title="preview.title || previewTitle"
          />
          <div v-else class="detail-preview__fallback">当前资料不支持内嵌预览。</div>
          <el-link :href="preview.url" target="_blank" type="primary">新窗口打开</el-link>
        </div>
      </section>

      <section class="detail-section">
        <div class="detail-section__title">{{ notesLabel }}</div>
        <div class="detail-notes">{{ record.notes || record.description || '--' }}</div>
      </section>

      <section v-if="showAttachments" class="detail-section">
        <div class="detail-section__title">附件</div>
        <div v-if="attachments.length" class="detail-attachments">
          <a v-for="attachment in attachments" :key="attachment.id" class="detail-attachment" :href="attachment.url" target="_blank">
            <img :src="attachment.url" :alt="attachment.name" />
            <div>
              <strong>{{ attachment.name }}</strong>
              <span>{{ formatFileSize(attachment.size) }}</span>
              <small>{{ formatDateTime(attachment.uploaded_at) }}</small>
            </div>
          </a>
        </div>
        <el-empty v-else description="当前没有附件" />
      </section>

      <div class="detail-actions">
        <el-button v-if="showAttachments && attachmentsEditable" @click="emit('attachments', record)">附件管理</el-button>
        <el-button v-if="extraActionLabel" @click="emit('extra-action', record)">{{ extraActionLabel }}</el-button>
        <el-button v-if="editable" type="primary" @click="emit('edit', record)">编辑</el-button>
      </div>
    </div>
  </el-drawer>
</template>
