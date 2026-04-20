import { unref, type MaybeRef } from 'vue'

type SeoSettingsInput =
  | {
      title?: string | null
      description?: string | null
      displayTitle?: string | null
      displayDescription?: string | null
    }
  | string
  | null
  | undefined

type SeoSettingsResolved = {
  title: string
  description: string
  displayTitle: string
  displayDescription: string
}

const buildDefaults = (): SeoSettingsResolved => {
  const runtimeConfig = useRuntimeConfig()
  return {
    title: runtimeConfig.public.seoTitle,
    description: runtimeConfig.public.seoDescription,
    displayTitle: runtimeConfig.public.seoDisplayTitle,
    displayDescription: runtimeConfig.public.seoDisplayDescription,
  }
}

const normalizeSeoInput = (input: SeoSettingsInput): SeoSettingsResolved => {
  const defaults = buildDefaults()

  if (!input) {
    return defaults
  }

  if (typeof input === 'string') {
    try {
      const parsed = JSON.parse(input) as Record<string, unknown>
      return {
        title: (parsed.title as string) ?? defaults.title,
        description: (parsed.description as string) ?? defaults.description,
        displayTitle: (parsed.displayTitle as string) ?? defaults.displayTitle,
        displayDescription: (parsed.displayDescription as string) ?? defaults.displayDescription,
      }
    } catch {
      return defaults
    }
  }

  return {
    title: input.title ?? defaults.title,
    description: input.description ?? defaults.description,
    displayTitle: input.displayTitle ?? defaults.displayTitle,
    displayDescription: input.displayDescription ?? defaults.displayDescription,
  }
}

export const useSeoSettings = (seoInput: MaybeRef<SeoSettingsInput>) => {
  useHead(() => {
    const seo = normalizeSeoInput(unref(seoInput))

    return {
      title: seo.title,
      meta: [
        { name: 'description', content: seo.description },
        { name: 'displayTitle', content: seo.displayTitle },
        { name: 'displayDescription', content: seo.displayDescription },
      ],
    }
  })
}
