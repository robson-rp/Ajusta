import { client } from '../client'

export interface BillingPlan {
  id: number
  code: string
  name: string
  price_monthly: number
  features: {
    max_users?: number | null
    max_companies?: number | null
    customer_portal?: boolean
    recurring_invoices?: boolean
    advanced_reports?: boolean
  }
  is_public: boolean
  sort: number
}

export type SubscriptionStatus = 'trialing' | 'active' | 'past_due' | 'suspended' | 'cancelled'

export interface BillingSubscription {
  id: number
  status: SubscriptionStatus
  plan: BillingPlan | null
  trial_ends_at: string | null
  current_period_ends_at: string | null
  grace_ends_at: string | null
  ends_at: string | null
  days_left: number | null
  writable: boolean
  is_owner?: boolean
  owner?: { id: number; name: string; email: string }
}

export type ChargeStatus = 'pending' | 'paid' | 'failed' | 'cancelled' | 'expired'

export interface BillingCharge {
  uuid: string
  plan?: { code: string; name: string }
  months: number
  amount: number
  currency: string
  gateway: string
  method: string
  status: ChargeStatus
  message: string | null
  reference_number: string | null
  entity_number: string | null
  customer_phone: string | null
  expires_at: string | null
  paid_at: string | null
  created_at: string | null
}

export interface CheckoutOption {
  gateway: string
  method: string
  label: string
  requires_phone: boolean
}

export interface BillingOverview {
  subscription: BillingSubscription | null
  can_manage: boolean
  plans: BillingPlan[]
  periods: number[]
  e_invoice_fee: number
  options: CheckoutOption[]
  charges: BillingCharge[]
}

export interface SignupPayload {
  company_name: string
  tax_id: string
  name: string
  email: string
  phone: string
  password: string
  password_confirmation: string
  plan: string
}

export const billingService = {
  async signupPlans(): Promise<{ enabled: boolean; trial_days: number; plans: BillingPlan[] }> {
    const { data } = await client.get('/api/v1/signup/plans')
    return data
  },

  async signup(payload: SignupPayload): Promise<{ token: string; company_id: number }> {
    // Same-site SPA requests are CSRF-protected by Sanctum.
    await client.get('/sanctum/csrf-cookie')
    const { data } = await client.post('/api/v1/signup', payload)
    return data
  },

  async overview(): Promise<BillingOverview> {
    const { data } = await client.get('/api/v1/billing')
    return data
  },

  async createCharge(payload: { plan: string; months: number; method: string; phone?: string | null }): Promise<BillingCharge> {
    const { data } = await client.post('/api/v1/billing/charges', payload)
    return data.data
  },

  async charge(uuid: string): Promise<BillingCharge> {
    const { data } = await client.get(`/api/v1/billing/charges/${uuid}`)
    return data.data
  },

  // Platform admin

  async adminSubscriptions(params: Record<string, unknown> = {}) {
    const { data } = await client.get('/api/v1/super-admin/billing/subscriptions', { params })
    return data as {
      data: BillingSubscription[]
      companies: Record<string, Array<{ id: number; name: string; tax_id: string | null }>>
      meta: { current_page: number; last_page: number; total: number }
    }
  },

  async adminUpdateSubscription(id: number, payload: { plan?: string; status?: string }): Promise<BillingSubscription> {
    const { data } = await client.put(`/api/v1/super-admin/billing/subscriptions/${id}`, payload)
    return data.data
  },

  async adminRecordPayment(id: number, payload: { plan: string; months: number; note?: string }): Promise<BillingSubscription> {
    const { data } = await client.post(`/api/v1/super-admin/billing/subscriptions/${id}/payments`, payload)
    return data.data
  },

  async adminPlans(): Promise<BillingPlan[]> {
    const { data } = await client.get('/api/v1/super-admin/billing/plans')
    return data.data
  },

  async adminUpdatePlan(id: number, payload: Partial<BillingPlan>): Promise<BillingPlan> {
    const { data } = await client.put(`/api/v1/super-admin/billing/plans/${id}`, payload)
    return data.data
  },
}
