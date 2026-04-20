import { useAuthStore } from "~/store/auth";
import { useLog, useLogError } from "./useLog";
import { useLayoutStore } from "~/store/app";

export type useApiPutFetchType = {
  success: boolean;
  error: any;
  data: any;
};

export const useApiPutFetch = async (
  path: any,
  _payload: any = {},
  files: boolean = false,
): Promise<useApiPutFetchType> => {
  const config = useRuntimeConfig();
  const { token } = storeToRefs(useAuthStore());
  const { currentCasinoId } = storeToRefs(useLayoutStore());

  const options: any = {};
  let _return: useApiPutFetchType = {
    success: true,
    data: null,
    error: null,
  };

  options.baseURL = config.public.baseURL;
  options.method = "put";
  options.watch = false;
  options.server = false;

  const isFormData =
    typeof FormData !== "undefined" && _payload instanceof FormData;

  if (isFormData || files) {
    const formData =
      isFormData || typeof FormData === "undefined"
        ? _payload
        : new FormData();

    if (!isFormData && formData && _payload && typeof _payload === "object") {
      Object.entries(_payload).forEach(([key, value]) => {
        formData.append(key, value as any);
      });
    }

    if (
      typeof currentCasinoId.value !== "undefined" &&
      currentCasinoId.value !== null
    ) {
      formData.append("int_casino_id", String(currentCasinoId.value));
    }

    options.body = formData;
  } else {
    options.body = {
      int_casino_id: currentCasinoId.value,
      ..._payload,
    };
  }

  //@ts-ignore
  options.onRequest = ({ request, options }) => {
    options.headers.set("Authorization", `Bearer ${token.value}`);
    options.headers.set("Accept", "application/json");
  };

  useLog(
    {
      path: options.baseURL + path,
      params: _payload,
    },
    true,
  );

  try {
    _return.data = await $fetch(path, options);
    useLog(_return, false);
    return _return;
  } catch (err) {
    //@ts-ignore
    useLogError(err.data);
    _return.success = false;
    _return.error = err;
    return _return;
  }
};

