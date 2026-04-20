import type {
  Bundle,
  BundleApiResponse,
  BundleListFilters,
  BundleListResponse,
  BundlePayload,
} from "~/types/bundles";

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

export type BundlesApiResult<T> = {
  success: boolean;
  data: T | null;
  error: unknown;
  message?: string;
};

const ok = <T>(data: T, message?: string): BundlesApiResult<T> => ({
  success: true,
  data,
  error: null,
  message,
});

const fail = <T>(error: unknown): BundlesApiResult<T> => ({
  success: false,
  data: null,
  error,
  message: toErrorMessage(error),
});

const unwrap = <T>(payload: any): T => {
  return (payload?.data ?? payload) as T;
};

export const useBundlesApi = () => {
  const listBundles = async (
    params: BundleListFilters,
  ): Promise<BundlesApiResult<{ items: Bundle[]; total: number; meta: any }>> => {
    const result = await useAPIFetch("/admin/bundles", params);

    if (!result.success) {
      return fail(result.error);
    }

    const res = result.data as BundleApiResponse<BundleListResponse>;
    const paginator = unwrap<BundleListResponse>(res);
    const items = Array.isArray(paginator?.data) ? paginator.data : [];
    const total = Number(paginator?.total ?? items.length);

    return ok({ items, total, meta: paginator }, res?.message);
  };

  const getBundle = async (id: string): Promise<BundlesApiResult<Bundle>> => {
    const result = await useAPIFetch(`/admin/bundles/${id}`, {});

    if (!result.success) {
      return fail(result.error);
    }

    const res = result.data as BundleApiResponse<Bundle>;
    return ok(unwrap<Bundle>(res), res?.message);
  };

  const createBundle = async (
    payload: BundlePayload,
  ): Promise<BundlesApiResult<Bundle>> => {
    const result = await useApiPostFetch("/admin/bundles", payload);

    if (!result.success) {
      return fail(result.error);
    }

    const res = result.data as BundleApiResponse<Bundle>;
    return ok(unwrap<Bundle>(res), res?.message);
  };

  const updateBundle = async (
    id: string,
    payload: BundlePayload,
  ): Promise<BundlesApiResult<Bundle>> => {
    const result = await useApiPutFetch(`/admin/bundles/${id}`, payload);

    if (!result.success) {
      return fail(result.error);
    }

    const res = result.data as BundleApiResponse<Bundle>;
    return ok(unwrap<Bundle>(res), res?.message);
  };

  const deleteBundle = async (
    id: string,
  ): Promise<BundlesApiResult<null>> => {
    const result = await useApiDeleteFetch(`/admin/bundles/${id}`, {});

    if (!result.success) {
      return fail(result.error);
    }

    const res = result.data as BundleApiResponse<null>;
    return ok(null, res?.message);
  };

  return {
    listBundles,
    getBundle,
    createBundle,
    updateBundle,
    deleteBundle,
  };
};

