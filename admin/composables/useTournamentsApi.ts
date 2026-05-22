import type {
  Tournament,
  TournamentApiResponse,
  TournamentListFilters,
  TournamentListResponse,
  TournamentPayload,
  TournamentRandomExtractionResponse,
  TournamentPrizeAward,
  TournamentTypeOption,
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

const downloadBlob = async (path: string): Promise<TournamentsApiResult<Blob>> => {
  const config = useRuntimeConfig();
  const { token } = storeToRefs(useAuthStore());
  const { currentCasinoId } = storeToRefs(useLayoutStore());

  try {
    const blob = await $fetch<Blob>(path, {
      baseURL: config.public.baseURL,
      query: {
        int_casino_id: currentCasinoId.value,
      },
      responseType: "blob",
      headers: {
        Authorization: `Bearer ${token.value}`,
        Accept: "text/csv",
      },
    } as any);

    return ok(blob);
  } catch (error) {
    return fail(error);
  }
};

const appendTournamentFormData = (formData: FormData, payload: TournamentPayload) => {
  formData.append("name", String(payload.name ?? ""));
  if (payload.thumbnail !== undefined && payload.thumbnail !== null) {
    formData.append("thumbnail", String(payload.thumbnail));
  }
  formData.append("started_at", String(payload.started_at ?? ""));
  formData.append("ended_at", String(payload.ended_at ?? ""));
  formData.append("status", String(payload.status ?? ""));
  formData.append("point_rate", String(payload.point_rate ?? 1));
  formData.append("tournament_type", String(payload.tournament_type ?? "DEFAULT"));
  formData.append("tournament_range", String(payload.tournament_range ?? -1));

  payload.game_ids.forEach((gameId, index) => {
    formData.append(`game_ids[${index}]`, String(gameId));
  });

  (payload.prizes ?? []).forEach((prize, index) => {
    formData.append(`prizes[${index}][prize_name]`, String(prize.prize_name ?? ""));
    formData.append(`prizes[${index}][prize_type]`, String(prize.prize_type ?? "rank"));

    if (prize.rank_from !== undefined && prize.rank_from !== null) {
      formData.append(`prizes[${index}][rank_from]`, String(prize.rank_from));
    }
    if (prize.rank_to !== undefined && prize.rank_to !== null) {
      formData.append(`prizes[${index}][rank_to]`, String(prize.rank_to));
    }
    if (prize.min_points !== undefined && prize.min_points !== null) {
      formData.append(`prizes[${index}][min_points]`, String(prize.min_points));
    }
    if (prize.prize_currency !== undefined && prize.prize_currency !== null) {
      formData.append(`prizes[${index}][prize_currency]`, String(prize.prize_currency));
    }

    formData.append(`prizes[${index}][prize_amount]`, String(prize.prize_amount ?? 0));

    if (prize.metadata && typeof prize.metadata === "object") {
      Object.entries(prize.metadata).forEach(([key, value]) => {
        formData.append(
          `prizes[${index}][metadata][${key}]`,
          typeof value === "string" ? value : JSON.stringify(value),
        );
      });
    }
  });

  const fileValue = Array.isArray(payload.thumbnail_file)
    ? payload.thumbnail_file[0]
    : payload.thumbnail_file;

  if (fileValue instanceof File) {
    formData.append("thumbnail_file", fileValue);
  }
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
    const formData = new FormData();
    appendTournamentFormData(formData, payload);
    const result = await useApiPostFetch("/admin/tournaments", formData);

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
    const formData = new FormData();
    appendTournamentFormData(formData, payload);
    formData.append("_method", "PUT");
    const result = await useApiPostFetch(`/admin/tournaments/${id}`, formData);

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

  const listTournamentTypes = async (): Promise<
    TournamentsApiResult<TournamentTypeOption[]>
  > => {
    const result = await useAPIFetch("/admin/tournaments/types", {});

    if (!result.success) {
      return fail(result.error);
    }

    const res = result.data as TournamentApiResponse<TournamentTypeOption[]>;
    return ok(unwrap<TournamentTypeOption[]>(res), res?.message);
  };

  const cloneTournament = async (
    id: string,
  ): Promise<TournamentsApiResult<Tournament>> => {
    const result = await useApiPostFetch(`/admin/tournaments/${id}/clone`, {});

    if (!result.success) {
      return fail(result.error);
    }

    const res = result.data as TournamentApiResponse<Tournament>;
    return ok(unwrap<Tournament>(res), res?.message);
  };

  const endTournament = async (
    id: string,
  ): Promise<TournamentsApiResult<Tournament>> => {
    const result = await useApiPostFetch(`/admin/tournaments/${id}/end`, {});

    if (!result.success) {
      return fail(result.error);
    }

    const res = result.data as TournamentApiResponse<Tournament>;
    return ok(unwrap<Tournament>(res), res?.message);
  };

  const randomExtraction = async (
    id: string,
  ): Promise<TournamentsApiResult<TournamentRandomExtractionResponse>> => {
    const result = await useApiPostFetch(`/admin/tournaments/${id}/random-extraction`, {});

    if (!result.success) {
      return fail(result.error);
    }

    const res = result.data as TournamentApiResponse<TournamentRandomExtractionResponse>;
    return ok(unwrap<TournamentRandomExtractionResponse>(res), res?.message);
  };

  const randomExtractionEligible = async (
    id: string,
  ): Promise<TournamentsApiResult<TournamentRandomExtractionResponse>> => {
    const result = await useAPIFetch(`/admin/tournaments/${id}/random-extraction/eligible`, {});

    if (!result.success) {
      return fail(result.error);
    }

    const res = result.data as TournamentApiResponse<TournamentRandomExtractionResponse>;
    return ok(unwrap<TournamentRandomExtractionResponse>(res), res?.message);
  };

  const approveRandomExtraction = async (
    id: string,
    awards: TournamentPrizeAward[],
  ): Promise<TournamentsApiResult<Tournament>> => {
    const result = await useApiPostFetch(`/admin/tournaments/${id}/random-extraction/approve`, {
      awards,
    });

    if (!result.success) {
      return fail(result.error);
    }

    const res = result.data as TournamentApiResponse<Tournament>;
    return ok(unwrap<Tournament>(res), res?.message);
  };

  const exportChanceList = async (
    id: string,
  ): Promise<TournamentsApiResult<Blob>> => {
    return downloadBlob(`/admin/tournaments/${id}/chance-list/export`);
  };

  return {
    listTournaments,
    getTournament,
    createTournament,
    updateTournament,
    deleteTournament,
    listTournamentTypes,
    cloneTournament,
    endTournament,
    randomExtractionEligible,
    randomExtraction,
    approveRandomExtraction,
    exportChanceList,
  };
};
