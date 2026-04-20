import { useDisplay } from 'vuetify'
import type { ResolutionValuesType } from '~/core/types/TabsFetch'

const resolutionKeys = ['XS', 'SM', 'MD', 'LG', 'XL'] as const
type ResolutionKey = (typeof resolutionKeys)[number]

const getCurrentResolution = (name: string | null | undefined): ResolutionKey => {
  const upperName = String(name ?? '').toUpperCase()
  return resolutionKeys.includes(upperName as ResolutionKey) ? (upperName as ResolutionKey) : 'LG'
}

const parseConfig = (
  resolutionsConfig?: ResolutionValuesType | string | null,
): ResolutionValuesType | undefined => {
  if (!resolutionsConfig) return undefined

  try {
    if (typeof resolutionsConfig === 'string') {
      return JSON.parse(resolutionsConfig) as ResolutionValuesType
    } else if (typeof resolutionsConfig === 'object') {
      return resolutionsConfig as ResolutionValuesType
    }
    return undefined
  } catch {
    return undefined
  }
}

export const useResolutionVars = (resolutionsConfig?: ResolutionValuesType | string | null) => {
  const { name } = useDisplay()
  const parsedConfig = parseConfig(resolutionsConfig)

  const style = computed(() => {
    const currentResolution = getCurrentResolution(name.value)
    const configForResolution = parsedConfig?.[currentResolution]

    const aspectRatio = configForResolution?.aspectRatioPercentage ?? 0
    const itemsPerRow = configForResolution?.itemsPerRow ?? 6

    return {
      '--aspect-ratio': aspectRatio,
      '--itemsPerRow': itemsPerRow,
    }
  })

  const itemsPerRow = computed(() => {
    const currentResolution = getCurrentResolution(name.value)
    const configForResolution = parsedConfig?.[currentResolution]

    return configForResolution?.itemsPerRow ?? 6
  })

  return {
    style,
    itemsPerRow,
  }
}
