export const useRules = () => {
  const required = (value: any) => !!value || "Required.";
  const min = (v: any) => String(v).length >= 8 || "Min 8 characters";

  return {
    required,
    min,
  };
};
