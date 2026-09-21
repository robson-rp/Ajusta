/**
 * Everything that must be confirmed before launch lives here.
 * TODO: replace the placeholders with the real domain and contacts.
 */
/**
 * Where the AJUSTA app lives. Set PUBLIC_APP_URL in website/.env
 * (e.g. http://invoiceshelf.test locally); production defaults to the real
 * domain.
 */
export const appBase = (import.meta.env.PUBLIC_APP_URL || 'https://app.ajusta.ao').replace(/\/+$/, '')

export const site = {
  name: 'AJUSTA',
  tagline: 'Uma conta. Um NIF. Um ciclo.',
  positioning: 'Facturação ajustada à realidade de Angola.',

  /** Where "Iniciar sessão" goes. */
  appUrl: `${appBase}/login`,

  /** Contact for demos and questions. TODO: confirm. */
  contactEmail: 'ola@ajusta.ao',
  /** International format, digits only, e.g. 244923000000. Empty hides WhatsApp. */
  whatsapp: '',

  sourceUrl: 'https://github.com/robson-rp/Ajusta',
  upstreamUrl: 'https://github.com/InvoiceShelf/InvoiceShelf',
}

/**
 * Legal entity shown in the Privacy Policy and Terms.
 * TODO: fill in before launch and have both pages reviewed by a lawyer.
 */
export const legal = {
  company: '[Denominação social], Lda',
  nif: '[NIF]',
  address: '[Morada], Luanda, Angola',
  email: 'privacidade@ajusta.ao',
  updatedAt: '21 de Setembro de 2026',
}

/** mailto: link with a pre-filled subject, so each button tells us why the person wrote. */
export function contactLink(subject: string): string {
  return `mailto:${site.contactEmail}?subject=${encodeURIComponent(subject)}`
}

/**
 * Fallback monthly prices in Kwanza, used only when the app cannot be reached
 * at build time. The real prices come from the app (see src/plans.ts).
 */
export const prices = {
  start: 6000,
  business: 25000,
}

export function formatKz(value: number): string {
  // Same grouping as the app's Kwanza: 15.000 Kz
  return `${String(value).replace(/\B(?=(\d{3})+(?!\d))/g, '.')} Kz`
}

/** Self-service signup in the app, with the plan pre-selected. */
export function signupLink(plan: 'start' | 'business' = 'start'): string {
  return `${appBase}/signup?plan=${plan}`
}

export const cta = {
  start: signupLink('start'),
  startBusiness: signupLink('business'),
  demo: contactLink('Pedido de demonstração do AJUSTA'),
  talk: contactLink('Falar com a equipa AJUSTA'),
  ai: contactLink('Quero acompanhar a IA do AJUSTA'),
}
