<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  value: {
    type: String,
    required: true,
  },
  hint: {
    type: String,
    default: '',
  },
  trend: {
    type: String,
    default: '',
  },
  tone: {
    type: String,
    default: 'default',
  },
})
</script>

<template>
  <article class="stat-card" :class="`is-${tone}`">
    <div class="stat-card__label">{{ title }}</div>
    <div class="stat-card__value">{{ value }}</div>
    <div class="stat-card__footer">
      <span>{{ hint }}</span>
      <span v-if="trend" class="stat-card__trend">{{ trend }}</span>
    </div>
  </article>
</template>
