<template>
  <!-- AGPL-3.0: AJUSTA is a modified InvoiceShelf; users must be offered the source. -->
  <p class="text-xs text-subtle">
    {{ $t('general.based_on') }}
    <a
      href="https://github.com/InvoiceShelf/InvoiceShelf"
      target="_blank"
      rel="noopener noreferrer"
      class="underline decoration-line-default underline-offset-2 hover:text-muted"
    >InvoiceShelf</a>
    ·
    <a
      href="https://www.gnu.org/licenses/agpl-3.0.html"
      target="_blank"
      rel="noopener noreferrer"
      class="underline decoration-line-default underline-offset-2 hover:text-muted"
    >AGPL-3.0</a>
    ·
    <a
      :href="sourceUrl"
      target="_blank"
      rel="noopener noreferrer"
      class="underline decoration-line-default underline-offset-2 hover:text-muted"
    >{{ $t('general.source_code') }}</a>
  </p>
</template>

<script setup lang="ts">
const sourceUrl = 'https://github.com/robson-rp/Ajusta'
</script>
