import type { VDataTableHeaders } from "~/core/types/App";

export const PLAYERS_TABLE_HEADERS: any[] = [
  {
    title: "Email",
    align: "start",
    sortable: false,
    value: "name",
    width: "18%",
  },
  {
    title: "Username",
    align: "start",
    sortable: false,
    value: "username",
    width: "12%",
  },
  {
    title: "Fixed ID",
    align: "start",
    sortable: false,
    value: "fixed_id",
    width: "12%",
  },
  {
    title: "Status",
    align: "start",
    sortable: false,
    value: "status",
    width: "10%",
  },
  {
    title: "Player Balance",
    align: "start",
    sortable: false,
    value: "player_balance",
    width: "14%",
  },
  {
    title: "Available",
    align: "start",
    sortable: false,
    value: "player_balance_available",
    width: "14%",
  },
  {
    title: "Last IP",
    align: "start",
    sortable: false,
    value: "last_ip",
    width: "12%",
  },
  {
    title: "Signup Date",
    align: "start",
    sortable: false,
    value: "created_at",
    width: "14%",
  },
  {
    title: "Last Login",
    align: "start",
    sortable: false,
    value: "last_login_at",
    width: "14%",
  },
  { title: "Actions", value: "iron", width: "28%" },
];
