<template>
  <BaseSettingCard :title="$t('billing.title')" :description="$t('billing.description')">
    <div v-if="isLoading" class="flex justify-center py-10"><BaseSpinner class="w-6 h-6 text-primary-500" /></div>

    <div v-else-if="!overview?.subscription" class="py-6 text-sm text-muted">
      {{ $t('billing.no_subscription') }}
    </div>

    <template v-else>
      <!-- Current subscription -->
      <div class="flex flex-wrap items-start justify-between gap-4 py-6 border-b border-line-light">
        <div>
          <p class="text-sm text-muted">{{ $t('billing.current_plan') }}</p>
          <p class="mt-1 text-xl font-semibold text-heading">{{ overview.subscription.plan?.name }}</p>
          <p class="mt-1 text-sm text-muted">
            {{ endLabel }} <span class="font-medium text-body">{{ formatDate(overview.subscription.ends_at) }}</span>
          </p>
        </div>
        <span :class="['rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset', statusClass]">
          {{ $t(`billing.status.${overview.subscription.status}`) }}
        </span>
      </div>

      <p v-if="!overview.can_manage" class="py-6 text-sm text-muted">{{ $t('billing.owner_only') }}</p>

      <!-- Charge in progress -->
      <div v-else-if="activeCharge" class="py-6">
        <div v-if="activeCharge.status === 'paid'" class="p-5 rounded-lg bg-alert-success-bg text-alert-success-text">
          <p class="font-medium">{{ $t('billing.paid_title') }}</p>
          <p class="mt-1 text-sm">{{ $t('billing.paid_text', { date: formatDate(overview.subscription.ends_at) }) }}</p>
        </div>

        <div v-else-if="activeCharge.status === 'pending' && activeCharge.method === 'reference'" class="max-w-md">
          <p class="text-sm text-muted">{{ $t('billing.reference_intro') }}</p>
          <dl class="mt-4 overflow-hidden border rounded-lg border-line-light divide-y divide-line-light">
            <div v-for="row in referenceRows" :key="row.label" class="flex items-center justify-between px-4 py-3">
              <dt class="text-sm text-muted">{{ row.label }}</dt>
              <dd class="flex items-center gap-2 font-mono text-base font-semibold text-heading">
                {{ row.value }}
                <button
                  v-if="row.copy"
                  type="button"
                  class="text-subtle hover:text-primary-600"
                  :aria-label="$t('general.copy_to_clipboard')"
                  @click="copy(row.value)"
                >
                  <BaseIcon name="ClipboardDocumentIcon" class="w-4 h-4" />
                </button>
              </dd>
            </div>
          </dl>
          <p class="mt-3 text-xs text-muted">{{ $t('billing.reference_waiting') }}</p>
        </div>

        <div v-else-if="activeCharge.status === 'pending'" class="flex items-start gap-3 max-w-md">
          <BaseSpinner class="w-5 h-5 mt-0.5 text-primary-500" />
          <div>
            <p class="font-medium text-heading">{{ $t('billing.gpo_title') }}</p>
            <p class="mt-1 text-sm text-muted">{{ $t('billing.gpo_text', { phone: activeCharge.customer_phone }) }}</p>
          </div>
        </div>

        <div v-else class="p-5 rounded-lg bg-alert-error-bg text-alert-error-text max-w-md">
          <p class="font-medium">{{ $t(`billing.charge_status.${activeCharge.status}`) }}</p>
          <p v-if="activeCharge.message" class="mt-1 text-sm">{{ $te(activeCharge.message) ? $t(activeCharge.message) : activeCharge.message }}</p>
        </div>

        <BaseButton variant="primary-outline" class="mt-6" @click="resetCheckout">
          {{ activeCharge.status === 'paid' ? $t('billing.done') : $t('billing.new_payment') }}
        </BaseButton>
      </div>

      <!-- Checkout -->
      <form v-else class="py-6 space-y-6" @submit.prevent="pay">
        <div v-if="!overview.options.length" class="p-4 text-sm rounded-lg bg-alert-warning-bg text-alert-warning-text">
          {{ $t('billing.no_methods') }}
        </div>

        <fieldset>
          <legend class="mb-2 text-sm font-medium text-heading">{{ $t('billing.choose_plan') }}</legend>
          <div class="grid gap-3 sm:grid-cols-2">
            <label
              v-for="plan in overview.plans"
              :key="plan.code"
              :class="[
                'cursor-pointer rounded-lg border p-4 transition-colors',
                checkout.plan === plan.code ? 'border-primary-500 bg-primary-50' : 'border-line-default hover:border-line-strong',
              ]"
            >
              <input v-model="checkout.plan" type="radio" class="sr-only" :value="plan.code" />
              <span class="block font-semibold text-heading">{{ plan.name }}</span>
              <span class="block mt-1 text-sm text-muted">{{ formatKz(plan.price_monthly, false) }}{{ $t('signup.per_month') }}</span>
            </label>
          </div>
        </fieldset>

        <fieldset>
          <legend class="mb-2 text-sm font-medium text-heading">{{ $t('billing.period') }}</legend>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="months in overview.periods"
              :key="months"
              type="button"
              :class="[
                'rounded-lg border px-4 py-2 text-sm',
                checkout.months === months ? 'border-primary-500 bg-primary-50 font-medium text-heading' : 'border-line-default text-body hover:border-line-strong',
              ]"
              @click="checkout.months = months"
            >
              {{ $t('billing.months', { n: months }, months) }}
            </button>
          </div>
        </fieldset>

        <fieldset v-if="overview.options.length">
          <legend class="mb-2 text-sm font-medium text-heading">{{ $t('billing.method') }}</legend>
          <div class="grid gap-3 sm:grid-cols-2">
            <label
              v-for="option in overview.options"
              :key="option.method"
              :class="[
                'cursor-pointer rounded-lg border p-4 transition-colors',
                checkout.method === option.method ? 'border-primary-500 bg-primary-50' : 'border-line-default hover:border-line-strong',
              ]"
            >
              <input v-model="checkout.method" type="radio" class="sr-only" :value="option.method" />
              <span class="block font-medium text-heading">{{ $t(option.label) }}</span>
              <span class="block mt-1 text-xs text-muted">{{ $t(`${option.label}_hint`) }}</span>
            </label>
          </div>
        </fieldset>

        <BaseInputGroup
          v-if="selectedOption?.requires_phone"
          :label="$t('billing.phone')"
          :error="errors.phone?.[0]"
          :help-text="$t('billing.phone_help')"
          class="max-w-xs"
          required
        >
          <BaseInput v-model.trim="checkout.phone" type="tel" placeholder="923 000 000" />
        </BaseInputGroup>

        <div class="flex flex-wrap items-center justify-between gap-4 pt-6 border-t border-line-light">
          <div>
            <p class="text-sm text-muted">{{ $t('billing.total') }}</p>
            <p class="text-2xl font-semibold text-heading">{{ formatKz(total) }}</p>
          </div>
          <BaseButton type="submit" :loading="isPaying" :disabled="isPaying || !overview.options.length || !checkout.method">
            {{ $t('billing.pay') }}
          </BaseButton>
        </div>
      </form>

      <!-- History -->
      <div v-if="overview.charges.length" class="pt-6 border-t border-line-light">
        <h6 class="mb-3 font-medium text-heading">{{ $t('billing.history') }}</h6>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-muted">
                <th class="py-2 pr-4 font-normal">{{ $t('general.date') }}</th>
                <th class="py-2 pr-4 font-normal">{{ $t('billing.plan') }}</th>
                <th class="py-2 pr-4 font-normal">{{ $t('billing.method') }}</th>
                <th class="py-2 pr-4 font-normal text-right">{{ $t('billing.amount') }}</th>
                <th class="py-2 font-normal">{{ $t('billing.state') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-line-light">
              <tr v-for="charge in overview.charges" :key="charge.uuid">
                <td class="py-2 pr-4">{{ formatDate(charge.created_at) }}</td>
                <td class="py-2 pr-4">{{ charge.plan?.name }} · {{ $t('billing.months', { n: charge.months }, charge.months) }}</td>
                <td class="py-2 pr-4">{{ $t(`billing.methods.${charge.method}`) }}</td>
                <td class="py-2 pr-4 text-right whitespace-nowrap">{{ formatKz(charge.amount) }}</td>
                <td class="py-2">{{ $t(`billing.charge_status.${charge.status}`) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </BaseSettingCard>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { billingService } from '@/scripts/api/services/billing.service'
import type { BillingCharge, BillingOverview } from '@/scripts/api/services/billing.service'
import { formatDate, formatKz } from '@/scripts/utils/format-kz'
import { useGlobalStore } from '@/scripts/stores/global.store'
import { useNotificationStore } from '@/scripts/stores/notification.store'

const { t } = useI18n()
const globalStore = useGlobalStore()
const notificationStore = useNotificationStore()

const overview = ref<BillingOverview | null>(null)
const isLoading = ref(true)
const isPaying = ref(false)
const activeCharge = ref<BillingCharge | null>(null)
const errors = ref<Record<string, string[]>>({})
let pollTimer: ReturnType<typeof setInterval> | null = null
let pollStartedAt = 0

const checkout = reactive({ plan: 'start', months: 1, method: '', phone: '' })

const selectedOption = computed(() => overview.value?.options.find((o) => o.method === checkout.method))

const total = computed(() => {
  const plan = overview.value?.plans.find((p) => p.code === checkout.plan)
  if (!plan) return 0
  return plan.price_monthly * checkout.months
})

const endLabel = computed(() => {
  const status = overview.value?.subscription?.status
  if (status === 'trialing') return t('billing.trial_until')
  if (status === 'active') return t('billing.paid_until')
  return t('billing.ended_on')
})

const statusClass = computed(() => {
  switch (overview.value?.subscription?.status) {
    case 'active':
      return 'bg-emerald-50 text-emerald-700 ring-emerald-300/50'
    case 'trialing':
      return 'bg-primary-50 text-primary-700 ring-primary-200/60'
    case 'past_due':
      return 'bg-amber-50 text-amber-700 ring-amber-300/50'
    default:
      return 'bg-red-50 text-red-700 ring-red-300/50'
  }
})

const referenceRows = computed(() => {
  const charge = activeCharge.value
  if (!charge) return []
  return [
    { label: t('billing.entity'), value: charge.entity_number ?? '—', copy: true },
    { label: t('billing.reference'), value: charge.reference_number ?? '—', copy: true },
    { label: t('billing.amount'), value: formatKz(charge.amount), copy: false },
    { label: t('billing.valid_until'), value: formatDate(charge.expires_at), copy: false },
  ]
})

async function load(): Promise<void> {
  overview.value = await billingService.overview()
  const sub = overview.value.subscription
  checkout.plan = sub?.plan?.code ?? overview.value.plans[0]?.code ?? 'start'
  checkout.method = overview.value.options[0]?.method ?? ''

  // Resume an open reference so the owner can see it again.
  const open = overview.value.charges.find((c) => c.status === 'pending' && c.method === 'reference')
  if (open && !activeCharge.value) {
    activeCharge.value = open
    startPolling()
  }
}

onMounted(async () => {
  try {
    await load()
  } finally {
    isLoading.value = false
  }
})

onBeforeUnmount(stopPolling)

async function pay(): Promise<void> {
  isPaying.value = true
  errors.value = {}

  try {
    activeCharge.value = await billingService.createCharge({
      plan: checkout.plan,
      months: checkout.months,
      method: checkout.method,
      phone: selectedOption.value?.requires_phone ? checkout.phone : null,
    })
    startPolling()
  } catch (err: unknown) {
    const response = (err as { response?: { status?: number; data?: { errors?: Record<string, string[]>; data?: BillingCharge } } }).response
    if (response?.data?.data?.status === 'failed') {
      activeCharge.value = response.data.data
    } else if (response?.status === 422 && response.data?.errors) {
      errors.value = response.data.errors
    } else {
      notificationStore.showNotification({ type: 'error', message: 'billing.errors.generic' })
    }
  } finally {
    isPaying.value = false
  }
}

function startPolling(): void {
  stopPolling()
  pollStartedAt = Date.now()
  pollTimer = setInterval(async () => {
    const charge = activeCharge.value
    // GPO resolves within minutes; a reference can take days, so stop
    // polling after 15 minutes and let the page reload pick it up.
    if (!charge || charge.status !== 'pending' || Date.now() - pollStartedAt > 15 * 60 * 1000) {
      stopPolling()
      return
    }
    activeCharge.value = await billingService.charge(charge.uuid)
    if (activeCharge.value.status !== 'pending') {
      stopPolling()
      await load()
      await globalStore.bootstrap()
    }
  }, 5000)
}

function stopPolling(): void {
  if (pollTimer) clearInterval(pollTimer)
  pollTimer = null
}

function resetCheckout(): void {
  stopPolling()
  activeCharge.value = null
  load()
}

async function copy(value: string): Promise<void> {
  try {
    await navigator.clipboard.writeText(value)
    notificationStore.showNotification({ type: 'success', message: 'billing.copied' })
  } catch {
    // Clipboard blocked: the value is on screen anyway.
  }
}
</script>
