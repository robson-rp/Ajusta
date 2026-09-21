import { appBase, prices } from './site.config'

export interface PublicPlan {
  code: string
  name: string
  /** Monthly price in Kwanza (units, not cents). */
  price: number
}

/**
 * The plans the app actually sells, read from its public API when the site
 * is built, so the website can never drift from the database. If the app is
 * not reachable at build time, fall back to the prices in site.config.ts.
 */
export async function loadPlans(): Promise<Record<string, PublicPlan>> {
  const fallback: Record<string, PublicPlan> = {
    start: { code: 'start', name: 'Start', price: prices.start },
    business: { code: 'business', name: 'Empresa', price: prices.business },
  }

  try {
    const response = await fetch(`${appBase}/api/v1/signup/plans`, {
      headers: { Accept: 'application/json' },
      signal: AbortSignal.timeout(5000),
    })

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`)
    }

    const data = (await response.json()) as { plans: Array<{ code: string; name: string; price_monthly: number }> }
    const plans: Record<string, PublicPlan> = {}

    for (const plan of data.plans) {
      plans[plan.code] = { code: plan.code, name: plan.name, price: plan.price_monthly / 100 }
    }

    return { ...fallback, ...plans }
  } catch (error) {
    console.warn(`[plans] Could not read plans from ${appBase}; using site.config prices.`, error)

    return fallback
  }
}
