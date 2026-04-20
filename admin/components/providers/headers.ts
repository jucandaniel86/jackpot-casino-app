import type { VDataTableHeaders } from "~/core/types/App";

export const CASINO_PROVIDERS_TABLE_HEADERS: any[] = [
  {
    title: "Thumbnail",
    align: "start",
    sortable: false,
    value: "thumbnail",
    width: "20%",
  },
  {
    title: "Name",
    align: "start",
    sortable: false,
    value: "name",
    width: "50%",
  },
  {
    title: "Active",
    align: "start",
    sortable: false,
    value: "active",
    width: "15%",
  },
  { title: "Actions", value: "iron", width: "20%" },
];
