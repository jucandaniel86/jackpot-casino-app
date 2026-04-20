/* eslint-disable @typescript-eslint/no-explicit-any */
import type { TabType } from '~/core/types/Game'

export const PROFILE_TABS: TabType[] = [
  {
    icon: '',
    label: 'settings.accountInfo',
    id: 'account-info',
    file: '',
  },
  {
    icon: '',
    label: 'settings.settings',
    id: 'settings',
    file: '',
  },
  {
    icon: '',
    label: 'settings.activity',
    id: 'activity',
    file: '',
  },
]

export enum ProfileActivityItemType {
  SELECT = 'SELECT',
  TIME = 'TIME',
  CURRENCY = 'CURRENCY',
  AUTOCOMPLETE = 'AUTOCOMPLETE',
}

export type ProfileActivityItem = {
  id: string
  label: string
  type: ProfileActivityItemType
  visible: boolean
  values?: any[]
  cols: number
}

export type ProfileActivityFilterT = {
  id: string
  label: string
  items: ProfileActivityItem[]
  fetchUrl: string
}

export const ProfileActivityFilters: ProfileActivityFilterT[] = [
  {
    id: 'transactions',
    label: 'Transactions',
    fetchUrl: '/player/transactions',
    items: [
      {
        id: 'type',
        label: 'Type',
        type: ProfileActivityItemType.SELECT,
        visible: true,
        cols: 6,
        values: [
          { label: 'All', value: '' },
          { label: 'Deposit', value: 'deposit' },
          { label: 'Withdrawal', value: 'withdraw' },
        ],
      },
      {
        id: 'currency',
        label: 'Currency',
        type: ProfileActivityItemType.CURRENCY,
        visible: true,
        cols: 6,
        values: [],
      },
      {
        id: 'time',
        cols: 12,
        type: ProfileActivityItemType.TIME,
        visible: true,
        label: '',
      },
    ],
  },
  {
    id: 'bets',
    label: 'Bets',
    fetchUrl: '/player/bets',
    items: [
      {
        id: 'game',
        type: ProfileActivityItemType.AUTOCOMPLETE,
        visible: true,
        label: 'Game',
        cols: 6,
        values: [
          {
            label: 'All',
            value: '',
          },
        ],
      },
      {
        id: 'currency',
        label: 'Currency',
        type: ProfileActivityItemType.CURRENCY,
        visible: true,
        cols: 6,
        values: [],
      },
      {
        id: 'time',
        cols: 12,
        type: ProfileActivityItemType.TIME,
        visible: true,
        label: '',
      },
    ],
  },
]

export const ProfileBonusFilters: ProfileActivityFilterT[] = [
  {
    id: 'history',
    label: 'History',
    fetchUrl: '/json/responses/history.json',
    items: [
      {
        id: 'type',
        label: 'Type',
        type: ProfileActivityItemType.SELECT,
        visible: true,
        cols: 6,
        values: [
          { label: 'All', value: '' },
          { label: 'Deposit', value: 'DEPOSIT' },
          { label: 'Withdrawal', value: 'WITHDRAWAL' },
        ],
      },
      {
        id: 'currency',
        label: 'Currency',
        type: ProfileActivityItemType.SELECT,
        visible: true,
        cols: 6,
        values: [],
      },
      {
        id: 'time',
        cols: 12,
        type: ProfileActivityItemType.TIME,
        visible: true,
        label: '',
      },
    ],
  },
]

export type BetTransactonType = {
  transaction_type: 'bet' | 'win' | 'refund'
  stake: number | string
  payout: number | string
  refund: number | string
  currency: string
  transaction_id: string
  round_finished: number
  ui_decimals?: number
}
