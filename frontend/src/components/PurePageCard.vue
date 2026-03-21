<script setup>
import { computed, useSlots } from "vue";

const props = defineProps({
  title: {
    type: String,
    default: "",
  },
  description: {
    type: String,
    default: "",
  },
  cardClass: {
    type: [String, Array, Object],
    default: "",
  },
  bodyClass: {
    type: [String, Array, Object],
    default: "",
  },
});

const slots = useSlots();

const hasHeader = computed(() => {
  return Boolean(props.title || props.description || slots.actions || slots.titleExtra);
});
</script>

<template>
  <section class="panel-card pure-page-card" :class="cardClass">
    <header v-if="hasHeader" class="panel-card__header pure-page-card__header">
      <div class="pure-page-card__title">
        <h3 v-if="title">{{ title }}</h3>
        <p v-if="description">{{ description }}</p>
        <slot name="titleExtra" />
      </div>
      <div v-if="$slots.actions" class="pure-page-card__actions">
        <slot name="actions" />
      </div>
    </header>

    <div class="panel-card__body" :class="bodyClass">
      <slot />
    </div>
  </section>
</template>
