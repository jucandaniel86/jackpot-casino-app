export type BreadcrumbType = {
  title: string;
  disabled: boolean;
  to?: string;
};

type BreadcrumbsInterface = {
  [index: string]: BreadcrumbType[];
};

export const APP_BREADCRUMBS: BreadcrumbsInterface = {
  DASHBOARD: [
    {
      title: "Dashboard",
      disabled: false,
    },
  ],
  PLAYERS: [
    {
      title: "Dashboard",
      disabled: false,
      to: "/",
    },
    {
      title: "Players",
      disabled: true,
    },
  ],
  SUMMARY: [
    {
      title: "Dashboard",
      disabled: false,
      to: "/",
    },
    {
      title: "Financiar",
      disabled: true,
    },
    {
      title: "Summary",
      disabled: true,
    },
  ],
  GAMES: [
    {
      title: "Dashboard",
      disabled: false,
      to: "/",
    },
    {
      title: "Casino",
      disabled: true,
    },
    {
      title: "Games",
      disabled: true,
    },
  ],
  GAMES_ADD: [
    {
      title: "Dashboard",
      disabled: false,
      to: "/",
    },
    {
      title: "Casino",
      disabled: true,
    },
    {
      title: "Games",
      disabled: false,
      to: "/casino/games",
    },
    {
      title: "Add new Game",
      disabled: true,
    },
  ],
  GAMES_EDIT: [
    {
      title: "Dashboard",
      disabled: false,
      to: "/",
    },
    {
      title: "Casino",
      disabled: true,
    },
    {
      title: "Games",
      disabled: false,
      to: "/casino/games",
    },
    {
      title: "Edit Game",
      disabled: true,
    },
  ],
  CATEGORIES: [
    {
      title: "Dashboard",
      disabled: false,
      to: "/",
    },
    {
      title: "Casino",
      disabled: true,
    },
    {
      title: "Categories",
      disabled: true,
    },
  ],
  PROVIDERS: [
    {
      title: "Dashboard",
      disabled: false,
      to: "/",
    },
    {
      title: "Casino",
      disabled: true,
    },
    {
      title: "Providers",
      disabled: true,
    },
  ],
  PAGES: [
    {
      title: "Dashboard",
      disabled: false,
      to: "/",
    },
    {
      title: "Casino",
      disabled: true,
    },
    {
      title: "Pages",
      disabled: true,
    },
  ],
  PAGE_ADD: [
    {
      title: "Dashboard",
      disabled: false,
      to: "/",
    },
    {
      title: "Casino",
      disabled: true,
    },
    {
      title: "Pages",
      disabled: false,
      to: "/casino/pages",
    },
    {
      title: "Add Page",
      disabled: true,
    },
  ],

  TAG_SAVE: [
    {
      title: "Dashboard",
      disabled: false,
      to: "/",
    },
    {
      title: "Casino",
      disabled: true,
    },
    {
      title: "Tags",
      disabled: false,
      to: "/casino/tags",
    },
    {
      title: "Save Tag",
      disabled: true,
    },
  ],
  PROMOTIONS: [
    {
      title: "Dashboard",
      disabled: false,
      to: "/",
    },
    {
      title: "Casino",
      disabled: true,
    },
    {
      title: "Promotions",
      disabled: true,
    },
  ],
  PROMOTIONS_SAVE: [
    {
      title: "Dashboard",
      disabled: false,
      to: "/",
    },
    {
      title: "Casino",
      disabled: true,
    },
    {
      title: "Promotions",
      disabled: false,
      to: "/casino/promotions",
    },
    {
      title: "Save Promotion",
      disabled: true,
    },
  ],
  TOURNAMENTS: [
    {
      title: "Dashboard",
      disabled: false,
      to: "/",
    },
    {
      title: "Casino",
      disabled: true,
    },
    {
      title: "Tournaments",
      disabled: true,
    },
  ],
  TOURNAMENTS_ADD: [
    {
      title: "Dashboard",
      disabled: false,
      to: "/",
    },
    {
      title: "Casino",
      disabled: true,
    },
    {
      title: "Tournaments",
      disabled: false,
      to: "/casino/tournaments",
    },
    {
      title: "Add tournament",
      disabled: true,
    },
  ],
  TOURNAMENTS_EDIT: [
    {
      title: "Dashboard",
      disabled: false,
      to: "/",
    },
    {
      title: "Casino",
      disabled: true,
    },
    {
      title: "Tournaments",
      disabled: false,
      to: "/casino/tournaments",
    },
    {
      title: "Edit tournament",
      disabled: true,
    },
  ],
  BUNDLES: [
    {
      title: "Dashboard",
      disabled: false,
      to: "/",
    },
    {
      title: "Casino",
      disabled: true,
    },
    {
      title: "Bundles",
      disabled: true,
    },
  ],
  BUNDLES_ADD: [
    {
      title: "Dashboard",
      disabled: false,
      to: "/",
    },
    {
      title: "Casino",
      disabled: true,
    },
    {
      title: "Bundles",
      disabled: false,
      to: "/casino/bundles",
    },
    {
      title: "Add bundle",
      disabled: true,
    },
  ],
  BUNDLES_EDIT: [
    {
      title: "Dashboard",
      disabled: false,
      to: "/",
    },
    {
      title: "Casino",
      disabled: true,
    },
    {
      title: "Bundles",
      disabled: false,
      to: "/casino/bundles",
    },
    {
      title: "Edit bundle",
      disabled: true,
    },
  ],
  MENUS: [
    {
      title: "Dashboard",
      disabled: false,
      to: "/",
    },
    {
      title: "Casino",
      disabled: true,
    },
    {
      title: "Menus",
      disabled: true,
    },
  ],
  WALLETS: [
    {
      title: "Dashboard",
      disabled: false,
      to: "/",
    },
    {
      title: "Settings",
      disabled: true,
    },
    {
      title: "Wallets",
      disabled: true,
    },
  ],
  STATS_GAMES: [
    {
      title: "Dashboard",
      disabled: false,
      to: "/",
    },
    {
      title: "Stats",
      disabled: true,
    },
    {
      title: "Games",
      disabled: true,
    },
  ],
  RISK_OVERVIEW: [
    {
      title: "Dashboard",
      disabled: false,
      to: "/",
    },
    {
      title: "Risk",
      disabled: true,
    },
    {
      title: "Overview",
      disabled: true,
    },
  ],
  RISK_SUSPICIOUS: [
    {
      title: "Dashboard",
      disabled: false,
      to: "/",
    },
    {
      title: "Risk",
      disabled: true,
    },
    {
      title: "Suspicious Players",
      disabled: true,
    },
  ],
};
