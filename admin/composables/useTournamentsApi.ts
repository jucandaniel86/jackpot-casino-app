import type {
  Tournament,
  TournamentApiResponse,
  TournamentListFilters,
  TournamentListResponse,
  TournamentPayload,
} from "~/types/tournaments";

const toErrorMessage = (error: any): string => {
  const data = error?.response?._data ?? error?.data ?? null;

  if (data?.message && typeof data.message === "string") {
    return data.message;
  }

  if (data?.errors && typeof data.errors === "object") {
    return Object.values(data.errors)
      .flat()
      .map((item) => String(item))
      .join("\n");
  }

  if (typeof error?.message === "string" && error.message.trim()) {
    return error.message;
  }

  return "Request failed. Please try again.";
};

export type TournamentsApiResult<T> = {
  success: boolean;
  data: T | null;
  error: unknown;
  message?: string;
};

const ok = <T>(data: T, message?: string): TournamentsApiResult<T> => ({
  success: true,
  data,
  error: null,
  message,
});

const fail = <T>(error: unknown): TournamentsApiResult<T> => ({
  success: false,
  data: null,
  error,
  message: toErrorMessage(error),
});

const unwrap = <T>(payload: any): T => {
  return (payload?.data ?? payload) as T;
};

export const useTournamentsApi = () => {
  const listTournaments = async (
    params: TournamentListFilters,
  ): Promise<
    TournamentsApiResult<{ items: Tournament[]; total: number; meta: any }>
  > => {
    const result = await useAPIFetch("/admin/tournaments", params);

    if (!result.success) {
      return fail(result.error);
    }

    const res = result.data as TournamentApiResponse<TournamentListResponse>;
    const paginator = unwrap<TournamentListResponse>(res);
    const items = Array.isArray(paginator?.data) ? paginator.data : [];
    const total = Number(paginator?.total ?? items.length);

    return ok({ items, total, meta: paginator }, res?.message);
  };

  const getTournament = async (
    id: string,
  ): Promise<TournamentsApiResult<Tournament>> => {
    const result = await useAPIFetch(`/admin/tournaments/${id}`, {});

    if (!result.success) {
      return fail(result.error);
    }

    const res = result.data as TournamentApiResponse<Tournament>;
    const tournament = unwrap<Tournament>(res);
    return ok(tournament, res?.message);
  };

  const createTournament = async (
    payload: TournamentPayload,
  ): Promise<TournamentsApiResult<Tournament>> => {
    const result = await useApiPostFetch("/admin/tournaments", payload);

    if (!result.success) {
      return fail(result.error);
    }

    const res = result.data as TournamentApiResponse<Tournament>;
    return ok(unwrap<Tournament>(res), res?.message);
  };

  const updateTournament = async (
    id: string,
    payload: TournamentPayload,
  ): Promise<TournamentsApiResult<Tournament>> => {
    const result = await useApiPutFetch(`/admin/tournaments/${id}`, payload);

    if (!result.success) {
      return fail(result.error);
    }

    const res = result.data as TournamentApiResponse<Tournament>;
    return ok(unwrap<Tournament>(res), res?.message);
  };

  const deleteTournament = async (
    id: string,
  ): Promise<TournamentsApiResult<null>> => {
    const result = await useApiDeleteFetch(`/admin/tournaments/${id}`, {});

    if (!result.success) {
      return fail(result.error);
    }

    const res = result.data as TournamentApiResponse<null>;
    return ok(null, res?.message);
  };

  return {
    listTournaments,
    getTournament,
    createTournament,
    updateTournament,
    deleteTournament,
  };
};

