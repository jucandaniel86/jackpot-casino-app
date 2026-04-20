import { useAuthStore } from "~/store/auth";

export const useApiUseFetch = (
  path: any,
  _payload: any = {},
  _blob: boolean = false,
  _options: any = {}
) => {
  const { token } = storeToRefs(useAuthStore());
  const config = useRuntimeConfig();
  const options: any = { ..._options };

  options.baseURL = config.public.baseURL;
  options.query = _payload;

  //@ts-ignore
  options.onRequest = ({ request, options }) => {
    options.headers.set("Authorization", `Bearer ${token.value}`);
    options.headers.set("content-type", "application/json");
    if (_blob) {
      options.headers.set("responseType", "blob");
    }
  };

  return useFetch(path, options);
};
