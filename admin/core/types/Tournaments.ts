export type TournamentStatus =
  | "draft"
  | "scheduled"
  | "active"
  | "finished"
  | "cancelled";

export type TournamentGame = {
  id: string;
  game_id: string;
  tournament_id?: string;
  name?: string;
  slug?: string;
  thumbnail?: string | null;
  thumbnail_url?: string | null;
  pivot?: {
    id?: string;
    tournament_id?: string;
    game_id?: string;
    created_at?: string;
    updated_at?: string;
  };
  created_at?: string;
  updated_at?: string;
};

export type TournamentPrize = {
  id: string;
  tournament_id: string;
  prize_name: string;
  prize_type: "rank" | "threshold";
  rank_from: number | null;
  rank_to: number | null;
  min_points: number | null;
  prize_currency: string | null;
  prize_amount: string | number;
  metadata: Record<string, unknown> | null;
  created_at?: string;
  updated_at?: string;
};

export type Tournament = {
  id: string;
  name: string;
  thumbnail: string | null;
  started_at: string;
  ended_at: string;
  status: TournamentStatus;
  point_rate: number;
  games: TournamentGame[];
  prizes: TournamentPrize[];
  created_at: string;
  updated_at: string;
};

export type TournamentListItem = Tournament;

export type TournamentListFilters = {
  page?: number;
  per_page?: number;
  sort_by?: "name" | "started_at" | "ended_at" | "status" | "created_at";
  sort_direction?: "asc" | "desc";
  status?: TournamentStatus;
  is_active?: boolean | null;
  started_from?: string | null;
  started_to?: string | null;
  ended_from?: string | null;
  ended_to?: string | null;
  search?: string | null;
  game_id?: string | null;
};

export type TournamentPrizePayload = {
  prize_name: string;
  prize_type: "rank" | "threshold";
  rank_from?: number | null;
  rank_to?: number | null;
  min_points?: number | null;
  prize_currency?: string | null;
  prize_amount: number | string;
  metadata?: Record<string, unknown> | null;
};

export type TournamentPayload = {
  name: string;
  thumbnail?: string | null;
  started_at: string;
  ended_at: string;
  status: TournamentStatus;
  point_rate: number;
  game_ids: string[];
  prizes?: TournamentPrizePayload[] | null;
};

export type TournamentListResponse = {
  current_page: number;
  data: TournamentListItem[];
  first_page_url?: string;
  from?: number | null;
  last_page: number;
  last_page_url?: string;
  links?: any[];
  next_page_url?: string | null;
  path?: string;
  per_page: number;
  prev_page_url?: string | null;
  to?: number | null;
  total: number;
};

export type TournamentApiResponse<T> = {
  success: boolean;
  message?: string;
  data: T;
};
