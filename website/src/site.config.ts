/**
 * Everything that must be confirmed before launch lives here.
 * TODO: replace the placeholders with the real domain and contacts.
 */
export const site = {
  name: 'AJUSTA',
  tagline: 'Uma conta. Um NIF. Um ciclo.',
  positioning: 'Facturação ajustada à realidade de Angola.',

  /** Where "Iniciar sessão" goes. */
  appUrl: 'https://app.ajusta.ao/login',

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

/** Indicative monthly prices in Kwanza. TODO: confirm before launch. */
export const prices = {
  start: 15000,
  business: 45000,
}

export function formatKz(value: number): string {
  // Same grouping as the app's Kwanza: 15.000 Kz
  return `${String(value).replace(/\B(?=(\d{3})+(?!\d))/g, '.')} Kz`
}

/** Self-service signup in the app, with the plan pre-selected. */
export function signupLink(plan: 'start' | 'business' = 'start'): string {
  return `${site.appUrl.replace(/\/login$/, '')}/signup?plan=${plan}`
}

export const cta = {
  start: signupLink('start'),
  startBusiness: signupLink('business'),
  demo: contactLink('Pedido de demonstração do AJUSTA'),
  talk: contactLink('Falar com a equipa AJUSTA'),
  ai: contactLink('Quero acompanhar a IA do AJUSTA'),
}
