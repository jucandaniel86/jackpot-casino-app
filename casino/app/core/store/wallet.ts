import type { WalletT } from '../types/Wallet'

export const useWalletStore = defineStore(
  'wallet',
  () => {
    const currentWallet = ref<WalletT | null>()
    const currentWalletDepositPage = ref<WalletT | null>()
    const wallets = ref<WalletT[]>([])

    const setWallet = (_wallet: WalletT | null | undefined) => (currentWallet.value = _wallet)

    const setCurrentWalletDepositPage = (_wallet: WalletT | null) =>
      (currentWalletDepositPage.value = _wallet)

    const getCurrentWallet = async (): Promise<void> => {
      const { data } = await useAPIFetch('/player/wallets/current')
      setWallet(data)
    }

    const setWallets = (_wallets: WalletT[]) => {
      wallets.value = _wallets
    }

    const cryptoWallets = computed(() => {
      return wallets.value.filter((wallet) => !wallet.is_fiat)
    })

    const fiatWallets = computed(() => {
      return wallets.value.filter((wallet) => wallet.is_fiat)
    })

    return {
      currentWallet,
      currentWalletDepositPage,
      wallets,
      cryptoWallets,
      fiatWallets,
      setCurrentWalletDepositPage,
      setWallet,
      getCurrentWallet,
      setWallets,
    }
  },
  {
    persist: true,
  },
)
