<template>
  <BasePage>
    <BasePageHeader :title="$t('modules.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('modules.module', 2)" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <BaseCard class="mt-6">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h6 class="text-heading text-lg font-medium">{{ $t('modules.marketplace.access_title') }}</h6>
          <p class="mt-1 text-sm text-muted">
            {{ $t('modules.marketplace.access_description') }}
          </p>
          <div v-if="pairingCode" class="mt-4 space-y-1 text-sm text-body">
            <i18n-t keypath="modules.marketplace.enter_code" tag="p">
              <template #code><strong>{{ pairingCode.user_code }}</strong></template>
            </i18n-t>
            <a v-if="pairingCode.verification_uri_complete || pairingCode.verification_uri" class="text-primary-600 underline" :href="pairingCode.verification_uri_complete || pairingCode.verification_uri || undefined" target="_blank" rel="noopener">{{ $t('modules.marketplace.open_verification') }}</a>
          </div>
        </div>
        <div class="flex flex-wrap gap-3">
          <BaseButton v-if="!moduleStore.marketplacePairing?.paired" :loading="isPairing" @click="startPairing">
            {{ $t('modules.marketplace.pair') }}
          </BaseButton>
          <BaseButton v-if="pairingCode" variant="primary-outline" :loading="isPolling" @click="pollPairing">
            {{ $t('modules.marketplace.approved') }}
          </BaseButton>
          <BaseButton v-if="moduleStore.marketplacePairing?.paired" variant="primary-outline" @click="disconnect">
            {{ $t('modules.marketplace.disconnect') }}
          </BaseButton>
        </div>
      </div>
    </BaseCard>

    <div class="mt-6">
      <BaseTabGroup @change="setStatusFilter">
        <BaseTab :title="$t('general.all')" filter="" />
        <BaseTab :title="$t('modules.installed')" filter="INSTALLED" />
      </BaseTabGroup>
      <div v-if="isFetchingModule" class="grid mt-6 w-full grid-cols-1 items-start gap-6 lg:grid-cols-2 xl:grid-cols-3">
        <div v-for="n in 3" :key="n" class="h-80 bg-surface-tertiary rounded-lg animate-pulse" />
      </div>
      <div v-else-if="filteredModules.length" class="grid mt-6 w-full grid-cols-1 items-start gap-6 lg:grid-cols-2 xl:grid-cols-3">
        <ModuleCard v-for="mod in filteredModules" :key="mod.slug" :data="mod" />
      </div>
      <div v-else class="mt-24">
        <label class="flex items-center justify-center text-muted">
          {{ activeTab === 'INSTALLED' ? $t('modules.no_modules_installed') : $t('modules.marketplace.none_available') }}
        </label>
      </div>
    </div>
  </BasePage>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { computed, onMounted, ref } from 'vue'
import { useModuleStore } from '../store'
import ModuleCard from '../components/ModuleCard.vue'
import type { MarketplacePairingCode } from '@/scripts/api/services/module.service'
import type { Module } from '@/scripts/types/domain/module'
import { useNotificationStore } from '@/scripts/stores/notification.store'

const moduleStore = useModuleStore()
const { t } = useI18n()
const notificationStore = useNotificationStore()
const activeTab = ref('')
const isFetchingModule = ref(false)
const isPairing = ref(false)
const isPolling = ref(false)
const pairingCode = ref<MarketplacePairingCode | null>(null)

const filteredModules = computed<Module[]>(() => activeTab.value === 'INSTALLED'
  ? moduleStore.installedModules
  : moduleStore.modules)

onMounted(async () => {
  await Promise.all([moduleStore.fetchMarketplacePairing(), fetchModulesData()])
})

async function fetchModulesData(): Promise<void> {
  isFetchingModule.value = true
  try {
    await moduleStore.fetchModules()
  } finally {
    isFetchingModule.value = false
  }
}

async function startPairing(): Promise<void> {
  isPairing.value = true
  try {
    pairingCode.value = await moduleStore.startMarketplacePairing()
  } finally {
    isPairing.value = false
  }
}

async function pollPairing(): Promise<void> {
  isPolling.value = true
  try {
    const result = await moduleStore.pollMarketplacePairing()
    if (result.status === 'paired') {
      pairingCode.value = null
      notificationStore.showNotification({ type: 'success', message: t('modules.marketplace.paired') })
      await fetchModulesData()
    } else {
      notificationStore.showNotification({ type: 'info', message: t('modules.marketplace.waiting') })
    }
  } finally {
    isPolling.value = false
  }
}

async function disconnect(): Promise<void> {
  await moduleStore.disconnectMarketplace()
  notificationStore.showNotification({ type: 'success', message: t('modules.marketplace.disconnected') })
  await fetchModulesData()
}

function setStatusFilter(data: { filter: string }): void {
  activeTab.value = data.filter
}
</script>
