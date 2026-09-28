<template>
  <BasePage>
    <BasePageHeader :title="$t('billing.admin.subscriptions')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('billing.admin.subscriptions')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <div class="flex flex-wrap gap-3 mt-6">
      <BaseInput v-model="search" :placeholder="$t('general.search')" class="max-w-xs" @input="debouncedLoad" />
      <select v-model="status" :aria-label="$t('billing.state')" class="rounded-lg text-sm" @change="load(1)">
        <option value="">{{ $t('general.all') }}</option>
        <option v-for="s in statuses" :key="s" :value="s">{{ $t(`billing.status.${s}`) }}</option>
      </select>
    </div>

    <div class="mt-6 overflow-x-auto bg-surface border rounded-xl border-line-light">
      <table class="w-full text-sm">
        <thead class="text-left text-muted border-b border-line-light">
          <tr>
            <th class="px-4 py-3 font-normal">{{ $t('billing.admin.owner') }}</th>
            <th class="px-4 py-3 font-normal">{{ $t('navigation.companies') }}</th>
            <th class="px-4 py-3 font-normal">{{ $t('billing.plan') }}</th>
            <th class="px-4 py-3 font-normal">{{ $t('billing.state') }}</th>
            <th class="px-4 py-3 font-normal">{{ $t('billing.admin.ends') }}</th>
            <th class="px-4 py-3" />
          </tr>
        </thead>
        <tbody class="divide-y divide-line-light">
          <tr v-if="!isLoading && !rows.length">
            <td colspan="6" class="px-4 py-8 text-center text-muted">{{ $t('general.no_data_found') }}</td>
          </tr>
          <tr v-for="row in rows" :key="row.id" class="align-top">
            <td class="px-4 py-3">
              <p class="font-medium text-heading">{{ row.owner?.name }}</p>
              <p class="text-muted">{{ row.owner?.email }}</p>
            </td>
            <td class="px-4 py-3">
              <p v-for="company in companiesOf(row)" :key="company.id">
                {{ company.name }} <span class="text-muted">· NIF {{ company.tax_id ?? '—' }}</span>
              </p>
            </td>
            <td class="px-4 py-3">{{ row.plan?.name }}</td>
            <td class="px-4 py-3">{{ $t(`billing.status.${row.status}`) }}</td>
            <td class="px-4 py-3 whitespace-nowrap">{{ formatDate(row.ends_at) }}</td>
            <td class="px-4 py-3 text-right whitespace-nowrap">
              <BaseButton size="sm" variant="primary-outline" @click="openPayment(row)">
                {{ $t('billing.admin.record_payment') }}
              </BaseButton>
              <BaseDropdown class="inline-block ml-2">
                <template #activator>
                  <BaseIcon name="EllipsisHorizontalIcon" class="w-5 h-5 text-muted" />
                </template>
                <BaseDropdownItem v-for="plan in plans" :key="plan.code" @click="update(row, { plan: plan.code })">
                  {{ $t('billing.admin.switch_to', { plan: plan.name }) }}
                </BaseDropdownItem>
                <BaseDropdownItem v-if="row.status !== 'suspended'" @click="update(row, { status: 'suspended' })">
                  {{ $t('billing.admin.suspend') }}
                </BaseDropdownItem>
                <BaseDropdownItem v-else @click="update(row, { status: 'active' })">
                  {{ $t('billing.admin.reactivate') }}
                </BaseDropdownItem>
              </BaseDropdown>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="lastPage > 1" class="flex justify-end gap-2 mt-4">
      <BaseButton size="sm" variant="white" :disabled="page <= 1" @click="load(page - 1)">{{ $t('general.pagination.previous') }}</BaseButton>
      <BaseButton size="sm" variant="white" :disabled="page >= lastPage" @click="load(page + 1)">{{ $t('general.pagination.next') }}</BaseButton>
    </div>

    <!-- Manual payment -->
    <BaseModal :show="!!paying" closable size="sm" @close="paying = null">
      <template #header>
        {{ $t('billing.admin.record_payment') }}
      </template>

      <form class="p-6" @submit.prevent="submitPayment">
        <p class="text-sm text-muted">{{ paying?.owner?.name }} · {{ $t('billing.admin.manual_hint') }}</p>

        <BaseInputGroup :label="$t('billing.plan')" class="mt-5">
          <select v-model="payment.plan" :aria-label="$t('billing.plan')" class="w-full rounded-lg text-sm">
            <option v-for="plan in plans" :key="plan.code" :value="plan.code">{{ plan.name }}</option>
          </select>
        </BaseInputGroup>
        <BaseInputGroup :label="$t('billing.period')" class="mt-4">
          <select v-model.number="payment.months" :aria-label="$t('billing.period')" class="w-full rounded-lg text-sm">
            <option v-for="m in [1, 3, 6, 12]" :key="m" :value="m">{{ $t('billing.months', { n: m }, m) }}</option>
          </select>
        </BaseInputGroup>
        <BaseInputGroup :label="$t('general.note')" class="mt-4">
          <BaseInput v-model="payment.note" :placeholder="$t('billing.admin.note_placeholder')" />
        </BaseInputGroup>

        <div class="flex justify-end gap-3 mt-6">
          <BaseButton variant="white" type="button" @click="paying = null">{{ $t('general.cancel') }}</BaseButton>
          <BaseButton type="submit" :loading="isSaving">{{ $t('general.save') }}</BaseButton>
        </div>
      </form>
    </BaseModal>
  </BasePage>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { billingService } from '@/scripts/api/services/billing.service'
import type { BillingPlan, BillingSubscription } from '@/scripts/api/services/billing.service'
import { formatDate } from '@/scripts/utils/format-kz'
import { useNotificationStore } from '@/scripts/stores/notification.store'

const notificationStore = useNotificationStore()

const statuses = ['trialing', 'active', 'past_due', 'suspended', 'cancelled']
const rows = ref<BillingSubscription[]>([])
const companies = ref<Record<string, Array<{ id: number; name: string; tax_id: string | null }>>>({})
const plans = ref<BillingPlan[]>([])
const search = ref('')
const status = ref('')
const page = ref(1)
const lastPage = ref(1)
const isLoading = ref(false)
const isSaving = ref(false)
const paying = ref<BillingSubscription | null>(null)
const payment = reactive({ plan: 'start', months: 1, note: '' })

function companiesOf(row: BillingSubscription) {
  return row.owner ? companies.value[row.owner.id] ?? [] : []
}

async function load(to = 1): Promise<void> {
  isLoading.value = true
  try {
    const data = await billingService.adminSubscriptions({ page: to, search: search.value || undefined, status: status.value || undefined })
    rows.value = data.data
    companies.value = data.companies
    page.value = data.meta.current_page
    lastPage.value = data.meta.last_page
  } finally {
    isLoading.value = false
  }
}

const debouncedLoad = useDebounceFn(() => load(1), 300)

onMounted(async () => {
  plans.value = await billingService.adminPlans()
  await load()
})

function openPayment(row: BillingSubscription): void {
  paying.value = row
  payment.plan = row.plan?.code ?? 'start'
  payment.months = 1
  payment.note = ''
}

async function submitPayment(): Promise<void> {
  if (!paying.value) return
  isSaving.value = true
  try {
    await billingService.adminRecordPayment(paying.value.id, { ...payment })
    notificationStore.showNotification({ type: 'success', message: 'billing.admin.payment_recorded' })
    paying.value = null
    await load(page.value)
  } finally {
    isSaving.value = false
  }
}

async function update(row: BillingSubscription, payload: { plan?: string; status?: string }): Promise<void> {
  await billingService.adminUpdateSubscription(row.id, payload)
  notificationStore.showNotification({ type: 'success', message: 'general.setting_updated' })
  await load(page.value)
}
</script>
