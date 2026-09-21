<template>
  <!-- Estimates (proformas): a sheet with a pending, dashed line. -->
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
      <path d="M19 15.5h9l5 5v14a1 1 0 0 1-1 1H19a1 1 0 0 1-1-1v-18a1 1 0 0 1 1-1Z" />
      <path d="M28 15.5v5h5" />
      <path d="M22 28h8" stroke-dasharray="2 2.5" />
    </g>
  </svg>
</template>
