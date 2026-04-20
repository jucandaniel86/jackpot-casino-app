import type { CategoryType } from "../categories/types";
import type { ProviderT } from "../providers/types";

export type GameT = {
  id: number;
  thumbnail_url: string;
  name: string;
  game_id: string | number;
  categories: CategoryType[];
  provider?: ProviderT | null | undefined;
  active_on_site: number;
  soon: number;
  is_recomended: number;
  is_fun: number;
  is_fullpage: number;
  provider_id: number;
  description: string;
  iframe_url: string;
  thumbnail_file?: any;
  casinos: { int_casino_id: string; name: string }[];
};

export const CASINO_GAMES_HEADERS: any[] = [
  {
    title: "Thumbnail",
    align: "start",
    sortable: false,
    value: "thumbnail",
    width: "10%",
  },
  {
    title: "Name",
    align: "start",
    sortable: false,
    value: "name",
    width: "10%",
  },
  {
    title: "Casinos",
    align: "start",
    sortable: false,
    value: "casinos",
    width: "10%",
  },
  {
    title: "Category",
    align: "start",
    sortable: false,
    value: "category",
    width: "10%",
  },
  {
    title: "Provider",
    align: "start",
    sortable: false,
    value: "provider",
    width: "10%",
  },
  {
    title: "Active",
    align: "center",
    sortable: false,
    value: "active_on_site",
    width: "10%",
  },
  {
    title: "Soon",
    align: "center",
    sortable: false,
    value: "soon",
    width: "10%",
  },
  {
    title: "Recomended",
    align: "center",
    sortable: false,
    value: "is_recomended",
    width: "10%",
  },
  {
    title: "Fun",
    align: "center",
    sortable: false,
    value: "is_fun",
    width: "10%",
  },
  { title: "Actions", value: "iron", width: "10%" },
];

export const YESNO_SELECT = [
  {
    id: 0,
    name: "NO",
  },
  {
    id: 1,
    name: "YES",
  },
];
