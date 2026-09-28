<script setup lang="ts">
import { computed, useId } from 'vue'

/**
 * The AJUSTA lockup: the "A" symbol, then the wordmark.
 *
 * The wordmark takes its colour from the surrounding text colour (a
 * text-heading or text-chrome-fg class, say), so it reads on the light pages
 * and on the dark sidebar alike. `lightColor` still sets it directly for
 * callers that pass one; `darkColor` is kept for compatibility and unused.
 */
interface Props {
  /** `full` = symbol + AJUSTA wordmark; `mark` = the "A" symbol only. */
  variant?: 'full' | 'mark'
  /** Single-colour lockup in `currentColor` (e.g. on a coloured surface). */
  mono?: boolean
  darkColor?: string
  lightColor?: string
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'full',
  mono: false,
  darkColor: undefined,
  lightColor: undefined,
})

const viewBox = computed(() => (props.variant === 'mark' ? '0 0 70 51' : '0 0 296 51'))

const style = computed(() => (props.lightColor ? { color: props.lightColor } : undefined))

// Each logo on the page gets its own gradient, so hiding one never blanks another
const gradientId = `ajusta-mark-${useId()}`
</script>

<template>
  <!--
    The symbol is two parts that fit together into an "A": a long bar and a
    shorter one, separated by a parallel gap. Corners are rounded by stroking
    each polygon with its own fill (round joins).
  -->
  <svg
    :viewBox="viewBox"
    xmlns="http://www.w3.org/2000/svg"
    :style="style"
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
      fill="currentColor"
    >ΛJUSTΛ</text>
  </svg>
</template>
