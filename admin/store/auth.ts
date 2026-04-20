import { defineStore } from "pinia";

export enum EnvEnum {
  DEV = "DEV",
  UAT = "UAT",
  PROD = "PROD",
}

export enum UserTypeEnum {
  ADMIN = "admin",
  OPERATOR = "operator",
}

type UserType = {
  id: number;
  name: string;
  email: string;
  env: EnvEnum;
  user_type: UserTypeEnum;
  site_id: string;
  int_casino_id: string | null;
};

interface AuthStoreInterface {
  authenticated: boolean;
  user: UserType | null;
  token: string | null;
}

interface LoginPayload {
  client_id: number | string;
  client_secret: string;
  grant_type: string;
  email: string;
  password: string;
}

export const useAuthStore = defineStore("auth-store", {
  state: (): AuthStoreInterface => ({
    authenticated: false,
    user: null,
    token: "",
  }),
  actions: {
    async login(_payload: LoginPayload): Promise<useApiFetchType> {
      const { data, success, error }: any = await useApiPostFetch(
        "/login",
        _payload,
      );

      if (success) {
        this.authenticated = true;
        this.token = data.token;
      }
      return {
        data,
        success,
        error,
      };
    },
    async logout() {
      const router = useRouter();
      await useApiPostFetch("/logout", {});

      router.push("/login");
      this.authenticated = false;
      this.token = null;
      this.user = null;
    },
    async me(): Promise<useApiFetchType> {
      const { data, success, error }: any = await useAPIFetch("/user", {});

      if (success && data?.user) {
        this.authenticated = true;
        this.user = data.user;
      } else {
        await this.logout();
      }

      return {
        data,
        success,
        error,
      };
    },
  },
  persist: {
    storage: persistedState.localStorage,
  },
});
