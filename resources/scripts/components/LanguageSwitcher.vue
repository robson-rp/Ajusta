<template>
  <div ref="root" class="relative">
    <button
      type="button"
      :class="triggerClass"
      :aria-label="$t('settings.language')"
      aria-haspopup="listbox"
      :aria-expanded="isOpen"
      @click="isOpen = !isOpen"
    >
      <BaseIcon name="LanguageIcon" class="w-4 h-4" />
      <span class="text-sm font-medium">{{ localeShortLabel(currentLocale) }}</span>
    </button>

    <transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="translate-y-1 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="translate-y-1 opacity-0"
    >
      <ul
        v-if="isOpen"
        role="listbox"
        class="absolute right-0 z-30 mt-2 min-w-44 py-1 bg-surface border border-line-light rounded-lg shadow-md"
      >
        <li
          v-for="language in languages"
          :key="language.code"
          role="option"
          :aria-selected="language.code === currentLocale"
          tabindex="0"
          class="flex items-center justify-between gap-3 px-3 py-2 text-sm cursor-pointer text-body hover:bg-hover focus:bg-hover focus:outline-hidden"
          @click="select(language.code)"
          @keydown.enter.prevent="select(language.code)"
          @keydown.space.prevent="select(language.code)"
        >
          <span class="whitespace-nowrap" :class="{ 'font-medium text-heading': language.code === currentLocale }">
            {{ language.name }}
          </span>
          <BaseIcon
            v-if="language.code === currentLocale"
            name="CheckIcon"
            class="w-4 h-4 text-primary-500"
          />
        </li>
      </ul>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onClickOutside } from '@vueuse/core'
import { useI18n } from 'vue-i18n'
import { useGlobalStore } from '@/scripts/stores/global.store'
import { useUserStore } from '@/scripts/stores/user.store'
import {
  FALLBACK_LANGUAGES,
  localeShortLabel,
  writeLocalePreference,
} from '@/scripts/config/locale'

interface Props {
  /**
   * `user` saves the choice on the signed-in user (admin area); `local`
   * keeps it in this browser only (login, installation, customer portal).
   */
  persist?: 'user' | 'local'
  /** `header` sits on the app header; `plain` on a light page surface. */
  variant?: 'header' | 'plain'
}

const props = withDefaults(defineProps<Props>(), {
  persist: 'local',
  variant: 'plain',
})

const { locale } = useI18n()
const globalStore = useGlobalStore()
const userStore = useUserStore()

const isOpen = ref(false)
const root = ref<HTMLElement | null>(null)

onClickOutside(root, () => {
  isOpen.value = false
})

const currentLocale = computed(() => locale.value)

const languages = computed(() => {
  const configured = (globalStore.config as Record<string, unknown> | null)?.languages
  return Array.isArray(configured) && configured.length
    ? (configured as Array<{ code: string; name: string }>)
    : FALLBACK_LANGUAGES
})

const triggerClass = computed(() => [
  'flex items-center gap-1.5 h-8 md:h-9 px-2.5 rounded-lg transition-colors',
  props.variant === 'header'
    ? 'text-muted hover:text-heading hover:bg-hover-strong'
    : 'text-muted hover:text-heading hover:bg-hover',
])

async function select(code: string): Promise<void> {
  isOpen.value = false
  if (code === currentLocale.value) return

  await window.loadLanguage?.(code)
  writeLocalePreference(code)

  if (props.persist === 'user') {
    await userStore.updateUserSettings({ settings: { language: code } })
  }
}
</script>
