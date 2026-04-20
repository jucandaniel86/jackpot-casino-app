/* eslint-disable @typescript-eslint/no-explicit-any */
export enum ContainerSection {
  BANNER = 'BannerContainer',
  SLIDER = 'SliderContainer',
  COLUMN = 'ColumnContainer',
  GAMES_CATEGORY = 'GamesCategoryContainer',
  FLEX_COLUMN = 'FlexColumnContainer',
  FLEX_ROW = 'FlexRowContainer',
  CAROUSEL = 'CarouselContainer',
  HTML = 'HTMLContainer',
  BET_FEED = 'BetFeedContainer',
  SEO = 'SeoContainer',
  LOGOS = 'LogosContainer',
  SEARCH = 'CasinoSearchContainer',
  TABS = 'TabContainer',
  PROVIDER_LOGOS = 'ProviderLogosContainer',
  CATEGORY_HEADLESS = 'GamesCategoryHeadlessContainer',
  ACCORDION = 'AccordionContainer',
  BUTTON = 'GenericButtonContainer',
  CATEGORY_DETAILS = 'GamesCategoryDetailsContainer',
  PROMOTIONS = 'PromotionsContainer',
  PROMO_IMAGE = 'PromotionImageContainer',
  PROMO_TITLE = 'PromotionTitleContainer',
  PROMO_SUBTITLE = 'PromotionSubTitleContainer',
  PROMO_DESCRIPTION = 'PromotionDescriptionContainer',
  PROMO_BUTTON = 'PromotionButtonContainer',
  BONUS = 'HomepageBonusContainer',
  OFFER = 'HomepageOfferContainer',
  PLAYER_FAVORITES = 'PlayerFavouritesContainer',
}

export type ResolutionConfig = {
  isVisible: boolean
  background?: string
}

export type ContainerResolutionConfig = {
  LG: ResolutionConfig
  MD: ResolutionConfig
  SM: ResolutionConfig
  XL: ResolutionConfig
  XS: ResolutionConfig
}

export type ContainerType = {
  id: string
  container: ContainerSection
  children: ContainerType[]
  appearance: {
    resolutionConfig: ContainerResolutionConfig
  }
  data: any
}
