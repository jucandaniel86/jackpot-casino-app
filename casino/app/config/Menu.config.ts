import type { ButtonActionTypesEnum } from '~/core/types/ActionButton'
import type { OverlaysTypes } from '~/core/types/Overlays'

export type MenuItemConfig = {
  title: string
  actionType: ButtonActionTypesEnum
  slug: string | OverlaysTypes
  icon: string
  id: string
  isSameTab: number
}
