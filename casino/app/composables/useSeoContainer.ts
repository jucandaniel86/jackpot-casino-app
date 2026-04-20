type SeoOptionsT = {
  canonicalUrl: string
  description: string
  displayDescription: string
  displayTitle: string
  domainQualifiedCanonicalUrl: string
  indexed: boolean
  locale: string

  redirectUrl: string
  title: string
}

export const useSeoContainer = (content: SeoOptionsT) => {
  useSeoMeta({
    title: content.title,
    ogTitle: content.title,
    description: content.description,
    ogDescription: content.description,
  })
}
