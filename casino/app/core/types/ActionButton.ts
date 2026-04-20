export enum ButtonActionTypesEnum {
  OPEN_EXTERNAL_PAGE = 'OPEN_EXTERNAL_PAGE',
  OPEN_INTERNAL_PAGE = 'OPEN_INTERNAL_PAGE',
  OPEN_OVERLAY = 'OPEN_OVERLAY',
}

export type ButtonAction = {
  overlay: unknown
  type: ButtonActionTypesEnum
  slug?: string
  isSameTab?: boolean
  noFollow?: boolean
  url?: string
}

export type ActionButtonType = {
  action: ButtonAction
  title: string
  color: string
}

export type ActionButtonComponentType = {
  color?: string
}
