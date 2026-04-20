export enum ButtonActionTypesEnum {
  OPEN_EXTERNAL_PAGE = 'OPEN_EXTERNAL_PAGE',
  OPEN_INTERNAL_PAGE = 'OPEN_INTERNAL_PAGE',
}

export type ButtonAction = {
  type: ButtonActionTypesEnum
  slug: string
  isSameTab?: boolean
  noFollow?: boolean
  url?: string
}

export type ButtonActionTypes = {
  action: ButtonAction
  title: string
  color: string
}

export type PromotionType = {
  id: string
  title: string
  description: string
  imageUrl: string
  ctaText: string
  primaryAction: ButtonActionTypes
  secondaryAction: ButtonActionTypes
}
