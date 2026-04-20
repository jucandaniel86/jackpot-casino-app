export type DataTableColumn = {
  title: string;
  align: string;
  sortable: boolean;
  value: string;
  width?: number | string;
};

export const REPORTS_HEADERS: DataTableColumn[] = [
  {
    title: "Game name",
    align: "start",
    sortable: false,
    value: "game_name",
  },
  {
    title: "Bets Count",
    align: "start",
    sortable: false,
    value: "bets_count",
  },
  {
    title: "Players Count",
    align: "start",
    sortable: false,
    value: "players_count",
  },
  {
    title: "Refunded",
    align: "start",
    sortable: false,
    value: "refunded",
  },
  {
    title: "Wagered",
    align: "start",
    sortable: false,
    value: "wagered",
  },
  {
    title: "Won",
    align: "start",
    sortable: false,
    value: "won",
  },
  {
    title: "RTP Net Percent",
    align: "start",
    sortable: false,
    value: "rtp_net_percent",
  },
  {
    title: "RTP Percent",
    align: "start",
    sortable: false,
    value: "rtp_percent",
  },
];
