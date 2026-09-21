<template>
  <!-- Amount due: a clock face — time left to collect, no currency symbol. -->
  <svg
    width="50"
    height="50"
    viewBox="0 0 50 50"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
  >
    <rect width="50" height="50" rx="12" fill="var(--color-primary-50)" />
    <g
      stroke="var(--color-primary-600)"
      stroke-width="1.75"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <circle cx="25" cy="25" r="9" />
      <path d="M25 20v5l3.5 2" />
    </g>
  </svg>
</template>
