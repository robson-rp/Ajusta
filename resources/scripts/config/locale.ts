/** Language used when nobody has chosen one yet. */
export const DEFAULT_LOCALE = 'pt_AO'

/** Languages offered by the switcher before the server config is loaded. */
export const FALLBACK_LANGUAGES: ReadonlyArray<{ code: string; name: string }> = [
  { code: 'pt_AO', name: 'Português (Angola)' },
  { code: 'en', name: 'English' },
]

const LOCALE_PREFERENCE_KEY = 'ajusta.locale'

/**
 * Language picked in the switcher on this browser. Used where there is no
 * signed-in user to store it on (login, installation, customer portal).
 * localStorage can throw in private-browsing edge cases, hence the guards.
 */
export function readLocalePreference(): string | null {
  try {
    return localStorage.getItem(LOCALE_PREFERENCE_KEY)
  } catch {
    return null
  }
}

export function writeLocalePreference(locale: string): void {
  try {
    localStorage.setItem(LOCALE_PREFERENCE_KEY, locale)
  } catch {
    // Not persisted; the choice still applies to this page view.
  }
}

/** Short label shown on the switcher button, e.g. "PT" for pt_AO. */
export function localeShortLabel(locale: string): string {
  return locale.split(/[_-]/)[0].toUpperCase()
}

/** Convert an app locale code (pt_AO) to a BCP 47 tag for Intl (pt-AO). */
export function toBcp47(locale: string): string {
  return locale.replace('_', '-')
}
