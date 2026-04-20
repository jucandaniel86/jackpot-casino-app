import { createAppKit } from '@reown/appkit/vue'
import { mainnet, type AppKitNetwork } from '@reown/appkit/networks'
import { WagmiAdapter } from '@reown/appkit-adapter-wagmi'

// 1. Get projectId from https://dashboard.reown.com
const projectId = 'c21da9dee824e562f20973612f6a543d'

// 2. Create a metadata object
const metadata = {
  name: 'AppKit',
  description: 'AppKit Example',
  url: 'http://localhost:3000/', // origin must match your domain & subdomain
  icons: ['https://avatars.githubusercontent.com/u/179229932'],
}

// 3. Set the networks
const networks: [AppKitNetwork, ...AppKitNetwork[]] = [mainnet, polygon, base]

// 4. Create Wagmi Adapter
const wagmiAdapter = new WagmiAdapter({
  networks,
  projectId,
})
