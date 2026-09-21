<template>
  <BasePage>
    <BasePageHeader :title="$t('billing.admin.plans')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('billing.admin.plans')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <div class="grid gap-6 mt-6 md:grid-cols-2">
      <form
        v-for="plan in plans"
        :key="plan.id"
        class="p-6 bg-surface border rounded-xl border-line-light"
        @submit.prevent="save(plan)"
      >
        <div class="flex items-center justify-between">
          <h3 class="font-semibold text-heading">{{ plan.code }}</h3>
          <BaseSwitch v-model="plan.is_public" :label-right="$t('billing.admin.public')" />
        </div>

        <BaseInputGroup :label="$t('billing.admin.plan_name')" class="mt-4">
          <BaseInput v-model="plan.name" />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('billing.admin.price_monthly')" class="mt-4">
          <BaseInput v-model.number="priceKz[plan.id]" type="number" min="0" step="100" />
        </BaseInputGroup>

        <div class="grid grid-cols-2 gap-4 mt-4">
          <BaseInputGroup :label="$t('billing.admin.max_users')" :help-text="$t('billing.admin.empty_unlimited')">
            <BaseInput v-model.number="plan.features.max_users" type="number" min="1" />
          </BaseInputGroup>
          <BaseInputGroup :label="$t('billing.admin.max_companies')" :help-text="$t('billing.admin.empty_unlimited')">
            <BaseInput v-model.number="plan.features.max_companies" type="number" min="1" />
          </BaseInputGroup>
        </div>

        <div class="mt-4 space-y-2">
          <BaseSwitch v-model="plan.features.customer_portal" :label-right="$t('billing.features.customer_portal')" />
          <BaseSwitch v-model="plan.features.recurring_invoices" :label-right="$t('billing.features.recurring_invoices')" />
          <BaseSwitch v-model="plan.features.advanced_reports" :label-right="$t('billing.features.advanced_reports')" />
        </div>

        <BaseButton type="submit" class="mt-6" :loading="savingId === plan.id">{{ $t('general.save') }}</BaseButton>
      </form>
    </div>
  </BasePage>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { billingService } from '@/scripts/api/services/billing.service'
import type { BillingPlan } from '@/scripts/api/services/billing.service'
import { useNotificationStore } from '@/scripts/stores/notification.store'

const notificationStore = useNotificationStore()
const plans = ref<BillingPlan[]>([])
// Prices are edited in Kwanza and stored in cents.
const priceKz = reactive<Record<number, number>>({})
const savingId = ref<number | null>(null)

onMounted(async () => {
  plans.value = await billingService.adminPlans()
  for (const plan of plans.value) {
    priceKz[plan.id] = plan.price_monthly / 100
  }
})

function limit(value: unknown): number | null {
  const n = Number(value)
  return Number.isFinite(n) && n > 0 ? n : null
}

async function save(plan: BillingPlan): Promise<void> {
  savingId.value = plan.id
  try {
    await billingService.adminUpdatePlan(plan.id, {
      name: plan.name,
      is_public: plan.is_public,
      price_monthly: Math.round((priceKz[plan.id] ?? 0) * 100),
      features: {
        ...plan.features,
        max_users: limit(plan.features.max_users),
        max_companies: limit(plan.features.max_companies),
      },
    })
    notificationStore.showNotification({ type: 'success', message: 'general.setting_updated' })
  } finally {
    savingId.value = null
  }
}
</script>
