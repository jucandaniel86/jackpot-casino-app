import { createConfig, http } from '@wagmi/vue'
import { mainnet, sepolia } from '@wagmi/vue/chains'
import { injected, walletConnect } from '@wagmi/connectors'

// WalletConnect v2 requires a project id.
// Reusing the existing project id present in `plugins/reown.ts`.
const projectId = 'c21da9dee824e562f20973612f6a543d'

const metadata = {
  name: 'CoinCasino',
  description: 'CoinCasino WalletConnect',
  url: 'http://localhost:3000/',
  icons: ['https://avatars.githubusercontent.com/u/179229932'],
}

export const WagmiConfig = createConfig({
  chains: [mainnet, sepolia],
  transports: {
    [mainnet.id]: http(),
    [sepolia.id]: http(),
  },
  connectors: [
    injected(),
    walletConnect({
      projectId,
      metadata,
      showQrModal: true,
    }),
  ],
})
