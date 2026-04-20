import type { VDataTableHeaders } from "~/core/types/App";

export const PLAYERS_TABLE_HEADERS: any[] = [
  {
    title: "Email",
    align: "start",
    sortable: false,
    value: "name",
    width: "30%",
  },
  {
    title: "Username",
    align: "start",
    sortable: false,
    value: "username",
    width: "20%",
  },
  {
    title: "Fixed ID",
    align: "start",
    sortable: false,
    value: "fixed_id",
    width: "20%",
  },

  { title: "Actions", value: "iron", width: "40%" },
];
