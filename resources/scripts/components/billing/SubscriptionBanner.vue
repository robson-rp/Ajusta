<template>
  <div
    v-if="message"
    :class="[
      'flex flex-wrap items-center justify-center gap-x-3 gap-y-1 px-4 py-2 text-sm border-b',
      tone === 'warning'
        ? 'bg-alert-warning-bg text-alert-warning-text border-line-light'
        : tone === 'danger'
          ? 'bg-alert-error-bg text-alert-error-text border-line-light'
          : 'bg-primary-50 text-heading border-line-light',
    ]"
    role="status"
  >
    <span>{{ message }}</span>
    <router-link
      v-if="subscription?.is_owner && route.name !== 'settings.billing'"
      :to="{ name: 'settings.billing' }"
      class="font-medium underline underline-offset-2"
    >
      {{ $t(subscription?.status === 'trialing' ? 'billing.banner.choose_plan' : 'billing.banner.pay_now') }}
    </router-link>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useGlobalStore } from '@/scripts/stores/global.store'

const { t } = useI18n()
const route = useRoute()
const globalStore = useGlobalStore()

const subscription = computed(() => globalStore.subscription)

const tone = computed<'info' | 'warning' | 'danger'>(() => {
  const status = subscription.value?.status
  if (status === 'suspended' || status === 'cancelled') return 'danger'
  if (status === 'past_due') return 'warning'
  return 'info'
})

// Quiet while the paid period has more than the notice window left.
const message = computed<string | null>(() => {
  const sub = subscription.value
  if (!sub) return null

  const days = sub.days_left ?? 0

  switch (sub.status) {
    case 'trialing':
      return days > 0 ? t('billing.banner.trial', { days }, days) : t('billing.banner.trial_last_day')
    case 'active':
      return days <= 7 ? t('billing.banner.renew_soon', { days }, days) : null
    case 'past_due':
      return t('billing.banner.past_due', { days }, days)
    case 'suspended':
    case 'cancelled':
      return t('billing.banner.read_only')
    default:
      return null
  }
})
</script>
