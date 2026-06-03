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

const appendNullable = (formData: FormData, key: string, value: unknown) => {
  formData.append(
    key,
    value === undefined || value === null ? "" : String(value)
  );
};

const appendBundleFormData = (formData: FormData, payload: BundlePayload) => {
  formData.append("name", String(payload.name ?? ""));
  formData.append("slug", String(payload.slug ?? ""));
  appendNullable(formData, "short_description", payload.short_description);
  appendNullable(formData, "description", payload.description);

  formData.append("price_amount", String(payload.price_amount ?? 0));
  formData.append("price_currency", String(payload.price_currency ?? "EUR"));
  formData.append("gc_amount", String(payload.gc_amount ?? 0));
  formData.append("coin_amount", String(payload.coin_amount ?? 0));

  appendNullable(formData, "thumbnail", payload.thumbnail);
  appendNullable(formData, "icon", payload.icon);
  appendNullable(formData, "badge_text", payload.badge_text);
  appendNullable(formData, "badge_color", payload.badge_color);
  appendNullable(formData, "background_color", payload.background_color);
  appendNullable(formData, "text_color", payload.text_color);
  appendNullable(formData, "ribbon_text", payload.ribbon_text);
  appendNullable(formData, "image_url", payload.image_url);

  formData.append("is_active", payload.is_active ? "1" : "0");
  formData.append("is_featured", payload.is_featured ? "1" : "0");
  formData.append("is_popular", payload.is_popular ? "1" : "0");
  formData.append("sort_order", String(payload.sort_order ?? 0));

  const metadataEntries = Object.entries(payload.metadata ?? {});
  if (metadataEntries.length === 0) {
    appendNullable(formData, "metadata", null);
  }

  metadataEntries.forEach(([key, value]) => {
    formData.append(
      `metadata[${key}]`,
      typeof value === "string" ? value : JSON.stringify(value)
    );
  });

  appendNullable(formData, "starts_at", payload.starts_at);
  appendNullable(formData, "ends_at", payload.ends_at);

  const fileValue = Array.isArray(payload.thumbnail_file)
    ? payload.thumbnail_file[0]
    : payload.thumbnail_file;

  if (fileValue instanceof File) {
    formData.append("thumbnail_file", fileValue);
  }
};

export const useBundlesApi = () => {
  const listBundles = async (
    params: BundleListFilters
  ): Promise<
    BundlesApiResult<{ items: Bundle[]; total: number; meta: any }>
  > => {
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
    payload: BundlePayload
  ): Promise<BundlesApiResult<Bundle>> => {
    const formData = new FormData();
    appendBundleFormData(formData, payload);
    const result = await useApiPostFetch("/admin/bundles", formData);

    if (!result.success) {
      return fail(result.error);
    }

    const res = result.data as BundleApiResponse<Bundle>;
    return ok(unwrap<Bundle>(res), res?.message);
  };

  const updateBundle = async (
    id: string,
    payload: BundlePayload
  ): Promise<BundlesApiResult<Bundle>> => {
    const formData = new FormData();
    appendBundleFormData(formData, payload);
    formData.append("_method", "PUT");
    const result = await useApiPostFetch(`/admin/bundles/${id}`, formData);

    if (!result.success) {
      return fail(result.error);
    }

    const res = result.data as BundleApiResponse<Bundle>;
    return ok(unwrap<Bundle>(res), res?.message);
  };

  const deleteBundle = async (id: string): Promise<BundlesApiResult<null>> => {
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
