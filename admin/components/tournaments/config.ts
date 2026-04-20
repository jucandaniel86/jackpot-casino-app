import type { VDataTableHeaders } from "~/core/types/App";
import type { TournamentStatus } from "~/types/tournaments";

export const TOURNAMENT_STATUSES: { title: string; value: TournamentStatus }[] =
  [
    { title: "Draft", value: "draft" },
    { title: "Scheduled", value: "scheduled" },
    { title: "Active", value: "active" },
    { title: "Finished", value: "finished" },
    { title: "Cancelled", value: "cancelled" },
  ];

export const IS_ACTIVE_OPTIONS: { title: string; value: boolean | null }[] = [
  { title: "Any", value: null },
  { title: "Active now", value: true },
  { title: "Not active now", value: false },
];

export const PER_PAGE_OPTIONS = [10, 20, 25, 50, 100];

export const TOURNAMENTS_HEADERS: VDataTableHeaders = [
  { title: "Thumbnail", key: "thumbnail", sortable: false, width: 140 },
  { title: "Name", key: "name", sortable: true, width: 240 },
  { title: "Status", key: "status", sortable: true, width: 140 },
  { title: "Starts", key: "started_at", sortable: true, width: 200 },
  { title: "Ends", key: "ended_at", sortable: true, width: 200 },
  { title: "Point rate", key: "point_rate", sortable: false, width: 140 },
  { title: "Games", key: "games_count", sortable: false, width: 90 },
  { title: "Prizes", key: "prizes_count", sortable: false, width: 90 },
  { title: "Created", key: "created_at", sortable: true, width: 200 },
  { title: "Actions", key: "actions", sortable: false, width: 180 },
];

