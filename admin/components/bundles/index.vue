<script setup lang="ts">
import { BUNDLES_HEADERS, BOOLEAN_OPTIONS, PER_PAGE_OPTIONS } from "./config";
import type { Bundle, BundleListFilters } from "~/types/bundles";

const router = useRouter();
const { confirmDelete, toastSuccess, toastError, axiosErrorAlert } = useAlert();
const api = useBundlesApi();

const items = ref<Bundle[]>([]);
const total = ref(0);
const loading = ref(false);
const errorMessage = ref<string | null>(null);

const page = ref(1);
const perPage = ref(20);

const filters = reactive<{
  search: string;
  slug: string;
  price_currency: string;
  is_active: boolean | null;
  is_featured: boolean | null;
  is_popular: boolean | null;
  is_available_now: boolean | null;
}>({
  search: "",
  slug: "",
  price_currency: "",
  is_active: null,
  is_featured: null,
  is_popular: null,
  is_available_now: null,
});

const sortBy = ref<Array<{ key: string; order?: "asc" | "desc" | boolean }>>([
  { key: "sort_order", order: "asc" },
]);

const headers = ref(BUNDLES_HEADERS);

const allowedSortKeys = [
  "name",
  "slug",
  "price_amount",
  "sort_order",
  "created_at",
  "starts_at",
  "ends_at",
] as const;

const toQuery = (): BundleListFilters => {
  const sort = sortBy.value?.[0] ?? null;
  const sortKey = sort?.key ? String(sort.key) : "";
  const sortDirection =
    String(sort?.order ?? "desc").toLowerCase() === "asc" ? "asc" : "desc";

  return {
    page: page.value,
    per_page: perPage.value,
    sort_by: (allowedSortKeys as readonly string[]).includes(sortKey)
      ? (sortKey as any)
      : undefined,
    sort_direction: (allowedSortKeys as readonly string[]).includes(sortKey)
      ? sortDirection
      : undefined,
    search: filters.search?.trim() ? filters.search.trim() : null,
    slug: filters.slug?.trim() ? filters.slug.trim() : null,
    price_currency: filters.price_currency?.trim()
      ? filters.price_currency.trim()
      : null,
    is_active: filters.is_active,
    is_featured: filters.is_featured,
    is_popular: filters.is_popular,
    is_available_now: filters.is_available_now,
  };
};

async function fetchData() {
  loading.value = true;
  errorMessage.value = null;

  try {
    const result = await api.listBundles(toQuery());
    if (!result.success || !result.data) {
      errorMessage.value = result.message || "Failed to fetch bundles.";
      items.value = [];
      total.value = 0;
      return;
    }

    items.value = result.data.items;
    total.value = result.data.total;
  } catch (err: any) {
    errorMessage.value = "Failed to fetch bundles.";
    axiosErrorAlert(err, true);
  } finally {
    loading.value = false;
  }
}

function onSearch() {
  page.value = 1;
  fetchData();
}

function onReset() {
  filters.search = "";
  filters.slug = "";
  filters.price_currency = "";
  filters.is_active = null;
  filters.is_featured = null;
  filters.is_popular = null;
  filters.is_available_now = null;
  perPage.value = 20;
  page.value = 1;
  sortBy.value = [{ key: "sort_order", order: "asc" }];
  fetchData();
}

function goCreate() {
  router.push({ name: "casino-bundles-add" });
}

function goEdit(id: string) {
  router.push({ name: "casino-bundles-edit-id", params: { id } });
}

function onDelete(item: Bundle) {
  confirmDelete(async () => {
    const result = await api.deleteBundle(String(item.id));
    if (!result.success) {
      toastError(result.message || "Failed to delete bundle.");
      return;
    }

    toastSuccess(result.message || "Bundle deleted successfully.");
    fetchData();
  });
}

function fmtDate(v: any) {
  if (!v) return "—";
  const d = new Date(v);
  if (Number.isNaN(d.getTime())) return String(v);
  return d.toLocaleString();
}

function flagColor(v: boolean) {
  return v ? "success" : "error";
}

function amount(v: any) {
  if (v === null || v === undefined || v === "") return "0";
  const n = Number(v);
  return Number.isFinite(n) ? n.toFixed(2) : String(v);
}
</script>

<template>
  <v-container fluid class="pa-0">
    <v-card class="mb-4" color="white" variant="elevated">
      <v-card-text class="d-flex align-center justify-space-between">
        <div>
          <div class="text-h5 font-weight-bold">Bundles</div>
          <div class="text-caption text-medium-emphasis">
            Create and manage shop bundles
          </div>
        </div>
        <div class="d-flex ga-2">
          <v-btn variant="flat" color="primary" @click="fetchData">
            <v-icon start icon="mdi-refresh" /> Refresh
          </v-btn>
          <v-btn variant="flat" color="blue" @click="goCreate">
            <v-icon start icon="mdi-plus" /> Add bundle
          </v-btn>
        </div>
      </v-card-text>
    </v-card>

    <v-card variant="elevated" color="white" class="mb-4">
      <v-card-text>
        <v-row dense>
          <v-col cols="12" md="4">
            <div class="text-caption text-medium-emphasis mb-1">Search</div>
            <v-text-field
              v-model="filters.search"
              placeholder="Name / slug / description..."
              variant="outlined"
              density="comfortable"
              clearable
              hide-details
              @keyup.enter="onSearch"
            />
          </v-col>

          <v-col cols="12" md="3">
            <div class="text-caption text-medium-emphasis mb-1">Slug</div>
            <v-text-field
              v-model="filters.slug"
              placeholder="exact slug"
              variant="outlined"
              density="comfortable"
              clearable
              hide-details
              @keyup.enter="onSearch"
            />
          </v-col>

          <v-col cols="12" md="2">
            <div class="text-caption text-medium-emphasis mb-1">Currency</div>
            <v-text-field
              v-model="filters.price_currency"
              placeholder="EUR"
              variant="outlined"
              density="comfortable"
              clearable
              hide-details
              @keyup.enter="onSearch"
            />
          </v-col>

          <v-col cols="12" md="3">
            <div class="text-caption text-medium-emphasis mb-1">Per page</div>
            <v-select
              v-model="perPage"
              :items="PER_PAGE_OPTIONS"
              variant="outlined"
              density="comfortable"
              hide-details
            />
          </v-col>

          <v-col cols="12" md="2">
            <div class="text-caption text-medium-emphasis mb-1">Active</div>
            <v-select
              v-model="filters.is_active"
              :items="BOOLEAN_OPTIONS"
              item-title="title"
              item-value="value"
              variant="outlined"
              density="comfortable"
              hide-details
            />
          </v-col>

          <v-col cols="12" md="2">
            <div class="text-caption text-medium-emphasis mb-1">Featured</div>
            <v-select
              v-model="filters.is_featured"
              :items="BOOLEAN_OPTIONS"
              item-title="title"
              item-value="value"
              variant="outlined"
              density="comfortable"
              hide-details
            />
          </v-col>

          <v-col cols="12" md="2">
            <div class="text-caption text-medium-emphasis mb-1">Popular</div>
            <v-select
              v-model="filters.is_popular"
              :items="BOOLEAN_OPTIONS"
              item-title="title"
              item-value="value"
              variant="outlined"
              density="comfortable"
              hide-details
            />
          </v-col>

          <v-col cols="12" md="3">
            <div class="text-caption text-medium-emphasis mb-1">
              Available now
            </div>
            <v-select
              v-model="filters.is_available_now"
              :items="BOOLEAN_OPTIONS"
              item-title="title"
              item-value="value"
              variant="outlined"
              density="comfortable"
              hide-details
            />
          </v-col>

          <v-col cols="12" md="3" class="d-flex align-end ga-2">
            <v-btn color="success" variant="flat" @click="onSearch">
              <v-icon start icon="mdi-magnify" /> Search
            </v-btn>
            <v-btn variant="tonal" color="blue-grey" @click="onReset">
              <v-icon start icon="mdi-filter-off" /> Reset
            </v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <v-alert v-if="errorMessage" type="error" class="mb-4" variant="tonal">
      {{ errorMessage }}
    </v-alert>

    <v-card variant="elevated" color="blue-grey-lighten-5">
      <v-data-table-server
        :headers="headers as any"
        :items="items"
        :items-length="total"
        :loading="loading"
        fixed-header
        height="560"
        v-model:page="page"
        v-model:items-per-page="perPage"
        v-model:sort-by="sortBy"
        @update:page="fetchData"
        @update:items-per-page="fetchData"
        @update:sort-by="fetchData"
      >
        <template #item.preview="{ item }">
          <div class="d-flex ga-2 align-center">
            <v-avatar size="42" variant="tonal" color="blue-grey">
              <v-img v-if="item.icon" :src="item.icon" />
              <span v-else>—</span>
            </v-avatar>
            <v-img
              v-if="item.thumbnail || item.image_url"
              :src="(item.thumbnail || item.image_url) as any"
              width="90"
              height="50"
              cover
              style="border-radius: 8px"
            />
          </div>
        </template>

        <template #item.price_amount="{ item }">
          {{ amount(item.price_amount) }}
        </template>

        <template #item.gc_amount="{ item }">
          {{ amount(item.gc_amount) }}
        </template>

        <template #item.coin_amount="{ item }">
          {{ amount(item.coin_amount) }}
        </template>

        <template #item.is_active="{ item }">
          <v-chip
            size="small"
            :color="flagColor(Boolean(item.is_active))"
            label
          >
            {{ item.is_active ? "YES" : "NO" }}
          </v-chip>
        </template>

        <template #item.is_featured="{ item }">
          <v-chip
            size="small"
            :color="flagColor(Boolean(item.is_featured))"
            label
          >
            {{ item.is_featured ? "YES" : "NO" }}
          </v-chip>
        </template>

        <template #item.is_popular="{ item }">
          <v-chip
            size="small"
            :color="flagColor(Boolean(item.is_popular))"
            label
          >
            {{ item.is_popular ? "YES" : "NO" }}
          </v-chip>
        </template>

        <template #item.starts_at="{ item }">
          {{ fmtDate(item.starts_at) }}
        </template>

        <template #item.ends_at="{ item }">
          {{ fmtDate(item.ends_at) }}
        </template>

        <template #item.created_at="{ item }">
          {{ fmtDate(item.created_at) }}
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex ga-2">
            <v-btn
              size="small"
              variant="flat"
              color="info"
              @click="goEdit(String(item.id))"
            >
              Edit
            </v-btn>
            <v-btn
              size="small"
              variant="flat"
              color="error"
              @click="onDelete(item)"
            >
              Delete
            </v-btn>
          </div>
        </template>

        <template #no-data>
          <div class="text-medium-emphasis py-6">
            No bundles found. Adjust filters or create a new bundle.
          </div>
        </template>
      </v-data-table-server>
    </v-card>
  </v-container>
</template>
