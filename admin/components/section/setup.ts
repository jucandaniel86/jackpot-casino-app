export enum ContainerSection {
  SLIDER = "SliderContainer",
  COLUMN = "ColumnContainer",
  GAMES_CATEGORY = "GamesCategoryContainer",
  FLEX_COLUMN = "FlexColumnContainer",
  FLEX_ROW = "FlexRowContainer",
  CAROUSEL = "CarouselContainer",
  HTML = "HTMLContainer",
  BET_FEED = "BetFeedContainer",
  SEO = "SeoContainer",
  LOGOS = "LogosContainer",
  SEARCH = "CasinoSearchContainer",
  TABS = "TabContainer",
  PROVIDER_LOGOS = "ProviderLogosContainer",
  CATEGORY_HEADLESS = "GamesCategoryHeadlessContainer",
  ACCORDION = "AccordionContainer",
  BUTTON = "GenericButtonContainer",
  PROMOTIONS = "PromotionsContainer",
  BONUS = "HomepageBonusContainer",
  OFFER = "HomepageOfferContainer",
  PLAYER_FAVORITES = "PlayerFavouritesContainer",
}

export type ResolutionConfig = {
  isVisible: boolean;
  background?: string;
};

export type ContainerResolutionConfig = {
  LG: ResolutionConfig;
  MD: ResolutionConfig;
  SM: ResolutionConfig;
  XL: ResolutionConfig;
  XS: ResolutionConfig;
};

export enum ContainerStatus {
  DRAFT = "DRAFT",
  PUBLISHED = "PUBLISHED",
  PRIVATE = "PRIVATE",
}

export type ContainerType = {
  id: string;
  container: ContainerSection;
  children: ContainerType[];
  name: string;
  appearance: {
    resolutionConfig: ContainerResolutionConfig;
  };
  data: any;
  status: ContainerStatus;
};

export type SelectedSectionsT = {
  label: string;
  container: ContainerSection;
};

export const APP_SECTIONS: SelectedSectionsT[] = [
  {
    label: "Slider Container",
    container: ContainerSection.SLIDER,
  },
  {
    label: "Tabs Container",
    container: ContainerSection.TABS,
  },
  {
    label: "Games Category Container",
    container: ContainerSection.GAMES_CATEGORY,
  },
  {
    label: "Games Container",
    container: ContainerSection.CATEGORY_HEADLESS,
  },
  {
    label: "HTML Container",
    container: ContainerSection.HTML,
  },
  {
    label: "SEO Container",
    container: ContainerSection.SEO,
  },
  {
    label: "Search Container",
    container: ContainerSection.SEARCH,
  },
  {
    label: "Logos Container",
    container: ContainerSection.LOGOS,
  },
  {
    label: "Provider Carousel Container",
    container: ContainerSection.PROVIDER_LOGOS,
  },
  {
    label: "Bet Feed Container",
    container: ContainerSection.BET_FEED,
  },
  {
    label: "Accordion Container",
    container: ContainerSection.ACCORDION,
  },
  {
    label: "Promotions Container",
    container: ContainerSection.PROMOTIONS,
  },
  {
    label: "Homepage Bonus Container",
    container: ContainerSection.BONUS,
  },
  {
    label: "Homepage Offer Container",
    container: ContainerSection.OFFER,
  },
  {
    label: "Player Favourites Games",
    container: ContainerSection.PLAYER_FAVORITES,
  },
];
