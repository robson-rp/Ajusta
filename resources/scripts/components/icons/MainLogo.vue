<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  /** `full` = symbol + AJUSTA wordmark; `mark` = the "A" symbol only. */
  variant?: 'full' | 'mark'
  /** Single-colour lockup in `currentColor` (e.g. on a coloured surface). */
  mono?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'full',
  mono: false,
})

const viewBox = computed(() => (props.variant === 'mark' ? '0 0 70 51' : '0 0 296 51'))

// Unique per instance so several logos on one page don't share a gradient id.
const gradientId = `ajusta-mark-${Math.random().toString(36).slice(2, 9)}`
</script>

<template>
  <!--
    AJUSTA lockup. The symbol is two parts that fit together into an "A":
    a long bar and a shorter one, separated by a parallel gap. Corners are
    rounded by stroking each polygon with its own fill (round joins).
  -->
  <svg
    :viewBox="viewBox"
    xmlns="http://www.w3.org/2000/svg"
    role="img"
    aria-label="AJUSTA"
  >
    <defs v-if="!mono">
      <linearGradient :id="gradientId" x1="1" y1="0" x2="0" y2="1">
        <stop offset="0" stop-color="#00BFA6" />
        <stop offset="1" stop-color="#05A99A" />
      </linearGradient>
    </defs>

    <g stroke-linejoin="round" stroke-width="6">
      <polygon
        points="4,48 20,48 44,3 28,3"
        :fill="mono ? 'currentColor' : `url(#${gradientId})`"
        :stroke="mono ? 'currentColor' : `url(#${gradientId})`"
      />
      <polygon
        points="53,48 66,48 49,17.5 46.3,17.5 41.2,27"
        :fill="mono ? 'currentColor' : '#5CD6C4'"
        :stroke="mono ? 'currentColor' : '#5CD6C4'"
        :opacity="mono ? 0.6 : 1"
      />
    </g>

    <text
      v-if="variant === 'full'"
      x="86"
      y="41.5"
      textLength="208"
      lengthAdjust="spacing"
      font-family="'Inter Variable', Inter, system-ui, sans-serif"
      font-size="39"
      font-weight="600"
      :fill="mono ? 'currentColor' : 'var(--color-heading, #0F2624)'"
    >ΛJUSTΛ</text>
  </svg>
</template>
