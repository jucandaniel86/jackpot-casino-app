<script setup lang="ts">
import { breakpointsTailwind, useBreakpoints, useWindowFocus } from '@vueuse/core'
import { useAppStore } from '~/core/store/app'
import { useAuthStore } from '~/core/store/auth'
import { useWalletStore } from '~/core/store/wallet'
import { OverlaysTypes } from '~/core/types/Overlays'
import type { WalletT } from '~/core/types/Wallet'

//composables
const { setCurrentWalletDepositPage } = useWalletStore()
const { currentWallet, wallets } = storeToRefs(useWalletStore())
const { isLogged } = storeToRefs(useAuthStore())
const { logout } = useAuthStore()
const { getCurrentWallet, setWallets } = useWalletStore()
const { replace } = useRouter()
const { name } = useDisplay()
const route = useRoute()
const focused = useWindowFocus()
const { loadWallets } = storeToRefs(useAppStore())
const { t } = useI18n()
const breakpoints = useBreakpoints(breakpointsTailwind)
const isMobileBp = breakpoints.smaller('sm')
const loadingSession = ref<boolean>(false)

//models
const menu = ref<boolean>(false)

const walletSelectLoading = ref<boolean>(false)

const ensureSessionIsValid = async (): Promise<boolean> => {
  if (!isLogged.value) return false
  loadingSession.value = true
  try {
    await useAPIFetch('/player/profile')
    loadingSession.value = false
    return true
  } catch (_error: any) {
    await logout()
    console.warn('error', _error)
    loadingSession.value = false
    return false
  }
}

const openWalletModal = async (): Promise<boolean> => {
  const isSessionValid = await ensureSessionIsValid()
  if (!isSessionValid) return false
  replace({ query: { overlay: OverlaysTypes.WALLET } })
  return true
}
const getUserWallets = async (): Promise<void> => {
  const { wallets: dataWallets } = await useAPIFetch('/player/wallets')
  if (dataWallets) {
    setWallets(dataWallets)
  }
}

const depositPepagy = async (): Promise<void> => {
  menu.value = false

  const opened = await openWalletModal()
  if (!opened) return

  setTimeout(() => {
    setCurrentWalletDepositPage(currentWallet.value as WalletT)
  }, 600)
}

const convertBalance = (balance: number, decimal: number) => Number(balance).toFixed(decimal)

//computed
const isMobile = computed(() => ['xs', 'sm'].indexOf(name.value) !== -1)
const isDesktop = computed(() => ['lg', 'md', 'xl'].indexOf(name.value) !== -1)
const inGame = computed(() => route.name === 'game-slug')
const bonusWallet = computed(() => wallets.value.find((wallet) => wallet.purpose === 'bonus'))
const totalBalance = computed(() => {
  const available = Number(currentWallet.value?.available ?? 0)
  const bonus = Number(bonusWallet.value?.available ?? 0)
  const precision = currentWallet.value?.precision ?? 0

  return Number((available + bonus).toFixed(precision))
})

//mounted
onMounted(async () => {
  await getUserWallets()
  if (!currentWallet.value) {
    // setWallet(WALLET_CONFIG.find((el) => el.balance > 0) as WalletType)
  }
})

//watchers
watch(focused, () => {
  console.log('watch:: logged, focused', isLogged.value, focused.value)
  if (focused.value && !isLogged.value) {
    logout()
    currentWallet.value = null
  }
})

watch(loadWallets, async () => {
  if (loadWallets) {
    await getUserWallets()
    await getCurrentWallet()
    loadWallets.value = false
  }
})

watch(isLogged, () => {
  if (!isLogged.value) {
    currentWallet.value = null
  }
})
</script>
<template>
  <div class="d-flex justify-center align-center ga-1 mx-auto">
    <v-menu
      v-if="currentWallet"
      v-model="menu"
      :close-on-content-click="false"
      location="bottom center"
      target="parent"
      scroll-strategy="block"
      :open-delay="400"
    >
      <template #activator="{ props }">
        <v-btn
          v-bind="props"
          class="wallet_btn"
          :disabled="inGame || loadingSession"
          :loading="loadingSession"
          style="box-shadow: none !important"
        >
          <div class="d-flex justify-center align-center ga-2">
            <span v-if="!inGame">{{ totalBalance }}</span>
            <span v-else>(In Play)</span>
            <SharedIcon
              :icon="`currency-ico-${String(currentWallet.code).toLowerCase()}`"
              class="svg-icon"
            />
          </div>
          <template #append>
            <IconArrowDown :class="{ 'arrow-up': menu, 'arrow-down': !menu }" />
          </template>
        </v-btn>
      </template>

      <v-card
        :min-width="isMobileBp ? '100%' : 414"
        width="100%"
        class="mt-3 wallet-currencies-modal"
      >
        <div class="close-btn-wrapper">
          <v-btn class="close-btn" @click.prevent="menu = false">
            <IconClose />
          </v-btn>
        </div>
        <div class="content">
          <div class="wrapper">
            <div class="wallet_header">
              <div class="current-balance">
                <span class="balance-label">{{ t('wallet.balance') }}</span>
                <span class="balance-value">
                  {{ convertBalance(totalBalance, currentWallet.precision) }}
                  <span>{{ currentWallet.code }}</span>
                </span>
              </div>
              <div class="balance-data">
                <div class="balance-item">
                  <span class="balance-label">{{ t('wallet.withdrawable') }}</span>
                  <span class="balance-value">
                    {{ convertBalance(currentWallet.available, currentWallet.precision) }}
                    <span>{{ currentWallet.code }}</span>
                  </span>
                </div>
                <div class="balance-item" v-if="bonusWallet">
                  <span class="balance-label">{{ t('wallet.bonus') }}</span>
                  <span class="balance-value">
                    {{ convertBalance(bonusWallet?.available, currentWallet.precision) }}
                    <span>{{ currentWallet.code }}</span>
                  </span>
                </div>
              </div>
              <div class="pa-2 d-flex align-center justify-center ga-2">
                <v-btn color="purple" @click.prevent="depositPepagy">Deposit</v-btn>
                <v-btn color="purple">Buy</v-btn>
              </div>
            </div>
            <div class="wallet_currencies_wrapper">
              <v-progress-linear v-if="walletSelectLoading" indeterminate color="purple" />
            </div>
          </div>
        </div>
      </v-card>
    </v-menu>
    <v-btn
      v-if="isDesktop && !inGame"
      color="primary"
      variant="flat"
      class="wallet_trigger_btn"
      @click.prevent="openWalletModal"
      >{{ t('wallet.wallet') }}</v-btn
    >
    <v-btn
      v-if="isMobile && !inGame"
      class="mobile-wallet-button ml-2"
      style="box-shadow: none !important"
      @click.prevent="openWalletModal"
    >
      <shared-icon icon="brand-ico-wallet2" class="svg-icon" />
    </v-btn>
  </div>
</template>
