import { useLog, useLogError } from "./useLog";
import { useAuthStore } from "~/store/auth";
import { useLayoutStore } from "~/store/app";

export type useAPIFetchType = {
  success: boolean;
  error: any;
  data: any;
};

export const useAPIFetch = async (
  path: any,
  _payload: any = {},
  _blob: boolean = false,
): Promise<useAPIFetchType> => {
  const { token } = storeToRefs(useAuthStore());
  const layoutStore = useLayoutStore();
  const { currentCasinoId } = storeToRefs(layoutStore);
  const config = useRuntimeConfig();
  const options: any = {};
  let _return: useAPIFetchType = {
    success: true,
    data: null,
    error: null,
  };

  options.baseURL = config.public.baseURL;
  options.query = {
    int_casino_id: currentCasinoId.value,
    ..._payload,
  };
  //@ts-ignore
  options.onRequest = ({ request, options }) => {
    options.headers.set("Authorization", `Bearer ${token.value}`);
    options.headers.set("content-type", "application/json");
    if (_blob) {
      options.headers.set("responseType", "blob");
    }
  };

  useLog(
    {
      path: options.baseURL + path,
      params: _payload,
    },
    true,
  );

  try {
    layoutStore.startGlobalLoading();
    _return.data = await $fetch(path, options);
    useLog(_return, false);
    return _return;
  } catch (err) {
    useLogError(err);
    _return.success = false;
    _return.error = err;
    return _return;
  } finally {
    layoutStore.stopGlobalLoading();
  }
};
