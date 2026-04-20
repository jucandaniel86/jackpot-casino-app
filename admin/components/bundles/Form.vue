<script setup lang="ts">
import type { Bundle, BundlePayload } from "~/types/bundles";

type Props = {
  loading: boolean;
  item?: Bundle | null;
};

const props = defineProps<Props>();
const emit = defineEmits<{
  (e: "save", payload: BundlePayload): void;
}>();

const { toastError } = useAlert();

const formRef = ref<any>(null);

const model = reactive<{
  name: string;
  slug: string;
  short_description: string;
  description: string;

  price_amount: number | null;
  price_currency: string;
  gc_amount: number | null;
  coin_amount: number | null;

  thumbnail: string;
  icon: string;
  badge_text: string;
  badge_color: string;
  background_color: string;
  text_color: string;
  ribbon_text: string;
  image_url: string;

  is_active: boolean;
  is_featured: boolean;
  is_popular: boolean;
  sort_order: number | null;

  starts_at: string;
  ends_at: string;

  metadata_text: string;
}>({
  name: "",
  slug: "",
  short_description: "",
  description: "",

  price_amount: 0,
  price_currency: "EUR",
  gc_amount: 0,
  coin_amount: 0,

  thumbnail: "",
  icon: "",
  badge_text: "",
  badge_color: "",
  background_color: "",
  text_color: "",
  ribbon_text: "",
  image_url: "",

  is_active: true,
  is_featured: false,
  is_popular: false,
  sort_order: 0,

  starts_at: "",
  ends_at: "",

  metadata_text: "{}",
});

const required = (v: any) => !!v || "Required.";
const nonNegative = (v: any) =>
  v === null || v === undefined || v === "" || Number(v) >= 0
    ? true
    : "Min 0.";

const slugRule = (v: any) => {
  const value = String(v ?? "");
  if (!value.trim()) return "Required.";
  return /^[A-Za-z0-9_-]+$/.test(value) || "Only alpha_dash (a-z, 0-9, - _).";
};

const endsAfterStarts = () => {
  if (!model.starts_at || !model.ends_at) return true;
  const s = new Date(model.starts_at);
  const e = new Date(model.ends_at);
  if (Number.isNaN(s.getTime()) || Number.isNaN(e.getTime())) return true;
  return e.getTime() > s.getTime() || "End must be after start.";
};

const normalizeDatetimeInput = (value: string) => {
  return value ? String(value) : "";
};

const asNullableString = (value: string) => {
  const v = String(value ?? "").trim();
  return v ? v : null;
};

const asNumber = (value: any, fallback = 0) => {
  const n = Number(value);
  return Number.isFinite(n) ? n : fallback;
};

const mapFromItem = (item: Bundle) => {
  model.name = item.name ?? "";
  model.slug = item.slug ?? "";
  model.short_description = item.short_description ?? "";
  model.description = item.description ?? "";

  model.price_amount = asNumber(item.price_amount, 0);
  model.price_currency = item.price_currency ?? "EUR";
  model.gc_amount = asNumber(item.gc_amount, 0);
  model.coin_amount = asNumber(item.coin_amount, 0);

  model.thumbnail = item.thumbnail ?? "";
  model.icon = item.icon ?? "";
  model.badge_text = item.badge_text ?? "";
  model.badge_color = item.badge_color ?? "";
  model.background_color = item.background_color ?? "";
  model.text_color = item.text_color ?? "";
  model.ribbon_text = item.ribbon_text ?? "";
  model.image_url = item.image_url ?? "";

  model.is_active = Boolean(item.is_active);
  model.is_featured = Boolean(item.is_featured);
  model.is_popular = Boolean(item.is_popular);
  model.sort_order = asNumber(item.sort_order, 0);

  model.starts_at = item.starts_at ?? "";
  model.ends_at = item.ends_at ?? "";

  const meta =
    item.metadata && typeof item.metadata === "object" ? item.metadata : {};
  model.metadata_text = JSON.stringify(meta, null, 2);
};

watch(
  () => props.item,
  (item) => {
    if (item) mapFromItem(item);
  },
  { immediate: true },
);

function validateAmounts(): boolean {
  const gc = asNumber(model.gc_amount, 0);
  const coin = asNumber(model.coin_amount, 0);

  if (gc <= 0 && coin <= 0) {
    toastError("At least one of GC amount or Coin amount must be > 0.");
    return false;
  }

  return true;
}

function parseMetadata(): Record<string, unknown> | null {
  const raw = String(model.metadata_text ?? "").trim();
  if (!raw) return {};

  try {
    const parsed = JSON.parse(raw);
    if (!parsed || typeof parsed !== "object" || Array.isArray(parsed)) {
      toastError("Metadata must be a JSON object.");
      return null;
    }
    return parsed as Record<string, unknown>;
  } catch {
    toastError("Invalid JSON in metadata.");
    return null;
  }
}

function buildPayload(): BundlePayload | null {
  if (!validateAmounts()) return null;

  const meta = parseMetadata();
  if (!meta) return null;

  const startsAt = asNullableString(model.starts_at);
  const endsAt = asNullableString(model.ends_at);
  if (startsAt && endsAt) {
    const s = new Date(startsAt);
    const e = new Date(endsAt);
    if (!Number.isNaN(s.getTime()) && !Number.isNaN(e.getTime())) {
      if (e.getTime() <= s.getTime()) {
        toastError("ends_at must be after starts_at.");
        return null;
      }
    }
  }

  return {
    name: String(model.name ?? ""),
    slug: String(model.slug ?? ""),
    short_description: asNullableString(model.short_description),
    description: asNullableString(model.description),

    price_amount: asNumber(model.price_amount, 0),
    price_currency: String(model.price_currency ?? "EUR"),
    gc_amount: asNumber(model.gc_amount, 0),
    coin_amount: asNumber(model.coin_amount, 0),

    thumbnail: asNullableString(model.thumbnail),
    icon: asNullableString(model.icon),
    badge_text: asNullableString(model.badge_text),
    badge_color: asNullableString(model.badge_color),
    background_color: asNullableString(model.background_color),
    text_color: asNullableString(model.text_color),
    ribbon_text: asNullableString(model.ribbon_text),
    image_url: asNullableString(model.image_url),

    is_active: Boolean(model.is_active),
    is_featured: Boolean(model.is_featured),
    is_popular: Boolean(model.is_popular),
    sort_order: asNumber(model.sort_order, 0),

    metadata: meta,
    starts_at: startsAt ? normalizeDatetimeInput(startsAt) : null,
    ends_at: endsAt ? normalizeDatetimeInput(endsAt) : null,
  };
}

async function onSave() {
  const form = formRef.value;
  const res = await form?.validate?.();
  if (res && res.valid === false) return;

  const payload = buildPayload();
  if (!payload) return;

  emit("save", payload);
}
</script>

<template>
  <v-form ref="formRef" validate-on="submit">
    <v-card variant="tonal" class="mb-4">
      <v-card-title class="text-subtitle-1 font-weight-bold">
        General
      </v-card-title>
      <v-divider />
      <v-card-text>
        <v-row dense>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="model.name"
              label="Name"
              variant="outlined"
              density="comfortable"
              :rules="[required]"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="model.slug"
              label="Slug"
              variant="outlined"
              density="comfortable"
              :rules="[slugRule]"
              hint="alpha_dash only"
              persistent-hint
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="model.short_description"
              label="Short description"
              variant="outlined"
              density="comfortable"
              clearable
            />
          </v-col>
          <v-col cols="12">
            <v-textarea
              v-model="model.description"
              label="Description"
              variant="outlined"
              density="comfortable"
              rows="3"
              auto-grow
            />
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <v-card variant="tonal" class="mb-4">
      <v-card-title class="text-subtitle-1 font-weight-bold">
        Business
      </v-card-title>
      <v-divider />
      <v-card-text>
        <v-row dense>
          <v-col cols="12" md="3">
            <v-text-field
              v-model="model.price_amount"
              type="number"
              label="Price amount"
              variant="outlined"
              density="comfortable"
              :rules="[required, nonNegative]"
              min="0"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-text-field
              v-model="model.price_currency"
              label="Price currency"
              variant="outlined"
              density="comfortable"
              :rules="[required]"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-text-field
              v-model="model.gc_amount"
              type="number"
              label="GC amount"
              variant="outlined"
              density="comfortable"
              :rules="[nonNegative]"
              min="0"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-text-field
              v-model="model.coin_amount"
              type="number"
              label="Coin amount"
              variant="outlined"
              density="comfortable"
              :rules="[nonNegative]"
              min="0"
            />
          </v-col>
          <v-col cols="12">
            <div class="text-caption text-medium-emphasis">
              Note: at least one of GC amount or Coin amount must be &gt; 0.
            </div>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <v-card variant="tonal" class="mb-4">
      <v-card-title class="text-subtitle-1 font-weight-bold">
        Design
      </v-card-title>
      <v-divider />
      <v-card-text>
        <v-row dense>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="model.thumbnail"
              label="Thumbnail"
              variant="outlined"
              density="comfortable"
              clearable
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="model.icon"
              label="Icon"
              variant="outlined"
              density="comfortable"
              clearable
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="model.image_url"
              label="Image URL"
              variant="outlined"
              density="comfortable"
              clearable
            />
          </v-col>

          <v-col cols="12" md="3">
            <v-text-field
              v-model="model.badge_text"
              label="Badge text"
              variant="outlined"
              density="comfortable"
              clearable
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-text-field
              v-model="model.badge_color"
              label="Badge color"
              variant="outlined"
              density="comfortable"
              clearable
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-text-field
              v-model="model.background_color"
              label="Background color"
              variant="outlined"
              density="comfortable"
              clearable
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-text-field
              v-model="model.text_color"
              label="Text color"
              variant="outlined"
              density="comfortable"
              clearable
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model="model.ribbon_text"
              label="Ribbon text"
              variant="outlined"
              density="comfortable"
              clearable
            />
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <v-card variant="tonal" class="mb-4">
      <v-card-title class="text-subtitle-1 font-weight-bold">
        Availability
      </v-card-title>
      <v-divider />
      <v-card-text>
        <v-row dense>
          <v-col cols="12" md="3">
            <v-switch
              v-model="model.is_active"
              inset
              color="success"
              label="Active"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-switch
              v-model="model.is_featured"
              inset
              color="info"
              label="Featured"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-switch
              v-model="model.is_popular"
              inset
              color="warning"
              label="Popular"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-text-field
              v-model="model.sort_order"
              type="number"
              label="Sort order"
              variant="outlined"
              density="comfortable"
              :rules="[nonNegative]"
              min="0"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-text-field
              v-model="model.starts_at"
              type="datetime-local"
              label="Starts at"
              variant="outlined"
              density="comfortable"
              clearable
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="model.ends_at"
              type="datetime-local"
              label="Ends at"
              variant="outlined"
              density="comfortable"
              clearable
              :rules="[endsAfterStarts]"
            />
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <v-card variant="tonal" class="mb-4">
      <v-card-title class="text-subtitle-1 font-weight-bold">
        Advanced / Metadata
      </v-card-title>
      <v-divider />
      <v-card-text>
        <v-textarea
          v-model="model.metadata_text"
          label="Metadata (JSON object)"
          variant="outlined"
          density="comfortable"
          rows="6"
          auto-grow
          placeholder='{"key":"value"}'
        />
      </v-card-text>
    </v-card>

    <div class="d-flex justify-end ga-2">
      <v-btn
        :disabled="props.loading"
        color="primary"
        variant="flat"
        @click.prevent="onSave"
      >
        Save
      </v-btn>
    </div>
  </v-form>
</template>

