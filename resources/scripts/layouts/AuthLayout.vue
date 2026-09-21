<template>
  <div class="min-h-screen w-full bg-surface lg:grid lg:grid-cols-[minmax(0,1.45fr)_minmax(0,1fr)]">
    <NotificationRoot />

    <!-- Brand panel: the promise, shown on large screens only -->
    <aside class="relative hidden overflow-hidden lg:block">
      <img
        :src="heroImage"
        alt=""
        class="absolute inset-0 h-full w-full object-cover object-[12%_center]"
      />
      <div
        class="absolute inset-0 bg-linear-to-t from-[#0F2624]/90 via-[#0F2624]/45 via-45% to-transparent to-75%"
      />

      <div class="relative flex h-full flex-col justify-end p-12 xl:p-16">
        <div class="max-w-md text-white">
          <p class="text-sm font-medium tracking-wide text-primary-200">
            {{ $t('auth_layout.hero.eyebrow') }}
          </p>
          <h2 class="mt-3 text-4xl font-semibold leading-tight tracking-tight">
            {{ $t('auth_layout.hero.title') }}
          </h2>
          <p class="mt-4 text-base leading-relaxed text-white/85">
            {{ $t('auth_layout.hero.description') }}
          </p>

          <ul class="mt-8 space-y-3 text-sm text-white/90">
            <li
              v-for="point in heroPoints"
              :key="point"
              class="flex items-center gap-3"
            >
              <span class="h-px w-6 shrink-0 bg-primary-300" />
              {{ point }}
            </li>
          </ul>

          <p class="mt-10 text-sm font-medium text-primary-200">
            {{ $t('auth_layout.hero.tagline') }}
          </p>
        </div>
      </div>
    </aside>

    <!-- Form panel -->
    <div class="relative flex min-h-screen flex-col">
      <div class="absolute z-10 top-4 right-4 sm:top-6 sm:right-6">
        <LanguageSwitcher />
      </div>

      <main class="flex flex-1 flex-col justify-center px-6 py-12 sm:px-12 xl:px-20">
        <div class="mx-auto w-full max-w-sm">
          <MainLogo v-if="!loginPageLogo" class="h-9 w-auto" />
          <img
            v-else
            :src="loginPageLogo"
            alt="AJUSTA"
            class="h-9 w-auto"
          />

          <header class="mt-12 mb-8">
            <h1 class="text-2xl font-semibold text-heading">
              {{ heading }}
            </h1>
            <p class="mt-2 text-sm text-muted">
              {{ subheading }}
            </p>
          </header>

          <router-view />
        </div>
      </main>

      <footer class="px-6 pb-6 text-center text-xs text-subtle sm:px-12">
        <span v-if="copyrightText">{{ copyrightText }}</span>
        <span v-else>© {{ new Date().getFullYear() }} AJUSTA</span>
      </footer>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import NotificationRoot from '@/scripts/components/notifications/NotificationRoot.vue'
import MainLogo from '@/scripts/components/icons/MainLogo.vue'
import LanguageSwitcher from '@/scripts/components/LanguageSwitcher.vue'

declare global {
  interface Window {
    login_page_heading?: string
    login_page_description?: string
    copyright_text?: string
    login_page_logo?: string
  }
}

const route = useRoute()
const { t } = useI18n()

const heroImage = new URL('$images/brand/login-hero.webp', import.meta.url).href

const heroPoints = computed<string[]>(() => [
  t('auth_layout.hero.point_invoicing'),
  t('auth_layout.hero.point_payroll'),
  t('auth_layout.hero.point_one_place'),
])

interface RouteCopy {
  heading: string
  subheading: string
}

const COPY: Record<string, RouteCopy> = {
  login: {
    heading: 'auth_layout.login.heading',
    subheading: 'auth_layout.login.subheading',
  },
  'forgot-password': {
    heading: 'auth_layout.forgot_password.heading',
    subheading: 'auth_layout.forgot_password.subheading',
  },
  'reset-password': {
    heading: 'auth_layout.reset_password.heading',
    subheading: 'auth_layout.reset_password.subheading',
  },
  'register-with-invitation': {
    heading: 'auth_layout.register.heading',
    subheading: 'auth_layout.register.subheading',
  },
}

const heading = computed<string>(() => {
  if (window.login_page_heading) return window.login_page_heading
  const name = route.name?.toString() ?? 'login'
  return t(COPY[name]?.heading ?? COPY.login.heading)
})

const subheading = computed<string>(() => {
  if (window.login_page_description) return window.login_page_description
  const name = route.name?.toString() ?? 'login'
  return t(COPY[name]?.subheading ?? COPY.login.subheading)
})

const copyrightText = computed<string | null>(() => window.copyright_text ?? null)

const loginPageLogo = computed<string | false>(() => {
  if (window.login_page_logo) return window.login_page_logo
  return false
})
</script>
