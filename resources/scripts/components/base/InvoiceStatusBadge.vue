<script setup lang="ts">
import { computed } from 'vue'
import { InvoiceStatus, InvoicePaidStatus } from '@/scripts/types/domain'

type InvoiceBadgeStatus =
  | InvoiceStatus
  | InvoicePaidStatus
  | 'DUE'
  | 'OVERDUE'

interface Props {
  status?: InvoiceBadgeStatus | string
}

const props = withDefaults(defineProps<Props>(), {
  status: '',
})

const baseClasses = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium'

const badgeColorClasses = computed<string>(() => {
  switch (props.status) {
    case InvoiceStatus.DRAFT:
    case 'DRAFT':
      return `${baseClasses} bg-surface-tertiary text-muted ring-1 ring-inset ring-line-default`
    case InvoiceStatus.SENT:
    case 'SENT':
      return `${baseClasses} bg-primary-50 text-primary-700 ring-1 ring-inset ring-primary-200/60`
    case InvoiceStatus.VIEWED:
    case 'VIEWED':
      return `${baseClasses} bg-primary-50 text-primary-700 ring-1 ring-inset ring-primary-200/60`
    case InvoiceStatus.COMPLETED:
    case 'COMPLETED':
      return `${baseClasses} bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-300/50`
    case 'DUE':
      return `${baseClasses} bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-300/50`
    case 'OVERDUE':
      return `${baseClasses} bg-red-50 text-red-700 ring-1 ring-inset ring-red-300/50`
    case InvoicePaidStatus.UNPAID:
    case 'UNPAID':
      return `${baseClasses} bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-300/50`
    case InvoicePaidStatus.PARTIALLY_PAID:
    case 'PARTIALLY_PAID':
      return `${baseClasses} bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-300/50`
    case InvoicePaidStatus.PAID:
    case 'PAID':
      return `${baseClasses} bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-300/50`
    default:
      return `${baseClasses} bg-surface-secondary text-muted ring-1 ring-inset ring-line-default`
  }
})
</script>

<template>
  <span :class="badgeColorClasses">
    <slot />
  </span>
</template>
