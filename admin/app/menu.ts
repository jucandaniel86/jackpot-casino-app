import { type MenuItemType } from "@/app/types";

export const menuItems: MenuItemType[] = [
  {
    label: "Menu",
    isHeader: true,
    id: "HeaderMenu",
  },
  {
    label: "Dashboard",
    id: "sideBarDashboard",
    icon: "ph-gauge",
    link: "/",
  },
  {
    label: "Players",
    id: "sidebarPlayers",
    icon: "ph-users",
    link: "/players",
  },
  {
    label: "Withdraw Requests",
    id: "sidebarWithdrawRequests",
    icon: "ph-wallet",
    subMenu: [],
    link: "/withdraw-requests",
  },
  {
    label: "Risk & Fraud",
    id: "sidebarRisk&Fraud",
    icon: "ph-warning",
    subMenu: [],
    link: "/risk",
  },
  {
    label: "Marketing",
    id: "sidebarMarketing",
    icon: "ph-image",
    subMenu: [],
    link: "/marketing",
  },
  {
    label: "Bonuses",
    id: "sidebarBonuses",
    icon: "ph-gift",
    subMenu: [
      {
        label: "Rules",
        link: "/bonuses/rules",
        id: "bonusRules",
      },
      {
        label: "Manual Grants",
        link: "/bonuses/manual",
        id: "bonusManual",
      },
      {
        label: "Grants",
        link: "/bonuses/grants",
        id: "bonusGrants",
      },
      {
        label: "Stats",
        link: "/bonuses/stats",
        id: "bonusStats",
      },
      {
        label: "Maintenance",
        link: "/bonuses/maintenance",
        id: "bonusMaintenance",
      },
      {
        label: "Test Runner",
        link: "/bonuses/tests",
        id: "bonusTests",
      },
    ],
  },
  {
    label: "Financiar",
    id: "sidebarFinanciar",
    icon: "ph-chart-line",
    subMenu: [
      {
        label: "Finance OPS",
        link: "/stats/finance-ops",
        id: "statsFinanceOPS",
      },
      {
        label: "Crypto OPS",
        link: "/stats/crypto-ops",
        id: "statsCryptoOPS",
      },
      { label: "Summary", link: "/summary", id: "casinoSummary" },
      {
        label: "Crypto Transactions",
        link: "/stats/crypto-transactions",
        id: "statsCryptoTransactions",
      },
      { label: "Games", link: "/stats/games", id: "statsGames" },
      {
        label: "Conversion Funnel",
        link: "/stats/funnel",
        id: "statsFunnel",
      },
    ],
  },
  {
    label: "Casino",
    icon: "ph-poker-chip",
    id: "sidebarCasino",
    prefix: "/reports",
    subMenu: [
      { label: "Pages", link: "/casino/pages", id: "casinoPages" },
      { label: "Games", link: "/casino/games", id: "casinoGames" },
      { label: "Tournaments", link: "/casino/tournaments", id: "casinoTournaments" },
      { label: "Bundles", link: "/casino/bundles", id: "casinoBundles" },
      {
        label: "Categories",
        link: "/casino/categories",
        id: "casinoCategories",
      },
      { label: "Tags", link: "/casino/tags", id: "casinoTags" },
      { label: "Providers", link: "/casino/providers", id: "casinoProviders" },
      {
        label: "Promotions",
        link: "/casino/promotions",
        id: "casinoPromotions",
      },
      { label: "Casino Menu", link: "/casino/menu", id: "casinoMenu" },
    ],
  },
  {
    label: "System",
    id: "siderbarSystem",
    icon: "ph-gear-six",
    link: "/",
    subMenu: [
      {
        label: "Dashboard Sweeps",
        link: "/system/sweeps",
        id: "systemFailedSweep",
      },
      { label: "Wallets", link: "/system/wallets", id: "systemWallets" },
      { label: "Jobs Run", link: "/system/jobs-run", id: "systemJobsRun" },
    ],
  },
  {
    label: "Admin Users",
    id: "sidebarUsers",
    icon: "ph-users",
    link: "/users",
  },
];

export const setAttributes = (key: string, value: string) => {
  document.documentElement.setAttribute(key, value);
};
