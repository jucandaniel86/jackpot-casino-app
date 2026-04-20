import { useLayoutStore } from "~/store/app";
import type {
  BonusApiResult,
  BonusGrant,
  BonusGrantEvent,
  BonusGrantEventsResponse,
  BonusGrantsFilters,
  BonusGrantsListResponse,
  BonusManualGrantPayload,
  BonusManualGrantResponse,
  BonusManualPreviewPayload,
  BonusManualPreviewResponse,
  BonusRule,
  BonusRuleGetResponse,
  BonusRulePayload,
  BonusRulesListResponse,
  BonusStatsResponse,
  BonusTestRun,
  BonusTestRunLog,
  BonusTestScenario,
  BonusWalletBackfillResponse,
} from "~/types/bonuses";

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

const ok = <T>(data: T, message?: string): BonusApiResult<T> => ({
  success: true,
  data,
  error: null,
  message,
});

const fail = <T>(error: unknown): BonusApiResult<T> => ({
  success: false,
  data: null,
  error,
  message: toErrorMessage(error),
});

const parseItems = <T>(payload: any): T[] => {
  const node = payload?.data ?? payload;

  if (Array.isArray(node)) return node;
  if (Array.isArray(node?.items)) return node.items;
  if (Array.isArray(node?.data)) return node.data;
  if (Array.isArray(node?.rules)) return node.rules;
  if (Array.isArray(node?.events)) return node.events;

  return [];
};

export const useBonusesApi = () => {
  const { currentCasinoId } = storeToRefs(useLayoutStore());

  const withCasino = <T extends Record<string, unknown>>(payload: T): T => {
    if (!currentCasinoId.value) {
      return payload;
    }

    return {
      ...payload,
      int_casino_id: payload.int_casino_id ?? currentCasinoId.value,
    } as T;
  };

  const getRules = async (): Promise<BonusApiResult<BonusRule[]>> => {
    const result = await useAPIFetch("/bonuses/rules", {});

    if (!result.success) {
      return fail<BonusRule[]>(result.error);
    }

    const rules = parseItems<BonusRule>(result.data as BonusRulesListResponse);
    return ok(rules);
  };

  const getRule = async (id: number): Promise<BonusApiResult<BonusRule>> => {
    const result = await useAPIFetch("/bonuses/rules/get", { id });

    if (!result.success) {
      return fail<BonusRule>(result.error);
    }

    const node = result.data as BonusRuleGetResponse;
    const rule = (node?.data ??
      node?.item ??
      node?.rule ??
      null) as BonusRule | null;

    if (!rule) {
      return fail<BonusRule>(new Error("Rule not found."));
    }

    return ok(rule);
  };

  const saveRule = async (
    payload: BonusRulePayload,
  ): Promise<BonusApiResult<BonusRule>> => {
    const result = await useApiPostFetch(
      "/bonuses/rules/save",
      withCasino(payload as any),
    );

    if (!result.success) {
      return fail<BonusRule>(result.error);
    }

    const node = result.data?.data ?? result.data;
    const rule = (node?.rule ?? node?.item ?? node) as BonusRule;
    return ok(rule, result.data?.message);
  };

  const deleteRule = async (id: number): Promise<BonusApiResult<null>> => {
    const result = await useApiDeleteFetch("/bonuses/rules/delete", {
      id,
      int_casino_id: currentCasinoId.value,
    });

    if (!result.success) {
      return fail<null>(result.error);
    }

    return ok(null, result.data?.message);
  };

  const toggleRule = async (
    id: number,
    is_active: boolean,
  ): Promise<BonusApiResult<BonusRule>> => {
    const result = await useApiPostFetch(
      "/bonuses/rules/toggle",
      withCasino({ id, is_active }),
    );

    if (!result.success) {
      return fail<BonusRule>(result.error);
    }

    const node = result.data?.data ?? result.data;
    const rule = (node?.rule ?? node?.item ?? node) as BonusRule;
    return ok(rule, result.data?.message);
  };

  const manualPreview = async (
    payload: BonusManualPreviewPayload,
  ): Promise<BonusApiResult<BonusManualPreviewResponse>> => {
    const result = await useApiPostFetch(
      "/bonuses/manual/preview",
      withCasino(payload as any),
    );

    if (!result.success) {
      return fail<BonusManualPreviewResponse>(result.error);
    }

    return ok(
      (result.data?.data ?? result.data) as BonusManualPreviewResponse,
      result.data?.message,
    );
  };

  const manualGrant = async (
    payload: BonusManualGrantPayload,
  ): Promise<BonusApiResult<BonusManualGrantResponse>> => {
    const result = await useApiPostFetch(
      "/bonuses/manual/grant",
      withCasino(payload as any),
    );

    if (!result.success) {
      return fail<BonusManualGrantResponse>(result.error);
    }

    return ok(
      (result.data?.data ?? result.data) as BonusManualGrantResponse,
      result.data?.message,
    );
  };

  const getGrants = async (
    filters: BonusGrantsFilters,
  ): Promise<BonusApiResult<{ items: BonusGrant[]; total: number }>> => {
    const result = await useAPIFetch("/bonuses/grants", {
      ...filters,
    });

    if (!result.success) {
      return fail<{ items: BonusGrant[]; total: number }>(result.error);
    }

    const node = result.data as BonusGrantsListResponse;
    const items = parseItems<BonusGrant>(node);

    const total =
      Number(node?.total) ||
      Number(node?.meta?.total) ||
      Number(result.data?.data?.total) ||
      items.length;

    return ok({ items, total });
  };

  const getGrantEvents = async (
    grantId: number,
  ): Promise<BonusApiResult<BonusGrantEvent[]>> => {
    const result = await useAPIFetch("/bonuses/grants/events", {
      grant_id: grantId,
    });

    if (!result.success) {
      return fail<BonusGrantEvent[]>(result.error);
    }

    const events = parseItems<BonusGrantEvent>(
      result.data as BonusGrantEventsResponse,
    );

    return ok(events);
  };

  const getStats = async (): Promise<BonusApiResult<BonusStatsResponse>> => {
    const result = await useAPIFetch("/bonuses/stats", {});

    if (!result.success) {
      return fail<BonusStatsResponse>(result.error);
    }

    return ok((result.data?.data ?? result.data) as BonusStatsResponse);
  };

  const createMissingWallets = async (): Promise<
    BonusApiResult<BonusWalletBackfillResponse>
  > => {
    const result = await useAPIFetch("/wallet/create-user-wallets", {});

    if (!result.success) {
      return fail<BonusWalletBackfillResponse>(result.error);
    }

    return ok(
      (result.data?.data ?? result.data) as BonusWalletBackfillResponse,
      result.data?.message,
    );
  };

  const runTestScenario = async (
    scenario: BonusTestScenario,
    params: Record<string, unknown> = {},
  ): Promise<BonusApiResult<BonusTestRun>> => {
    const result = await useApiPostFetch(
      "/bonuses/tests/run",
      withCasino({ scenario, params } as any),
    );

    if (!result.success) {
      return fail<BonusTestRun>(result.error);
    }

    return ok((result.data?.data ?? result.data) as BonusTestRun, result.data?.message);
  };

  const getTestRuns = async (
    filters: { page?: number; length?: number; status?: string; scenario?: string } = {},
  ): Promise<BonusApiResult<{ items: BonusTestRun[]; total: number }>> => {
    const result = await useAPIFetch("/bonuses/tests/runs", {
      ...filters,
    });

    if (!result.success) {
      return fail<{ items: BonusTestRun[]; total: number }>(result.error);
    }

    const node: any = result.data?.data ?? result.data ?? {};
    const items = Array.isArray(node.items) ? (node.items as BonusTestRun[]) : [];
    const total = Number(node.total ?? items.length);
    return ok({ items, total });
  };

  const getTestRun = async (id: number): Promise<BonusApiResult<BonusTestRun>> => {
    const result = await useAPIFetch("/bonuses/tests/runs/get", { id });
    if (!result.success) {
      return fail<BonusTestRun>(result.error);
    }
    return ok((result.data?.data ?? result.data) as BonusTestRun);
  };

  const getTestRunLogs = async (runId: number): Promise<BonusApiResult<BonusTestRunLog[]>> => {
    const result = await useAPIFetch("/bonuses/tests/runs/logs", { run_id: runId });
    if (!result.success) {
      return fail<BonusTestRunLog[]>(result.error);
    }
    const node: any = result.data?.data ?? result.data ?? [];
    const logs = Array.isArray(node) ? (node as BonusTestRunLog[]) : [];
    return ok(logs);
  };

  return {
    getRules,
    getRule,
    saveRule,
    deleteRule,
    toggleRule,
    manualPreview,
    manualGrant,
    getGrants,
    getGrantEvents,
    getStats,
    createMissingWallets,
    runTestScenario,
    getTestRuns,
    getTestRun,
    getTestRunLogs,
    toErrorMessage,
  };
};
