export const useDefaultCurrency = (): string => {
  const config = useRuntimeConfig();

  return config.public.defaultCurrency || "JKP";
};
