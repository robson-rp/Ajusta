/** Cents of Kwanza → "15.000,00 Kz" (same grouping as the app's AOA). */
export function formatKz(cents: number, withDecimals = true): string {
  const value = cents / 100
  const [int, dec] = value.toFixed(2).split('.')
  const grouped = int.replace(/\B(?=(\d{3})+(?!\d))/g, '.')

  return withDecimals ? `${grouped},${dec} Kz` : `${grouped} Kz`
}

/** ISO date → dd/mm/yyyy. */
export function formatDate(iso: string | null | undefined): string {
  if (!iso) return '—'
  const date = new Date(iso)
  const dd = String(date.getDate()).padStart(2, '0')
  const mm = String(date.getMonth() + 1).padStart(2, '0')

  return `${dd}/${mm}/${date.getFullYear()}`
}
