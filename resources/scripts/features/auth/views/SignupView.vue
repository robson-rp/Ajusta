<template>
  <form class="text-left" @submit.prevent="submit">
    <!-- Plan -->
    <fieldset class="mb-6">
      <legend class="mb-2 text-sm font-medium text-heading">{{ $t('signup.plan') }}</legend>
      <div class="grid grid-cols-2 gap-3">
        <label
          v-for="plan in plans"
          :key="plan.code"
          :class="[
            'cursor-pointer rounded-lg border px-4 py-3 transition-colors',
            form.plan === plan.code
              ? 'border-primary-500 bg-primary-50'
              : 'border-line-default hover:border-line-strong',
          ]"
        >
          <input v-model="form.plan" type="radio" class="sr-only" :value="plan.code" />
          <span class="block text-sm font-semibold text-heading">{{ plan.name }}</span>
          <span class="block text-xs text-muted">{{ formatKz(plan.price_monthly, false) }}{{ $t('signup.per_month') }}</span>
        </label>
      </div>
      <p class="mt-2 text-xs text-muted">{{ $t('signup.trial_note', { days: trialDays }) }}</p>
    </fieldset>

    <BaseInputGroup :label="$t('signup.company_name')" :error="fieldError('company_name')" class="mb-4" required>
      <BaseInput v-model.trim="form.company_name" :invalid="!!fieldError('company_name')" />
    </BaseInputGroup>

    <BaseInputGroup label="NIF" :error="fieldError('tax_id')" class="mb-4" required>
      <BaseInput v-model.trim="form.tax_id" :invalid="!!fieldError('tax_id')" />
    </BaseInputGroup>

    <div class="grid grid-cols-1 gap-4 mb-4 sm:grid-cols-2">
      <BaseInputGroup :label="$t('signup.your_name')" :error="fieldError('name')" required>
        <BaseInput v-model.trim="form.name" :invalid="!!fieldError('name')" />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('signup.phone')" :error="fieldError('phone')" required>
        <BaseInput v-model.trim="form.phone" type="tel" placeholder="923 000 000" :invalid="!!fieldError('phone')" />
      </BaseInputGroup>
    </div>

    <BaseInputGroup :label="$t('login.email')" :error="fieldError('email')" class="mb-4" required>
      <BaseInput v-model.trim="form.email" type="email" :invalid="!!fieldError('email')" />
    </BaseInputGroup>

    <div class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-2">
      <BaseInputGroup :label="$t('login.password')" :error="fieldError('password')" required>
        <BaseInput v-model="form.password" type="password" :invalid="!!fieldError('password')" />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('login.confirm_password')" required>
        <BaseInput v-model="form.password_confirmation" type="password" />
      </BaseInputGroup>
    </div>

    <BaseButton :loading="isSubmitting" :disabled="isSubmitting" type="submit" class="w-full justify-center">
      {{ $t('signup.submit', { days: trialDays }) }}
    </BaseButton>

    <p class="mt-6 text-sm text-center text-muted">
      {{ $t('signup.have_account') }}
      <router-link to="/login" class="font-medium text-primary-600 hover:text-primary-700">
        {{ $t('login.login') }}
      </router-link>
    </p>
  </form>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { billingService } from '@/scripts/api/services/billing.service'
import type { BillingPlan } from '@/scripts/api/services/billing.service'
import { formatKz } from '@/scripts/utils/format-kz'
import { useNotificationStore } from '@/scripts/stores/notification.store'
import { useAuthStore } from '@/scripts/stores/auth.store'
import * as localStore from '@/scripts/utils/local-storage'

const route = useRoute()
const notificationStore = useNotificationStore()
const authStore = useAuthStore()

const plans = ref<BillingPlan[]>([])
const trialDays = ref(14)
const isSubmitting = ref(false)
const errors = ref<Record<string, string[]>>({})

const form = reactive({
  plan: typeof route.query.plan === 'string' ? route.query.plan : 'start',
  company_name: '',
  tax_id: '',
  name: '',
  phone: '',
  email: '',
  password: '',
  password_confirmation: '',
})

function fieldError(field: string): string | undefined {
  return errors.value[field]?.[0]
}

onMounted(async () => {
  try {
    const data = await billingService.signupPlans()
    plans.value = data.plans
    trialDays.value = data.trial_days
    if (!plans.value.some((plan) => plan.code === form.plan) && plans.value.length) {
      form.plan = plans.value[0].code
    }
  } catch {
    // The form still works with the default plan code.
  }
})

async function submit(): Promise<void> {
  isSubmitting.value = true
  errors.value = {}

  try {
    const { company_id } = await billingService.signup(form)
    // The admin shell needs a web session: sign in like the login page does.
    await authStore.login({ email: form.email, password: form.password })
    localStore.set('selectedCompany', String(company_id))
    window.location.assign('/admin/dashboard')
  } catch (err: unknown) {
    const response = (err as { response?: { status?: number; data?: { errors?: Record<string, string[]>; message?: string } } }).response
    if (response?.status === 422 && response.data?.errors) {
      errors.value = response.data.errors
    } else {
      notificationStore.showNotification({
        type: 'error',
        message: response?.status === 403 ? 'signup.disabled' : 'general.something_went_wrong',
      })
    }
    isSubmitting.value = false
  }
}
</script>
