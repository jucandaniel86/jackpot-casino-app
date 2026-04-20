import type { ActionButtonType } from '~/core/types/ActionButton'

export type PromotionT = {
  title: string
  description: string
  image: string
  slug?: string
  ctaText: string
  primaryAction?: ActionButtonType
  secondaryAction: ActionButtonType
}
