<template>
  <div class="relative min-h-screen w-full bg-surface-secondary">
    <NotificationRoot />

    <div class="absolute top-4 right-4 sm:top-6 sm:right-6">
      <LanguageSwitcher />
    </div>

    <main
      class="
        relative flex min-h-screen flex-col items-center justify-center
        px-4 py-12 sm:px-6
      "
    >
      <div class="mb-10 flex justify-center">
        <MainLogo v-if="!loginPageLogo" class="h-10 w-auto" />
        <img
          v-else
          :src="loginPageLogo"
          alt="AJUSTA"
          class="h-10 w-auto"
        />
      </div>

      <article
        class="
          w-full max-w-md
          bg-surface
          rounded-xl
          border border-line-light
          px-8 py-10 sm:px-10
        "
      >
        <header class="mb-8">
          <h1 class="text-xl font-semibold text-heading">
            {{ heading }}
          </h1>
          <p class="mt-1.5 text-sm text-muted">
            {{ subheading }}
          </p>
        </header>

        <router-view />
      </article>

      <footer class="mt-10 text-center text-xs text-subtle">
        <span v-if="copyrightText">{{ copyrightText }}</span>
        <span v-else>© {{ new Date().getFullYear() }} AJUSTA</span>
      </footer>
    </main>
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
