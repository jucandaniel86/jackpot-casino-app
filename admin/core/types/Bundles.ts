export type Bundle = {
  id: string;
  name: string;
  slug: string;
  short_description: string | null;
  description: string | null;

  price_amount: string | number;
  price_currency: string;
  gc_amount: string | number;
  coin_amount: string | number;

  thumbnail: string | null;
  icon: string | null;
  badge_text: string | null;
  badge_color: string | null;
  background_color: string | null;
  text_color: string | null;
  ribbon_text: string | null;
  image_url: string | null;

  is_active: boolean;
  is_featured: boolean;
  is_popular: boolean;
  sort_order: number;

  metadata: Record<string, unknown> | null;
  starts_at: string | null;
  ends_at: string | null;

  created_at: string;
  updated_at: string;
};

export type BundleListItem = Bundle;

export type BundleListFilters = {
  page?: number;
  per_page?: number;
  sort_by?:
    | "name"
    | "slug"
    | "price_amount"
    | "sort_order"
    | "created_at"
    | "starts_at"
    | "ends_at";
  sort_direction?: "asc" | "desc";

  is_active?: boolean | null;
  is_featured?: boolean | null;
  is_popular?: boolean | null;
  is_available_now?: boolean | null;
  search?: string | null;
  slug?: string | null;
  price_currency?: string | null;
};

export type BundlePayload = {
  name: string;
  slug: string;
  short_description: string | null;
  description: string | null;

  price_amount: number;
  price_currency: string;
  gc_amount: number;
  coin_amount: number;

  thumbnail: string | null;
  icon: string | null;
  badge_text: string | null;
  badge_color: string | null;
  background_color: string | null;
  text_color: string | null;
  ribbon_text: string | null;
  image_url: string | null;

  is_active: boolean;
  is_featured: boolean;
  is_popular: boolean;
  sort_order: number;

  metadata: Record<string, unknown>;
  starts_at: string | null;
  ends_at: string | null;
};

export type BundleListResponse = {
  current_page: number;
  data: BundleListItem[];
  first_page_url?: string;
  from?: number | null;
  last_page: number;
  last_page_url?: string;
  links?: any[];
  next_page_url?: string | null;
  path?: string;
  per_page: number;
  prev_page_url?: string | null;
  to?: number | null;
  total: number;
};

export type BundleApiResponse<T> = {
  success: boolean;
  message?: string;
  data: T;
};

