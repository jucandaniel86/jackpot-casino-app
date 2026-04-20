import { useAuthStore } from "@/store/auth";
import { watch } from "vue";

export default defineNuxtPlugin(async () => {
  const authStore = useAuthStore();
  let didCheck = false;

  const runMe = async () => {
    if (didCheck) {
      return;
    }
    didCheck = true;
    await authStore.me();
  };

  await runMe();

  watch(
    () => authStore.token,
    async () => {
      await runMe();
    },
    { immediate: true },
  );
});
