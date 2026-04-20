import { useAccount, useConnect } from '@wagmi/vue'
import { useAuthStore } from '~/core/store/auth'

export const useWalletConnect = () => {
  const { walletConnectLogin, setConnectedWallet } = useAuthStore()
  const { address, isConnected } = useAccount()
  const { connectAsync, connectors } = useConnect()
  const changeView = ref<string>('')

  const connect = async () => {
    try {
      const walletConnectConnector = connectors.find((c) => c.id === 'walletConnect')
      const connector = walletConnectConnector ?? connectors[0]

      if (!connector) {
        console.error('No wagmi connector is available.')
        return
      }

      const result = await connectAsync({ connector })
      const connectedAddress = result.accounts?.[0] ?? address.value

      if (!connectedAddress) {
        console.error('Wallet connected but no address was returned.')
        return
      }

      const loginResponse = await walletConnectLogin(connectedAddress)
      if (!loginResponse) {
        changeView.value = 'register'
      }
    } catch (error) {
      console.error('WalletConnect login failed:', error)
    }
  }

  onMounted(() => {
    setConnectedWallet('')
  })

  return {
    isConnected,
    changeView,
    connect,
  }
}
