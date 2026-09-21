<template>
  <div class="flex flex-col items-center justify-center mt-16">
    <div class="flex flex-col items-center justify-center">
      <slot>
        <EmptyStateMark class="mb-4" />
      </slot>
    </div>
    <div class="mt-2">
      <label class="font-medium text-heading">{{ title }}</label>
    </div>
    <div class="mt-1">
      <label class="text-sm text-muted">
        {{ description }}
      </label>
    </div>
    <div class="mt-6">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup lang="ts">
import EmptyStateMark from '@/scripts/components/icons/EmptyStateMark.vue'
interface Props {
  title?: string
  description?: string
}

withDefaults(defineProps<Props>(), {
  title: '',
  description: '',
})
</script>
