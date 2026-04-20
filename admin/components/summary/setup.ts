export type DataTableColumn = {
  title: string;
  align: string;
  sortable: boolean;
  value: string;
  width?: number | string;
};

export const REPORT_TRANSACTION_HEADERS: DataTableColumn[] = [
  {
    title: "Transaction Details",
    align: "start",
    sortable: false,
    value: "transaction_id",
  },
  {
    title: "Round ID",
    align: "start",
    sortable: false,
    value: "operator_round_id",
  },
  {
    title: "User",
    align: "start",
    sortable: false,
    value: "user",
  },
  {
    title: "Game",
    align: "start",
    sortable: false,
    value: "game",
  },
  {
    title: "Currency",
    align: "start",
    sortable: false,
    value: "currency",
  },
  {
    title: "Stake",
    align: "start",
    sortable: false,
    value: "stake",
  },
  {
    title: "Payout",
    align: "start",
    sortable: false,
    value: "payout",
  },
  {
    title: " Type",
    align: "start",
    sortable: false,
    value: "transaction_type",
  },
  {
    title: "When Placed",
    align: "start",
    sortable: false,
    value: "when_placed",
  },
];

export const REPORT_USER_HEADERS: DataTableColumn[] = [
  {
    title: "User",
    align: "start",
    sortable: false,
    value: "user",
  },
  {
    title: "Currency",
    align: "start",
    sortable: false,
    value: "currency",
  },
  {
    title: "Total Stake",
    align: "start",
    sortable: false,
    value: "total_stake",
  },
  {
    title: "Total Payout",
    align: "start",
    sortable: false,
    value: "total_payout",
  },
  {
    title: "GGR",
    align: "start",
    sortable: false,
    value: "ggr",
  },
  {
    title: "Total Bets",
    align: "start",
    sortable: false,
    value: "total_bets",
  },
];

export const REPORT_GAME_HEADERS: DataTableColumn[] = [
  {
    title: "Game",
    align: "start",
    sortable: false,
    value: "game",
  },
  {
    title: "Currency",
    align: "start",
    sortable: false,
    value: "currency",
  },
  {
    title: "Total Stake",
    align: "start",
    sortable: false,
    value: "total_stake",
  },
  {
    title: "Total Payout",
    align: "start",
    sortable: false,
    value: "total_payout",
  },
  {
    title: "GGR",
    align: "start",
    sortable: false,
    value: "ggr",
  },
  {
    title: "Total Bets",
    align: "start",
    sortable: false,
    value: "total_bets",
  },
];

export const REPORT_SESSION_HEADERS: DataTableColumn[] = [
  {
    title: "Session",
    align: "start",
    sortable: false,
    value: "session",
  },
  {
    title: "Currency",
    align: "start",
    sortable: false,
    value: "currency",
  },
  {
    title: "Total Stake",
    align: "start",
    sortable: false,
    value: "total_stake",
  },
  {
    title: "Total Payout",
    align: "start",
    sortable: false,
    value: "total_payout",
  },
  {
    title: "GGR",
    align: "start",
    sortable: false,
    value: "ggr",
  },
  {
    title: "Total Bets",
    align: "start",
    sortable: false,
    value: "total_bets",
  },
];

export enum ReportTypeEnums {
  TRANSACTIONS = "transactions",
  USERS = "users",
  GAMES = "games",
  SESSION = "session",
}

export const REPORT_TYPES = [
  { label: "Transactions", id: ReportTypeEnums.TRANSACTIONS },
  { label: "Users", id: ReportTypeEnums.USERS },
  { label: "Games", id: ReportTypeEnums.GAMES },
  { label: "Session", id: ReportTypeEnums.SESSION },
];

export const REPORTS_HEADERS = [
  {
    id: ReportTypeEnums.TRANSACTIONS,
    headers: REPORT_TRANSACTION_HEADERS,
  },
  {
    id: ReportTypeEnums.USERS,
    headers: REPORT_USER_HEADERS,
  },
  {
    id: ReportTypeEnums.GAMES,
    headers: REPORT_GAME_HEADERS,
  },
  {
    id: ReportTypeEnums.SESSION,
    headers: REPORT_SESSION_HEADERS,
  },
];

export const DEFAULT_REPORT = ReportTypeEnums.TRANSACTIONS;
export const REPORT_CURRENCIES = ["EUR", "USD", "ETH", "BTC"];
