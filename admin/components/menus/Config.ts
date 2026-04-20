export enum LinkActionTypes {
  OPEN_EXTERNAL_PAGE = "OPEN_EXTERNAL_PAGE",
  OPEN_INTERNAL_PAGE = "OPEN_INTERNAL_PAGE",
  OPEN_OVERLAY = "OPEN_OVERLAY",
}

export const MenusPositions = [
  {
    id: "HEADER",
    label: "Header",
  },
  {
    id: "SIDEBAR",
    label: "Sidebar",
  },
  {
    id: "FOOTER",
    label: "Footer",
  },
];

export const ActionTypes = [
  {
    id: LinkActionTypes.OPEN_EXTERNAL_PAGE,
    label: "Open External Page",
  },
  {
    id: LinkActionTypes.OPEN_INTERNAL_PAGE,
    label: "Open Internal Page",
  },
  {
    id: LinkActionTypes.OPEN_OVERLAY,
    label: "Open Overlay",
  },
];

export const Overlays = [
  { id: "wallet", label: "Wallet" },
  { id: "login", label: "Login" },
  { id: "register", label: "Register" },
];

export type ItemMenuType = {
  action_type: LinkActionTypes;
  external_link: string | null;
  game_id: number;
  icon: string;
  id: number;
  is_same_tab: number;
  item_order: number;
  menu_id: string;
  overlay: string;
  page_id: number;
  position: "SIDEBAR" | "HEADER" | "FOOTER";
  promotion_id: number;
  title: string;
};
