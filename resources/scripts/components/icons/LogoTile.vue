<template>
  <!--
    The AJUSTA "A" symbol drawn into a 48 x 48 area of the parent <svg>, the
    same two bars as MainLogo, scaled and centred.
  -->
  <defs>
    <linearGradient :id="gradientId" x1="1" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#00BFA6" />
      <stop offset="1" stop-color="#05A99A" />
    </linearGradient>
  </defs>
  <g transform="translate(1 7.2) scale(0.657)" stroke-linejoin="round" stroke-width="6">
    <polygon
      points="4,48 20,48 44,3 28,3"
      :fill="`url(#${gradientId})`"
      :stroke="`url(#${gradientId})`"
    />
    <polygon points="53,48 66,48 49,17.5 46.3,17.5 41.2,27" fill="#5CD6C4" stroke="#5CD6C4" />
  </g>
</template>

<script setup lang="ts">
import { useId } from 'vue'

// Each logo on the page gets its own gradient, so hiding one never blanks another
const gradientId = `logo-tile-${useId()}`
</script>
