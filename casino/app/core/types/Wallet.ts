export type NetworkType = {
  name: string
  minAmount: number
}

export type NetworkDataT = {
  [index: string]: NetworkType
}

export type WalletT = {
  wallet_id: string
  code: string
  name: string
  symbol: string
  balance: number
  is_fiat: boolean
  precision: number
  decimals: number
  minAmount: number
  supportsTag: boolean
  networkData?: NetworkDataT[]
  available_base: number
  available: number
  reserved: number
  owner_address: string
  token_mint: string
  purpose: 'real' | 'bonus'
}
