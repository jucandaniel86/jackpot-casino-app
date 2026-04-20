import type { BreadcrumbType } from "~/app/types";

export const USERS_BRANDCRUMBS: BreadcrumbType[] = [
  {
    title: "Dashboard",
    disabled: false,
    to: "/",
  },
  {
    title: "Users",
    disabled: true,
  },
];

export const CURRENCIES_BREADCRUMBS: BreadcrumbType[] = [
  {
    title: "Dashboard",
    disabled: false,
    to: "/",
  },
  {
    title: "Currencies",
    disabled: true,
  },
];

export type UserType = {
  name: string;
  email: string;
  id: number;
  created_at: string;
};

export function debounce(func: any, timeout: number) {
  let timeoutId: any;
  return (...args: any) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
      func(...args);
    }, timeout);
  };
}
