import type { VDataTableHeaders } from "~/core/types/App";

export const BOOLEAN_OPTIONS: { title: string; value: boolean | null }[] = [
  { title: "Any", value: null },
  { title: "Yes", value: true },
  { title: "No", value: false },
];

export const PER_PAGE_OPTIONS = [10, 20, 25, 50, 100];

export const BUNDLES_HEADERS: VDataTableHeaders[] = [
  { title: "Preview", value: "preview", sortable: false, width: 160 },
  { title: "Name", value: "name", sortable: true, width: 220 },
  { title: "Slug", value: "slug", sortable: true, width: 180 },
  { title: "Price", value: "price_amount", sortable: true, width: 120 },
  { title: "Curr.", value: "price_currency", sortable: false, width: 90 },
  { title: "GC", value: "gc_amount", sortable: false, width: 90 },
  { title: "Coins", value: "coin_amount", sortable: false, width: 90 },
  { title: "Active", value: "is_active", sortable: false, width: 90 },
  { title: "Featured", value: "is_featured", sortable: false, width: 100 },
  { title: "Popular", value: "is_popular", sortable: false, width: 100 },
  { title: "Starts", value: "starts_at", sortable: true, width: 200 },
  { title: "Ends", value: "ends_at", sortable: true, width: 200 },
  { title: "Sort", value: "sort_order", sortable: true, width: 90 },
  { title: "Created", value: "created_at", sortable: true, width: 200 },
  { title: "Actions", value: "actions", sortable: false, width: 180 },
];
